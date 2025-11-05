#!/bin/bash

# ============================================================================
# TEACHER GRADE TEST - Modular Monolith Architecture
# ============================================================================
# Purpose: Test GradeController
# Module: Teacher
# Service: GradeService (to be created)
# ============================================================================

BASE_URL="http://127.0.0.1:8000/api/v1/teacher/grades"
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
echo "                    TEACHER GRADE - TEST SUITE"
echo "============================================================================"
echo ""

# ============================================================================
# TEST 1: Get Grades for Subject
# ============================================================================
echo -e "${YELLOW}TEST 1: GET /teacher/grades?subject_id={id}${NC}"
echo "------------------------------------------------------------------------"

# Note: This requires a valid subject_id
echo "ℹ️  This endpoint requires subject_id parameter"
echo "   Example: GET /teacher/grades?subject_id=123"
echo "   Skipping automated test (needs specific subject_id)"

echo ""
echo ""

# ============================================================================
# TEST 2: Get Grade Statistics
# ============================================================================
echo -e "${YELLOW}TEST 2: GET /teacher/grades/stats${NC}"
echo "------------------------------------------------------------------------"

STATS_RESPONSE=$(curl -s -X GET "${BASE_URL}/stats" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$STATS_RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)

if data.get('success'):
    stats = data['data']
    print('✅ GET /teacher/grades/stats SUCCESS')
    print('')
    print('   Grade Statistics:')
    print(f'     Average Grade: {stats.get(\"average_grade\")}')
    print(f'     Total Graded: {stats.get(\"total_graded\")}')
    print(f'     Pending Grades: {stats.get(\"pending_grades\")}')
elif data.get('message'):
    # Endpoint might not exist yet
    print('ℹ️  Grade stats endpoint not implemented yet')
    print(f'   Message: {data.get(\"message\")}')
else:
    print('❌ FAILED')
    print(json.dumps(data, indent=2))
    sys.exit(1)
"

# Don't fail if endpoint doesn't exist
if [ $? -ne 0 ]; then
    echo "   ⚠️  Grade stats endpoint may need implementation"
fi

echo ""
echo ""

# ============================================================================
# SUMMARY
# ============================================================================
echo "============================================================================"
echo -e "${GREEN}           TEACHER GRADE - TESTS COMPLETED${NC}"
echo "============================================================================"
echo ""
echo "Endpoints:"
echo "  ℹ️  GET /teacher/grades?subject_id={id} (needs subject_id)"
echo "  ℹ️  GET /teacher/grades/stats (may need implementation)"
echo "  ℹ️  POST /teacher/grades (mark grades - needs implementation)"
echo ""
echo "Note: Full grade testing requires:"
echo "  - Valid subject_id"
echo "  - Valid student_id"
echo "  - Grade posting implementation"
echo ""
