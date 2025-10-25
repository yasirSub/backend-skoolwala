# Vercel Deployment Guide for Skoolwala Backend

## Step 1: Prepare Your Repository
1. Create a new GitHub repository
2. Upload your backend files to the repository
3. Make sure all files are committed

## Step 2: Install Vercel CLI
```bash
npm install -g vercel
```

## Step 3: Login to Vercel
```bash
vercel login
```

## Step 4: Deploy
```bash
# In your backend directory
vercel --prod
```

## Step 5: Update Your Flutter App
Update your API base URL in api_service.dart:
```dart
static const String localBaseUrl = 'https://your-app-name.vercel.app/api';
```

## Step 6: Environment Variables (if needed)
Set up environment variables in Vercel dashboard:
- DATABASE_URL (if using external database)
- APP_ENV=production

## Benefits of Vercel:
✅ Free tier available
✅ Automatic HTTPS
✅ Global CDN
✅ Easy GitHub integration
✅ Automatic deployments on push
✅ Built-in PHP support
✅ Custom domains available

## Alternative: Railway (Also Great)
If Vercel doesn't work, try Railway:
1. Go to railway.app
2. Connect your GitHub repo
3. Deploy automatically
4. Get a live URL instantly
