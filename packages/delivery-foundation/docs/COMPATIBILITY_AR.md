# توافق الإصدارات — Laravel 12 — 24 أكتوبر 2026

## القرار الحالي

بناءً على طلب محمود، أصبح هدف الحزمة **Laravel 12 فقط**. شرط Composer هو `laravel/framework: ^12.0`، وأداة الاختبار `orchestra/testbench: ^10.0`. أُزيل السماح بإصداري Laravel 10 و13 من هذه الحزمة.

الإصدارات الفرعية الفعلية المختبرة مثبتة في `composer.lock` ومذكورة في `VALIDATION_AR.md`. تغيير إصدار الإطار أعقبه تشغيل الاختبارات وفحص الاعتمادات من جديد.

## مكتبة الجداول

الصورة الأولى كانت تعرض `yajra/laravel-datatables: ^10.0`. مع اعتماد Laravel 12، الإصدار الموافق في وثائق Yajra هو سلسلة 12:

```bash
composer require yajra/laravel-datatables-oracle:"^12.0"
```

الحزمة الأساسية أعلاه مناسبة للبحث والفرز وتقسيم الصفحات من السيرفر. حزمة all-in-one المتاحة هي `yajra/laravel-datatables: ^12.0` وتضاف عند الحاجة لملحقاتها. اختيار أسلوب تثبيت المكتبة وملفات المتصفح سيتم عند تنفيذ واجهات الجداول؛ لم تُثبّت أي من حزم Yajra في مرحلة الحسابات الحالية، ولا توجد دعوى باختبار تكاملها بعد.

## بقية الاعتمادات

- PHP: `^8.2`، مع الالتزام أيضًا بمتطلبات Laravel والاعتمادات المحلولة.
- الصلاحيات: `spatie/laravel-permission: ^6.25`، تدعم Laravel 12 في بيانات Composer المنشورة.
- PHPUnit: `^11.5`، مع Testbench 10 لبيئة Laravel 12.
- ملف `composer.lock` خاص باختبار الحزمة المستقلة. عند دمجها بتطبيق موجود، دع Composer يحل اعتمادات التطبيق، ولا تستبدل ملف lock للتطبيق بهذا الملف.

قاعدة الإنتاج ستُثبت عند تجهيز التطبيق. المخطط يستخدم Laravel Schema وعلاقات وفهارس؛ الاختبارات الحالية على SQLite في الذاكرة. يلزم فحص MySQL/PostgreSQL، خصوصًا الأقفال تحت التزامن، قبل التشغيل الفعلي.

## المصادر الرسمية

- https://laravel.com/docs/12.x
- https://yajrabox.com/docs/laravel-datatables/12.0/installation
- https://github.com/spatie/laravel-permission
- https://repo.packagist.org/p2/spatie/laravel-permission.json
