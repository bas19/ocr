#!/bin/bash

# =============================================================================
# Deploy to Laravel Cloud
# =============================================================================
# Automated deployment script for cloud.laravel.com
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_header() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# =============================================================================
# Start Deployment
# =============================================================================

clear
echo -e "${BLUE}"
cat << "EOF"
╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║     🚀  Laravel Cloud Deployment Script                  ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

# =============================================================================
# Pre-flight Checks
# =============================================================================

print_header "1/7 Pre-flight Checks"

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    print_error "Not a git repository"
    exit 1
fi
print_success "Git repository detected"

# Check for uncommitted changes
if ! git diff-index --quiet HEAD -- 2>/dev/null; then
    print_warning "You have uncommitted changes"
    echo ""
    git status --short
    echo ""
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Deployment cancelled"
        exit 1
    fi
else
    print_success "Working directory clean"
fi

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
print_info "Current branch: $CURRENT_BRANCH"

# =============================================================================
# Install Dependencies
# =============================================================================

print_header "2/7 Installing Dependencies"

if [ -f "composer.json" ]; then
    print_info "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    print_success "Composer dependencies installed"
else
    print_warning "No composer.json found"
fi

if [ -f "package.json" ]; then
    print_info "Installing Node dependencies..."
    npm ci --silent
    print_success "Node dependencies installed"
else
    print_warning "No package.json found"
fi

# =============================================================================
# Run Tests
# =============================================================================

print_header "3/7 Running Tests"

if [ -f "phpunit.xml" ] || [ -f "phpunit.xml.dist" ]; then
    print_info "Running PHPUnit/Pest tests..."
    if php artisan test --compact; then
        print_success "All tests passed"
    else
        print_error "Tests failed"
        read -p "Continue deployment despite test failures? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_error "Deployment cancelled"
            exit 1
        fi
    fi
else
    print_warning "No test configuration found"
fi

# =============================================================================
# Code Quality
# =============================================================================

print_header "4/7 Code Quality Checks"

# Run Pint
if [ -f "vendor/bin/pint" ]; then
    print_info "Running Laravel Pint..."
    if vendor/bin/pint --format agent; then
        print_success "Code formatted"
    else
        print_warning "Pint encountered issues"
    fi
else
    print_warning "Laravel Pint not installed"
fi

# Check for PHP errors
print_info "Checking for PHP syntax errors..."
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
print_success "No configuration errors"

# =============================================================================
# Build Assets
# =============================================================================

print_header "5/7 Building Production Assets"

if [ -f "vite.config.ts" ] || [ -f "vite.config.js" ]; then
    print_info "Building frontend assets with Vite..."
    npm run build
    print_success "Assets built successfully"
else
    print_warning "No Vite configuration found"
fi

# =============================================================================
# Prepare Google Cloud Credentials
# =============================================================================

print_header "6/7 Google Cloud Credentials"

if [ -f "storage/app/google-credentials.json" ]; then
    print_success "Google credentials file found"

    # Validate source JSON first
    if command -v jq > /dev/null 2>&1; then
        print_info "Validating source credentials..."
        if ! jq empty storage/app/google-credentials.json 2>/dev/null; then
            print_error "Source credentials file is invalid JSON!"
            print_info "Please fix storage/app/google-credentials.json first"
            exit 1
        fi
        print_success "Source credentials are valid JSON"

        # Create minified version
        print_info "Creating minified credentials..."
        jq -c . storage/app/google-credentials.json > google-credentials-minified.txt

        # Validate minified version
        print_info "Validating minified credentials..."
        if ! jq empty google-credentials-minified.txt 2>/dev/null; then
            print_error "Minified credentials are invalid!"
            exit 1
        fi
        print_success "Minified credentials validated"

        # Show character count
        CHAR_COUNT=$(wc -c < google-credentials-minified.txt | tr -d ' ')
        print_info "Minified JSON size: $CHAR_COUNT characters"

        # Copy to clipboard on macOS
        if [[ "$OSTYPE" == "darwin"* ]]; then
            cat google-credentials-minified.txt | pbcopy
            print_success "Credentials copied to clipboard!"
            echo ""
            print_warning "IMPORTANT: When pasting in Laravel Cloud:"
            echo "   • DO NOT add extra quotes around the JSON"
            echo "   • Paste the ENTIRE string (${CHAR_COUNT} characters)"
            echo "   • Make sure nothing is truncated"
        else
            print_success "Credentials minified: google-credentials-minified.txt"
        fi
    else
        print_warning "jq not installed - skipping credential preparation"
        print_info "Install jq: brew install jq (macOS) or apt install jq (Linux)"
    fi
else
    print_warning "Google credentials not found at storage/app/google-credentials.json"
fi

# =============================================================================
# Git Operations
# =============================================================================

print_header "7/7 Git Operations"

# Show git status
echo ""
git status --short
echo ""

# Ask to commit changes
if ! git diff-index --quiet HEAD -- 2>/dev/null; then
    read -p "Commit changes before pushing? (Y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
        read -p "Commit message: " COMMIT_MSG
        if [ -z "$COMMIT_MSG" ]; then
            COMMIT_MSG="Deploy to cloud.laravel.com"
        fi

        git add .
        git commit -m "$COMMIT_MSG"
        print_success "Changes committed"
    fi
fi

# Ask to push
read -p "Push to origin/$CURRENT_BRANCH? (Y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
    if git push origin "$CURRENT_BRANCH"; then
        print_success "Pushed to origin/$CURRENT_BRANCH"
    else
        print_error "Push failed"
        print_info "You may need to push manually"
    fi
else
    print_warning "Skipped push to remote"
fi

# =============================================================================
# Deployment Summary
# =============================================================================

print_header "Deployment Summary"

echo ""
echo -e "${GREEN}✨ Pre-deployment tasks completed!${NC}"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo ""
echo "1. Go to: https://cloud.laravel.com"
echo "2. Select your project"
echo "3. Navigate to Environment Variables"
echo ""
echo -e "${YELLOW}Required Environment Variables:${NC}"
echo ""
echo "   GOOGLE_CREDENTIALS_JSON"
if [[ "$OSTYPE" == "darwin"* ]] && [ -f "google-credentials-minified.txt" ]; then
    echo "   └─ Paste from clipboard (⌘+V)"
    echo "   └─ DO NOT add quotes around it"
    echo "   └─ Paste the entire string"
else
    echo "   └─ Contents of google-credentials-minified.txt"
fi
echo ""
echo "   GOOGLE_VISION_ENABLED"
echo "   └─ true"
echo ""
echo "   APP_ENV"
echo "   └─ production"
echo ""
echo "   APP_DEBUG"
echo "   └─ false"
echo ""
echo -e "${RED}⚠️  Common Mistakes to Avoid:${NC}"
echo "   ❌ DO NOT wrap JSON in quotes: \"{'type':...}\""
echo "   ❌ DO NOT truncate the JSON (must be complete)"
echo "   ❌ DO NOT use GOOGLE_APPLICATION_CREDENTIALS in cloud"
echo "   ✅ Paste raw JSON: {'type':'service_account',...}"
echo ""
echo "4. Click 'Deploy' in Laravel Cloud dashboard"
echo ""

if [ -f "CLOUD_LARAVEL_SETUP.md" ]; then
    echo -e "${BLUE}📖 Detailed instructions: CLOUD_LARAVEL_SETUP.md${NC}"
fi

if [ -f "DEPLOYMENT_CHECKLIST.md" ]; then
    echo -e "${BLUE}✅ Deployment checklist: DEPLOYMENT_CHECKLIST.md${NC}"
fi

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  Ready for deployment! 🚀${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
