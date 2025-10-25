#!/bin/bash

# Skoolwala Backend Deployment Script for Render
# This script will help you deploy your backend to Render

echo "🚀 Skoolwala Backend Deployment Script"
echo "====================================="

# Check if user is logged into Render CLI
if ! command -v render &> /dev/null; then
    echo "❌ Render CLI not found. Installing..."
    npm install -g @render/cli
fi

# Login to Render (if not already logged in)
echo "🔐 Logging into Render..."
render auth login

# Create web service
echo "🏗️ Creating web service..."
render services create web \
  --name "skoolwala-backend" \
  --repo "https://github.com/yasirSub/backend-skoolwala.git" \
  --branch "YasirDev" \
  --build-command "composer install --no-dev --optimize-autoloader" \
  --start-command "php -S 0.0.0.0:\$PORT -t ." \
  --plan "starter" \
  --region "oregon"

# Set environment variables
echo "🔧 Setting environment variables..."
render env set CI_ENV=production
render env set BASE_URL=https://skoolwala-backend.onrender.com

# Deploy
echo "🚀 Deploying..."
render deploy

echo "✅ Deployment complete!"
echo "🌐 Your backend will be available at: https://skoolwala-backend.onrender.com"
echo "🧪 Test endpoints:"
echo "   - Health: https://skoolwala-backend.onrender.com/health"
echo "   - F2F API: https://skoolwala-backend.onrender.com/api/f2f/testController"
