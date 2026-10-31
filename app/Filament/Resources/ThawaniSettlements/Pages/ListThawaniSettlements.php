<?php

namespace App\Filament\Resources\ThawaniSettlements\Pages;

use App\Filament\Resources\ThawaniSettlements\ThawaniSettlementResource;
use App\Modules\Finance\Actions\RecordThawaniSettlement;
use App\Support\Money\MoneyFactory;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ListThawaniSettlements extends ListRecords
{
    protected static string $resource = ThawaniSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordSettlement')->label('تسجيل تسوية Thawani')->visible(fn (): bool => ThawaniSettlementResource::canRecord())
                ->schema([
                    TextInput::make('provider_reference')->label('مرجع التسوية لدى Thawani')->required(),
                    DateTimePicker::make('settled_at')->label('تاريخ التسوية')->required(),
                    Textarea::make('operations')->label('العمليات: payment,provider_id,amount_baisa أو refund,provider_id,-amount_baisa؛ سطر لكل عملية')->required()->rows(8),
                    TextInput::make('fee_omr')->label('رسوم Thawani OMR')->required()->numeric()->default('0'),
                    TextInput::make('bank_net_omr')->label('المبلغ الواصل إلى Bank OMR')->required()->numeric(),
                    FileUpload::make('statement_path')->label('كشف التسوية')->disk('local')->directory('thawani-statements')->visibility('private')->required(),
                ])
                ->action(function (array $data, RecordThawaniSettlement $settlements): void {
                    abort_unless(ThawaniSettlementResource::canRecord(), 403);
                    $items = [];
                    foreach (preg_split('/\R/', trim((string) $data['operations'])) ?: [] as $line) {
                        $parts = array_map('trim', explode(',', $line));
                        if (count($parts) !== 3 || ! preg_match('/^-?\d+$/', $parts[2])) {
                            throw ValidationException::withMessages(['operations' => 'Each operation must contain kind, provider reference and signed baisa amount.']);
                        }
                        $items[] = ['kind' => $parts[0], 'reference' => $parts[1], 'amount_baisa' => (int) $parts[2]];
                    }
                    $settlements->execute((string) $data['provider_reference'], $items, MoneyFactory::omrStringToBaisa((string) $data['fee_omr']), MoneyFactory::omrStringToBaisa((string) $data['bank_net_omr']), (string) $data['statement_path'], (int) Filament::auth()->id(), Carbon::parse($data['settled_at']));
                    Notification::make()->title('تمت مطابقة العمليات وتسجيل التسوية في Ledger')->success()->send();
                }),
        ];
    }
}
