<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Store\Actions\CreateAdminOrder;
use App\Modules\Store\Filament\Resources\Orders\Schemas\OrderForm;
use Filament\Actions\Action;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Model;

class CreateGuestOrder extends CreateOrder
{
    public function getTitle(): string
    {
        return __('admin.store.admin_order.create_guest_order');
    }

    /** @return array<Step> */
    public function getSteps(): array
    {
        return OrderForm::guestSteps();
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateAdminOrder::class)->executeGuest($data, $this->orderAttemptKey);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('admin.store.admin_order.create_guest_order'));
    }
}
