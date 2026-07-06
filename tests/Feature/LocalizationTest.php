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
        ->and(__('ui.events.book_this_event'))->toBe('احجز هذه الفعالية')
        ->and(__('ui.events.coupon_code'))->toBe('رمز القسيمة')
        ->and(__('ui.events.coupon_code_placeholder'))->toBe('أدخل رمز القسيمة')
        ->and(__('ui.messages.invalid_coupon_code'))->toBe('هذه القسيمة غير صالحة للفعالية المحددة وعدد أفراد العائلة.')
        ->and(__('ui.messages.coupon_usage_limit_reached'))->toBe('وصلت هذه القسيمة إلى حد الاستخدام.');
});

test('admin translations are available in arabic', function () {
    app()->setLocale('ar');

    expect(__('admin.resources.customers.navigation_label'))->toBe('العملاء')
        ->and(__('admin.resources.events.navigation_label'))->toBe('الفعاليات')
        ->and(__('admin.actions.approve'))->toBe('اعتماد')
        ->and(__('admin.statuses.pending_review'))->toBe('بانتظار المراجعة')
        ->and(__('admin.resources.coupons.navigation_label'))->toBe('القسائم')
        ->and(__('admin.fields.maximum_uses'))->toBe('الحد الأقصى للاستخدامات')
        ->and(__('admin.fields.maximum_uses_per_customer'))->toBe('الحد الأقصى للاستخدامات لكل عميل')
        ->and(__('admin.fields.usage'))->toBe('الاستخدام')
        ->and(__('admin.fields.unlimited'))->toBe('غير محدود')
        ->and(__('admin.coupon_form.help.blank_usage_limit'))->toBe('اتركه فارغًا لاستخدام غير محدود.');
});

test('validation messages use arabic field names', function () {
    app()->setLocale('ar');

    $validator = Validator::make(['email' => 'not-an-email'], [
        'email' => ['required', 'email'],
    ]);

    expect($validator->errors()->first('email'))->toBe('يجب أن يكون البريد الإلكتروني بريداً إلكترونياً صالحاً.');
});
