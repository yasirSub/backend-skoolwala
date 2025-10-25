# Skoolwala Backend Deployment Script for Render (PowerShell)
# This script will help you deploy your backend to Render

Write-Host "🚀 Skoolwala Backend Deployment Script" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green

# Check if user is logged into Render CLI
if (!(Get-Command render -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Render CLI not found. Installing..." -ForegroundColor Red
    npm install -g @render/cli
}

# Login to Render (if not already logged in)
Write-Host "🔐 Logging into Render..." -ForegroundColor Yellow
render auth login

# Create web service
Write-Host "🏗️ Creating web service..." -ForegroundColor Yellow
render services create web `
  --name "skoolwala-backend" `
  --repo "https://github.com/yasirSub/backend-skoolwala.git" `
  --branch "YasirDev" `
  --build-command "composer install --no-dev --optimize-autoloader" `
  --start-command "php -S 0.0.0.0:`$PORT -t ." `
  --plan "starter" `
  --region "oregon"

# Set environment variables
Write-Host "🔧 Setting environment variables..." -ForegroundColor Yellow
render env set CI_ENV=production
render env set BASE_URL=https://skoolwala-backend.onrender.com

# Deploy
Write-Host "🚀 Deploying..." -ForegroundColor Yellow
render deploy

Write-Host "✅ Deployment complete!" -ForegroundColor Green
Write-Host "🌐 Your backend will be available at: https://skoolwala-backend.onrender.com" -ForegroundColor Cyan
Write-Host "🧪 Test endpoints:" -ForegroundColor Cyan
Write-Host "   - Health: https://skoolwala-backend.onrender.com/health" -ForegroundColor White
Write-Host "   - F2F API: https://skoolwala-backend.onrender.com/api/f2f/testController" -ForegroundColor White
