<?php

namespace App\Support;

use App\Modules\Finance\Models\LedgerAccount;

class LedgerText
{
    public static function accountName(string $code, string $fallback): string
    {
        return match ($code) {
            LedgerAccount::THAWANI_CLEARING_CODE => 'حساب تسوية Thawani',
            LedgerAccount::AFFILIATE_PAYOUT_CLEARING_CODE => 'حساب تسوية مستحقات الشركاء',
            LedgerAccount::CUSTOMER_DEPOSITS_CODE => 'ودائع العملاء',
            LedgerAccount::CASH_ON_HAND_CODE => 'النقدية بالصندوق',
            LedgerAccount::BANK_CODE => 'البنك',
            LedgerAccount::WALLET_LIABILITY_CODE => 'التزام المحافظ',
            LedgerAccount::STORE_DEFERRED_CODE => 'إيرادات القهوة المؤجلة',
            LedgerAccount::VAT_PAYABLE_CODE => 'ضريبة القيمة المضافة المستحقة',
            LedgerAccount::STORE_SALES_CODE => 'مبيعات القهوة',
            LedgerAccount::STORE_SALES_RETURNS_CODE => 'مردودات مبيعات القهوة',
            LedgerAccount::THAWANI_FEES_CODE => 'رسوم Thawani',
            LedgerAccount::AFFILIATE_COMMISSION_LIABILITY_CODE => 'عمولات الشركاء المستحقة',
            LedgerAccount::AFFILIATE_COMMISSION_EXPENSE_CODE => 'مصروف عمولات الشركاء',
            default => $fallback,
        };
    }

    public static function description(string $description): string
    {
        if ($description === 'Affiliate commission reversal for cancelled event') {
            return 'عكس عمولة الشريك بسبب إلغاء الفعالية';
        }

        if (preg_match('/^Wallet top-up (.+) (payment|refund) (.+)$/', $description, $matches) === 1) {
            return 'شحن المحفظة '.$matches[1].'، '.($matches[2] === 'payment' ? 'دفعة ' : 'استرداد ').$matches[3];
        }

        if (preg_match('/^Booking (.+) (payment|refund) (.+)$/', $description, $matches) === 1) {
            return 'الحجز '.$matches[1].'، '.($matches[2] === 'payment' ? 'دفعة ' : 'استرداد ').$matches[3];
        }

        if (preg_match('/^Affiliate commission (.+) for (.+)$/', $description, $matches) === 1) {
            return 'عمولة الشريك '.$matches[1].' للدفعة '.$matches[2];
        }

        $prefixes = [
            'Store payment ' => 'قبض قيمة طلب القهوة ',
            'Store sale ' => 'إثبات إيراد طلب القهوة ',
            'Store refund ' => 'استرداد قيمة طلب القهوة ',
            'Wallet withdrawal ' => 'صرف سحب من المحفظة ',
            'Thawani settlement ' => 'تسوية Thawani ',
            'Thawani payment ' => 'دفعة Thawani ',
            'Thawani refund ' => 'استرداد دفعة Thawani ',
            'Payment ' => 'دفعة ',
            'Refund ' => 'استرداد ',
            'Wallet top-up ' => 'شحن المحفظة ',
            'Booking ' => 'حجز ',
            'Affiliate payout ' => 'صرف مستحقات الشريك ',
            'Affiliate commission ' => 'عمولة الشريك ',
        ];

        foreach ($prefixes as $english => $arabic) {
            if (str_starts_with($description, $english)) {
                return $arabic.substr($description, strlen($english));
            }
        }

        return $description;
    }
}
