#!/bin/bash
BASE_URL="http://127.0.0.1:8000/api/v1/admin/students"
TOKEN_FILE="/tmp/auth_token.txt"

if [ ! -f "$TOKEN_FILE" ]; then
    echo "❌ Token not found. Run auth test first!"
    exit 1
fi

TOKEN=$(cat "$TOKEN_FILE")

echo "============================================================================"
echo "              ADMIN STUDENT CRUD - TEST SUITE"
echo "============================================================================"

# Test 1: List students
echo "TEST 1: GET /admin/students (list with pagination)"
RESPONSE=$(curl -s -X GET "$BASE_URL?per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)
if data.get('success'):
    print('✅ List students SUCCESS')
    print(f'   Total: {data.get(\"meta\", {}).get(\"total\", 0)}')
    print(f'   Per page: {data.get(\"meta\", {}).get(\"per_page\", 0)}')
else:
    print('❌ FAILED:', data.get('message'))
    sys.exit(1)
"

echo ""
echo "✅ ADMIN STUDENT CRUD TEST COMPLETE"
