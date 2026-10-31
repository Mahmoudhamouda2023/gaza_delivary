<?php

return [
    'release' => '1.3.1',
    'contact_phone' => env('DELIVERY_CONTACT_PHONE'),
    'contact_email' => env('DELIVERY_CONTACT_EMAIL'),
    'contact_whatsapp' => env('DELIVERY_CONTACT_WHATSAPP'),
    'load_foundation_migrations' => false,
    'currency' => 'ILS',
    'payment_methods' => ['cash' => 'نقدًا', 'jawwal_pay' => 'جوال باي', 'palpay' => 'بال باي', 'bank' => 'تحويل بنكي'],
    'order_statuses' => [
        'created' => 'جديد', 'assigned' => 'مسند لكابتن', 'picked_up' => 'تم الاستلام من المتجر',
        'out_for_delivery' => 'في الطريق', 'delivered' => 'تم التسليم', 'failed' => 'تعذّر التسليم',
        'returned' => 'تمت إعادة الطرد للمتجر', 'cancelled' => 'ملغي',
    ],
    'status_labels' => [
        'pending' => 'قيد المراجعة',
        'active' => 'فعّال',
        'suspended' => 'موقوف مؤقتًا',
        'rejected' => 'لم يُعتمد',
    ],
    'role_labels' => [
        'super_admin' => 'مشرف عام',
        'operations' => 'إدارة التشغيل',
        'finance' => 'الشؤون المالية',
        'captain' => 'كابتن توصيل',
    ],
];
