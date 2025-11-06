# HEMIS University Management System - Architecture

## System Overview

HEMIS (Higher Education Management Information System) is a comprehensive university management platform built on Laravel 11.x with a **Modular Monolith** architecture following Clean Architecture principles.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Web App    │  │  Mobile App  │  │  Third-party APIs    │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                     API Gateway / Router                         │
│                   (Laravel Routes - api_v1.php)                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    Authentication Layer                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  JWT Authentication + Role-Based Access Control (RBAC)   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Controller Layer                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Teacher    │  │   Student    │  │       Admin          │  │
│  │ Controllers  │  │ Controllers  │  │    Controllers       │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│         │                  │                     │               │
│         │   HTTP Request/Response handling only  │               │
│         └──────────────────┼─────────────────────┘               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                       Service Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Teacher    │  │   Student    │  │   Admin + Shared     │  │
│  │   Services   │  │   Services   │  │      Services        │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│                                                                   │
│  Business Logic Layer - Contains all domain logic                │
│  - DashboardService, ScheduleService, GradeService, etc.         │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    Repository Layer (Optional)                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │        Data Access Layer - Complex Queries               │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                        Model Layer                               │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │   Eloquent Models (60+ models with E prefix)             │  │
│  │   EStudent, ETeacher, ECurriculum, EGrade, etc.          │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                       Database Layer                             │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │         PostgreSQL / MySQL (configurable)                │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘

         ┌────────────────────────────────────────┐
         │      Cross-Cutting Concerns            │
         ├────────────────────────────────────────┤
         │  • Caching (Redis)                     │
         │  • Logging (Sentry)                    │
         │  • Notifications (Email, SMS, Push)    │
         │  • File Storage                        │
         │  • HEMIS Integration                   │
         │  • Translation Service                 │
         └────────────────────────────────────────┘
```

## Architectural Patterns

### 1. Modular Monolith Architecture

The application is organized into logical modules:

- **Teacher Module**: Teacher-specific functionality
- **Student Module**: Student-specific functionality  
- **Admin Module**: Administrative functionality
- **Shared Services**: Cross-cutting concerns

Each module contains:
```
Module/
├── Controllers/     # HTTP layer
├── Services/        # Business logic
├── Requests/        # Request validation
├── Resources/       # Response transformation
└── Models/          # Data models (shared)
```

### 2. Clean Architecture Principles

**Dependency Rule**: Source code dependencies point inward.

```
Controller → Service → Model → Database
   (HTTP)    (Logic)   (Data)   (Storage)
```

- **Controllers**: Handle HTTP requests/responses only
- **Services**: Contain all business logic
- **Models**: Represent data and relationships
- **Repositories**: Optional layer for complex queries

### 3. Service Layer Pattern

All business logic resides in Service classes:

```php
// Example: Teacher Dashboard
Controller receives request
    ↓
Validates request (FormRequest)
    ↓
Calls DashboardService::getDashboardData()
    ↓
Service performs business logic
    ↓
Returns response (Resource/Array)
```

**Benefits:**
- Testable business logic
- Reusable across controllers
- Clear separation of concerns
- Easy to maintain and extend

## Module Architecture

### Teacher Module

```
app/Http/Controllers/Api/V1/Teacher/
├── DashboardController.php       → DashboardService
├── ScheduleController.php        → ScheduleService
├── AttendanceController.php      → AttendanceService
├── GradeController.php           → GradeService
├── AssignmentController.php      → AssignmentService
├── ExamController.php            → ExamService
└── ...

app/Services/Teacher/
├── DashboardService.php          # Dashboard logic
├── ScheduleService.php           # Schedule management
├── AttendanceService.php         # Attendance tracking
├── GradeService.php              # Grading system
└── ...
```

**Refactoring Results:**
- Controller size reduced by 41% on average
- Business logic moved to services (~1000 lines)
- Improved testability and maintainability

### Student Module

```
app/Http/Controllers/Api/V1/Student/
├── DashboardController.php
├── ScheduleController.php
├── GradeController.php
├── AttendanceController.php
└── ...

app/Services/Student/
└── [To be created during Week 2 refactoring]
```

### Admin Module

```
app/Http/Controllers/Api/V1/Admin/
├── StudentController.php
├── EmployeeController.php
├── DepartmentController.php
└── ...

app/Services/Admin/
└── [To be created during Week 3 refactoring]
```

## Data Flow

### 1. Request Flow

```
Client Request
    ↓
Route (api_v1.php)
    ↓
Middleware (auth:api, role, permission)
    ↓
Controller (HTTP handling)
    ↓
FormRequest (Validation)
    ↓
Service (Business Logic)
    ↓
Model/Repository (Data Access)
    ↓
Database
    ↓
Model (Data)
    ↓
Service (Processing)
    ↓
Resource/DTO (Transformation)
    ↓
Controller (Response)
    ↓
JSON Response to Client
```

### 2. Example: Teacher Grades Flow

```php
1. POST /api/v1/teacher/grades
2. auth:api middleware → Authenticate JWT token
3. GradeController@store → Receive request
4. StoreGradeRequest → Validate input
5. GradeService::createGrade() → Business logic
6. EGrade::create() → Save to database
7. GradeResource → Transform response
8. Return JSON response
```

## Authentication & Authorization

### JWT Authentication

```
Login Request
    ↓
AuthController validates credentials
    ↓
Generate JWT token (tymon/jwt-auth)
    ↓
Return token to client
    ↓
Client includes token in Authorization header
    ↓
auth:api middleware validates token
    ↓
Request proceeds if valid
```

### Role-Based Access Control (RBAC)

```
User has Role(s)
    ↓
Role has Permission(s)
    ↓
Permission checked on each endpoint
    ↓
Access granted/denied
```

**Roles:**
- Admin
- Teacher
- Student
- Employee

**Permission Examples:**
```
teacher.dashboard.view
teacher.grades.create
teacher.grades.update
student.schedule.view
admin.students.manage
```

## Database Architecture

### Model Naming Convention

All models use the `E` prefix (e.g., `EStudent`, `ETeacher`):

```php
EStudent         # Student model
ETeacher         # Teacher model
ECurriculum      # Curriculum model
EGrade           # Grade model
EAttendance      # Attendance model
```

### Key Relationships

```
EStudent
├── belongsTo → EGroup
├── belongsTo → ESpecialty
├── hasMany → EGrade
├── hasMany → EAttendance
└── hasMany → EAssignmentSubmission

ETeacher
├── hasMany → ESchedule
├── hasMany → ESubject
└── hasMany → EResource

ECurriculum
├── hasMany → ECurriculumSubject
└── belongsTo → ESpecialty

ESchedule
├── belongsTo → ETeacher
├── belongsTo → ESubject
└── belongsTo → EGroup
```

## Performance Optimization

### 1. Caching Strategy

```php
// CacheService.php
- Query result caching
- API response caching
- Cache invalidation on updates
- Redis for distributed caching
```

### 2. Query Optimization

```php
// Eager Loading to prevent N+1
EStudent::with(['group', 'specialty', 'grades'])->get();

// Selective field loading
EStudent::select(['id', 'name', 'email'])->get();

// Database indexing
- Foreign keys indexed
- Commonly queried fields indexed
```

### 3. API Response Caching

```php
// Cache dashboard data for 5 minutes
Cache::remember('teacher.dashboard.' . $teacherId, 300, function() {
    return $this->dashboardService->getDashboardData($teacherId);
});
```

## Security

### 1. Authentication
- JWT tokens with expiration
- Refresh token mechanism
- Token blacklisting on logout

### 2. Authorization
- Role-based permissions (Spatie)
- Granular endpoint permissions
- Policy-based authorization

### 3. API Security
- Rate limiting (60 req/min)
- CORS configuration
- Input validation (FormRequests)
- SQL injection prevention (Eloquent ORM)
- XSS protection (Laravel defaults)

### 4. Production Security
```php
// .env production settings
APP_DEBUG=false
APP_ENV=production

// Security headers
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
```

## Testing Strategy

### 1. Unit Tests
```
tests/Unit/Services/
├── Teacher/
│   ├── DashboardServiceTest.php
│   ├── GradeServiceTest.php
│   └── ...
└── Student/
    └── ...
```

Test individual service methods in isolation.

### 2. Feature Tests
```
tests/Feature/
├── Teacher/
│   ├── GradingWorkflowTest.php
│   └── AttendanceWorkflowTest.php
└── Student/
    └── EnrollmentTest.php
```

Test complete workflows end-to-end.

### 3. API Integration Tests
```
tests/api/
├── auth/
│   └── auth_test.sh
└── teacher/
    ├── dashboard_test.sh
    └── grade_test.sh
```

Test actual API endpoints with bash/curl.

## Deployment Architecture

### Docker Containerization

```
Docker Container
├── Nginx (Web Server)
├── PHP-FPM (8.3)
├── PostgreSQL (Database)
└── Redis (Cache)
```

### Kubernetes Orchestration

```
k8s/
├── deployment.yaml      # Application deployment
├── service.yaml         # Service definition
├── ingress.yaml         # Ingress rules
└── configmap.yaml       # Configuration
```

### CI/CD Pipeline

```
GitHub Actions Workflow
├── Test Job           # Run PHPUnit tests
├── Lint Job           # Code quality checks
├── Security Job       # Security audit
└── Build Job          # Docker image build
    └── Deploy Job     # Deploy to environment
```

## Monitoring & Logging

### Error Tracking
- **Sentry Integration**: Real-time error tracking
- **Log Channels**: Daily, Stack, Sentry
- **Error Notifications**: Critical errors alert team

### Performance Monitoring
- **Laravel Telescope**: Local development debugging
- **Database Query Monitoring**: Slow query detection
- **API Response Time Tracking**: Performance metrics

## External Integrations

### HEMIS Integration
```php
HemisSyncService
├── Sync students from HEMIS
├── Sync curriculum data
├── Sync academic calendar
└── Sync exam schedules
```

### Notification System
```php
NotificationService
├── Email notifications
├── SMS notifications
└── Push notifications
```

## Scalability Considerations

### Horizontal Scaling
- Stateless API design
- Session storage in Redis
- File storage in S3/Object Storage
- Database read replicas

### Vertical Scaling
- Query optimization
- Caching strategy
- Database indexing
- Load balancing

## Technology Stack Summary

| Layer | Technology |
|-------|-----------|
| **Framework** | Laravel 11.x |
| **Language** | PHP 8.3+ |
| **Database** | PostgreSQL/MySQL |
| **Cache** | Redis |
| **Queue** | Redis |
| **Authentication** | JWT (tymon/jwt-auth) |
| **Authorization** | Spatie Permission |
| **API Docs** | Swagger + Scramble |
| **Testing** | Pest PHP + PHPUnit |
| **Error Tracking** | Sentry |
| **Container** | Docker |
| **Orchestration** | Kubernetes |
| **CI/CD** | GitHub Actions |

## Future Enhancements

### Short-term (1-2 months)
- Complete Student module refactoring
- Complete Admin module refactoring
- Increase test coverage to 80%+
- Implement comprehensive caching

### Medium-term (3-6 months)
- Microservices migration (optional)
- GraphQL API support
- Real-time features (WebSockets)
- Mobile app development

### Long-term (6-12 months)
- Multi-tenancy support
- Advanced analytics
- Machine learning integration
- International expansion

---

**Document Version:** 1.0  
**Last Updated:** 2025-11-06  
**Maintained by:** Development Team
