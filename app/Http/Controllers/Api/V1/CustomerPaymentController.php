<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\PrepareBookingSeatHold;
use App\Actions\ReleaseBookingSeats;
use App\Actions\ReserveBookingSeats;
use App\Enums\BookingInstallmentState;
use App\Enums\PaymentState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerPaymentRequest;
use App\Http\Requests\Api\V1\UpdateCustomerPaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\BookingInstallment;
use App\Models\Payment;
use App\Modules\Identity\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function index(Request $request, Customer $customer): AnonymousResourceCollection
    {
        $payments = $this->customerPayments($customer)
            ->with('bookingInstallment')
            ->latest()
            ->paginate($this->perPage($request));

        return PaymentResource::collection($payments);
    }

    public function store(
        StoreCustomerPaymentRequest $request,
        Customer $customer,
        PrepareBookingSeatHold $prepareBookingSeatHold,
        ReserveBookingSeats $reserveBookingSeats,
    ): JsonResponse {
        $validated = $request->validated();
        $installment = $this->customerInstallment($customer, (int) $validated['booking_installment_id']);
        $customer->ensureProfileIsComplete();
        unset($validated['booking_installment_id']);
        $targetState = PaymentState::tryFrom((string) ($validated['state'] ?? PaymentState::Pending->value));

        if ($this->isCapturedState($targetState) && $installment->paymentSchedule->booking->familyMembers()->exists()) {
            $prepareBookingSeatHold->execute($installment->paymentSchedule->booking_id);
            $installment->refresh();
        }

        $payment = DB::transaction(function () use ($installment, $validated): Payment {
            $payment = $installment->payments()->create($validated);

            if (in_array($payment->state, [PaymentState::Paid, PaymentState::PartiallyRefunded], true)) {
                $installment->forceFill(['state' => BookingInstallmentState::Paid, 'paid_at' => $payment->paid_at ?? now()])->save();
            }

            return $payment;
        });

        if ($this->isCapturedState($payment->state)) {
            $reserveBookingSeats->execute($payment);
        }

        return PaymentResource::make($payment->load('bookingInstallment'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Customer $customer, Payment $payment): PaymentResource
    {
        return PaymentResource::make($this->resolveCustomerPayment($customer, $payment));
    }

    public function update(
        UpdateCustomerPaymentRequest $request,
        Customer $customer,
        Payment $payment,
        PrepareBookingSeatHold $prepareBookingSeatHold,
        ReserveBookingSeats $reserveBookingSeats,
        ReleaseBookingSeats $releaseBookingSeats,
    ): PaymentResource {
        $payment = $this->resolveCustomerPayment($customer, $payment);
        $customer->ensureProfileIsComplete();
        $validated = $request->validated();

        if (array_key_exists('booking_installment_id', $validated)) {
            $validated['booking_installment_id'] = $this->customerInstallment($customer, (int) $validated['booking_installment_id'])->id;
        }

        $targetInstallment = array_key_exists('booking_installment_id', $validated)
            ? $this->customerInstallment($customer, (int) $validated['booking_installment_id'])
            : $payment->bookingInstallment;
        $targetState = array_key_exists('state', $validated)
            ? PaymentState::tryFrom((string) $validated['state'])
            : $payment->state;

        if ($this->isCapturedState($targetState) && $targetInstallment->paymentSchedule->booking->familyMembers()->exists()) {
            $prepareBookingSeatHold->execute($targetInstallment->paymentSchedule->booking_id);
            $targetInstallment->refresh();
        }

        DB::transaction(function () use ($payment, $validated): void {
            $payment->update($validated);

            if (in_array($payment->state, [PaymentState::Paid, PaymentState::PartiallyRefunded], true)) {
                $payment->bookingInstallment->forceFill([
                    'state' => BookingInstallmentState::Paid,
                    'paid_at' => $payment->paid_at ?? now(),
                ])->save();
            }
        });

        if ($this->isCapturedState($payment->refresh()->state)) {
            $reserveBookingSeats->execute($payment);
        }

        if (! $this->isCapturedState($payment->refresh()->state)) {
            $releaseBookingSeats->execute($payment);
        }

        return PaymentResource::make($payment->refresh()->load('bookingInstallment'));
    }

    public function destroy(Customer $customer, Payment $payment, ReleaseBookingSeats $releaseBookingSeats): Response
    {
        $payment = $this->resolveCustomerPayment($customer, $payment);
        $customer->ensureProfileIsComplete();
        $booking = $payment->loadMissing('bookingInstallment.paymentSchedule.booking')->bookingInstallment->paymentSchedule->booking;
        $payment->delete();
        $releaseBookingSeats->execute($booking);

        return response()->noContent();
    }

    /**
     * @return Builder<Payment>
     */
    private function customerPayments(Customer $customer): Builder
    {
        return Payment::query()
            ->whereHas(
                'bookingInstallment.paymentSchedule.booking',
                fn (Builder $query): Builder => $query->whereBelongsTo($customer),
            );
    }

    private function resolveCustomerPayment(Customer $customer, Payment $payment): Payment
    {
        return $this->customerPayments($customer)
            ->whereKey($payment->id)
            ->with('bookingInstallment')
            ->firstOrFail();
    }

    private function customerInstallment(Customer $customer, int $bookingInstallmentId): BookingInstallment
    {
        return BookingInstallment::query()
            ->whereKey($bookingInstallmentId)
            ->whereHas(
                'paymentSchedule.booking',
                fn (Builder $query): Builder => $query->whereBelongsTo($customer),
            )
            ->firstOrFail();
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }

    private function isCapturedState(?PaymentState $state): bool
    {
        return in_array($state, [PaymentState::Paid, PaymentState::PartiallyRefunded], true);
    }
}
