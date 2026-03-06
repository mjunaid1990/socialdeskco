#!/bin/bash

# Social Media Automation Platform - Quick Start Guide
# This script sets up and validates the application

set -e

echo "=================================================="
echo "Social Media Automation Platform - Setup Script"
echo "=================================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Environment Setup
echo -e "${BLUE}Step 1: Environment Setup${NC}"
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
    echo -e "${GREEN}✓ .env created${NC}"
else
    echo -e "${GREEN}✓ .env already exists${NC}"
fi
echo ""

# Step 2: Install Dependencies
echo -e "${BLUE}Step 2: Installing Dependencies${NC}"
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install
    echo -e "${GREEN}✓ Composer dependencies installed${NC}"
else
    echo -e "${GREEN}✓ Vendor directory already exists${NC}"
fi

if [ ! -d "node_modules" ]; then
    echo "Installing NPM dependencies..."
    npm install
    npm run build
    echo -e "${GREEN}✓ NPM dependencies installed${NC}"
else
    echo -e "${GREEN}✓ Node modules already exist${NC}"
fi
echo ""

# Step 3: Database Setup
echo -e "${BLUE}Step 3: Database Setup${NC}"
echo "Running migrations..."
php artisan migrate --force
echo -e "${GREEN}✓ Database migrated${NC}"
echo ""

# Step 4: Create Queues Tables
echo -e "${BLUE}Step 4: Queue Tables Setup${NC}"
php artisan queue:table
php artisan queue:failed-table
php artisan migrate --force
echo -e "${GREEN}✓ Queue tables created${NC}"
echo ""

# Step 5: Validate Configuration
echo -e "${BLUE}Step 5: Validating Configuration${NC}"
php artisan queue:validate
echo -e "${GREEN}✓ Configuration validated${NC}"
echo ""

# Step 6: Run Tests
echo -e "${BLUE}Step 6: Running Tests${NC}"
echo "This may take a minute..."
php artisan test --parallel
echo -e "${GREEN}✓ All tests passed!${NC}"
echo ""

# Step 7: Clear Cache
echo -e "${BLUE}Step 7: Clearing Cache${NC}"
php artisan cache:clear
php artisan config:clear
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

# Final Summary
echo "=================================================="
echo -e "${GREEN}✓ Setup Complete!${NC}"
echo "=================================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Start the development server:"
echo "   ${BLUE}php artisan serve${NC}"
echo ""
echo "2. In another terminal, start the queue worker:"
echo "   ${BLUE}php artisan queue:work${NC}"
echo ""
echo "3. If developing frontend assets:"
echo "   ${BLUE}npm run dev${NC}"
echo ""
echo "4. Visit: ${YELLOW}http://localhost:8000${NC}"
echo ""
echo "Useful commands:"
echo "  - Run tests: ${BLUE}php artisan test${NC}"
echo "  - Check queue: ${BLUE}php artisan queue:validate${NC}"
echo "  - Failed jobs: ${BLUE}php artisan queue:failed${NC}"
echo "  - Retry jobs: ${BLUE}php artisan queue:retry all${NC}"
echo ""
echo "For detailed setup guide, see: ${YELLOW}SETUP_AND_DEPLOYMENT.md${NC}"
echo "For fix summary, see: ${YELLOW}COMPLETION_REPORT.md${NC}"
echo ""
