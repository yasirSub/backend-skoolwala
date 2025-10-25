#!/bin/bash

# F2F API Test Script
BASE_URL="https://school.firmbeginners.com/api"

echo "🔍 Testing F2F API Endpoints..."
echo "Base URL: $BASE_URL"
echo "=================================="

# Test 1: Test Controller
echo "1. Testing F2F Test Controller..."
curl -X GET "$BASE_URL/f2f/testController" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s

echo -e "\n==================================\n"

# Test 2: Create Person
echo "2. Testing F2F Create..."
curl -X POST "$BASE_URL/f2f/create" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Person", "age": 25}' \
  -w "\nHTTP Status: %{http_code}\n" \
  -s

echo -e "\n==================================\n"

# Test 3: List Persons
echo "3. Testing F2F List..."
curl -X GET "$BASE_URL/f2f/list" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s

echo -e "\n==================================\n"

# Test 4: Check Face
echo "4. Testing F2F Check Face..."
curl -X POST "$BASE_URL/f2f/checkFace" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"embedding": [0.1, 0.2, 0.3, 0.4, 0.5]}' \
  -w "\nHTTP Status: %{http_code}\n" \
  -s

echo -e "\n==================================\n"

# Test 5: Analyze
echo "5. Testing F2F Analyze..."
curl -X POST "$BASE_URL/f2f/analyze" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"embedding": [0.1, 0.2, 0.3, 0.4, 0.5]}' \
  -w "\nHTTP Status: %{http_code}\n" \
  -s

echo -e "\n==================================\n"
echo "✅ All tests completed!"
