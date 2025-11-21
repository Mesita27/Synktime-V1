#!/bin/bash
#
# Smoke test for attendance endpoint
# Tests that the refactored endpoint returns valid JSON
#

set -e

BASE_URL="${BASE_URL:-http://localhost:8080}"
ENDPOINT="/api/attendance/details-refactored.php"

echo "=== Synktime Attendance Endpoint Smoke Test ==="
echo "Base URL: $BASE_URL"
echo "Endpoint: $ENDPOINT"
echo ""

# Test 1: Check endpoint with tipo parameter
echo "[TEST 1] Testing endpoint with tipo=faltas..."
RESPONSE=$(curl -s "$BASE_URL$ENDPOINT?tipo=faltas&fecha=2024-01-15")

# Check if response is valid JSON
if echo "$RESPONSE" | jq . > /dev/null 2>&1; then
    echo "✓ Valid JSON response"
else
    echo "✗ Invalid JSON response"
    echo "Response: $RESPONSE"
    exit 1
fi

# Check if response has expected fields
if echo "$RESPONSE" | jq -e '.success' > /dev/null 2>&1; then
    echo "✓ Response contains 'success' field"
else
    echo "✗ Response missing 'success' field"
    exit 1
fi

echo ""

# Test 2: Check invalid tipo parameter
echo "[TEST 2] Testing endpoint with invalid tipo..."
RESPONSE=$(curl -s "$BASE_URL$ENDPOINT?tipo=invalid")

if echo "$RESPONSE" | jq -e '.success == false' > /dev/null 2>&1; then
    echo "✓ Correctly returns error for invalid tipo"
else
    echo "✗ Did not return error for invalid tipo"
    exit 1
fi

echo ""

# Test 3: Check different attendance types
for tipo in temprano aTiempo tarde faltas; do
    echo "[TEST 3.$tipo] Testing tipo=$tipo..."
    RESPONSE=$(curl -s "$BASE_URL$ENDPOINT?tipo=$tipo&fecha=2024-01-15")
    
    if echo "$RESPONSE" | jq . > /dev/null 2>&1; then
        echo "✓ Valid JSON for tipo=$tipo"
    else
        echo "✗ Invalid JSON for tipo=$tipo"
        exit 1
    fi
done

echo ""
echo "=== All smoke tests passed! ==="
