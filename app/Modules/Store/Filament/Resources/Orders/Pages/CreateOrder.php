<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Store\Actions\CreateAdminOrder;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use App\Modules\Store\Filament\Resources\Orders\Schemas\OrderForm;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateOrder extends CreateRecord
{
    use HasWizard;

    protected static string $resource = OrderResource::class;

    protected static bool $canCreateAnother = false;

    public string $orderAttemptKey = '';

    public function mount(): void
    {
        $this->orderAttemptKey = (string) Str::uuid();

        parent::mount();
    }

    /** @return array<Step> */
    public function getSteps(): array
    {
        return OrderForm::steps();
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateAdminOrder::class)->execute($data, $this->orderAttemptKey);
        } catch (ValidationException $exception) {
            if (in_array('The wallet balance is insufficient.', $exception->errors()['payment'] ?? [], true)) {
                $message = __('admin.store.admin_order.wallet_insufficient');

                Notification::make()->title($message)->danger()->send();

                throw ValidationException::withMessages(['data.payment_method' => $message]);
            }

            Notification::make()
                ->title(__('admin.store.admin_order.creation_failed'))
                ->body(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('admin.store.admin_order.creation_failed'))
                ->body(__('admin.store.admin_order.creation_failed_help'))
                ->danger()
                ->persistent()
                ->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('admin.store.admin_order.create_order'))
            ->requiresConfirmation()
            ->modalDescription(__('admin.store.admin_order.confirmation'));
    }
}
