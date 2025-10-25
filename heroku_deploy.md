# Heroku Deployment Configuration

## 1. Create Procfile
echo "web: vendor/bin/heroku-php-apache2" > Procfile

## 2. Create composer.json (if not exists)
{
    "require": {
        "php": "^7.4|^8.0"
    },
    "scripts": {
        "post-install-cmd": [
            "php -r \"copy('.env.example', '.env');\""
        ]
    }
}

## 3. Create .env file for Heroku
DATABASE_URL=your_database_url_here
APP_ENV=production

## 4. Deploy Commands
git init
git add .
git commit -m "Initial commit"
heroku create your-app-name
git push heroku main
