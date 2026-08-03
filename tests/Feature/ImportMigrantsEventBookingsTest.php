<?php

use App\Models\WebhookDelivery;
use App\Modules\Events\Actions\ImportMigrantsEventBookings;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;

beforeEach(function (): void {
    config([
        'byruhaa.webhooks.booking_created_url' => 'https://partner.test/webhooks/booking-created',
        'byruhaa.webhooks.queue' => 'sync',
    ]);
});

test('import workbook honors send webhook column', function (): void {
    $filePath = tempnam(sys_get_temp_dir(), 'migrants_').'.xlsx';
    $writer = WriterFactory::createFromFile($filePath);
    $writer->openToFile($filePath);

    $writer->addRow(Row::fromValues([
        'تاريخ التسجيل',
        'اسم الطالب',
        'الصف الدراسي',
        'تاريخ الميلاد',
        'صلة القرابة',
        'اسم ولي الأمر',
        'الرقم المدني',
        'هاتف ولي الأمر',
        'البريد الإلكتروني',
        'العنوان التفصيلي',
        'المبلغ المطلوب دفعه',
        'طريقة الدفع',
        'حالة الطلب',
        'send webhook',
    ]));

    $writer->addRow(Row::fromValues([
        '2026-06-20',
        'أحمد',
        'الصف السابع',
        '2012-05-01',
        'ابن',
        'والد أحمد',
        '1234567890',
        '91234567',
        'ahmed@example.com',
        'Address 1',
        700.0,
        'دفعة كاملة',
        'تم دفع كامل المبلغ',
        '1',
    ]));

    $writer->addRow(Row::fromValues([
        '2026-06-21',
        'سالم',
        'الصف الثامن',
        '2011-07-10',
        'ابن',
        'والد سالم',
        '0987654321',
        '92345678',
        'salim@example.com',
        'Address 2',
        700.0,
        'دفعة كاملة',
        'تم دفع كامل المبلغ',
        '0',
    ]));

    $writer->close();

    try {
        $result = app(ImportMigrantsEventBookings::class)->execute($filePath, 'temporary-password');

        expect($result['bookings'])->toBe(2)
            ->and($result['payments'])->toBe(2)
            ->and($result['approval_webhooks'])->toBe(1);

        expect(WebhookDelivery::query()
            ->where('event', 'booking.created')
            ->count())->toBe(1);
    } finally {
        @unlink($filePath);
    }
});
