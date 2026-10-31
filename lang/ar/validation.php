<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب.',
    'string' => 'حقل :attribute يجب أن يكون نصًا.',
    'array' => 'حقل :attribute يجب أن يكون قائمة صالحة.',
    'email' => 'أدخل بريدًا إلكترونيًا صحيحًا.',
    'unique' => 'قيمة :attribute مستخدمة بالفعل.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'in' => 'قيمة :attribute غير صالحة.',
    'enum' => 'قيمة :attribute غير صالحة.',
    'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
    'distinct' => 'لا تكرر القيمة في :attribute.',
    'min' => ['string' => 'حقل :attribute يجب أن يتكون من :min أحرف على الأقل.', 'array' => 'اختر عنصرًا واحدًا على الأقل في :attribute.', 'numeric' => 'قيمة :attribute أقل من الحد المسموح.'],
    'max' => ['string' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.', 'array' => 'عدد عناصر :attribute أكبر من المسموح.', 'numeric' => 'قيمة :attribute أكبر من الحد المسموح.'],
    'password' => ['letters' => 'كلمة المرور يجب أن تتضمن أحرفًا.', 'numbers' => 'كلمة المرور يجب أن تتضمن أرقامًا.', 'mixed' => 'كلمة المرور يجب أن تتضمن أحرفًا كبيرة وصغيرة.', 'symbols' => 'كلمة المرور يجب أن تتضمن رمزًا.'],
    'attributes' => ['name' => 'الاسم', 'phone' => 'رقم الجوال', 'email' => 'البريد الإلكتروني', 'store_name' => 'اسم المتجر', 'password' => 'كلمة المرور', 'current_password' => 'كلمة المرور الحالية', 'roles' => 'الأدوار', 'roles.*' => 'الدور', 'vehicle_type' => 'وسيلة التوصيل', 'reason' => 'السبب', 'status' => 'الحالة'],
];
