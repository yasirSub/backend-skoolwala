# 🚀 SKOOLWALA BACKEND DEPLOYMENT GUIDE

## Quick Deploy to Render (Manual Steps)

### Step 1: Go to Render Dashboard
1. Open: https://dashboard.render.com
2. Click "New" → "Web Service"

### Step 2: Connect Repository
1. Click "Connect a repository"
2. Find and select "backend-skoolwala"
3. Click "Connect"

### Step 3: Configure Service
**Basic Settings:**
- Name: `skoolwala-backend`
- Environment: `PHP`
- Region: `Oregon (US West)`

**Build & Deploy:**
- Build Command: `composer install --no-dev --optimize-autoloader`
- Start Command: `php -S 0.0.0.0:$PORT -t .`
- Branch: `YasirDev`

**Environment Variables:**
- `CI_ENV` = `production`
- `BASE_URL` = `https://skoolwala-backend.onrender.com`

### Step 4: Deploy
1. Click "Create Web Service"
2. Wait 3-5 minutes for deployment

## 🧪 Test Your Deployment

Once deployed, test these endpoints:

```bash
# Health check
curl https://skoolwala-backend.onrender.com/health

# F2F API test
curl https://skoolwala-backend.onrender.com/api/f2f/testController

# Basic API test
curl https://skoolwala-backend.onrender.com/api/test
```

## 📱 Update Flutter App

After deployment, update your Flutter app's API URL to:
`https://skoolwala-backend.onrender.com/api`

## 🎯 Expected Results

✅ Health endpoint returns: `{"status":"ok","timestamp":"...","version":"1.0.0"}`
✅ F2F API returns: `{"status":"success","message":"F2F API is working"}`
✅ No more 404 errors in Flutter app

## 🚨 Troubleshooting

If deployment fails:
1. Check Render logs for errors
2. Verify environment variables are set
3. Ensure GitHub repository is accessible
4. Check PHP version compatibility

## 📞 Support

If you need help, the deployment is configured with:
- ✅ Environment variables
- ✅ Health checks
- ✅ Proper routing
- ✅ Database configuration
- ✅ URL rewriting
