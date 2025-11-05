#!/bin/bash
BASE_URL="http://127.0.0.1:8000/api/v1/employee/auth"

echo "============================================================================"
echo "              EMPLOYEE AUTH - TEST"
echo "============================================================================"

# Test login
RESPONSE=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{
    "login": "islom_raxmatullayev",
    "password": "test123"
  }')

echo "$RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)
if data.get('success'):
    print('✅ Employee auth working')
    print('   User:', data.get('data', {}).get('user', {}).get('login'))
else:
    print('⚠️  Auth may need attention:', data.get('message'))
"
