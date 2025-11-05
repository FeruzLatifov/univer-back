#!/bin/bash

# ============================================================================
# RUN ALL API TESTS - Modular Monolith Architecture
# ============================================================================

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo "============================================================================"
echo "              HEMIS UNIVERSITY - API TEST SUITE"
echo "              Modular Monolith Architecture"
echo "============================================================================"
echo ""

FAILED_TESTS=()
PASSED_TESTS=()

# ============================================================================
# 1. AUTH MODULE
# ============================================================================
echo -e "${BLUE}[1/5] Running AUTH tests...${NC}"
echo ""

if ./auth/auth_test.sh; then
    PASSED_TESTS+=("AUTH")
else
    FAILED_TESTS+=("AUTH")
fi

echo ""
echo ""

# ============================================================================
# Get fresh token for module tests (auth tests logged out)
# ============================================================================
echo -e "${BLUE}Getting fresh authentication token for module tests...${NC}"
./auth/login_helper.sh
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to get authentication token${NC}"
    exit 1
fi
echo ""

# ============================================================================
# 2. TEACHER MODULE - Dashboard
# ============================================================================
echo -e "${BLUE}[2/5] Running TEACHER DASHBOARD tests (Refactored)...${NC}"
echo ""

if ./teacher/dashboard_test.sh; then
    PASSED_TESTS+=("TEACHER_DASHBOARD")
else
    FAILED_TESTS+=("TEACHER_DASHBOARD")
fi

echo ""
echo ""

# ============================================================================
# 3. TEACHER MODULE - Schedule
# ============================================================================
echo -e "${BLUE}[3/5] Running TEACHER SCHEDULE tests...${NC}"
echo ""

if ./teacher/schedule_test.sh; then
    PASSED_TESTS+=("TEACHER_SCHEDULE")
else
    FAILED_TESTS+=("TEACHER_SCHEDULE")
fi

echo ""
echo ""

# ============================================================================
# 4. TEACHER MODULE - Attendance
# ============================================================================
echo -e "${BLUE}[4/5] Running TEACHER ATTENDANCE tests...${NC}"
echo ""

if ./teacher/attendance_test.sh; then
    PASSED_TESTS+=("TEACHER_ATTENDANCE")
else
    FAILED_TESTS+=("TEACHER_ATTENDANCE")
fi

echo ""
echo ""

# ============================================================================
# 5. TEACHER MODULE - Grade
# ============================================================================
echo -e "${BLUE}[5/5] Running TEACHER GRADE tests...${NC}"
echo ""

if ./teacher/grade_test.sh; then
    PASSED_TESTS+=("TEACHER_GRADE")
else
    FAILED_TESTS+=("TEACHER_GRADE")
fi

echo ""
echo ""

# ============================================================================
# FINAL SUMMARY
# ============================================================================
echo "============================================================================"
echo "                          TEST SUMMARY"
echo "============================================================================"
echo ""

TOTAL_TESTS=$((${#PASSED_TESTS[@]} + ${#FAILED_TESTS[@]}))
PASSED_COUNT=${#PASSED_TESTS[@]}
FAILED_COUNT=${#FAILED_TESTS[@]}

echo "Total Tests: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $PASSED_COUNT${NC}"
echo -e "${RED}Failed: $FAILED_COUNT${NC}"
echo ""

if [ $FAILED_COUNT -eq 0 ]; then
    echo -e "${GREEN}✅ ALL TESTS PASSED!${NC}"
    echo ""
    echo "Modules Tested:"
    for test in "${PASSED_TESTS[@]}"; do
        echo -e "  ${GREEN}✅${NC} $test"
    done
    echo ""
    echo "Architecture:"
    echo "  🏗️  Modular Monolith"
    echo "  📦 Service Layer Pattern"
    echo "  🎯 Clean Architecture: Controller → Service → Model"
    echo ""
    exit 0
else
    echo -e "${RED}❌ SOME TESTS FAILED${NC}"
    echo ""
    echo "Failed Modules:"
    for test in "${FAILED_TESTS[@]}"; do
        echo -e "  ${RED}❌${NC} $test"
    done
    echo ""
    echo "Passed Modules:"
    for test in "${PASSED_TESTS[@]}"; do
        echo -e "  ${GREEN}✅${NC} $test"
    done
    echo ""
    exit 1
fi
