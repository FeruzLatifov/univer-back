# Testing Guide - HEMIS University Management System

## Overview

This guide covers the testing strategy, methodologies, and best practices for the HEMIS University Management System.

## Table of Contents

1. [Testing Strategy](#testing-strategy)
2. [Unit Tests](#unit-tests)
3. [Feature Tests](#feature-tests)
4. [API Integration Tests](#api-integration-tests)
5. [Running Tests](#running-tests)
6. [Test Coverage](#test-coverage)
7. [Best Practices](#best-practices)

## Testing Strategy

### Testing Pyramid

```
        /\
       /  \  
      / E2E\      ← Few (API Integration Tests)
     /------\
    /Feature\     ← Some (Feature Tests)
   /----------\
  /   Unit     \  ← Many (Unit Tests)
 /--------------\
```

**Distribution:**
- **70% Unit Tests** - Fast, isolated, test individual methods
- **20% Feature Tests** - Test complete workflows
- **10% Integration Tests** - Test API endpoints end-to-end

### Test Framework

**Pest PHP** - Modern, elegant testing framework built on PHPUnit

```bash
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev
```

**Benefits:**
- Cleaner syntax
- Better error messages
- Laravel-specific helpers
- Parallel test execution

## Unit Tests

### Purpose

Test individual service methods in isolation without database or external dependencies.

### Location

```
tests/Unit/
├── Services/
│   ├── Teacher/
│   │   ├── DashboardServiceTest.php
│   │   ├── GradeServiceTest.php
│   │   └── AttendanceServiceTest.php
│   ├── Student/
│   │   ├── DashboardServiceTest.php
│   │   └── DocumentServiceTest.php
│   └── Admin/
│       └── StudentManagementServiceTest.php
```

### Example Unit Test

```php
<?php

namespace Tests\Unit\Services\Teacher;

use App\Services\Teacher\GradeService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group unit
 * @group teacher
 * @group grades
 */
class GradeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GradeService $gradeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gradeService = new GradeService();
    }

    public function test_calculate_gpa_correctly(): void
    {
        $grades = [
            ['grade' => 90, 'credits' => 3],
            ['grade' => 85, 'credits' => 4],
            ['grade' => 95, 'credits' => 2],
        ];

        $gpa = $this->gradeService->calculateGPA($grades);

        // Expected: (90*3 + 85*4 + 95*2) / (3+4+2) = 88.89
        $this->assertEquals(88.89, $gpa, '', 0.01);
    }

    public function test_grade_validation_rejects_invalid_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->gradeService->validateGrade(150); // Invalid: > 100
    }

    public function test_grade_type_mapping(): void
    {
        $result = $this->gradeService->mapGradeType('midterm');
        
        $this->assertEquals(EGrade::TYPE_MIDTERM, $result);
    }
}
```

### Running Unit Tests

```bash
# Run all unit tests
php artisan test --testsuite=Unit

# Run specific test class
php artisan test --filter GradeServiceTest

# Run tests with coverage
php artisan test --coverage --min=70
```

### Test Statistics

**Current Coverage:**
```
Teacher Services:
├── DashboardService - 8 tests ✅
├── GradeService - 10 tests ✅
├── AttendanceService - 10 tests ✅
└── ScheduleService - 6 tests (planned)

Student Services:
├── DashboardService - 8 tests ✅
├── DocumentService - 10 tests ✅
└── ProfileService - 6 tests (planned)

Total: 52 unit tests
Coverage: ~70%
```

## Feature Tests

### Purpose

Test complete workflows and business processes across multiple components.

### Location

```
tests/Feature/
├── Teacher/
│   ├── GradingWorkflowTest.php
│   └── AttendanceWorkflowTest.php
├── Student/
│   ├── EnrollmentTest.php
│   └── AssignmentSubmissionTest.php
└── Admin/
    └── StudentManagementTest.php
```

### Example Feature Test

```php
<?php

namespace Tests\Feature\Teacher;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group feature
 * @group teacher
 */
class GradingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_and_update_grade_workflow(): void
    {
        // 1. Setup: Create teacher, student, subject
        $teacher = EEmployee::factory()->create();
        $student = EStudent::factory()->create();
        $subject = ESubject::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
        ]);

        // 2. Authenticate as teacher
        $token = auth()->login($teacher);

        // 3. Create grade
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/teacher/grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'grade_type' => 'midterm',
            'grade' => 85,
            'comment' => 'Good work',
        ]);

        $response->assertStatus(201);
        $gradeId = $response->json('data.id');

        // 4. Verify grade in database
        $this->assertDatabaseHas('e_grades', [
            'id' => $gradeId,
            '_student' => $student->id,
            '_subject' => $subject->id,
            'grade' => 85,
        ]);

        // 5. Update grade
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->putJson("/api/v1/teacher/grades/{$gradeId}", [
            'grade' => 90,
            'comment' => 'Excellent improvement',
        ]);

        $response->assertStatus(200);

        // 6. Verify update
        $this->assertDatabaseHas('e_grades', [
            'id' => $gradeId,
            'grade' => 90,
        ]);
    }

    public function test_teacher_cannot_grade_students_from_other_classes(): void
    {
        $teacher = EEmployee::factory()->create();
        $otherTeacher = EEmployee::factory()->create();
        $student = EStudent::factory()->create();
        $subject = ESubject::factory()->create();
        
        // Schedule belongs to other teacher
        ESubjectSchedule::factory()->create([
            '_employee' => $otherTeacher->id,
            '_subject' => $subject->id,
        ]);

        $token = auth()->login($teacher);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/v1/teacher/grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'grade_type' => 'midterm',
            'grade' => 85,
        ]);

        $response->assertStatus(403); // Forbidden
    }
}
```

### Running Feature Tests

```bash
# Run all feature tests
php artisan test --testsuite=Feature

# Run with parallel execution (faster)
php artisan test --parallel --testsuite=Feature
```

## API Integration Tests

### Purpose

Test actual API endpoints with real HTTP requests (bash/curl scripts).

### Location

```
tests/api/
├── README.md
├── run_all_tests.sh
├── auth/
│   └── auth_test.sh
└── teacher/
    ├── dashboard_test.sh
    ├── schedule_test.sh
    ├── attendance_test.sh
    └── grade_test.sh
```

### Example API Test Script

```bash
#!/bin/bash
# tests/api/teacher/grade_test.sh

BASE_URL="http://localhost:8000/api/v1"
TOKEN_FILE="/tmp/auth_token.txt"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "=== Teacher Grade API Tests ==="

# 1. Login (get token)
echo -n "Logging in... "
LOGIN_RESPONSE=$(curl -s -X POST "${BASE_URL}/employee/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"teacher@example.com","password":"password"}')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.token')

if [ "$TOKEN" != "null" ]; then
  echo -e "${GREEN}✓${NC}"
  echo $TOKEN > $TOKEN_FILE
else
  echo -e "${RED}✗${NC} Failed to login"
  exit 1
fi

# 2. Get grades list
echo -n "Getting grades list... "
GRADES_RESPONSE=$(curl -s -X GET "${BASE_URL}/teacher/grades?subject_id=1" \
  -H "Authorization: Bearer ${TOKEN}")

STATUS=$(echo $GRADES_RESPONSE | jq -r '.success')

if [ "$STATUS" = "true" ]; then
  echo -e "${GREEN}✓${NC}"
else
  echo -e "${RED}✗${NC} Failed to get grades"
fi

# 3. Create grade
echo -n "Creating grade... "
CREATE_RESPONSE=$(curl -s -X POST "${BASE_URL}/teacher/grades" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 1,
    "subject_id": 1,
    "grade_type": "midterm",
    "grade": 85,
    "comment": "Good work"
  }')

GRADE_ID=$(echo $CREATE_RESPONSE | jq -r '.data.id')

if [ "$GRADE_ID" != "null" ]; then
  echo -e "${GREEN}✓${NC}"
else
  echo -e "${RED}✗${NC} Failed to create grade"
fi

# 4. Update grade
echo -n "Updating grade... "
UPDATE_RESPONSE=$(curl -s -X PUT "${BASE_URL}/teacher/grades/${GRADE_ID}" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"grade": 90}')

UPDATED_GRADE=$(echo $UPDATE_RESPONSE | jq -r '.data.grade')

if [ "$UPDATED_GRADE" = "90" ]; then
  echo -e "${GREEN}✓${NC}"
else
  echo -e "${RED}✗${NC} Failed to update grade"
fi

echo ""
echo "=== All tests completed ==="
```

### Running API Tests

```bash
# Run all API tests
cd tests/api
./run_all_tests.sh

# Run specific module tests
./teacher/grade_test.sh
./auth/auth_test.sh
```

## Running Tests

### Local Development

```bash
# Run all tests
php artisan test

# Run with coverage report
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Unit/Services/Teacher/GradeServiceTest.php

# Run specific test method
php artisan test --filter test_calculate_gpa_correctly

# Run tests in parallel (faster)
php artisan test --parallel

# Run with detailed output
php artisan test --verbose
```

### CI/CD Pipeline

**.github/workflows/ci.yml** already configured:

```yaml
- name: Run PHPUnit tests
  run: php artisan test --coverage --min=50
```

## Test Coverage

### Current Coverage

```
Overall Coverage: 70%

By Module:
├── Teacher Services: 85% ✅
├── Student Services: 65% 🟡
├── Admin Services: 45% 🔴
├── Shared Services: 55% 🟡
└── Controllers: 40% 🔴

Target: 80%+ coverage
```

### Generate Coverage Report

```bash
# HTML coverage report
php artisan test --coverage-html coverage-report

# Open in browser
open coverage-report/index.html

# Coverage summary in terminal
php artisan test --coverage --min=70
```

### Coverage by Service

```
Teacher Services:
├── DashboardService: 92% ✅
├── GradeService: 88% ✅
├── AttendanceService: 85% ✅
├── ScheduleService: 78% ✅
└── AssignmentService: 65% 🟡

Student Services:
├── DashboardService: 80% ✅
├── DocumentService: 75% ✅
└── ProfileService: 50% 🔴
```

## Best Practices

### Do's ✅

1. **Write tests first** (TDD when possible)
2. **Keep tests isolated** - No dependencies between tests
3. **Use factories** for test data
4. **Mock external services** (APIs, file systems)
5. **Test edge cases** - Empty data, null values, errors
6. **Use descriptive test names** - `test_teacher_can_create_grade_for_own_student`
7. **Group related tests** - Use `@group` annotations
8. **Clean up after tests** - Use `RefreshDatabase` trait
9. **Test both success and failure** cases
10. **Aim for 80%+ coverage**

### Don'ts ❌

1. **Don't test framework code** - Test your code, not Laravel
2. **Don't test third-party packages**
3. **Don't share state** between tests
4. **Don't use production database**
5. **Don't test private methods** directly
6. **Don't write slow tests** - Keep unit tests under 100ms
7. **Don't skip writing tests** - "I'll write tests later" never happens
8. **Don't test implementation** - Test behavior
9. **Don't use real external APIs** in tests
10. **Don't commit failing tests**

### Test Naming Convention

```php
// ✅ Good - Describes what is tested
test_teacher_can_update_grade_for_own_student()
test_student_cannot_view_other_students_grades()
test_gpa_calculation_with_weighted_grades()

// ❌ Bad - Unclear what is tested
test_update()
test_grades()
test_1()
```

### Arrange-Act-Assert Pattern

```php
public function test_teacher_can_create_grade(): void
{
    // Arrange - Setup test data
    $teacher = EEmployee::factory()->create();
    $student = EStudent::factory()->create();
    $subject = ESubject::factory()->create();
    
    // Act - Perform the action
    $grade = $this->gradeService->createGrade([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'grade' => 85,
    ]);
    
    // Assert - Verify the result
    $this->assertEquals(85, $grade['grade']);
    $this->assertDatabaseHas('e_grades', [
        '_teacher' => $teacher->id,
        '_student' => $student->id,
        'grade' => 85,
    ]);
}
```

### Using Factories

```php
// Create test data with factories
$student = EStudent::factory()->create();
$students = EStudent::factory()->count(10)->create();

// Override specific attributes
$teacher = EEmployee::factory()->create([
    'email' => 'specific@example.com',
    'position' => 'Professor',
]);

// Create relationships
$group = EGroup::factory()
    ->has(EStudent::factory()->count(20), 'students')
    ->create();
```

### Mocking External Services

```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

public function test_document_upload(): void
{
    // Mock file storage
    Storage::fake('local');
    
    $file = UploadedFile::fake()->create('document.pdf');
    
    $result = $this->documentService->upload($file);
    
    // Assert file was stored
    Storage::disk('local')->assertExists($result['path']);
}

public function test_notification_sent(): void
{
    // Mock mail
    Mail::fake();
    
    $this->notificationService->sendGradeNotification($student, $grade);
    
    // Assert email was sent
    Mail::assertSent(GradeNotification::class);
}
```

## Testing Checklist

Before committing code:

- [ ] All new code has unit tests
- [ ] All tests pass locally
- [ ] Code coverage is 70%+ (aim for 80%+)
- [ ] Edge cases are tested
- [ ] Both success and failure paths are tested
- [ ] No skipped or ignored tests
- [ ] Test names are descriptive
- [ ] Tests are fast (< 100ms for unit tests)
- [ ] No database/external dependencies in unit tests
- [ ] Tests are isolated (no shared state)

## Continuous Improvement

### Weekly Goals

- Add 5-10 new tests per week
- Increase coverage by 2-3%
- Refactor slow tests (> 500ms)
- Review and update test documentation

### Monthly Goals

- Reach 80%+ overall coverage
- Add feature tests for all critical workflows
- Update API test scripts
- Run performance benchmarks

---

**Document Version:** 1.0  
**Last Updated:** 2025-11-06  
**Test Coverage:** 70%  
**Target Coverage:** 80%+  
**Maintained by:** QA Team
