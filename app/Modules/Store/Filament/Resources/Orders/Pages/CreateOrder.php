<?php

namespace App\Modules\Store\Filament\Resources\Orders\Pages;

use App\Modules\Store\Actions\CreateAdminOrder;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static bool $canCreateAnother = false;

    public string $orderAttemptKey = '';

    public function mount(): void
    {
        $this->orderAttemptKey = (string) Str::uuid();

        parent::mount();
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateAdminOrder::class)->execute($data, $this->orderAttemptKey);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('admin.store.admin_order.create_order'))
            ->requiresConfirmation()
            ->modalDescription(__('admin.store.admin_order.confirmation'));
    }
}
