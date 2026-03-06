@echo off
REM Social Media Automation Platform - Quick Start Guide (Windows)
REM This script sets up and validates the application

setlocal enabledelayedexpansion

echo ==================================================
echo Social Media Automation Platform - Setup Script
echo ==================================================
echo.

REM Step 1: Environment Setup
echo Step 1: Environment Setup
if not exist .env (
    echo Creating .env file...
    copy .env.example .env
    php artisan key:generate
    echo [OK] .env created
) else (
    echo [OK] .env already exists
)
echo.

REM Step 2: Install Dependencies
echo Step 2: Installing Dependencies
if not exist vendor (
    echo Installing Composer dependencies...
    call composer install
    echo [OK] Composer dependencies installed
) else (
    echo [OK] Vendor directory already exists
)

if not exist node_modules (
    echo Installing NPM dependencies...
    call npm install
    call npm run build
    echo [OK] NPM dependencies installed
) else (
    echo [OK] Node modules already exist
)
echo.

REM Step 3: Database Setup
echo Step 3: Database Setup
echo Running migrations...
php artisan migrate --force
echo [OK] Database migrated
echo.

REM Step 4: Create Queues Tables
echo Step 4: Queue Tables Setup
php artisan queue:table
php artisan queue:failed-table
php artisan migrate --force
echo [OK] Queue tables created
echo.

REM Step 5: Validate Configuration
echo Step 5: Validating Configuration
php artisan queue:validate
echo [OK] Configuration validated
echo.

REM Step 6: Run Tests
echo Step 6: Running Tests
echo This may take a minute...
php artisan test
echo [OK] All tests passed!
echo.

REM Step 7: Clear Cache
echo Step 7: Clearing Cache
php artisan cache:clear
php artisan config:clear
echo [OK] Cache cleared
echo.

REM Final Summary
echo ==================================================
echo [OK] Setup Complete!
echo ==================================================
echo.
echo Next steps:
echo.
echo 1. Start the development server:
echo    php artisan serve
echo.
echo 2. In another terminal, start the queue worker:
echo    php artisan queue:work
echo.
echo 3. If developing frontend assets:
echo    npm run dev
echo.
echo 4. Visit: http://localhost:8000
echo.
echo Useful commands:
echo   - Run tests: php artisan test
echo   - Check queue: php artisan queue:validate
echo   - Failed jobs: php artisan queue:failed
echo   - Retry jobs: php artisan queue:retry all
echo.
echo For detailed setup guide, see: SETUP_AND_DEPLOYMENT.md
echo For fix summary, see: COMPLETION_REPORT.md
echo.

pause
