@echo off
REM PHP Graph Builder Setup Script for Windows
REM This script initializes the application for first run

echo.
echo 🚀 PHP Graph Builder Setup
echo ============================
echo.

REM Create storage directory
echo 📁 Creating storage directory...
if not exist "storage" mkdir storage
echo ✅ Storage directory created
echo.

REM Check PHP version
echo 🔍 Checking PHP version...
php -v | findstr /R "^PHP"
echo.

REM Install composer dependencies
echo 📦 Checking composer...
composer --version >nul 2>&1
if %errorlevel% equ 0 (
    echo 📦 Installing composer dependencies...
    call composer install
    echo ✅ Dependencies installed
) else (
    echo ⚠️  Composer not found. Please install dependencies manually with: composer install
)
echo.

echo ✅ Setup complete!
echo.
echo To start the server, run:
echo   composer start
echo.
echo Then open: http://localhost:8080
echo.
pause
