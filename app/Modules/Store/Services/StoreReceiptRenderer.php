<?php

namespace App\Modules\Store\Services;

use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Settings\StoreSettings;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class StoreReceiptRenderer
{
    public function __construct(private StoreSettings $settings) {}

    public function isAvailable(Order $order): bool
    {
        return in_array($order->status->getValue(), [
            OrderStatus::Confirmed->value,
            OrderStatus::Accepted->value,
            OrderStatus::Preparing->value,
            OrderStatus::ReadyForPickup->value,
            OrderStatus::Completed->value,
            OrderStatus::RefundPending->value,
            OrderStatus::Refunded->value,
        ], true) || $order->paid_at !== null;
    }

    /** @return array{vat_rate_percentage: int, seller_legal_name: ?string, seller_tax_number: ?string, seller_address: ?string, seller_phone: ?string, receipt_footer: ?string} */
    public function settingsFor(Order $order): array
    {
        return [
            'vat_rate_percentage' => $order->vat_rate_percentage ?? $this->settings->vat_rate_percentage,
            'seller_legal_name' => filled($order->seller_legal_name) ? $order->seller_legal_name : $this->settings->legal_name,
            'seller_tax_number' => filled($order->seller_tax_number) ? $order->seller_tax_number : $this->settings->tax_number,
            'seller_address' => filled($order->seller_address) ? $order->seller_address : $this->settings->receipt_address,
            'seller_phone' => filled($order->seller_phone) ? $order->seller_phone : $this->settings->receipt_phone,
            'receipt_footer' => filled($order->receipt_footer) ? $order->receipt_footer : $this->settings->receipt_footer,
        ];
    }

    public function pdf(Order $order): string
    {
        return PDF::loadView('pages.customer.store.orders.receipt', [
            'order' => $order,
            'receiptSettings' => $this->settingsFor($order),
            'forPdf' => true,
        ], [], [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'xbriyaz',
            'default_font_size' => 11,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'orientation' => 'P',
            'title' => 'فاتورة ضريبية - '.$order->reference,
            'author' => 'بيرحاء',
            'auto_language_detection' => true,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ])->output();
    }
}
