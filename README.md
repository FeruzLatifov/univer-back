# HEMIS Laravel Backend

**HEMIS** - Higher Education Management Information System uchun Laravel backend API.

Bu loyiha PHP 8.3 va Laravel 11.x framework dan foydalanadi. Shuning uchun kodni yozishda zamonaviy PHP imkoniyatlaridan foydalaning.

#### Happy coding!

---

## 📋 Talablar

- **PHP**: 8.3 yoki yuqori
- **Composer**: Dependency manager
- **PostgreSQL**: 14 yoki yuqori
- **Node.js & NPM**: (agar kerak bo'lsa, frontend assets uchun)

---

## 🚀 O'rnatish

### 1. Loyihani yuklab olish

```bash
git clone <repository-url>
cd univer-back
```

### 2. Dependencies o'rnatish

```bash
composer install
```

### 3. Environment sozlash

`.env` faylni yarating:

```bash
cp .env.example .env
```

Application key yaratish:

```bash
php artisan key:generate
```

### 4. Database sozlash

`.env` faylda database ma'lumotlarini to'ldiring:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hemis_401
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

### 5. JWT Secret yaratish

```bash
php artisan jwt:secret
```

### 6. Database migration

**Yangi baza uchun** (0 dan boshlash):

```bash
# Database yaratish
sudo -u postgres psql -c "CREATE DATABASE hemis_new;"
sudo -u postgres psql hemis_new -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;"

# Migration ishlatish
php artisan migrate
```

**Mavjud univer-yii2 baza uchun**:

```bash
# Backup olish (MAJBURIY!)
pg_dump -U postgres -d hemis_401 > backup_$(date +%Y%m%d_%H%M%S).sql

# Migration ishlatish
php artisan migrate

# Permission mapping
php artisan db:seed --class=MapYii2ToLaravelPermissions
```

### 7. Serverni ishga tushirish

```bash
php artisan serve
```

Server ishga tushadi: `http://127.0.0.1:8000`

---

## 📁 Loyiha Strukturasi

```
app/
    Console/         Artisan commands
    Http/
        Controllers/ API controllers
        Middleware/  HTTP middleware
        Requests/    Form request validation
    Models/          Eloquent models
    Services/        Business logic
bootstrap/           Framework bootstrap
config/              Configuration files
database/
    migrations/      Database migrations
    seeders/         Database seeders
public/              Public assets (entry point)
resources/           Views, locales
routes/
    api.php          API routes
storage/             Logs, cache, uploads
tests/               Unit and feature tests
```

---

## 🔧 Asosiy Commandlar

### Development

```bash
# Development server ishga tushirish
php artisan serve

# Development server (boshqa port)
php artisan serve --port=8001

# Cache tozalash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Database

```bash
# Migration ishlatish
php artisan migrate

# Migration rollback (1 qadam orqaga)
php artisan migrate:rollback --step=1

# Migration holati
php artisan migrate:status

# Database seeder
php artisan db:seed
php artisan db:seed --class=MapYii2ToLaravelPermissions
```

### Testing

```bash
# Testlarni ishga tushirish
php artisan test

# Specific test
php artisan test --filter=AuthTest

# Specific test class
php artisan test --filter=AuthenticationTest

# Coverage bilan
php artisan test --coverage
```

---

## 🧪 Testing Guide

HEMIS backend ikkita test rejimini qo'llab-quvvatlaydi: **Test Database** va **Production Database** rejimlari. Bu sizga xavfsiz va moslashuvchan test muhitini ta'minlaydi.

### 🎯 Ikkita Test Rejimi

#### 1️⃣ Test Database Rejimi (Tavsiya etiladi - Development)

**Xususiyatlari:**
- ✅ Test database (`test_401`) ishlatiladi
- ✅ RefreshDatabase yoqilgan - har test oldidan baza tozalanadi
- ✅ Test ma'lumotlar avtomatik seed qilinadi
- ✅ Toza muhit - har safar 0 dan boshlanadi
- ✅ Xavfsiz - production ma'lumotlariga tegmaydi

**Qachon ishlatish:**
- Development jarayonida
- Yangi test yozishda
- CI/CD pipeline'da
- Toza baza muhiti kerak bo'lganda

#### 2️⃣ Production Database Rejimi (Integration Testing)

**Xususiyatlari:**
- ✅ Production database (`hemis_401`) ishlatiladi
- 🛡️ RefreshDatabase **BLOKLANGAN** - baza hech qachon tozlanmaydi
- ✅ Mavjud univer-yii2 ma'lumotlari bilan integratsiya testi
- ✅ Real-world test - asl ma'lumotlar bilan
- 🔒 Xavfsizlik mexanizmi - ma'lumotlar o'chirilmaydi

**Qachon ishlatish:**
- Production bazada integratsiya test qilishda
- Asl ma'lumotlar bilan test qilishda
- Migration'dan keyin tekshiruvda
- Yii2 ↔ Laravel mos-mutanosoblik testida

---

### ⚙️ Test Rejimini Sozlash

Test rejimi **faqat `.env` fayl orqali** boshqariladi. Bu eng xavfsiz yondashuv.

#### `.env` Konfiguratsiyasi

```env
# Database Configuration
DB_DATABASE=hemis_401          # Production database (univer-yii2)

# Test Configuration
# CRITICAL: Faqat shu sozlama barcha xatti-harakatni boshqaradi!
USE_TEST_DATABASE=true         # true yoki false
DB_DATABASE_TEST=test_401      # Test database nomi
```

#### Sozlamalar Tushuntirilishi

| Sozlama | Qiymat | Baza | RefreshDatabase | Ma'lumotlar |
|---------|--------|------|-----------------|-------------|
| `USE_TEST_DATABASE=true` | Yoqilgan | test_401 | ✅ Yoqilgan | 🔄 Har safar yangilanadi |
| `USE_TEST_DATABASE=false` | O'chirilgan | hemis_401 | 🛡️ Bloklangan | 🔒 Saqlanadi |
| O'rnatilmagan | Default | hemis_401 | 🛡️ Bloklangan | 🔒 Saqlanadi |

---

### 📝 Test Rejimlarini Ishlatish

#### Development - Test Database Bilan

**1. `.env` faylni sozlang:**

```env
USE_TEST_DATABASE=true
DB_DATABASE_TEST=test_401
```

**2. Test database yarating (birinchi marta):**

```bash
# PostgreSQL'da test database yaratish
sudo -u postgres psql -c "CREATE DATABASE test_401;"
sudo -u postgres psql test_401 -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;"
```

**3. Testlarni ishga tushiring:**

```bash
php artisan test
```

**Natija:**

```
🧪 TEST MODE ENABLED (.env: USE_TEST_DATABASE=true)
   Database: test_401
   ✅ RefreshDatabase ENABLED - all tables will be dropped and recreated
   📝 Seeding test users...
   ✅ Test data ready

Tests:  12 passed (69 assertions)
```

**Nima sodir bo'ladi:**
1. ✅ test_401 database'ga ulanadi
2. ✅ Barcha jadvallar o'chiriladi (`migrate:fresh`)
3. ✅ Migrationslar qayta ishga tushadi
4. ✅ Test ma'lumotlar seed qilinadi (TestUsersSeeder)
5. ✅ Testlar bajariladi
6. ✅ Keyingi test uchun qaytadan tozalanadi

---

#### Production - Real Database Bilan

**1. `.env` faylni sozlang:**

```env
USE_TEST_DATABASE=false
DB_DATABASE=hemis_401
```

**2. Backup oling (MAJBURIY!):**

```bash
# Xavfsizlik uchun backup
pg_dump -U postgres -d hemis_401 > backup_before_test_$(date +%Y%m%d_%H%M%S).sql
```

**3. Testlarni ishga tushiring:**

```bash
php artisan test
```

**Natija:**

```
🏢 PRODUCTION MODE (.env: USE_TEST_DATABASE=false)
   Database: hemis_401
   🛡️  RefreshDatabase BLOCKED - data is safe
   📌 Using existing data from univer-yii2

Tests:  X passed (Y assertions)
```

**Nima sodir bo'ladi:**
1. ✅ hemis_401 database'ga ulanadi
2. 🛡️ RefreshDatabase **BLOKLANADI** - hech narsa o'chirilmaydi
3. ✅ Mavjud ma'lumotlar bilan test bajariladi
4. ✅ Yii2 ↔ Laravel integratsiya tekshiriladi
5. 🔒 Barcha ma'lumotlar saqlanib qoladi

---

### 🔐 Xavfsizlik Mexanizmlari

Backend bir nechta xavfsizlik qatlamlariga ega:

#### 1. `.env` Nazorat

Faqat `.env` fayl `USE_TEST_DATABASE` o'zgaruvchisi orqali rejimni boshqaradi:
- `phpunit.xml` hech narsa override qilmaydi
- Command-line orqali o'zgartirish mumkin emas
- Kod ichidan o'zgartirish mumkin emas

#### 2. Database Almashish Xavfsizligi

`tests/CreatesApplication.php`:
- ✅ `USE_TEST_DATABASE=true` bo'lsa → `DB_DATABASE_TEST` ishlatadi
- ✅ Agar `DB_DATABASE_TEST` sozlanmagan bo'lsa → Exception
- ✅ Database almashinishi aniq va shaffof

#### 3. RefreshDatabase Bloklanishi

`tests/SeedsTestData.php`:
- 🛡️ Production bazada RefreshDatabase **MUTLAQ BLOKLANGAN**
- 🛡️ Manual ishlatish ham mumkin emas
- 🛡️ Trait override qilish orqali boshqariladi
- ✅ Test bazada faqat ruxsat etiladi

#### 4. Ko'p Qatlamli Tekshiruvlar

```php
// 1. .env tekshiruvi
if (!env('USE_TEST_DATABASE')) {
    // Production rejim - RefreshDatabase bloklangan
}

// 2. Baza nomi tekshiruvi
if ($currentDB === $productionDB) {
    throw new Exception("Cannot use RefreshDatabase on production!");
}

// 3. Test baza sozlanganligini tekshirish
if (!$testDatabase) {
    throw new Exception("DB_DATABASE_TEST not configured!");
}
```

---

### 🎓 Best Practices

#### ✅ Development Jarayonida

```bash
# 1. .env da test rejimni yoqing
USE_TEST_DATABASE=true

# 2. Test database yarating (faqat birinchi marta)
sudo -u postgres psql -c "CREATE DATABASE test_401;"

# 3. Testlarni tez-tez ishga tushiring
php artisan test

# 4. Specific testlarni debug qiling
php artisan test --filter=test_employee_can_login
```

#### ✅ Production Bazada Test Qilish

```bash
# 1. BACKUP OLING!
pg_dump -U postgres -d hemis_401 > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. .env da production rejimga o'ting
USE_TEST_DATABASE=false

# 3. Testlarni ishga tushiring
php artisan test

# 4. Bazaning butun qolganini tekshiring
PGPASSWORD=postgres psql -h 127.0.0.1 -U postgres -d hemis_401 -c \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';"
```

#### ✅ CI/CD Pipeline

```yaml
# .github/workflows/tests.yml
jobs:
  test:
    steps:
      - name: Setup Test Database
        run: |
          createdb test_401
          psql test_401 -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;"

      - name: Run Tests
        env:
          USE_TEST_DATABASE: true
          DB_DATABASE_TEST: test_401
        run: php artisan test
```

#### ❌ Nima Qilmaslik Kerak

```bash
# ❌ Production bazada USE_TEST_DATABASE=true o'rnatmang
USE_TEST_DATABASE=true    # Bu production bazani tozlaydi!
DB_DATABASE=hemis_401     # XAVFLI!

# ✅ To'g'risi:
USE_TEST_DATABASE=true
DB_DATABASE_TEST=test_401  # Alohida test baza
```

---

### 📊 Test Ma'lumotlari

Test database rejimida quyidagi test ma'lumotlar avtomatik yaratiladi:

#### Test Adminlar

| Login | Parol | Status | Rol |
|-------|-------|--------|-----|
| test_admin | admin123 | active | Admin |
| inactive_admin | admin123 | inactive | Admin |

#### Test Studentlar

| Student ID | Parol | Status |
|------------|-------|--------|
| TEST001 | student123 | active |
| TEST002 | student123 | inactive |

#### OAuth Clientlar

- Admin Panel Client
- Mobile App Client
- Student Portal Client

**Ushbu ma'lumotlar:** `database/seeders/TestUsersSeeder.php`

---

### 🔍 Testlarni Debug Qilish

#### Specific Test Ishga Tushirish

```bash
# Test class
php artisan test --filter=AuthenticationTest

# Specific test method
php artisan test --filter=test_employee_can_login_with_valid_credentials

# Multiple filters
php artisan test --filter=Authentication --filter=Login
```

#### Verbose Output

```bash
# Batafsil chiqish
php artisan test -v

# Juda batafsil
php artisan test -vv

# Maximum detail
php artisan test -vvv
```

#### Database Holatini Tekshirish

```bash
# Test database'dagi jadvallar
PGPASSWORD=postgres psql -h 127.0.0.1 -U postgres -d test_401 -c \
  "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename;"

# Production database'dagi jadvallar (FAQAT o'qish!)
PGPASSWORD=postgres psql -h 127.0.0.1 -U postgres -d hemis_401 -c \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';"
```

#### Test Xatolarini Debug Qilish

```bash
# Stop on failure
php artisan test --stop-on-failure

# Show more details
php artisan test --testdox

# With coverage
php artisan test --coverage --min=80
```

---

### ⚠️ Muhim Eslatmalar

#### 🔴 Production Bazada Xavfsizlik

1. **HECH QACHON** production bazada `USE_TEST_DATABASE=true` qilmang
2. **DOIM** backup oling test'dan oldin
3. **FAQAT** read-only testlar yozing production uchun
4. **TEKSHIRING** test natijalari bazani o'zgartirmaganini

#### 🟡 Test Database

1. Test database CI/CD'da avtomatik yaratilishi kerak
2. Development muhitda bitta marta yarating
3. Test ma'lumotlar yetarli bo'lishiga ishonch hosil qiling
4. Seed qilish tez ishlashi kerak (optimizatsiya qiling)

#### 🟢 Best Practices

1. Integration testlarni production bazada ishlatmang
2. Unit testlar uchun doim test database ishlatiladi
3. Feature testlar uchun test database tavsiya etiladi
4. Manual testlar uchun production database (ehtiyotkorlik bilan)

---

### 📈 Test Coverage

```bash
# Coverage hisoboti
php artisan test --coverage

# Minimum coverage belgilash
php artisan test --coverage --min=80

# HTML report
php artisan test --coverage-html coverage/
```

Coverage hisobotini ko'rish:

```bash
# Browser'da ochish
firefox coverage/index.html
```

---

### 🐛 Troubleshooting

#### Test Database Ulanish Xatosi

```bash
# Database mavjudligini tekshiring
sudo -u postgres psql -c "\l" | grep test_401

# Agar yo'q bo'lsa, yarating
sudo -u postgres psql -c "CREATE DATABASE test_401;"
sudo -u postgres psql test_401 -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;"
```

#### RefreshDatabase Ishlayotgan Bo'lsa (Production'da)

```bash
# 1. DARHOL TO'XTATING (Ctrl+C)

# 2. .env ni tekshiring
cat .env | grep USE_TEST_DATABASE

# 3. false ga o'rnating
USE_TEST_DATABASE=false

# 4. Backup'dan restore qiling (agar kerak bo'lsa)
psql -U postgres -d hemis_401 < backup_YYYYMMDD_HHMMSS.sql
```

#### Test Ma'lumotlari Seed Bo'lmayapti

```bash
# Seeder'ni qo'lda ishga tushiring
php artisan db:seed --class=TestUsersSeeder

# Seeder xatolarini debug qiling
php artisan db:seed --class=TestUsersSeeder -vvv
```

---

## 📖 API Documentation

### Swagger UI (Interactive)

API documentation Swagger UI orqali ko'rish mumkin:

```bash
# Browser'da oching
http://127.0.0.1:8000/docs/api
```

**Features**:
- ✅ Interactive API testing ("Try it out")
- ✅ OpenAPI 3.0.3 specification
- ✅ Complete endpoint documentation
- ✅ Request/Response examples
- ✅ Authentication testing

### Scramble UI (Alternative)

Zamonaviy API documentation:

```bash
# Browser'da oching
http://127.0.0.1:8000/docs/api
```

**Features**:
- ✅ Auto-generated from code
- ✅ Modern UI
- ✅ Type-safe
- ✅ Laravel 11 optimized

### API Spec File

OpenAPI specification JSON:

```bash
# JSON formatda yuklab olish
http://127.0.0.1:8000/docs/api/spec

# yoki curl orqali
curl http://127.0.0.1:8000/docs/api/spec > api-docs.json
```

### Documentation Yangilash

API documentation'ni yangilash uchun:

```bash
# Barcha API documentation'ni generate qilish
php artisan docs:generate --all

# Ma'lum bir role uchun
php artisan docs:generate --role=student
php artisan docs:generate --role=teacher
php artisan docs:generate --role=admin
```

---

## 🔑 API Authentication

### JWT Tokens

Backend JWT (JSON Web Token) authentication ishlatadi:

- **Access Token**: 60 daqiqa (API requestlar uchun)
- **Refresh Token**: 30 kun (access token yangilash uchun)

### Login

```bash
POST /api/auth/login
{
  "login": "admin@example.com",
  "password": "password"
}

Response:
{
  "access_token": "eyJ0eXAiOiJKV1...",
  "refresh_token": "eyJ0eXAiOiJKV1...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### API Request

```bash
GET /api/auth/me
Authorization: Bearer eyJ0eXAiOiJKV1...
```

---

## 📚 Batafsil Hujjatlar

### Migration & Database

- **[docs/MIGRATION-GUIDE.md](../docs/MIGRATION-GUIDE.md)** - To'liq migration qo'llanmasi
- **[docs/YII2-DATABASE-CHANGES.md](../docs/YII2-DATABASE-CHANGES.md)** - Database o'zgarishlar hujjati
- **[docs/ROLLBACK-SAFETY-GUIDE.md](../docs/ROLLBACK-SAFETY-GUIDE.md)** - Xavfsiz rollback qo'llanmasi

### Test Reports

- **[docs/26-MIGRATION-FINAL-TEST-REPORT.md](../docs/26-MIGRATION-FINAL-TEST-REPORT.md)** - Migration test natijalari

### Database Schema

- **[docs/CHARTDB-IMPORT-GUIDE.md](../docs/CHARTDB-IMPORT-GUIDE.md)** - ChartDB schema import qo'llanmasi

---

## 🔄 Git Workflow

### Loyihani yangilash

```bash
# Git'dan yangilanishlarni olish
git pull

# Dependencies yangilash (agar kerak bo'lsa)
composer install

# Migration ishlatish (agar yangi migration bo'lsa)
php artisan migrate

# Cache tozalash
php artisan config:clear
php artisan cache:clear
```

---

## ❓ Tez-tez so'raladigan savollar (FAQ)

### Q: Migration ishlatganimda ma'lumotlar o'chadimi?

**A**: YO'Q! Migration faqat jadval strukturasini o'zgartiradi. Barcha mavjud ma'lumotlar saqlanib qoladi.

```
BEFORE: 380 admins, 24 roles, 487 resources
AFTER:  380 admins, 24 roles, 487 resources ✅
```

### Q: Rollback xavfslimi?

**A**: HA! Rollback faqat Laravel qo'shgan 3 ta jadval va 5 ta ustunni o'chiradi. Yii2 ma'lumotlariga tegmaydi.

### Q: univer-yii2 bilan bir vaqtda ishlash mumkinmi?

**A**: HA! Ikkala tizim bir xil bazada parallel ishlashi mumkin. Conflict bo'lmaydi.

---

## 🐛 Muammolarni hal qilish (Troubleshooting)

### Migration xatolik bersa

```bash
# 1. Migration holatini tekshirish
php artisan migrate:status

# 2. Rollback qilish
php artisan migrate:rollback --step=1

# 3. Qayta ishlatish
php artisan migrate
```

### JWT token xatoligi

```bash
# JWT secret qayta yaratish
php artisan jwt:secret

# Config cache tozalash
php artisan config:clear
```

### Database connection xatoligi

```bash
# 1. .env faylni tekshiring (DB_* parametrlar)
# 2. PostgreSQL ishlayotganini tekshiring
sudo systemctl status postgresql

# 3. Database mavjudligini tekshiring
psql -U postgres -l
```

---

## 📞 Support

**Loyiha hujjatlari**: `/home/adm1n/univer/docs`

**Asosiy hujjatlar**:
- Migration guide
- Database changes
- Rollback safety
- Test reports

---

**Versiya**: 1.0.0
**Laravel**: 11.x
**PHP**: 8.3+
**Database**: PostgreSQL 14+
**Status**: ✅ Production Ready

**Last Updated**: January 9, 2025
