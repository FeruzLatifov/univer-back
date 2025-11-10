# HEMIS Laravel Backend

**HEMIS** - Higher Education Management Information System uchun Laravel backend API.

**Loyiha holati**: ✅ Production Ready  
**Laravel versiyasi**: 11.x  
**PHP versiyasi**: 8.2+  
**Database**: PostgreSQL 14+

[English version → README.EN.md](README.EN.md)

---

## 📋 Mundarija

1. [Loyiha Haqida](#loyiha-haqida)
2. [O'rnatish](#ornatish)
3. [Database Setup](#database-setup)
4. [Migration](#migration)
5. [Rollback (Xavfsiz)](#rollback-xavfsiz)
6. [Hujjatlar](#hujjatlar)

---

## 🎯 Loyiha Haqida

HEMIS Laravel backend - bu **univer-yii2** bilan **parallel** ishlaydigan hybrid tizim.

### Arxitektura

```
┌────────────────────────────────────┐
│ UNIVER-YII2 (Legacy - Ishlayapti) │
│ - 273 ta jadval                    │
│ - 380 admin, 24 role, 487 resource │
│ - OAuth2 autentifikatsiya          │
│ - Path-based permissions           │
└────────────────────────────────────┘
             ↕ Bir xil database
┌────────────────────────────────────┐
│ UNIVER-BACK (Laravel - Yangi)      │
│ - 3 ta yangi jadval                │
│ - 5 ta yangi ustun (hybrid)        │
│ - JWT autentifikatsiya             │
│ - Name-based permissions           │
└────────────────────────────────────┘
```

### O'zgarishlar

**Yangi jadvallar** (Laravel):
- `e_password_reset_tokens` - Parol tiklash
- `e_auth_refresh_tokens` - JWT refresh tokenlar
- `e_system_login` - Login audit va rate limiting

**Yangi ustunlar** (Hybrid):
- `e_admin_role` → `guard_name`, `spatie_enabled`
- `e_admin_resource` → `permission_name`, `guard_name`, `spatie_enabled`

**Ma'lumotlar**: ✅ Barcha Yii2 ma'lumotlar saqlanib qoladi!

---

## 🚀 O'rnatish

### 1. Talablar

```bash
# PHP 8.2+
php -v

# Composer
composer --version

# PostgreSQL 14+
psql --version
```

### 2. Dependencies o'rnatish

```bash
cd /home/adm1n/univer/univer-back
composer install
```

### 3. .env faylni sozlash

```bash
cp .env.example .env
php artisan key:generate
```

`.env` faylni tahrirlang:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hemis_401
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

---

## 💾 Database Setup

### VARIANT 1: Yangi (Nol) Baza 🆕

**Qachon**: Siz yangi HEMIS tizimni 0 dan boshlayapsiz.

```bash
# 1. Baza yaratish
sudo -u postgres psql << 'EOF'
CREATE DATABASE hemis_new;
\c hemis_new
CREATE EXTENSION IF NOT EXISTS pg_trgm;
\q
EOF

# 2. .env ni sozlash
# DB_DATABASE=hemis_new

# 3. Migration ishlatish
php artisan migrate

# Natija: ✅ 18 ta jadval yaratiladi (Yii2 + Laravel)
```

---

### VARIANT 2: Mavjud univer-yii2 Baza ⚡

**Qachon**: Siz mavjud ishlab turgan univer-yii2 bazasiga Laravel qo'shyapsiz.

⚠️ **MUHIM**: Bu eng xavfsiz variant. Ma'lumotlaringiz saqlanib qoladi!

#### 1-qadam: Backup olish (MAJBURIY!)

```bash
# Database backup
pg_dump -U postgres -d hemis_401 > backup_$(date +%Y%m%d_%H%M%S).sql
```

#### 2-qadam: Ma'lumotlarni tekshirish (BEFORE)

```bash
psql -U postgres -d hemis_401 -c "
  SELECT 'Admins: ' || COUNT(*) FROM e_admin
  UNION ALL SELECT 'Roles: ' || COUNT(*) FROM e_admin_role
  UNION ALL SELECT 'Resources: ' || COUNT(*) FROM e_admin_resource;
"

# Natija (saqlab qoying!):
# Admins: 380
# Roles: 24
# Resources: 487
```

#### 3-qadam: Migration ishlatish

```bash
# Laravel qo'shimchalarini qo'shadi
php artisan migrate --force

# Permission mapping (Yii2 → Laravel)
php artisan db:seed --class=MapYii2ToLaravelPermissions --force
```

**Jarayon**:
```
✅ Tekshiradi: Jadvallar bormi?
   - BOR → faqat ustunlar qo'shadi
   - YO'Q → butun jadvalni yaratadi

✅ Qo'shadi: 3 ta yangi Laravel jadval
✅ Qo'shadi: 5 ta yangi ustun (hybrid)
❌ TEGMAYDI: Hech qanday ma'lumotga!
```

#### 4-qadam: Verification (AFTER)

```bash
# Ma'lumotlar saqlanib qolganini tekshirish
psql -U postgres -d hemis_401 -c "
  SELECT 'Admins: ' || COUNT(*) FROM e_admin
  UNION ALL SELECT 'Roles: ' || COUNT(*) FROM e_admin_role
  UNION ALL SELECT 'Resources: ' || COUNT(*) FROM e_admin_resource;
"

# Kutilgan natija:
# Admins: 380      ✅ (o'zgarmagan)
# Roles: 24        ✅ (o'zgarmagan)
# Resources: 487   ✅ (o'zgarmagan)

# Yangi ustunlar qo'shilganini tekshirish
psql -U postgres -d hemis_401 -c "\d e_admin_role" | grep guard_name
# ✅ guard_name | character varying(255) | ...
```

---

## ⏪ Rollback (Xavfsiz)

### Mavjud Bazadan Rollback

⚠️ **MUHIM**: Ma'lumotlar SAQLANADI!

#### 1-qadam: Ma'lumotlarni tekshirish (BEFORE rollback)

```bash
psql -U postgres -d hemis_401 -c "
  SELECT 'Admins: ' || COUNT(*) FROM e_admin
  UNION ALL SELECT 'Roles: ' || COUNT(*) FROM e_admin_role
  UNION ALL SELECT 'Resources: ' || COUNT(*) FROM e_admin_resource;
"

# Natija: Admins: 380, Roles: 24, Resources: 487
```

#### 2-qadam: Rollback ishlatish

```bash
php artisan migrate:rollback --step=1 --force
```

**Jarayon**:
```
✅ O'chiradi: e_password_reset_tokens
✅ O'chiradi: e_auth_refresh_tokens
✅ O'chiradi: e_system_login
✅ Olib tashlaydi: guard_name, spatie_enabled ustunlarini
✅ Olib tashlaydi: permission_name ustunini

❌ TEGMAYDI: e_admin jadvaliga
❌ TEGMAYDI: e_admin ma'lumotlariga (380 admin saqlanadi)
❌ TEGMAYDI: oauth_* jadvallariga
```

#### 3-qadam: Verification (AFTER rollback)

```bash
# Ma'lumotlar saqlanib qolganini tekshirish
psql -U postgres -d hemis_401 -c "
  SELECT 'Admins: ' || COUNT(*) FROM e_admin
  UNION ALL SELECT 'Roles: ' || COUNT(*) FROM e_admin_role
  UNION ALL SELECT 'Resources: ' || COUNT(*) FROM e_admin_resource;
"

# Kutilgan natija:
# Admins: 380      ✅ (SAQLANIB QOLGAN)
# Roles: 24        ✅ (SAQLANIB QOLGAN)
# Resources: 487   ✅ (SAQLANIB QOLGAN)

# Laravel ustunlari o'chirilganini tekshirish
psql -U postgres -d hemis_401 -c "\d e_admin_role" | grep guard_name
# (empty) ✅ (ustun o'chirilgan)
```

---

## 📚 Hujjatlar

### Asosiy Hujjatlar

- **[docs/MIGRATION-GUIDE.md](docs/MIGRATION-GUIDE.md)** - To'liq migration guide
- **[docs/26-MIGRATION-FINAL-TEST-REPORT.md](docs/26-MIGRATION-FINAL-TEST-REPORT.md)** - Test natijalari
- **[docs/CHARTDB-IMPORT-GUIDE.md](docs/CHARTDB-IMPORT-GUIDE.md)** - ChartDB import

### ChartDB Schemas

- **[docs/22-CHARTDB-BEFORE-MIGRATION-COLORED.dsl](docs/22-CHARTDB-BEFORE-MIGRATION-COLORED.dsl)** - BEFORE
- **[docs/23-CHARTDB-AFTER-MIGRATION-COLORED.dsl](docs/23-CHARTDB-AFTER-MIGRATION-COLORED.dsl)** - AFTER

---

## ❓ FAQ

### Q: Migration ishlatganimda ma'lumotlarim o'chadimi?

**A**: YO'Q! ❌ Migration faqat jadval strukturasini o'zgartiradi.

```
BEFORE: 380 admins, 24 roles, 487 resources
AFTER:  380 admins, 24 roles, 487 resources ✅
```

### Q: Rollback qilsam ma'lumotlar o'chadimi?

**A**: YO'Q! ❌ Rollback faqat Laravel qo'shganlarni o'chiradi.

```
Rollback o'chiradi:
✅ e_password_reset_tokens (yangi jadval)
✅ e_auth_refresh_tokens (yangi jadval)  
✅ e_system_login (yangi jadval)
✅ guard_name ustunlari

Rollback TEGMAYDI:
❌ e_admin ma'lumotlariga (380 admin saqlanadi)
❌ oauth_* jadvallariga
```

### Q: univer-yii2 ishlayaptimi migration paytida?

**A**: HA! ✅ univer-yii2 hech qanday muammo bo'lmaydi.

```
Migration faqat QO'SHADI, O'CHIRMAYDI!
```

---

## 📞 Support

**Hujjatlar**: `/home/adm1n/univer/univer-back/docs`  
**Test Report**: `docs/26-MIGRATION-FINAL-TEST-REPORT.md`  
**Migration Guide**: `docs/MIGRATION-GUIDE.md`

---

**Last Updated**: January 9, 2025  
**Status**: ✅ Production Ready
