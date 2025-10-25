# F2F API Test Script for PowerShell
$baseUrl = "https://school.firmbeginners.com/api"

Write-Host "🔍 Testing F2F API Endpoints..." -ForegroundColor Green
Write-Host "Base URL: $baseUrl" -ForegroundColor Yellow
Write-Host "==================================" -ForegroundColor Cyan

# Test 1: Test Controller
Write-Host "1. Testing F2F Test Controller..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/f2f/testController" -Method GET -Headers @{
        "Accept" = "application/json"
        "Content-Type" = "application/json"
    }
    Write-Host "✅ SUCCESS" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json -Depth 3)
} catch {
    Write-Host "❌ FAILED" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n==================================`n" -ForegroundColor Cyan

# Test 2: Create Person
Write-Host "2. Testing F2F Create..." -ForegroundColor Yellow
try {
    $body = @{
        name = "Test Person"
        age = 25
    } | ConvertTo-Json
    
    $response = Invoke-RestMethod -Uri "$baseUrl/f2f/create" -Method POST -Body $body -Headers @{
        "Accept" = "application/json"
        "Content-Type" = "application/json"
    }
    Write-Host "✅ SUCCESS" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json -Depth 3)
} catch {
    Write-Host "❌ FAILED" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n==================================`n" -ForegroundColor Cyan

# Test 3: List Persons
Write-Host "3. Testing F2F List..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/f2f/list" -Method GET -Headers @{
        "Accept" = "application/json"
        "Content-Type" = "application/json"
    }
    Write-Host "✅ SUCCESS" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json -Depth 3)
} catch {
    Write-Host "❌ FAILED" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n==================================`n" -ForegroundColor Cyan
Write-Host "✅ All tests completed!" -ForegroundColor Green
