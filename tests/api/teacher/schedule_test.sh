#!/bin/bash

# ============================================================================
# TEACHER SCHEDULE TEST - Modular Monolith Architecture
# ============================================================================
# Purpose: Test ScheduleController
# Module: Teacher
# Service: ScheduleService (to be created)
# ============================================================================

BASE_URL="http://127.0.0.1:8000/api/v1/teacher/schedule"
TOKEN_FILE="/tmp/auth_token.txt"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check token
if [ ! -f "$TOKEN_FILE" ]; then
    echo -e "${RED}❌ Token not found. Run tests/api/auth/auth_test.sh first!${NC}"
    exit 1
fi

TOKEN=$(cat "$TOKEN_FILE")

echo "============================================================================"
echo "                   TEACHER SCHEDULE - TEST SUITE"
echo "============================================================================"
echo ""

# ============================================================================
# TEST 1: Get Today's Schedule
# ============================================================================
echo -e "${YELLOW}TEST 1: GET /teacher/schedule/today${NC}"
echo "------------------------------------------------------------------------"

TODAY_RESPONSE=$(curl -s -X GET "${BASE_URL}/today" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$TODAY_RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)

if data.get('success'):
    schedule = data['data']
    print('✅ GET /teacher/schedule/today SUCCESS')
    print(f'   Lessons today: {len(schedule)}')

    if schedule:
        print('')
        print('   Schedule:')
        for i, lesson in enumerate(schedule[:5], 1):
            subject = lesson.get('subject', {})
            group = lesson.get('group', {})
            time = lesson.get('time', {})
            print(f'     {i}. Pair {time.get(\"pair_number\")}: {subject.get(\"name\")} ({group.get(\"name\")})')
            print(f'        Time: {time.get(\"start\")} - {time.get(\"end\")}')
    else:
        print('   ℹ️  No lessons scheduled for today')
else:
    print('❌ FAILED')
    print(json.dumps(data, indent=2))
    sys.exit(1)
"

if [ $? -ne 0 ]; then
    exit 1
fi

echo ""
echo ""

# ============================================================================
# TEST 2: Get Teacher Workload
# ============================================================================
echo -e "${YELLOW}TEST 2: GET /teacher/schedule/workload${NC}"
echo "------------------------------------------------------------------------"

WORKLOAD_RESPONSE=$(curl -s -X GET "${BASE_URL}/workload" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$WORKLOAD_RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)

if data.get('success'):
    workload = data['data']
    print('✅ GET /teacher/schedule/workload SUCCESS')
    print('')
    print('   Workload:')
    print(f'     Subjects: {len(workload.get(\"subjects\", []))}')
    print(f'     Total Students: {workload.get(\"total_students\")}')
    print(f'     Weekly Hours: {workload.get(\"weekly_hours\")}')

    subjects = workload.get('subjects', [])
    if subjects:
        print('')
        print('   Subject Details:')
        for subj in subjects[:3]:
            print(f'     - {subj.get(\"name\")}: {subj.get(\"groups\")} groups, {subj.get(\"students\")} students')
else:
    print('❌ FAILED')
    print(json.dumps(data, indent=2))
    sys.exit(1)
"

if [ $? -ne 0 ]; then
    exit 1
fi

echo ""
echo ""

# ============================================================================
# SUMMARY
# ============================================================================
echo "============================================================================"
echo -e "${GREEN}          TEACHER SCHEDULE - ALL TESTS PASSED ✅${NC}"
echo "============================================================================"
echo ""
echo "Endpoints Tested:"
echo "  ✅ GET /teacher/schedule/today"
echo "  ✅ GET /teacher/schedule/workload"
echo ""
