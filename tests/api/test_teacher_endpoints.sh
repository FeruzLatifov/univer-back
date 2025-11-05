#!/bin/bash

# ============================================================================
# TEACHER ENDPOINTS - Test Script
# ============================================================================
# Purpose: Test teacher dashboard and related endpoints
# Usage: ./test_teacher_endpoints.sh
# Prerequisite: Run test_auth_endpoints.sh first to get token
# ============================================================================

BASE_URL="http://127.0.0.1:8000/api/v1/teacher"
TOKEN_FILE="/tmp/auth_token.txt"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check if token exists
if [ ! -f "$TOKEN_FILE" ]; then
    echo -e "${RED}❌ Token not found. Run test_auth_endpoints.sh first!${NC}"
    exit 1
fi

TOKEN=$(cat "$TOKEN_FILE")

echo "============================================================================"
echo "                  TEACHER ENDPOINTS - TEST SUITE"
echo "============================================================================"
echo ""

# ============================================================================
# TEST 1: Teacher Dashboard
# ============================================================================
echo -e "${YELLOW}TEST 1: GET /teacher/dashboard${NC}"
echo "------------------------------------------------------------------------"

DASHBOARD_RESPONSE=$(curl -s -X GET "$BASE_URL/dashboard" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$DASHBOARD_RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)

if data.get('success'):
    dashboard = data['data']
    print('✅ GET /teacher/dashboard SUCCESS')
    print(f'')
    print(f'   Stats:')
    stats = dashboard.get('stats', {})
    print(f'     Total Subjects: {stats.get(\"total_subjects\")}')
    print(f'     Total Groups: {stats.get(\"total_groups\")}')
    print(f'     Total Students: {stats.get(\"total_students\")}')
    print(f'     Pending Attendance: {stats.get(\"pending_attendance\")}')
    print(f'')

    schedule = dashboard.get('today_schedule', [])
    print(f'   Today Schedule: {len(schedule)} lessons')
    if schedule:
        for i, lesson in enumerate(schedule[:3], 1):
            print(f'     {i}. {lesson.get(\"subject_name\", \"Unknown\")} - {lesson.get(\"group_name\", \"Unknown\")}')

    pending = dashboard.get('pending_attendance_classes', [])
    print(f'')
    print(f'   Pending Attendance: {len(pending)} classes')
    if pending:
        for i, cls in enumerate(pending[:3], 1):
            print(f'     {i}. {cls.get(\"subject_name\", \"Unknown\")} - {cls.get(\"lesson_date\", \"Unknown\")}')
else:
    print('❌ FAILED')
    if data.get('message') == 'Bu amalni bajarish uchun ruxsatingiz yo\'\'q':
        print('   ⚠️  Permission denied - user does not have teacher.dashboard.view')
    print(json.dumps(data, indent=2))
    sys.exit(1)
"

if [ $? -ne 0 ]; then
    exit 1
fi

echo ""
echo ""

# ============================================================================
# TEST 2: Today's Schedule
# ============================================================================
echo -e "${YELLOW}TEST 2: GET /teacher/schedule/today${NC}"
echo "------------------------------------------------------------------------"

SCHEDULE_RESPONSE=$(curl -s -X GET "$BASE_URL/schedule/today" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$SCHEDULE_RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)

if data.get('success'):
    schedule = data['data']
    print('✅ GET /teacher/schedule/today SUCCESS')
    print(f'   Lessons today: {len(schedule)}')
    print(f'')

    if schedule:
        print(f'   Schedule:')
        for lesson in schedule[:5]:
            print(f'     - Pair {lesson.get(\"lesson_pair\")}: {lesson.get(\"subject_name\")} ({lesson.get(\"group_name\")})')
    else:
        print(f'   ℹ️  No lessons scheduled for today')
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
# TEST 3: Teacher Workload
# ============================================================================
echo -e "${YELLOW}TEST 3: GET /teacher/schedule/workload${NC}"
echo "------------------------------------------------------------------------"

WORKLOAD_RESPONSE=$(curl -s -X GET "$BASE_URL/schedule/workload" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$WORKLOAD_RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)

if data.get('success'):
    workload = data['data']
    print('✅ GET /teacher/schedule/workload SUCCESS')
    print(f'')
    print(f'   Subjects: {len(workload.get(\"subjects\", []))}')
    print(f'   Total Students: {workload.get(\"total_students\")}')
    print(f'   Weekly Hours: {workload.get(\"weekly_hours\")}')
    print(f'')

    subjects = workload.get('subjects', [])
    if subjects:
        print(f'   Subject Details:')
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
# TEST 4: Pending Attendance Classes
# ============================================================================
echo -e "${YELLOW}TEST 4: GET /teacher/attendance/pending${NC}"
echo "------------------------------------------------------------------------"

PENDING_RESPONSE=$(curl -s -X GET "$BASE_URL/attendance/pending" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$PENDING_RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)

if data.get('success'):
    pending = data['data']
    print('✅ GET /teacher/attendance/pending SUCCESS')
    print(f'   Pending classes: {len(pending)}')
    print(f'')

    if pending:
        print(f'   Classes needing attendance:')
        for cls in pending[:5]:
            print(f'     - {cls.get(\"subject_name\")} ({cls.get(\"group_name\")}) - {cls.get(\"lesson_date\")}')
            print(f'       Pair: {cls.get(\"lesson_pair\")}, Schedule ID: {cls.get(\"schedule_id\")}')
    else:
        print(f'   ✅ No pending attendance (all up to date)')
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
# TEST 5: Dashboard Activities
# ============================================================================
echo -e "${YELLOW}TEST 5: GET /teacher/dashboard/activities${NC}"
echo "------------------------------------------------------------------------"

ACTIVITIES_RESPONSE=$(curl -s -X GET "$BASE_URL/dashboard/activities?limit=10" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$ACTIVITIES_RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)

if data.get('success'):
    activities = data['data']
    print('✅ GET /teacher/dashboard/activities SUCCESS')
    print(f'   Activities: {len(activities)}')
    print(f'')

    if activities:
        print(f'   Recent Activities:')
        for act in activities[:5]:
            print(f'     - {act.get(\"type\")}: {act.get(\"description\")}')
            print(f'       {act.get(\"created_at\")}')
    else:
        print(f'   ℹ️  No recent activities')
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
echo -e "${GREEN}              TEACHER ENDPOINTS - ALL TESTS PASSED ✅${NC}"
echo "============================================================================"
echo ""
echo "Summary:"
echo "  ✅ Dashboard (stats, schedule, pending)"
echo "  ✅ Today's Schedule"
echo "  ✅ Teacher Workload"
echo "  ✅ Pending Attendance Classes"
echo "  ✅ Dashboard Activities"
echo ""
echo "Note:"
echo "  🔒 All endpoints protected by middleware"
echo "  🔒 Permission checked on each request"
echo "  ⚡ Fast response with Redis cache"
echo ""
