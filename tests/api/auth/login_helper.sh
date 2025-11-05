#!/bin/bash

# ============================================================================
# LOGIN HELPER - Get fresh auth token for testing
# ============================================================================
# This script performs a simple login and saves the token
# Used by test suite to get a valid token for module tests
# ============================================================================

BASE_URL="http://127.0.0.1:8000/api/v1/employee/auth"
TOKEN_FILE="/tmp/auth_token.txt"

# Login credentials (teacher)
LOGIN_DATA='{
  "login": "islom_raxmatullayev",
  "password": "test123"
}'

# Perform login
RESPONSE=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$LOGIN_DATA")

# Extract and save token
echo "$RESPONSE" | python3 -c "
import sys, json
data = json.load(sys.stdin)
if data.get('success'):
    token = data['data']['access_token']
    with open('$TOKEN_FILE', 'w') as f:
        f.write(token)
    print('✅ Login successful - token saved to $TOKEN_FILE')
    sys.exit(0)
else:
    print('❌ Login failed')
    print(json.dumps(data, indent=2))
    sys.exit(1)
"

exit $?
