# Heroku Deployment Configuration
# Create this file: Procfile
web: vendor/bin/heroku-php-apache2

# Create this file: composer.json
{
    "require": {
        "php": "^7.4|^8.0",
        "ext-json": "*",
        "ext-mysqli": "*",
        "ext-mbstring": "*"
    },
    "scripts": {
        "post-install-cmd": [
            "chmod -R 755 application/logs",
            "chmod -R 755 uploads"
        ]
    }
}
