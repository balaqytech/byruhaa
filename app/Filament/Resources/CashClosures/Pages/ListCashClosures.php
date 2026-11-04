<?php

namespace App\Filament\Resources\CashClosures\Pages;

use App\Filament\Resources\CashClosures\CashClosureResource;
use App\Modules\Finance\Actions\CloseCashDrawer;
use App\Modules\Identity\Models\User;
use App\Support\Money\MoneyFactory;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCashClosures extends ListRecords
{
    protected static string $resource = CashClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('closeCash')->label('إقفال صندوق الكاشير')->visible(fn (): bool => CashClosureResource::canClose())
                ->schema([
                    Select::make('cashier_user_id')->label('الكاشير')->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->required(),
                    DatePicker::make('business_date')->label('يوم العمل')->default(today())->required(),
                    TextInput::make('opening_float_omr')->label('رصيد أول المدة OMR')->numeric()->default('0')->required(),
                    TextInput::make('counted_omr')->label('النقد المعدود OMR')->numeric()->required(),
                    Textarea::make('notes')->label('ملاحظات'),
                ])
                ->action(function (array $data, CloseCashDrawer $closer): void {
                    abort_unless(CashClosureResource::canClose(), 403);
                    $closure = $closer->execute(
                        (int) $data['cashier_user_id'],
                        (string) $data['business_date'],
                        MoneyFactory::omrStringToBaisa((string) $data['opening_float_omr']),
                        MoneyFactory::omrStringToBaisa((string) $data['counted_omr']),
                        (int) Filament::auth()->id(),
                        $data['notes'] ?? null,
                    );
                    Notification::make()->title($closure->status === 'balanced' ? 'تم الإقفال دون فروق' : 'تم الإقفال مع فروق تتطلب مراجعة')->send();
                }),
        ];
    }
}
