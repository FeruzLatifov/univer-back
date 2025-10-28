# HEMIS Univer Backend - Laravel 11

Modern University Management System Backend API

## 🚀 Quick Start

### Local (Windows)

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
# Edit .env: DB_PASSWORD=yourpassword

# Generate keys
php artisan key:generate
php artisan jwt:secret

# Test database connection
php artisan tinker
>>> DB::table('e_student')->count()

# Run development server
php artisan serve
# Server: http://localhost:8000
```

### Docker (Recommended)

```bash
# Copy environment
cp .env.docker .env

# Build and run
docker-compose up -d

# Setup
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan jwt:secret

# Test
curl http://localhost:8000/api/health
```

**Using Makefile:**

```bash
make build      # Build images
make up         # Start containers
make logs       # View logs
make shell      # Enter container
make help       # Show all commands
```

## 📚 Documentation

Barcha hujjatlar: **[docs/sonnet/](../docs/sonnet/00-index.md)**

**Quick links:**
- [Quick Start](../docs/sonnet/03-quick-start.md) - 5 daqiqada boshlash
- [Windows Setup](../docs/sonnet/07-windows-php-setup.md) - PHP o'rnatish
- [Docker & K8s](../docs/sonnet/09-docker-kubernetes-guide.md) - Container deployment
- [Password Compatibility](../docs/sonnet/08-password-compatibility.md) - Yii2 paritet
- [Migration Strategy](../docs/sonnet/05-migration-database-strategy.md) - Database xavfsizligi

## ✅ Features

- ✅ JWT Authentication (Admin + Student)
- ✅ RESTful API with filters/sorts/includes
- ✅ PostgreSQL (272 tables, zero migration)
- ✅ RBAC ready
- ✅ Production safeguards (SQL blocker)
- ✅ CORS configured
- ✅ Docker + Kubernetes ready
- ✅ Horizontal auto-scaling (HPA)
- ✅ Health checks & monitoring

## 🏗️ Architecture

```
univer-backend/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php       # Login, JWT
│   │   └── StudentController.php    # CRUD students
│   ├── Models/                       # 13 models
│   └── Services/                     # Business logic
│
├── docker/                           # Docker configs
│   ├── nginx/
│   ├── supervisor/
│   └── postgres/
│
├── k8s/                              # Kubernetes manifests
│   ├── base/
│   └── overlays/prod/
│
├── Dockerfile                        # Multi-stage build
├── docker-compose.yml                # Local development
└── Makefile                          # Helper commands
```

## 📡 API Endpoints

### Auth
- `POST /api/auth/login` - Login (admin/student)
- `POST /api/auth/refresh` - Refresh token
- `GET /api/auth/me` - Current user
- `POST /api/auth/logout` - Logout

### Students
- `GET /api/students` - List (with filters)
- `GET /api/students/{id}` - Show
- `POST /api/students` - Create
- `PUT /api/students/{id}` - Update
- `DELETE /api/students/{id}` - Delete

### Health
- `GET /api/health` - API status

**Examples:**
```bash
# Health check
curl http://localhost:8000/api/health

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"password","type":"admin"}'

# Students with filters
curl http://localhost:8000/api/students?filter[first_name]=Ahmad&include=meta \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🐳 Docker Deployment

### Development

```bash
# Build
docker-compose build

# Start
docker-compose up -d

# Logs
docker-compose logs -f backend

# Stop
docker-compose down
```

### Production

```bash
# Build production image
docker build -t univer-backend:v1.0.0 --target production .

# Run with Docker Compose
docker-compose -f docker-compose.prod.yml up -d

# Or deploy to Kubernetes
kubectl apply -k k8s/overlays/prod
```

## ☸️ Kubernetes Deployment

### Prerequisites
- Kubernetes cluster
- kubectl configured
- Docker registry

### Deploy

```bash
# Create namespace
kubectl create namespace univer-prod

# Create secrets
kubectl create secret generic db-credentials \
  --from-literal=database=univer \
  --from-literal=username=postgres \
  --from-literal=password=YourPassword \
  -n univer-prod

kubectl create secret generic jwt-secret \
  --from-literal=secret=$(openssl rand -base64 32) \
  -n univer-prod

# Deploy with Kustomize
kubectl apply -k k8s/overlays/prod

# Check status
kubectl get pods -n univer-prod
kubectl get svc -n univer-prod
kubectl get ingress -n univer-prod
```

### Scaling

```bash
# Manual
kubectl scale deployment univer-backend --replicas=5 -n univer-prod

# Auto (HPA already configured)
kubectl get hpa -n univer-prod
```

### Update

```bash
# Build new version
docker build -t univer-backend:v1.0.1 .
docker push your-registry.com/univer-backend:v1.0.1

# Rolling update
kubectl set image deployment/univer-backend \
  backend=your-registry.com/univer-backend:v1.0.1 \
  -n univer-prod

# Rollback if needed
kubectl rollout undo deployment/univer-backend -n univer-prod
```

## 🗃️ Database

**Zero Migration Strategy:**
- Database: `univer.sql` (272 tables)
- Models: ORM layer only
- No schema changes via migrations
- Idempotent migrations for future updates

**Import:**
```bash
# Docker
docker-compose exec -T postgres psql -U postgres -d univer < univer.sql

# Local
psql -U postgres -d univer < univer.sql
```

## 🔐 Security

### Production Safeguards
- ✅ SQL blocker (DROP/TRUNCATE/ALTER disabled)
- ✅ Rollback disabled in production
- ✅ Environment-based checks
- ✅ Rate limiting ready
- ✅ CORS configured

### Authentication
- ✅ JWT tokens (1 hour TTL)
- ✅ Refresh tokens (2 weeks)
- ✅ Multi-guard (admin + student)
- ✅ Yii2 password compatibility (bcrypt)

## 📦 Tech Stack

- **PHP 8.3** + Laravel 11
- **PostgreSQL** 16
- **Redis** 7 (caching/queue)
- **Nginx** (web server)
- **Supervisor** (process manager)
- **Docker** + **Kubernetes**
- **JWT Auth** (tymon/jwt-auth)
- **Spatie Query Builder**
- **Scramble** (API docs)

## 🛠️ Development

```bash
# Install dependencies
composer install

# Run tests
php artisan test

# Generate API docs
php artisan scramble:generate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## 🧪 Testing

```bash
# Docker
docker-compose exec backend php artisan test

# Local
php artisan test

# Specific test
php artisan test --filter StudentTest
```

## 📝 License

MIT

## 🤝 Contributing

1. Fork repo
2. Create feature branch (`git checkout -b feature/amazing`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing`)
5. Create Pull Request

---

**Status:** ✅ Phase 1 Complete (Auth + CRUD + Docker + K8s)

**Next:** Phase 2 - Validation, Rate Limiting, Frontend Integration

**Documentation:** [docs/sonnet/00-index.md](../docs/sonnet/00-index.md)
