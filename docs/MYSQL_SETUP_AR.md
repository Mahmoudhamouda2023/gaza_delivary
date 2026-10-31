# تشغيل غزة ديلفري على MySQL

هذه النسخة تستخدم MySQL افتراضيًا. جميع الجداول وعلاقاتها تُنشأ عبر Laravel Migrations، ومحرك التخزين InnoDB والترميز utf8mb4_unicode_ci لحفظ العربية.

## 1. إنشاء القاعدة

شغّل خدمة MySQL وافتح phpMyAdmin، ثم نفّذ من تبويب SQL:

```sql
CREATE DATABASE IF NOT EXISTS gaza_delivery
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

الأمر نفسه موجود في database/create-database.mysql.sql. هذا الأمر ينشئ القاعدة فقط؛ الجداول تُنشأ في الخطوة الثالثة أو بطريقة الاستيراد البديلة.

## 2. الاتصال

فك المشروع في مجلد جديد، وافتح Terminal داخل المجلد الذي يحتوي artisan:

```bash
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
```

لتثبيت جديد فقط يُنشأ APP_KEY. عند تحديث مشروع قائم احتفظ بمفتاحه السابق.

عدّل .env حسب إعدادات MySQL الفعلية:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gaza_delivery
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

root وكلمة المرور الفارغة مثال لتطوير محلي إذا كان جهازك مضبوطًا هكذا؛ أدخل حسابك وكلمة مروره الفعليين. المنفذ قد يختلف داخل Wamp. تأكد من تفعيل pdo_mysql في PHP الخاص بسطر الأوامر، واحذف DB_URL القديم إن وُجد حتى لا يتجاوز الإعدادات.

## 3. إنشاء الجداول والتشغيل

```bash
php artisan optimize:clear
php artisan migrate --seed
php artisan migrate:status
php artisan delivery:bootstrap-admin
php artisan serve
```

ينشئ migrate --seed الجداول والأدوار والصلاحيات. لا يوجد حساب أدمن افتراضي، ولا تُضاف متاجر أو طلبات أو أسعار وهمية. أمر bootstrap-admin يطلب منك بيانات المشرف وكلمة مروره.

لوحة الإدارة: http://127.0.0.1:8000/staff/login

## بديل: استيراد الجداول من phpMyAdmin

ملف database/gaza_delivery.mysql.sql يحتوي مخطط MySQL كاملًا مع بيانات الأدوار والصلاحيات وسجل migrations، دون حسابات أو بيانات تشغيل.

اختر قاعدة فارغة ثم «استيراد» واختر الملف. لا تستورده فوق قاعدة قائمة. بعد الاستيراد وضبط .env نفّذ optimize:clear ثم migrate --seed للتحقق وتطبيق أي تحديثات لاحقة، ثم bootstrap-admin. لا تحتاج إلى إنشاء الجداول يدويًا.

لا يحتوي ملف SQL على DROP TABLE أو DROP DATABASE. ومع ذلك يجب استيراده في قاعدة فارغة لمنع التعارض مع جداول قائمة. لا تستورد ملف create-database وحده وتتوقع ظهور الجداول.

## إذا سبق استخدام SQLite

التحويل إلى MySQL لا ينسخ بيانات SQLite تلقائيًا. احتفظ بالنسخة السابقة وقاعدتها وملف .env. إذا كانت تحتوي بيانات مطلوبة، تُرحّل في خطوة مستقلة مع مراجعة أعداد السجلات والعلاقات والقيم المالية. لا تستخدم migrate:fresh أو db:wipe على قاعدة العمل.

## الاختبارات

أنشئ قاعدة اختبار مستقلة:

```sql
CREATE DATABASE gaza_delivery_test
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

ثم نفّذ:

```bash
php artisan test -c phpunit.mysql.xml
```

هذا الإعداد يفرض اسم gaza_delivery_test واتصال mysql، ويستخدم بيانات الاتصال من بيئتك أو .env. الاختبارات تعيد بناء جداول قاعدة الاختبار؛ لا تستخدم قاعدة العمل للاختبار. إعداد phpunit.xml الافتراضي يستخدم MySQL وقاعدة الاختبار نفسها أيضًا.

## النسخ الاحتياطي

استخدم تصدير SQL من phpMyAdmin للبنية والبيانات، أو من سطر الأوامر:

```bash
mysqldump --host=127.0.0.1 --port=3306 --user=root --password --single-transaction --routines --triggers --no-tablespaces --set-gtid-purged=OFF --result-file=gaza_delivery_backup.sql gaza_delivery
```

عدّل المستخدم والمنفذ؛ ستُطلب كلمة المرور دون كتابتها ضمن الأمر. استخدام --result-file يتجنب مشاكل ترميز إعادة توجيه الإخراج في بعض إصدارات PowerShell. تجنّب تغييرات بنية الجداول أثناء النسخ، واحتفظ بنسخة محمية من .env وAPP_KEY، واختبر الاستعادة في قاعدة منفصلة. أمر delivery:backup القديم خاص بـ SQLite ويرفض العمل على MySQL.

## مشاكل الاتصال المعتادة

- Unknown database: أنشئ القاعدة وتأكد من DB_DATABASE.
- Access denied: راجع DB_USERNAME وDB_PASSWORD وصلاحيات الحساب.
- Connection refused: شغّل MySQL وراجع DB_HOST وDB_PORT.
- could not find driver: فعّل pdo_mysql في PHP المستخدم بالـ Terminal ثم افتح Terminal جديدًا.
- الإعداد القديم ما زال مستخدمًا: نفّذ php artisan optimize:clear.

نتائج التحقق الفعلية وحدوده موجودة في VALIDATION_AR.md. نتائج الإصدار السابق ضمن previous-sqlite-release هي توثيق تاريخي لـ SQLite.
