# HEMIS University Management System - Backend API

[![CI/CD Pipeline](https://github.com/FeruzLatifov/univer-back/actions/workflows/ci.yml/badge.svg)](https://github.com/FeruzLatifov/univer-back/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> **HEMIS (Higher Education Management Information System)** - A comprehensive university management platform built on Laravel 11.x with a Modular Monolith architecture following Clean Architecture principles.

## 🎯 Overview

HEMIS is a complete university management solution that handles:
- 🎓 **Student Management** - Enrollment, grades, attendance tracking
- 👨‍🏫 **Teacher Management** - Teaching schedules, grading, class management
- 📚 **Academic Management** - Curriculum, subjects, exams, assignments
- 📊 **Analytics & Reporting** - Performance metrics and insights
- 🔐 **Authentication & Authorization** - JWT-based with role-based access control

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Documentation](#documentation)
- [Getting Started](#getting-started)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### Core Features
- **JWT Authentication** - Secure token-based authentication
- **Role-Based Access Control** - Admin, Teacher, Student, Employee roles
- **RESTful API** - Clean, versioned API architecture (v1)
- **Multi-language Support** - Uzbek, Russian, English
- **Real-time Notifications** - Email, SMS, Push notifications
- **Document Management** - Upload, sign, and manage documents
- **HEMIS Integration** - Sync with external HEMIS system

### Teacher Module ✅
- Dashboard with statistics and quick actions
- Schedule management
- Attendance tracking and reporting
- Grading system with multiple grade types
- Assignment creation and management
- Exam management
- Teaching resources

### Student Module 🔄
- Personal dashboard
- Schedule viewing
- Grades and transcripts
- Attendance records
- Assignment submission
- Document requests
- Profile management

### Admin Module 📋
- Student management
- Employee management
- Department management
- Group and specialty management
- System configuration
- HEMIS synchronization

## 🛠️ Tech Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| **Backend Framework** | Laravel | 11.x |
| **Language** | PHP | 8.3+ |
| **Database** | PostgreSQL/MySQL | 14+/8+ |
| **Cache & Queue** | Redis | 7+ |
| **Authentication** | JWT | tymon/jwt-auth 2.0 |
| **Authorization** | Spatie Permission | 6.22 |
| **API Documentation** | Swagger + Scramble | Latest |
| **Testing** | Pest PHP + PHPUnit | 2.0 |
| **Error Tracking** | Sentry | 4.10 |
| **Code Quality** | PHPStan + PHP CS Fixer | Latest |
| **CI/CD** | GitHub Actions | - |
| **Container** | Docker + Docker Compose | Latest |
| **Orchestration** | Kubernetes | Latest |

## 🏗️ Architecture

### Modular Monolith Architecture

```
┌─────────────────────────────────────────────┐
│            API Gateway / Router              │
│               (Laravel Routes)               │
└─────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────┐
│        Authentication & Authorization        │
│     (JWT + Spatie Permission + RBAC)        │
└─────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────┐
│              Controller Layer                │
│     (HTTP Request/Response Handling)         │
└─────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────┐
│               Service Layer                  │
│         (Business Logic & Rules)             │
└─────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────┐
│              Model Layer                     │
│        (Eloquent ORM & Relations)            │
└─────────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────┐
│            Database Layer                    │
│         (PostgreSQL/MySQL)                   │
└─────────────────────────────────────────────┘
```

**Key Principles:**
- **Clean Architecture** - Separation of concerns
- **Service Layer Pattern** - Business logic in services
- **Repository Pattern** - Complex queries abstraction
- **Dependency Injection** - Loose coupling
- **SOLID Principles** - Maintainable code

## 📚 Documentation

Comprehensive documentation is available in the `/docs` directory:

- **[📖 Documentation Index](docs/README.md)** - Complete documentation navigation guide
- **[Architecture Guide](docs/ARCHITECTURE.md)** - System architecture and design patterns
- **[Security Guide](docs/SECURITY.md)** - Security measures and best practices
- **[Performance Guide](docs/PERFORMANCE.md)** - Optimization strategies and caching
- **[Testing Guide](docs/TESTING.md)** - Testing strategy and best practices
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Production deployment instructions
- **[Implementation Summary](docs/IMPLEMENTATION_SUMMARY.md)** - Complete implementation details
- **[Project Analysis](PROJECT_ANALYSIS_UZ.md)** - Detailed project analysis (Uzbek)

### API Documentation

- **Swagger UI**: `http://localhost:8000/docs/api`
- **Scramble**: `http://localhost:8000/docs/scramble`

## 🚀 Getting Started

### Prerequisites

- PHP 8.3 or higher
- Composer 2.x
- PostgreSQL 14+ or MySQL 8+
- Redis 7+
- Node.js & npm (for asset compilation, if needed)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/FeruzLatifov/univer-back.git
   cd univer-back
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

4. **Configure database**
   ```bash
   # Edit .env file with your database credentials
   nano .env
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000`

### Docker Setup (Recommended)

```bash
# Build and start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# View logs
docker-compose logs -f
```

## 💻 Development

### Project Structure

```
univer-back/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/  # API controllers
│   │   ├── Middleware/          # Custom middleware
│   │   └── Requests/            # Form requests
│   ├── Models/                  # Eloquent models (60+)
│   ├── Services/                # Business logic
│   │   ├── Teacher/
│   │   ├── Student/
│   │   └── Admin/
│   └── Repositories/            # Data access layer
├── config/                      # Configuration files
├── database/
│   ├── migrations/              # Database migrations (44)
│   ├── seeders/                 # Database seeders
│   └── factories/               # Model factories
├── docs/                        # Documentation
├── routes/
│   ├── api.php                  # Legacy API routes
│   └── api_v1.php              # V1 API routes (325+)
├── tests/
│   ├── Unit/                    # Unit tests
│   ├── Feature/                 # Feature tests
│   └── api/                     # API integration tests
└── storage/                     # File storage

```

### Code Quality

```bash
# Run PHPStan
vendor/bin/phpstan analyse

# Run PHP CS Fixer
vendor/bin/php-cs-fixer fix

# Run all quality checks
composer quality
```

### API Development

**Create a new endpoint:**

1. Create controller
2. Create service for business logic
3. Create form request for validation
4. Add route to `routes/api_v1.php`
5. Write tests

**Example:**
```php
// Controller
class ExampleController extends Controller
{
    public function __construct(
        private ExampleService $service
    ) {}

    public function index(Request $request)
    {
        $data = $this->service->getData($request->all());
        return response()->json($data);
    }
}

// Service
class ExampleService
{
    public function getData(array $filters): array
    {
        // Business logic here
        return EExample::filter($filters)->paginate(15);
    }
}
```

## 🧪 Testing

### Run Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run API integration tests
cd tests/api
./run_all_tests.sh
```

### Test Coverage

Current coverage: **70%** | Target: **80%+**

```
Unit Tests:     52 tests ✅
Feature Tests:  15 tests ✅
API Tests:      20+ scripts ✅
```

### Writing Tests

```bash
# Generate test
php artisan make:test Services/ExampleServiceTest --unit

# Run specific test
php artisan test --filter ExampleServiceTest
```

## 📦 Deployment

### Production Deployment

```bash
# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize

# Run migrations
php artisan migrate --force

# Start queue workers
php artisan queue:work redis --daemon
```

### Docker Deployment

```bash
# Build production image
docker build -t hemis/univer-backend:latest .

# Deploy with docker-compose
docker-compose -f docker-compose.prod.yml up -d
```

### Kubernetes Deployment

```bash
# Apply configurations
kubectl apply -f k8s/

# Check status
kubectl get pods -n hemis-prod
```

See [Deployment Guide](docs/DEPLOYMENT.md) for detailed instructions.

## 📊 Project Statistics

- **Total PHP Files**: 300+
- **Models**: 60+ (all prefixed with `E`)
- **Controllers**: 30+
- **Services**: 20+
- **Migrations**: 44
- **API Endpoints**: 325+
- **Lines of Code**: ~50,000+
- **Test Coverage**: 70%

## 🔄 Continuous Integration

GitHub Actions CI/CD pipeline includes:

- ✅ Automated testing (PHPUnit/Pest)
- ✅ Code quality checks (PHPStan, PHP CS Fixer)
- ✅ Security audit (Composer audit)
- ✅ Docker image building
- ✅ Deployment automation

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Write unit tests for new features
- Update documentation
- Pass all CI/CD checks

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Team

- **Lead Developer**: Feruz Latifov
- **Architecture**: Clean Architecture + Service Layer
- **Status**: Active Development

## 📞 Support

For support, email support@example.com or create an issue in the repository.

## 🎯 Roadmap

### Current Focus (Q4 2025)
- [x] Teacher module refactoring (100% complete)
- [x] CI/CD pipeline implementation
- [x] Code quality tools setup
- [x] Comprehensive documentation
- [ ] Student module refactoring (in progress)
- [ ] Admin module refactoring (planned)
- [ ] Performance optimization
- [ ] 80%+ test coverage

### Future Plans (2026)
- [ ] GraphQL API support
- [ ] Real-time features (WebSockets)
- [ ] Mobile app backend
- [ ] Microservices migration (optional)
- [ ] Multi-tenancy support
- [ ] Advanced analytics

## ⭐ Acknowledgments

- Laravel Framework
- Spatie packages
- JWT Auth
- All contributors

---

**Made with ❤️ by the HEMIS Development Team**

**Version**: 1.0.0  
**Last Updated**: 2025-11-06  
**Project Score**: 85/100 (A-) → Target: 95+ (A+)
