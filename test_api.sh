#!/bin/bash

# OOUTH Salary API Test Script
# Usage: ./test_api.sh YOUR_API_KEY

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
API_BASE_URL="https://oouthsalary.com.ng/api/v1"
API_KEY="${1:-YOUR_API_KEY_HERE}"

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         OOUTH Salary API Test Script                      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

if [ "$API_KEY" == "YOUR_API_KEY_HERE" ]; then
    echo -e "${RED}❌ Error: Please provide your API key${NC}"
    echo -e "Usage: ./test_api.sh oouth_001_allow_5_..."
    exit 1
fi

echo -e "${YELLOW}API Key: ${API_KEY}${NC}"
echo -e "${YELLOW}Base URL: ${API_BASE_URL}${NC}"
echo ""

# Test 1: Authentication
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test 1: Authentication${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

AUTH_RESPONSE=$(curl -s -X POST "$API_BASE_URL/auth/token" \
    -H "Content-Type: application/json" \
    -H "X-API-Key: $API_KEY" \
    -d '{}')

echo "$AUTH_RESPONSE" | jq '.' 2>/dev/null || echo "$AUTH_RESPONSE"

# Extract JWT token
JWT_TOKEN=$(echo "$AUTH_RESPONSE" | jq -r '.data.access_token // empty' 2>/dev/null)

if [ -z "$JWT_TOKEN" ] || [ "$JWT_TOKEN" == "null" ]; then
    echo -e "${RED}❌ Authentication failed. Cannot proceed with other tests.${NC}"
    echo -e "${YELLOW}💡 Tip: Make sure REQUIRE_SIGNATURE is set to false in api/config/api_config.php for testing${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Authentication successful!${NC}"
echo -e "${GREEN}JWT Token: ${JWT_TOKEN:0:50}...${NC}"
echo ""

# Test 2: Get Periods
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test 2: Get Payroll Periods${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

PERIODS_RESPONSE=$(curl -s -X GET "$API_BASE_URL/payroll/periods" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -H "X-API-Key: $API_KEY")

echo "$PERIODS_RESPONSE" | jq '.' 2>/dev/null || echo "$PERIODS_RESPONSE"

if echo "$PERIODS_RESPONSE" | jq -e '.success' >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Get periods successful!${NC}"
else
    echo -e "${RED}❌ Get periods failed${NC}"
fi
echo ""

# Test 3: Get Active Period
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test 3: Get Active Period${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

ACTIVE_PERIOD_RESPONSE=$(curl -s -X GET "$API_BASE_URL/payroll/periods/active" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -H "X-API-Key: $API_KEY")

echo "$ACTIVE_PERIOD_RESPONSE" | jq '.' 2>/dev/null || echo "$ACTIVE_PERIOD_RESPONSE"

if echo "$ACTIVE_PERIOD_RESPONSE" | jq -e '.success' >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Get active period successful!${NC}"
    PERIOD_ID=$(echo "$ACTIVE_PERIOD_RESPONSE" | jq -r '.data.period.period_id // empty')
else
    echo -e "${RED}❌ Get active period failed${NC}"
    PERIOD_ID=""
fi
echo ""

# Test 4: Get Allowance/Deduction Data
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test 4: Get Allowance/Deduction Data${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Extract resource type and ID from API key
if [[ $API_KEY =~ oouth_[0-9]{3}_(allow|deduc)_([0-9]+)_ ]]; then
    RESOURCE_TYPE="${BASH_REMATCH[1]}"
    RESOURCE_ID="${BASH_REMATCH[2]}"
    
    if [ "$RESOURCE_TYPE" == "allow" ]; then
        ENDPOINT="allowances"
    else
        ENDPOINT="deductions"
    fi
    
    URL="$API_BASE_URL/payroll/$ENDPOINT/$RESOURCE_ID"
    
    if [ -n "$PERIOD_ID" ]; then
        URL="$URL?period=$PERIOD_ID"
    fi
    
    DATA_RESPONSE=$(curl -s -X GET "$URL" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "X-API-Key: $API_KEY")
    
    echo "$DATA_RESPONSE" | jq '.' 2>/dev/null || echo "$DATA_RESPONSE"
    
    if echo "$DATA_RESPONSE" | jq -e '.success' >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Get data successful!${NC}"
        RECORD_COUNT=$(echo "$DATA_RESPONSE" | jq -r '.metadata.total_records // 0')
        TOTAL_AMOUNT=$(echo "$DATA_RESPONSE" | jq -r '.metadata.total_amount // 0')
        echo -e "${GREEN}   Records: $RECORD_COUNT${NC}"
        echo -e "${GREEN}   Total Amount: ₦$TOTAL_AMOUNT${NC}"
    else
        echo -e "${RED}❌ Get data failed${NC}"
    fi
else
    echo -e "${RED}❌ Invalid API key format${NC}"
fi
echo ""

# Test 5: Check Rate Limit Headers
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}Test 5: Rate Limit Headers${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

HEADERS=$(curl -s -I -X GET "$API_BASE_URL/payroll/periods" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -H "X-API-Key: $API_KEY")

RATE_LIMIT=$(echo "$HEADERS" | grep -i "X-RateLimit-Limit" | awk '{print $2}' | tr -d '\r')
RATE_REMAINING=$(echo "$HEADERS" | grep -i "X-RateLimit-Remaining" | awk '{print $2}' | tr -d '\r')
RATE_RESET=$(echo "$HEADERS" | grep -i "X-RateLimit-Reset" | awk '{print $2}' | tr -d '\r')

echo -e "Rate Limit: ${YELLOW}${RATE_LIMIT}${NC} requests/minute"
echo -e "Remaining: ${YELLOW}${RATE_REMAINING}${NC} requests"
echo -e "Resets at: ${YELLOW}${RATE_RESET}${NC} (Unix timestamp)"
echo ""

# Summary
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    Test Summary                           ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo -e "${GREEN}✅ All tests completed!${NC}"
echo -e ""
echo -e "${YELLOW}📋 Next Steps:${NC}"
echo -e "   1. If tests failed, check that API tables are imported"
echo -e "   2. Verify REQUIRE_SIGNATURE = false in api/config/api_config.php"
echo -e "   3. Check that your API key is active in the database"
echo -e "   4. Review test results above for any errors"
echo ""

