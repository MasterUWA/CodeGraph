#!/bin/bash

# PHP Graph Builder Setup Script
# This script initializes the application for first run

echo "🚀 PHP Graph Builder Setup"
echo "============================"
echo ""

# Create storage directory
echo "📁 Creating storage directory..."
mkdir -p storage
chmod 755 storage
echo "✅ Storage directory created"
echo ""

# Check PHP version
echo "🔍 Checking PHP version..."
PHP_VERSION=$(php -r 'echo phpversion();')
echo "✅ PHP Version: $PHP_VERSION"
echo ""

# Check required extensions
echo "🔍 Checking required extensions..."
php -r "
\$extensions = ['json', 'pdo', 'pdo_mysql'];
foreach (\$extensions as \$ext) {
    if (extension_loaded(\$ext)) {
        echo \"✅ \$ext installed\n\";
    } else {
        echo \"⚠️  \$ext not installed (optional)\n\";
    }
}
"
echo ""

# Install composer dependencies
if command -v composer &> /dev/null; then
    echo "📦 Installing composer dependencies..."
    composer install
    echo "✅ Dependencies installed"
else
    echo "⚠️  Composer not found. Please install dependencies manually with: composer install"
fi
echo ""

echo "✅ Setup complete!"
echo ""
echo "To start the server, run:"
echo "  composer start"
echo ""
echo "Then open: http://localhost:8080"
