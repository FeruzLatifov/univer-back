# API Test Suite - HEMIS University (Modular Monolith)

## 📁 STRUKTURA

```
tests/api/
├── README.md                  # Bu fayl
├── run_all_tests.sh          # Barcha testlarni ishga tushirish
│
├── auth/                      # AUTH MODULE
│   └── auth_test.sh          # Login, permissions, JWT tests
│
├── teacher/                   # TEACHER MODULE (Modular Monolith)
│   ├── dashboard_test.sh     # Dashboard tests (Refactored)
│   ├── schedule_test.sh      # Schedule tests
│   ├── attendance_test.sh    # Attendance tests
│   └── grade_test.sh         # Grade tests
│
├── student/                   # STUDENT MODULE (Coming soon)
│   └── (testlar keyinchalik)
│
└── admin/                     # ADMIN MODULE (Coming soon)
    └── (testlar keyinchalik)
```

---

## 🚀 ISHLATISH

### 1. Barcha testlarni ishga tushirish

```bash
cd /home/adm1n/univer/univer-back/tests/api
./run_all_tests.sh
```

### 2. Modulga oid testlarni ishga tushirish

#### Auth tests:
```bash
./auth/auth_test.sh
```

#### Teacher module tests:
```bash
# Dashboard (refactored - 362 → 118 qator)
./teacher/dashboard_test.sh

# Schedule
./teacher/schedule_test.sh

# Attendance
./teacher/attendance_test.sh

# Grade
./teacher/grade_test.sh
```

---

## 🏗️ MODULAR MONOLITH ARCHITECTURE

### Refactoring Progress:

#### ✅ Week 1: Teacher Module - COMPLETED ✅
- [x] **DashboardController** - REFACTORED ✅
  - Old: 362 qator (Fat Controller)
  - New: 118 qator (Thin Controller)
  - Reduction: 67% (244 lines removed)
  - Service: `app/Services/Teacher/DashboardService.php`
  - Test: `tests/api/teacher/dashboard_test.sh`

- [x] **ScheduleController** - REFACTORED ✅
  - Old: 252 qator (Fat Controller)
  - New: 171 qator (Thin Controller)
  - Reduction: 32% (81 lines removed)
  - Service: `app/Services/Teacher/ScheduleService.php`
  - Test: `tests/api/teacher/schedule_test.sh`

- [x] **AttendanceController** - REFACTORED ✅
  - Old: 296 qator (Fat Controller)
  - New: 222 qator (Thin Controller)
  - Reduction: 25% (74 lines removed)
  - Service: `app/Services/Teacher/AttendanceService.php`
  - Test: `tests/api/teacher/attendance_test.sh`

- [x] **GradeController** - REFACTORED ✅
  - Old: 341 qator (Fat Controller)
  - New: 225 qator (Thin Controller)
  - Reduction: 34% (116 lines removed)
  - Service: `app/Services/Teacher/GradeService.php`
  - Test: `tests/api/teacher/grade_test.sh`

**Week 1 Summary:**
- ✅ 4 Controllers refactored (1251 → 736 lines)
- ✅ 515 lines removed (41% average reduction)
- ✅ 4 Service classes created (~1000 lines business logic)
- ✅ Clean Architecture: Controller → Service → Model
- ✅ All test scripts created and configured

#### 🔄 Week 2: Student Module
- [ ] Student module refactoring
- [ ] Student tests

#### 🔄 Week 3: Admin Module
- [ ] Admin module refactoring
- [ ] Admin tests

#### 🔄 Week 4: Shared Components
- [ ] Shared services
- [ ] Integration tests

---

## 📊 TEST RESULTS FORMAT

Har bir test script quyidagi formatda natija beradi:

```
============================================================================
                    MODULE NAME - TEST SUITE
============================================================================

TEST 1: Endpoint name
------------------------------------------------------------------------
✅ SUCCESS / ❌ FAILED
   Details...

TEST 2: Another endpoint
------------------------------------------------------------------------
✅ SUCCESS / ❌ FAILED
   Details...

============================================================================
                    ALL TESTS PASSED ✅
============================================================================

Summary:
  ✅ Test 1
  ✅ Test 2
  ✅ Test 3
```

---

## 🎯 CLEAN ARCHITECTURE VERIFICATION

Har bir test script refactoring natijalarini ham tekshiradi:

### Dashboard Test Verification:
```bash
./teacher/dashboard_test.sh

# Output includes:
   ✅ Refactoring Verification:
     ✅ Controller: HTTP layer only (no business logic)
     ✅ Service: DashboardService.getDashboardData()
     ✅ Clean Architecture: Controller → Service → Model
```

---

## ❌ TROUBLESHOOTING

### Token not found
```
❌ Token not found. Run tests/api/auth/auth_test.sh first!
```

**Yechim:**
```bash
./auth/auth_test.sh
```

### Connection refused
```
curl: (7) Failed to connect to 127.0.0.1 port 8000
```

**Yechim:**
```bash
cd /home/adm1n/univer/univer-back
php artisan serve --host=127.0.0.1 --port=8000
```

### Permission denied
```
⚠️  Permission denied - user does not have required permission
```

**Yechim:** Test user'ga kerakli permission bering yoki boshqa user bilan test qiling.

---

## 🧪 YANGI TEST YARATISH

### Template:

```bash
#!/bin/bash

# ============================================================================
# MODULE NAME TEST - Modular Monolith Architecture
# ============================================================================
# Purpose: Test XYZController
# Module: ModuleName
# Service: XYZService
# ============================================================================

BASE_URL="http://127.0.0.1:8000/api/v1/module/endpoint"
TOKEN_FILE="/tmp/auth_token.txt"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check token
if [ ! -f "$TOKEN_FILE" ]; then
    echo -e "${RED}❌ Token not found!${NC}"
    exit 1
fi

TOKEN=$(cat "$TOKEN_FILE")

echo "============================================================================"
echo "                   MODULE NAME - TEST SUITE"
echo "============================================================================"
echo ""

# Your tests here...

echo "============================================================================"
echo -e "${GREEN}              ALL TESTS PASSED ✅${NC}"
echo "============================================================================"
```

---

## 📚 DOCUMENTATION

- **JWT Implementation:** `/home/adm1n/univer/docs/JWT_IMPLEMENTATION_COMPLETE.md`
- **Frontend Guide:** `/home/adm1n/univer/docs/FRONTEND_IMPLEMENTATION_GUIDE.md`
- **Architecture Analysis:** `/home/adm1n/univer/docs/ARCHITECTURE_ANALYSIS_UZ.md`
- **Refactoring Plan:** `/home/adm1n/univer/docs/REFACTORING_PLAN_VARIANT2.md`
- **Swagger Docs:** `http://127.0.0.1:8000/docs/api`

---

**Last Updated:** 2025-01-04
**Status:** ✅ Week 1 COMPLETED - Teacher Module Fully Refactored
**Architecture:** Modular Monolith (Clean Architecture)
**Next:** Week 2 - Student Module Refactoring

**Week 1 Achievements:**
- ✅ 4 Controllers: 1251 → 736 lines (41% reduction)
- ✅ 4 Service classes created (~1000 lines)
- ✅ Clean Architecture implemented
- ✅ All test scripts created
