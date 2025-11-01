# univer-backend - O'rnatish Qo'llanmasi

## Talablar
- PHP 8.3+
- PostgreSQL 13+
- Composer 2.x
- Docker + Docker Compose (tavsiya etiladi)

## Docker bilan o'rnatish (tez start)

### 1. Loyihani clone qilish
```bash
cd C:\Projects\univer\univer-backend
```

### 2. Env faylini yaratish
```bash
copy env.example .env
```

### 3. .env ni to'ldiring
```env
DB_HOST=<PostgreSQL host>
DB_PORT=5432
DB_DATABASE=univer
DB_USERNAME=postgres
DB_PASSWORD=<parol>
```

### 4. Laravel skeleton yaratish (birinchi marta)
Docker ichida Composer orqali Laravel'ni o'rnatamiz:

```bash
docker run --rm -v ${PWD}:/app composer create-project laravel/laravel temp
# Keyin temp/ ichidagi fayllarni ko'chiramiz
move temp\* .
rmdir temp
```

Yoki local Composer bilan:
```bash
composer create-project laravel/laravel .
```

### 5. Compose bilan ishga tushirish
```bash
docker-compose up -d
```

### 6. APP_KEY generatsiya
```bash
docker-compose exec app php artisan key:generate
```

### 7. Database ulanishini tekshirish
```bash
docker-compose exec app php artisan tinker
# >>> DB::connection()->getPdo();
```

### 8. JWT secret yaratish
```bash
docker-compose exec app php artisan jwt:secret
```

### 9. Brauzerda ochish
http://localhost:8000

---

## Local o'rnatish (Composer bilan)

### 1. Dependencies o'rnatish
```bash
composer install
```

### 2. Env sozlash
```bash
copy env.example .env
php artisan key:generate
```

### 3. Database ulanish
.env da PostgreSQL sozlamalarini to'ldiring

### 4. Server ishga tushirish
```bash
php artisan serve
```

---

## Keyingi qadamlar
- Auth API endpoints yaratish
- RBAC middleware qo'shish
- Employees API endpoints
- Frontend bilan ulash

**Eslatma:** Hech qanday migration ishlatilmaydi! Mavjud `univer.sql` sxemasi o'zgarishsiz qoladi.

