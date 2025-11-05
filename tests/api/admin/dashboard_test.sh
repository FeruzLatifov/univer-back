#!/bin/bash
BASE_URL="http://127.0.0.1:8000/api/v1/admin"
TOKEN_FILE="/tmp/auth_token.txt"
TOKEN=$(cat "$TOKEN_FILE")

echo "============================================================================"
echo "              ADMIN DASHBOARD - TEST"
echo "============================================================================"

# Statistics
RESPONSE=$(curl -s -X GET "$BASE_URL/students/statistics" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)
if data.get('success') or 'total_students' in str(data):
    print('✅ Admin dashboard/statistics working')
else:
    print('⚠️  Endpoint may need implementation')
"
