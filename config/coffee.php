<?php

return [
    'opening_date' => '1 أغسطس 2026',
    'location' => 'مخيم بيرحاء، ولاية إبراء',
    'service_note' => 'البيع والاستلام من الموقع',
    'currency' => 'OMR',
    'whatsapp_number' => '96874155123',
    'whatsapp_message' => 'السلام عليكم، لدي استفسار عن قهوة بيرحاء.',

    'groups' => [
        [
            'id' => 'brewed',
            'name' => 'القهوة المقطرة',
            'description' => 'تحضير هادئ يبرز نكهة حبوب الموسم.',
            'items' => [
                [
                    'name' => 'V60 حبوب الموسم',
                    'description' => 'تقطير يدوي بحبوب مختارة.',
                    'price_baisa' => 2200,
                ],
            ],
        ],
        [
            'id' => 'espresso',
            'name' => 'الإسبريسو والحليب',
            'description' => 'مشروبات يومية متوازنة وواضحة.',
            'items' => [
                [
                    'name' => 'إسبريسو',
                    'description' => 'جرعة مركزة ومتوازنة.',
                    'price_baisa' => 900,
                ],
                [
                    'name' => 'كورتادو',
                    'description' => 'إسبريسو وحليب بمقدار متقارب.',
                    'price_baisa' => 1400,
                ],
                [
                    'name' => 'لاتيه بيرحاء',
                    'description' => 'إسبريسو مع حليب مخملي.',
                    'price_baisa' => 1600,
                ],
                [
                    'name' => 'كرك بيرحاء',
                    'description' => 'شاي بالحليب والهيل والزعفران.',
                    'price_baisa' => 600,
                ],
            ],
        ],
        [
            'id' => 'cold',
            'name' => 'المشروبات الباردة',
            'description' => 'خيارات منعشة لأيام إبراء.',
            'items' => [
                [
                    'name' => 'كولد برو',
                    'description' => 'استخلاص بارد طويل المدة.',
                    'price_baisa' => 1900,
                ],
                [
                    'name' => 'آيس لاتيه',
                    'description' => 'إسبريسو وحليب وثلج.',
                    'price_baisa' => 2300,
                ],
            ],
        ],
        [
            'id' => 'bakery',
            'name' => 'المخبوزات',
            'description' => 'مرافقة خفيفة للفنجان، حسب التوفر اليومي.',
            'items' => [
                [
                    'name' => 'كرواسون اللوز',
                    'description' => 'كرواسون هش بحشوة اللوز.',
                    'price_baisa' => 1200,
                ],
                [
                    'name' => 'كوكيز التمر',
                    'description' => 'تمر من الشرقية وقليل من السكر.',
                    'price_baisa' => 700,
                ],
                [
                    'name' => 'كيك الجزر',
                    'description' => 'قطعة بالجوز والقرفة.',
                    'price_baisa' => 1100,
                ],
            ],
        ],
    ],
];
