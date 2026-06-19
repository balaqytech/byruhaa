<?php

use Illuminate\Support\Facades\Validator;

test('arabic is the default application locale', function () {
    expect(config('app.locale'))->toBe('ar')
        ->and(config('app.faker_locale'))->toBe('ar_OM');
});

test('payment page translations are available in arabic', function () {
    app()->setLocale('ar');

    expect(__('ui.labels.payments'))->toBe('المدفوعات')
        ->and(__('ui.payments.list_subheading'))->toBe('راجع أقساط فعالياتك ومحاولات الدفع والاستردادات.')
        ->and(__('ui.payments.no_payment_attempts'))->toBe('لا توجد محاولات دفع بعد.')
        ->and(__('ui.payments.total_refunded'))->toBe('إجمالي المسترد');
});

test('customer interface translations are available in arabic', function () {
    app()->setLocale('ar');

    expect(__('ui.dashboard.heading'))->toBe('لوحة التحكم')
        ->and(__('ui.actions.log_in'))->toBe('تسجيل الدخول')
        ->and(__('ui.events.book_this_event'))->toBe('احجز هذه الفعالية');
});

test('admin translations are available in arabic', function () {
    app()->setLocale('ar');

    expect(__('admin.resources.customers.navigation_label'))->toBe('العملاء')
        ->and(__('admin.resources.events.navigation_label'))->toBe('الفعاليات')
        ->and(__('admin.actions.approve'))->toBe('اعتماد')
        ->and(__('admin.statuses.pending_review'))->toBe('بانتظار المراجعة');
});

test('validation messages use arabic field names', function () {
    app()->setLocale('ar');

    $validator = Validator::make(['email' => 'not-an-email'], [
        'email' => ['required', 'email'],
    ]);

    expect($validator->errors()->first('email'))->toBe('يجب أن يكون البريد الإلكتروني بريداً إلكترونياً صالحاً.');
});
