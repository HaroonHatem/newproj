# بوابة التوظيف - Job Portal

نظام شامل لإدارة التوظيف يربط بين الخريجين والشركات مع نظام تحقق من الهوية ومحادثات مباشرة.

## ✨ الميزات الرئيسية

### 🔐 نظام التحقق من الهوية
- **للخريجين**: التحقق من شهادات التخرج
- **للشركات**: التحقق من السجلات التجارية
- **لوحة إدارة**: مراجعة وموافقة طلبات التحقق

### 💬 نظام المحادثات
- محادثات مباشرة بين الشركات والخريجين
- إشعارات فورية للرسائل الجديدة
- واجهة حديثة وسهلة الاستخدام

### 📊 لوحات التحكم
- **لوحة الخريج**: إدارة الطلبات والمحادثات
- **لوحة الشركة**: إدارة الوظائف والمتقدمين
- **لوحة الإدارة**: إدارة النظام والتحقق

### 🔍 البحث والتصفية
- بحث متقدم عن الخريجين والوظائف
- أولوية للخريجين المحققين
- فلاتر ذكية للنتائج

## 🛠️ التقنيات المستخدمة

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Security**: Prepared Statements, Password Hashing
- **File Upload**: Secure file handling

## 📋 متطلبات النظام

- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- Apache/Nginx web server
- مساحة تخزين للملفات المرفوعة

## 🚀 التثبيت

1. **استنساخ المشروع:**
```bash
git clone https://github.com/yourusername/job-portal.git
cd job-portal
```

2. **إعداد قاعدة البيانات:**
```sql
-- تشغيل ملف schema.sql في MySQL
mysql -u username -p database_name < schema.sql
```

3. **تكوين الاتصال:**
```php
// تحديث ملف db.php
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";
```

4. **إعداد الصلاحيات:**
```bash
chmod 755 uploads/
chmod 755 uploads/certificates/
chmod 755 uploads/company_docs/
```

## 📁 هيكل المشروع

```
job-portal/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── uploads/
│   ├── certificates/
│   └── company_docs/
├── admin_users.php
├── admin_verification.php
├── apply.php
├── chat.php
├── db.php
├── employer_dashboard.php
├── graduate_dashboard.php
├── index.php
├── login.php
├── logout.php
├── navbar.php
├── notifications.php
├── register_company.php
├── register_graduate.php
├── schema.sql
├── search_graduates.php
├── search_jobs.php
└── view_applicants.php
```

## 🔧 التكوين

### إعدادات المسؤولين
```php
// في ملفات التسجيل
$admin_emails = ['haroonhatem34@gmail.com', 'hamzahmisr@gmail.com'];
```

### إعدادات الملفات
- **حجم الملف الأقصى**: 10MB للمستندات
- **الأنواع المسموحة**: PDF, JPG, PNG
- **مجلد التخزين**: uploads/

## 📱 الاستخدام

### للخريجين:
1. التسجيل مع رفع شهادة التخرج
2. البحث عن الوظائف
3. التقديم على الوظائف
4. التواصل مع الشركات

### للشركات:
1. التسجيل مع رفع السجل التجاري
2. نشر الوظائف
3. مراجعة المتقدمين
4. التواصل مع الخريجين

### للإدارة:
1. مراجعة طلبات التحقق
2. إدارة المستخدمين
3. مراقبة النظام

## 🔒 الأمان

- تشفير كلمات المرور
- حماية من SQL Injection
- حماية من XSS
- التحقق من صلاحيات المستخدمين
- فحص أنواع الملفات المرفوعة

## 📈 التطوير المستقبلي

- [ ] إشعارات البريد الإلكتروني
- [ ] تطبيق الهاتف المحمول
- [ ] نظام التقييمات
- [ ] إحصائيات متقدمة
- [ ] دعم اللغات المتعددة

## 🤝 المساهمة

نرحب بالمساهمات! يرجى:
1. عمل Fork للمشروع
2. إنشاء branch جديد
3. إجراء التعديلات
4. إرسال Pull Request

## 📄 الترخيص

هذا المشروع مرخص تحت رخصة MIT.

## 📞 الدعم

للحصول على الدعم، يرجى التواصل عبر:
- البريد الإلكتروني: haroonhatem34@gmail.com
- GitHub Issues

---

**تم التطوير بواسطة**: فريق تطوير بوابة التوظيف
**آخر تحديث**: ديسمبر 2024








