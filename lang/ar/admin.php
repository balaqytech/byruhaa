<?php

return [
    'navigation' => [
        'customer_management' => 'إدارة العملاء',
    ],

    'resources' => [
        'bookings' => [
            'label' => 'حجز',
            'plural_label' => 'الحجوزات',
            'navigation_label' => 'الحجوزات',
        ],
        'customers' => [
            'label' => 'عميل',
            'plural_label' => 'العملاء',
            'navigation_label' => 'العملاء',
        ],
        'events' => [
            'label' => 'فعالية',
            'plural_label' => 'الفعاليات',
            'navigation_label' => 'الفعاليات',
        ],
    ],

    'actions' => [
        'approve' => 'اعتماد',
        'cancel' => 'إلغاء',
        'reject' => 'رفض',
    ],

    'event_types' => [
        'camp' => 'مخيم',
        'festival' => 'مهرجان',
        'trip' => 'رحلة',
    ],

    'fields' => [
        'bookings' => 'الحجوزات',
        'contract_terms' => 'شروط العقد',
        'created_at' => 'تاريخ الإنشاء',
        'customer' => 'العميل',
        'description' => 'الوصف',
        'email_address' => 'البريد الإلكتروني',
        'email_verified_at' => 'تاريخ التحقق من البريد',
        'ends_at' => 'ينتهي في',
        'event' => 'الفعالية',
        'excerpt' => 'المختصر',
        'family' => 'العائلة',
        'family_members' => 'أفراد العائلة',
        'location' => 'الموقع',
        'maximum_age' => 'العمر الأقصى',
        'minimum_age' => 'العمر الأدنى',
        'name' => 'الاسم',
        'password' => 'كلمة المرور',
        'phone_number' => 'رقم الهاتف',
        'reference' => 'المرجع',
        'review_notes' => 'ملاحظات المراجعة',
        'seat_capacity' => 'سعة المقاعد',
        'seats' => 'المقاعد',
        'slug' => 'الرابط المختصر',
        'starts_at' => 'يبدأ في',
        'state' => 'الحالة',
        'status' => 'الحالة',
        'type' => 'النوع',
        'updated_at' => 'تاريخ التحديث',
    ],

    'statuses' => [
        'archived' => 'مؤرشفة',
        'approved' => 'معتمد',
        'awaiting_signature' => 'بانتظار التوقيع',
        'cancelled' => 'ملغي',
        'draft' => 'مسودة',
        'pending_review' => 'بانتظار المراجعة',
        'published' => 'منشورة',
        'rejected' => 'مرفوض',
        'signed' => 'موقّع',
        'voided' => 'لاغٍ',
    ],
];
