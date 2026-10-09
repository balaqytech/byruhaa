<?php

namespace App\Modules\Pos\Livewire;

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Identity\Contracts\PosPurchasing;
use App\Modules\Identity\Models\User;
use App\Modules\Pos\Actions\CreateCashPosOrder;
use App\Modules\Pos\Actions\CreatePosOrder;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Services\PricingContextResolver;
use App\Modules\Store\Services\StorePricing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PosTerminal extends Component
{
    public string $search = '';

    public string $scanToken = '';

    public string $buyerType = 'guest';

    public string $paymentMethod = 'cash';

    public string $guestName = '';

    public string $guestPhone = '';

    public string $cashReceived = '';

    public bool $showReviewModal = false;

    /** @var array<int, int> */
    public array $cart = [];

    #[Locked]
    public ?int $selectedProfileId = null;

    #[Locked]
    public string $selectedProfileName = '';

    #[Locked]
    public bool $reviewed = false;

    #[Locked]
    public int $reviewedTotalBaisa = 0;

    /** @var array<int, array{name: string, quantity: int, total_baisa: int}> */
    #[Locked]
    public array $reviewLines = [];

    #[Locked]
    public string $attemptKey = '';

    #[Locked]
    public string $completedReference = '';

    #[Locked]
    public ?int $completedCashChangeBaisa = null;

    #[Locked]
    public string $completedPaymentMethod = '';

    #[Locked]
    public ?int $completedOrderId = null;

    public function mount(): void
    {
        $this->attemptKey = (string) Str::uuid();
    }

    public function render(): View
    {
        $this->authorizeTerminal();

        return view('livewire.store.pos-terminal', [
            'catalog' => $this->catalog(),
            'cartOptions' => $this->cartOptions(),
        ])->layout('layouts.staff-workspace', ['workspace' => 'cashier']);
    }

    /** @return Collection<int, ProductOption> */
    private function catalog(): Collection
    {
        return ProductOption::query()
            ->with('product.category')
            ->where('is_available', true)
            ->whereHas('product', fn (Builder $query): Builder => $query
                ->where('status', 'active')
                ->whereHas('category', fn (Builder $category): Builder => $category->where('is_active', true)))
            ->when(trim($this->search) !== '', fn (Builder $query): Builder => $query->where(function (Builder $search): void {
                $term = '%'.trim($this->search).'%';
                $search->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhereHas('product', fn (Builder $product): Builder => $product->where('name', 'like', $term));
            }))
            ->orderBy('id')
            ->limit(50)
            ->get();
    }

    /** @return Collection<int, ProductOption> */
    private function cartOptions(): Collection
    {
        return ProductOption::query()->with('product')->whereIn('id', array_keys($this->cart))->orderBy('id')->get();
    }

    public function addOption(int $optionId): void
    {
        $this->authorizeTerminal();
        $option = ProductOption::query()->with('product.category')->findOrFail($optionId);
        AddCartItem::ensurePurchasable($option);
        $quantity = ($this->cart[$optionId] ?? 0) + 1;

        if ($quantity > 99 || ($option->tracks_inventory && ($option->availableQuantity() ?? 0) < $quantity)) {
            throw ValidationException::withMessages(['cart' => 'الكمية المطلوبة غير متاحة.']);
        }

        $this->cart[$optionId] = $quantity;
        $this->reviewed = false;
        $this->showReviewModal = false;
        $this->completedReference = '';
    }

    public function selectBuyerType(string $buyerType): void
    {
        $this->authorizeTerminal();
        abort_unless(in_array($buyerType, ['guest', 'minor'], true), 422);

        $this->buyerType = $buyerType;
        $this->paymentMethod = $buyerType === 'minor' ? 'wallet' : 'cash';
        $this->scanToken = '';
        $this->selectedProfileId = null;
        $this->selectedProfileName = '';
        $this->reviewed = false;
        $this->showReviewModal = false;
        $this->cashReceived = '';
        $this->resetValidation();
    }

    public function selectPaymentMethod(string $paymentMethod): void
    {
        $this->authorizeTerminal();
        abort_unless($paymentMethod === 'cash' || ($this->buyerType === 'minor' && $paymentMethod === 'wallet'), 422);

        $this->paymentMethod = $paymentMethod;
        $this->reviewed = false;
        $this->showReviewModal = false;
        $this->cashReceived = '';
        $this->resetValidation();
    }

    public function removeOption(int $optionId): void
    {
        $this->authorizeTerminal();
        unset($this->cart[$optionId]);
        $this->reviewed = false;
        $this->showReviewModal = false;
    }

    public function decrementOption(int $optionId): void
    {
        $this->authorizeTerminal();

        if (isset($this->cart[$optionId])) {
            $this->cart[$optionId]--;

            if ($this->cart[$optionId] < 1) {
                unset($this->cart[$optionId]);
            }
        }

        $this->reviewed = false;
        $this->showReviewModal = false;
    }

    public function scan(PosPurchasing $credentials, WalletService $wallets): void
    {
        $this->authorizeTerminal();
        $this->resetValidation();
        abort_unless($this->buyerType === 'minor', 422);
        $profile = $credentials->resolve(trim($this->scanToken));

        if ($this->paymentMethod === 'wallet') {
            if (! $profile->walletSpendingEnabled || $wallets->summary($profile->profileId)->status !== 'active') {
                throw ValidationException::withMessages(['scanToken' => 'محفظة القائد غير متاحة.']);
            }
        }

        $this->selectedProfileId = $profile->profileId;
        $this->selectedProfileName = $profile->name;
        $this->reviewed = false;
        $this->showReviewModal = false;
        $this->completedReference = '';
    }

    public function review(PosPurchasing $credentials, StorePricing $pricing, PricingContextResolver $contexts): void
    {
        $this->authorizeTerminal();
        $this->resetValidation();

        if (! in_array($this->buyerType, ['guest', 'minor'], true)
            || ! in_array($this->paymentMethod, ['wallet', 'cash'], true)
            || ($this->buyerType === 'guest' && $this->paymentMethod !== 'cash')) {
            throw ValidationException::withMessages(['payment' => 'اختر طريقة دفع صالحة.']);
        }

        if ($this->cart === []) {
            throw ValidationException::withMessages(['cart' => 'أضف منتجات قبل مراجعة الإجمالي.']);
        }

        $profile = $this->buyerType === 'minor' ? $credentials->resolve(trim($this->scanToken)) : null;

        if ($this->buyerType === 'minor' && ($profile === null || $this->selectedProfileId !== $profile->profileId)) {
            throw ValidationException::withMessages(['cart' => 'امسح بطاقة القائد قبل المراجعة.']);
        }

        if ($this->paymentMethod === 'wallet' && ! $profile->walletSpendingEnabled) {
            throw ValidationException::withMessages(['wallet' => 'الدفع من المحفظة متوقف لهذا القائد.']);
        }

        $context = $contexts->forIdentity($profile?->guardianId, $profile?->profileId);
        $options = ProductOption::query()->with('product.category')->whereIn('id', array_keys($this->cart))->get()->keyBy('id');
        $lines = [];
        $total = 0;

        foreach ($this->cart as $optionId => $quantity) {
            $option = $options->get($optionId);

            if (! $option instanceof ProductOption || $quantity < 1 || $quantity > 99) {
                throw ValidationException::withMessages(['cart' => 'راجع المنتجات والكميات.']);
            }

            AddCartItem::ensurePurchasable($option);
            $lineTotal = $pricing->forOption($option, $context)->effectivePriceBaisa * $quantity;
            $lines[] = ['name' => $option->product->name.' — '.$option->name, 'quantity' => $quantity, 'total_baisa' => $lineTotal];
            $total += $lineTotal;
        }

        $this->reviewLines = $lines;
        $this->reviewedTotalBaisa = $total;
        $this->reviewed = true;
        $this->showReviewModal = true;
    }

    public function useExactCash(): void
    {
        $this->authorizeTerminal();
        abort_unless($this->reviewed && $this->paymentMethod === 'cash', 422);

        $this->cashReceived = number_format($this->reviewedTotalBaisa / 1000, 3, '.', '');
        $this->resetValidation('cashReceived');
    }

    public function pay(CreatePosOrder $createPosOrder, CreateCashPosOrder $createCashPosOrder): void
    {
        $this->authorizeTerminal();
        $this->resetValidation();

        if (! $this->reviewed || ($this->buyerType === 'minor' && $this->selectedProfileId === null)) {
            throw ValidationException::withMessages(['cart' => 'راجع الإجمالي قبل الدفع.']);
        }

        /** @var User $cashier */
        $cashier = Auth::guard('cashier')->user();

        try {
            $items = collect($this->cart)->map(fn (int $quantity, int $optionId): array => [
                'product_option_id' => $optionId,
                'quantity' => $quantity,
            ])->values()->all();

            if ($this->buyerType === 'minor' && $this->paymentMethod === 'wallet') {
                $order = $createPosOrder->execute(
                    $cashier,
                    trim($this->scanToken),
                    $items,
                    $this->reviewedTotalBaisa,
                    $this->attemptKey,
                    $this->selectedProfileId,
                );
            } elseif ($this->paymentMethod === 'cash') {
                $order = $createCashPosOrder->execute($cashier, [
                    'buyer_type' => $this->buyerType,
                    'token' => $this->buyerType === 'minor' ? trim($this->scanToken) : null,
                    'reviewed_profile_id' => $this->selectedProfileId,
                    'guest_name' => $this->buyerType === 'guest' ? $this->guestName : null,
                    'guest_phone' => $this->buyerType === 'guest' ? $this->guestPhone : null,
                    'items' => $items,
                    'reviewed_total_baisa' => $this->reviewedTotalBaisa,
                    'cash_received_baisa' => $this->cashReceivedBaisa(),
                    'idempotency_key' => $this->attemptKey,
                ]);
            } else {
                throw ValidationException::withMessages(['payment' => 'اختر طريقة دفع صالحة.']);
            }
        } catch (ValidationException $exception) {
            if (isset($exception->errors()['total'])) {
                $this->reviewed = false;
                $this->showReviewModal = false;
            }

            if (isset($exception->errors()['customer_phone'])) {
                throw ValidationException::withMessages(['guestPhone' => 'رقم الهاتف غير صالح.']);
            }

            if (in_array('The wallet balance is insufficient.', $exception->errors()['payment'] ?? [], true)) {
                throw ValidationException::withMessages(['payment' => 'رصيد المحفظة غير كافٍ.']);
            }

            if (in_array('The wallet is not available.', $exception->errors()['wallet'] ?? [], true)) {
                throw ValidationException::withMessages(['wallet' => 'المحفظة غير متاحة للدفع.']);
            }

            throw $exception;
        }

        $this->completedReference = $order->reference;
        $this->completedOrderId = $order->id;
        $this->completedCashChangeBaisa = $order->cash_change_baisa;
        $this->completedPaymentMethod = $order->payment_method;
        $this->cart = [];
        $this->scanToken = '';
        $this->selectedProfileId = null;
        $this->selectedProfileName = '';
        $this->reviewed = false;
        $this->showReviewModal = false;
        $this->reviewLines = [];
        $this->reviewedTotalBaisa = 0;
        $this->cashReceived = '';
        $this->guestName = '';
        $this->guestPhone = '';
        $this->attemptKey = (string) Str::uuid();
        $this->dispatch('pos-order-completed');
    }

    private function cashReceivedBaisa(): int
    {
        $amount = strtr(trim($this->cashReceived), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.',
        ]);

        if (preg_match('/^(0|[1-9][0-9]{0,5})(?:\.([0-9]{1,3}))?$/D', $amount, $matches) !== 1) {
            throw ValidationException::withMessages(['cashReceived' => 'أدخل المبلغ المستلم بالريال العماني حتى ثلاث خانات عشرية.']);
        }

        return ((int) $matches[1] * 1000) + (int) str_pad($matches[2] ?? '', 3, '0');
    }

    private function authorizeTerminal(): void
    {
        $cashier = Auth::guard('cashier')->user();

        abort_unless($cashier instanceof User && $cashier->can('Sell:Pos'), 403);
    }
}
