# Security Guide - HEMIS University Management System

## Overview

This document outlines the security measures, best practices, and configurations implemented in the HEMIS University Management System to protect against common vulnerabilities and ensure data security.

## Table of Contents

1. [Authentication Security](#authentication-security)
2. [Authorization & Access Control](#authorization--access-control)
3. [API Security](#api-security)
4. [Data Protection](#data-protection)
5. [Infrastructure Security](#infrastructure-security)
6. [Security Checklist](#security-checklist)

## Authentication Security

### JWT Token Management

**Implementation:**
```php
// Token Configuration
JWT_TTL=60                    // Token lifetime: 60 minutes
JWT_REFRESH_TTL=20160         // Refresh token: 14 days
JWT_ALGO=HS256               // Algorithm: HMAC SHA-256
```

**Security Features:**
- ✅ Token expiration (60 minutes)
- ✅ Refresh token mechanism
- ✅ Token blacklisting on logout
- ✅ Secure secret key (min 256 bits)

**Best Practices:**
```php
// Generate strong JWT secret
php artisan jwt:secret --force

// Logout properly to blacklist token
public function logout(Request $request)
{
    auth()->logout();
    return response()->json(['message' => 'Successfully logged out']);
}
```

### Password Security

**Requirements:**
- Minimum 8 characters
- Must include: uppercase, lowercase, numbers
- Special characters recommended

**Implementation:**
```php
// Password hashing (bcrypt by default)
use Illuminate\Support\Facades\Hash;

Hash::make($password);        // Hash password
Hash::check($password, $hash); // Verify password
```

### Multi-Factor Authentication (Planned)

Future enhancement for admin accounts:
- SMS OTP
- Email verification codes
- Authenticator app support

## Authorization & Access Control

### Role-Based Access Control (RBAC)

**Roles:**
1. **Admin** - Full system access
2. **Teacher** - Teaching and grading
3. **Student** - Academic information access
4. **Employee** - Limited administrative access

**Permission Structure:**
```
{role}.{module}.{action}

Examples:
- teacher.grades.create
- teacher.grades.update
- student.schedule.view
- admin.students.manage
```

### Middleware Protection

**API Routes Protection:**
```php
// Authentication required
Route::middleware('auth:api')->group(function () {
    // Protected routes
});

// Permission check
Route::middleware(['auth:api', 'permission:teacher.grades.create'])->group(function () {
    // Permission-specific routes
});
```

### Policy-Based Authorization

**Example:**
```php
// GradePolicy
public function update(User $user, Grade $grade): bool
{
    return $user->id === $grade->teacher_id;
}

// In Controller
$this->authorize('update', $grade);
```

## API Security

### Rate Limiting

**Configuration:**
```php
// Rate limits per user role
'public'  => 30 requests/minute   // Unauthenticated
'student' => 80 requests/minute   // Student endpoints
'teacher' => 100 requests/minute  // Teacher endpoints
'admin'   => 120 requests/minute  // Admin endpoints
'auth'    => 10 requests/minute   // Login attempts (strict)
```

**Implementation:**
```php
// Apply rate limiting to routes
Route::middleware(['auth:api', 'throttle.api:teacher'])->group(function () {
    // Teacher routes
});
```

**Response Headers:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
Retry-After: 60
```

### Input Validation

**FormRequest Validation:**
```php
class StoreGradeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:e_students,id',
            'grade' => 'required|numeric|min:0|max:100',
            'grade_type' => 'required|in:current,midterm,final,overall',
        ];
    }
}
```

**SQL Injection Prevention:**
- ✅ Use Eloquent ORM (parameterized queries)
- ✅ Never use raw SQL without bindings
- ✅ Validate all user inputs

```php
// ✅ SAFE - Parameterized query
User::where('email', $email)->first();

// ❌ UNSAFE - SQL injection risk
DB::select("SELECT * FROM users WHERE email = '$email'");

// ✅ SAFE - With bindings
DB::select("SELECT * FROM users WHERE email = ?", [$email]);
```

### Cross-Site Scripting (XSS) Prevention

**Laravel Default Protection:**
```php
// Blade templates auto-escape output
{{ $userInput }}  // Escaped

// Raw output (use with caution)
{!! $trustedHtml !!}
```

**API Response Sanitization:**
```php
// Always use Resource classes for API responses
class StudentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            // All fields automatically escaped
        ];
    }
}
```

### CORS Configuration

**config/cors.php:**
```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Security Headers

**Implemented Headers:**
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
```

**Implementation (Middleware):**
```php
// SecurityHeadersMiddleware
$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-XSS-Protection', '1; mode=block');
```

## Data Protection

### Database Security

**Connection Security:**
```env
# Use SSL for database connections
DB_SSLMODE=require
DB_SSLCERT=/path/to/cert.pem
DB_SSLKEY=/path/to/key.pem
DB_SSLROOTCERT=/path/to/rootcert.pem
```

**Query Logging (Development Only):**
```php
// Enable in .env
DB_LOG_QUERIES=true    // Only in development!
```

### Sensitive Data Protection

**Encrypted Fields:**
```php
// Model with encrypted attributes
protected $casts = [
    'ssn' => 'encrypted',
    'bank_account' => 'encrypted',
];
```

**Environment Variables:**
```env
# Never commit .env file
# Use strong encryption key
APP_KEY=base64:...  # Generate with: php artisan key:generate
```

### File Upload Security

**Validation:**
```php
'document' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
'image' => 'required|image|mimes:jpg,png|max:2048',        // 2MB max
```

**Storage:**
```php
// Store files outside public directory
Storage::disk('private')->put('documents', $file);

// Serve via controller with authorization
public function download($id)
{
    $this->authorize('download', $document);
    return Storage::disk('private')->download($path);
}
```

## Infrastructure Security

### Production Environment

**.env Configuration:**
```env
APP_ENV=production
APP_DEBUG=false             # CRITICAL: Never true in production!
APP_URL=https://yourdomain.com

# Use HTTPS
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
```

### Docker Security

**Dockerfile Best Practices:**
```dockerfile
# Use specific versions, not 'latest'
FROM php:8.3-fpm-alpine

# Run as non-root user
RUN addgroup -g 1000 appuser && adduser -u 1000 -G appuser -s /bin/sh -D appuser
USER appuser

# Don't include sensitive files
.dockerignore:
.env
.git
storage/logs/
```

### Kubernetes Security

**Security Context:**
```yaml
securityContext:
  runAsNonRoot: true
  runAsUser: 1000
  capabilities:
    drop:
      - ALL
  readOnlyRootFilesystem: true
```

**Secrets Management:**
```yaml
# Store credentials in Kubernetes Secrets
apiVersion: v1
kind: Secret
metadata:
  name: app-secrets
type: Opaque
data:
  app-key: <base64-encoded>
  db-password: <base64-encoded>
  jwt-secret: <base64-encoded>
```

### SSL/TLS Configuration

**Nginx Configuration:**
```nginx
server {
    listen 443 ssl http2;
    ssl_certificate /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;
    
    # Modern SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';
    ssl_prefer_server_ciphers off;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```

## Security Checklist

### Pre-Deployment

- [ ] **Environment Configuration**
  - [ ] APP_DEBUG=false
  - [ ] APP_ENV=production
  - [ ] Strong APP_KEY generated
  - [ ] Strong JWT_SECRET generated
  - [ ] Database credentials secure

- [ ] **Dependencies**
  - [ ] Run `composer audit`
  - [ ] Update vulnerable packages
  - [ ] Remove unused dependencies

- [ ] **Code Review**
  - [ ] No hardcoded credentials
  - [ ] No sensitive data in logs
  - [ ] All inputs validated
  - [ ] All outputs escaped

- [ ] **File Permissions**
  - [ ] storage/ writable by app only
  - [ ] .env not in version control
  - [ ] bootstrap/cache/ writable

### Post-Deployment

- [ ] **Monitoring**
  - [ ] Sentry configured for error tracking
  - [ ] Log monitoring active
  - [ ] Unusual activity alerts set up

- [ ] **Backups**
  - [ ] Database backups automated
  - [ ] File storage backups configured
  - [ ] Backup restoration tested

- [ ] **Updates**
  - [ ] Regular security updates scheduled
  - [ ] Vulnerability scanning automated
  - [ ] Patch management process defined

### Regular Audits

- [ ] **Monthly**
  - [ ] Review access logs
  - [ ] Check for failed login attempts
  - [ ] Review user permissions

- [ ] **Quarterly**
  - [ ] Full security audit
  - [ ] Penetration testing
  - [ ] Update security documentation

## Incident Response

### Security Incident Procedure

1. **Detection**
   - Monitor Sentry alerts
   - Check application logs
   - Review access patterns

2. **Containment**
   - Identify affected systems
   - Isolate compromised components
   - Block suspicious IPs

3. **Investigation**
   - Analyze logs
   - Identify root cause
   - Document findings

4. **Recovery**
   - Apply security patches
   - Restore from clean backups
   - Update credentials

5. **Prevention**
   - Implement additional controls
   - Update security policies
   - Train development team

### Emergency Contacts

```
Security Team: security@example.com
DevOps Team: devops@example.com
On-Call: +XXX-XXX-XXXX
```

## Security Tools

### Automated Security Scanning

**Composer Audit:**
```bash
# Check for known vulnerabilities
composer audit
```

**PHPStan Security Rules:**
```bash
# Static analysis for security issues
vendor/bin/phpstan analyse --level=6
```

**GitHub Security Alerts:**
- Enabled for dependency vulnerabilities
- Automatic PRs for security updates

### Manual Testing

**OWASP ZAP:**
```bash
# API security testing
zap-cli quick-scan http://api.example.com
```

**Burp Suite:**
- Manual penetration testing
- API endpoint testing
- Authentication flow testing

## Compliance

### Data Privacy (GDPR Considerations)

- [ ] User consent mechanisms
- [ ] Data export functionality
- [ ] Data deletion (right to be forgotten)
- [ ] Privacy policy displayed
- [ ] Data processing agreements

### Audit Logging

**Logged Events:**
- User authentication (login/logout)
- Permission changes
- Grade modifications
- Attendance updates
- Administrative actions

**Log Format:**
```json
{
  "timestamp": "2025-11-06T15:00:00Z",
  "user_id": 123,
  "action": "grade.update",
  "resource": "grade:456",
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0..."
}
```

## Resources

### Security References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [JWT Best Practices](https://tools.ietf.org/html/rfc8725)

### Update Schedule

- **Security patches**: Within 24 hours of release
- **Minor updates**: Monthly
- **Major updates**: Quarterly (with testing)

---

**Document Version:** 1.0  
**Last Updated:** 2025-11-06  
**Next Review:** 2025-12-06  
**Maintained by:** Security Team
