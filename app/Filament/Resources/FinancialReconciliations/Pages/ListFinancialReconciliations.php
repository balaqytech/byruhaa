<?php

namespace App\Filament\Resources\FinancialReconciliations\Pages;

use App\Filament\Resources\FinancialReconciliations\FinancialReconciliationResource;
use App\Modules\Finance\Actions\RunFinancialReconciliation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFinancialReconciliations extends ListRecords
{
    protected static string $resource = FinancialReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runReconciliation')->label('تشغيل المطابقة')->visible(fn (): bool => FinancialReconciliationResource::canRun())
                ->schema([
                    DatePicker::make('as_of_date')->label('حتى تاريخ')->default(today())->required(),
                    FileUpload::make('bank_statement_path')->label('كشف Bank بصيغة CSV: reference,amount_baisa')->disk('local')->directory('bank-statements')->visibility('private')->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel']),
                ])
                ->action(function (array $data, RunFinancialReconciliation $reconciliation): void {
                    abort_unless(FinancialReconciliationResource::canRun(), 403);
                    $run = $reconciliation->execute((string) $data['as_of_date'], (int) Filament::auth()->id(), $data['bank_statement_path'] ?? null);
                    Notification::make()->title("نتيجة المطابقة: {$run->discrepancy_count} فرق، {$run->pending_count} عملية معلقة")->send();
                }),
        ];
    }
}
