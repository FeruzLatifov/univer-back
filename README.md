# 🔧 Univer Backend - Laravel API

Backend API for Univer University Management System built with Laravel 11.

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# Configure database in .env
# Then import database
psql -U postgres -d univer < ../univer.sql

# Start server
php artisan serve
```

## 📚 API Documentation

**Interactive Swagger UI:** http://localhost:8000/api/documentation

**Generate Documentation:**
```bash
php artisan l5-swagger:generate
```

## 🗂 Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── V1/
│   │       │   ├── Student/     # Student Portal APIs
│   │       │   ├── Teacher/     # Teacher Portal APIs
│   │       │   ├── Staff/       # Staff Portal APIs
│   │       │   └── Admin/       # Admin Panel APIs
│   │       └── Admin/
│   │           └── TranslationController.php  # Translation Management
│   ├── Middleware/
│   │   ├── SetLocale.php        # Language detection
│   │   └── ...
│   └── Requests/                # Form validation
├── Models/
│   ├── Student/
│   ├── Teacher/
│   ├── Staff/
│   ├── System/
│   │   ├── SystemMessage.php              # Translations
│   │   └── SystemMessageTranslation.php
│   └── ...
├── Services/
│   └── DatabaseTranslationLoader.php  # i18n service
└── Providers/
    └── TranslationServiceProvider.php

routes/
├── api_v1.php                   # API V1 routes
└── web.php

database/
├── migrations/                  # Database migrations
└── seeders/
```

## 🌐 Multi-Language

Language is detected from URL parameter: `?l=ru-RU`

**Supported languages:**
- uz-UZ (O'zbekcha)
- ru-RU (Русский)
- en-US (English)

**Usage in code:**
```php
__('Welcome')      // Auto-detect language
__uz('Profile')    // Force Uzbek: "Profil"
__ru('Profile')    // Force Russian: "Профиль"
__en('Profile')    // Force English: "Profile"
```

## 🔐 Authentication

JWT-based authentication with refresh tokens.

**Guards:**
- `student-api` - Student portal
- `teacher-api` - Teacher portal
- `staff-api` - Staff & Admin portal

**Example:**
```php
Route::middleware('auth:student-api')->group(function () {
    Route::get('/profile', [StudentController::class, 'profile']);
});
```

## 📊 Cache

**Driver:** Redis

**Clear cache:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Translation cache:**
```bash
# Via API
POST /api/admin/translations/clear-cache

# Or in code
clear_translation_cache();
```

## 🧪 Testing

```bash
php artisan test
```

## 🚀 Production Deployment

```bash
# Set environment
APP_ENV=production
APP_DEBUG=false

# Optimize
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate API docs
php artisan l5-swagger:generate
```

## 📝 Environment Variables

```env
APP_NAME="Univer Management System"
APP_ENV=local
APP_KEY=base64:...
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=univer
DB_USERNAME=postgres
DB_PASSWORD=

JWT_SECRET=...
JWT_TTL=60

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🔗 Links

- [Main Project README](../README.md)
- [API Documentation](../docs/API_DOCUMENTATION_GUIDE.md)
- [Swagger Setup](../docs/SWAGGER_SETUP_GUIDE.md)
