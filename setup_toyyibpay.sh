#!/bin/bash

# ToyyibPay Integration Setup Script
# This script helps set up ToyyibPay integration for the Wedding Management System

echo "==============================================="
echo "   ToyyibPay Integration Setup Script"
echo "==============================================="
echo

# Function to prompt for user input
prompt_input() {
    local prompt="$1"
    local var_name="$2"
    local default_value="$3"
    
    if [ -n "$default_value" ]; then
        read -p "$prompt [$default_value]: " input
        eval "$var_name=\"${input:-$default_value}\""
    else
        read -p "$prompt: " input
        eval "$var_name=\"$input\""
    fi
}

# Function to generate random string
generate_random_string() {
    local length=$1
    openssl rand -base64 $length | tr -d "=+/" | cut -c1-$length 2>/dev/null || \
    cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w $length | head -n 1
}

echo "This script will help you configure ToyyibPay integration."
echo "You'll need your ToyyibPay credentials from https://dev.toyyibpay.com"
echo

# Get configuration values
prompt_input "Enter your ToyyibPay Secret Key" SECRET_KEY ""
prompt_input "Enter your ToyyibPay Category Code" CATEGORY_CODE ""
prompt_input "Enter your base URL (e.g., https://yourdomain.com/wedding-management-system)" BASE_URL "https://shazwan-danial.my/wedding-management-system"
prompt_input "Environment (sandbox/production)" ENVIRONMENT "sandbox"

# Generate callback secret if not provided
CALLBACK_SECRET=$(generate_random_string 32)
echo "Generated callback secret: $CALLBACK_SECRET"

# Set base API URL based on environment
if [ "$ENVIRONMENT" = "production" ]; then
    API_BASE_URL="https://toyyibpay.com"
else
    API_BASE_URL="https://dev.toyyibpay.com"
fi

echo
echo "Configuration Summary:"
echo "====================="
echo "Environment: $ENVIRONMENT"
echo "API Base URL: $API_BASE_URL"
echo "Secret Key: ${SECRET_KEY:0:10}..."
echo "Category Code: $CATEGORY_CODE"
echo "Base URL: $BASE_URL"
echo "Callback Secret: ${CALLBACK_SECRET:0:10}..."
echo

# Confirm before proceeding
read -p "Do you want to proceed with this configuration? (y/N): " confirm
if [[ ! $confirm =~ ^[Yy]$ ]]; then
    echo "Setup cancelled."
    exit 1
fi

# Create backup of original toyyibpay.php if it exists
if [ -f "includes/toyyibpay.php" ]; then
    cp includes/toyyibpay.php includes/toyyibpay.php.backup.$(date +%Y%m%d_%H%M%S)
    echo "Created backup of original toyyibpay.php"
fi

# Update toyyibpay.php configuration
echo "Updating ToyyibPay configuration..."

# Create temporary sed script
cat > /tmp/toyyibpay_config.sed << EOF
s/sandbox_secret_key_here/$SECRET_KEY/g
s/sandbox_category_code_here/$CATEGORY_CODE/g
s|https://dev.toyyibpay.com|$API_BASE_URL|g
s/your_callback_secret_here/$CALLBACK_SECRET/g
EOF

# Apply configuration
if [ -f "includes/toyyibpay.php" ]; then
    sed -f /tmp/toyyibpay_config.sed includes/toyyibpay.php > includes/toyyibpay.php.tmp
    mv includes/toyyibpay.php.tmp includes/toyyibpay.php
    echo "✓ Updated includes/toyyibpay.php"
else
    echo "✗ includes/toyyibpay.php not found"
fi

# Clean up
rm -f /tmp/toyyibpay_config.sed

# Create environment configuration file
cat > .env.toyyibpay << EOF
# ToyyibPay Configuration
# Generated on $(date)

TOYYIBPAY_ENVIRONMENT=$ENVIRONMENT
TOYYIBPAY_BASE_URL=$API_BASE_URL
TOYYIBPAY_SECRET_KEY=$SECRET_KEY
TOYYIBPAY_CATEGORY_CODE=$CATEGORY_CODE
TOYYIBPAY_CALLBACK_SECRET=$CALLBACK_SECRET
TOYYIBPAY_RETURN_URL=$BASE_URL/customer/payment-return.php
TOYYIBPAY_CALLBACK_URL=$BASE_URL/includes/toyyibpay-callback.php

# Important URLs for ToyyibPay Dashboard:
# Return URL: $BASE_URL/customer/payment-return.php
# Callback URL: $BASE_URL/includes/toyyibpay-callback.php
EOF

echo "✓ Created .env.toyyibpay configuration file"

# Database setup
echo
echo "Database Setup"
echo "=============="
read -p "Do you want to run the database updates now? (y/N): " run_db_update

if [[ $run_db_update =~ ^[Yy]$ ]]; then
    echo "Please ensure your database credentials are correct in includes/config.php"
    
    # Try to detect database credentials from config.php
    if [ -f "includes/config.php" ]; then
        DB_HOST=$(grep "define('DB_HOST'" includes/config.php | cut -d"'" -f4)
        DB_NAME=$(grep "define('DB_NAME'" includes/config.php | cut -d"'" -f4)
        DB_USER=$(grep "define('DB_USER'" includes/config.php | cut -d"'" -f4)
        
        echo "Detected database configuration:"
        echo "Host: $DB_HOST"
        echo "Database: $DB_NAME"
        echo "User: $DB_USER"
        echo
        
        read -p "Enter database password: " -s DB_PASS
        echo
        
        # Run database updates
        if command -v mysql >/dev/null 2>&1; then
            echo "Running database updates..."
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < toyyibpay_database_updates.sql
            if [ $? -eq 0 ]; then
                echo "✓ Database updates completed successfully"
            else
                echo "✗ Database update failed. Please run manually:"
                echo "mysql -h$DB_HOST -u$DB_USER -p $DB_NAME < toyyibpay_database_updates.sql"
            fi
        else
            echo "MySQL client not found. Please run database updates manually:"
            echo "mysql -h$DB_HOST -u$DB_USER -p $DB_NAME < toyyibpay_database_updates.sql"
        fi
    else
        echo "includes/config.php not found. Please run database updates manually."
    fi
fi

# Set proper permissions
echo
echo "Setting file permissions..."
chmod 644 includes/toyyibpay.php
chmod 644 includes/toyyibpay-callback.php
chmod 644 customer/payment.php
chmod 644 customer/payment-return.php
chmod 644 admin/toyyibpay-management.php
chmod 600 .env.toyyibpay
echo "✓ File permissions set"

# Create test script
cat > test_toyyibpay.php << 'EOF'
<?php
/**
 * ToyyibPay Integration Test Script
 * Run this script to test your ToyyibPay configuration
 */

require_once 'includes/config.php';
require_once 'includes/toyyibpay.php';

echo "<h2>ToyyibPay Integration Test</h2>";

try {
    $toyyibpay = new ToyyibPay();
    echo "<p>✓ ToyyibPay class loaded successfully</p>";
    
    // Test bill creation with dummy data
    $testBillParams = [
        'billName' => 'Test Payment',
        'billDescription' => 'Integration test payment',
        'billAmount' => 10.00, // RM 10.00 for testing
        'billExternalReferenceNo' => 'TEST_' . time(),
        'billTo' => 'Test Customer',
        'billEmail' => 'test@example.com',
        'billPhone' => '60123456789'
    ];
    
    echo "<h3>Testing Bill Creation</h3>";
    $result = $toyyibpay->createBill($testBillParams);
    
    if ($result['success']) {
        echo "<p>✓ Test bill created successfully</p>";
        echo "<p>Bill Code: " . $result['billCode'] . "</p>";
        echo "<p>Payment URL: <a href='" . $result['paymentUrl'] . "' target='_blank'>" . $result['paymentUrl'] . "</a></p>";
        echo "<p><strong>Note:</strong> This is a test bill. You can use it to test the payment flow.</p>";
    } else {
        echo "<p>✗ Failed to create test bill</p>";
        echo "<p>Error: " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Configuration Check</h3>";
echo "<p>Make sure to configure the following URLs in your ToyyibPay dashboard:</p>";
echo "<ul>";
echo "<li><strong>Return URL:</strong> " . SITE_URL . "customer/payment-return.php</li>";
echo "<li><strong>Callback URL:</strong> " . SITE_URL . "includes/toyyibpay-callback.php</li>";
echo "</ul>";

echo "<p><a href='admin/toyyibpay-management.php'>Go to ToyyibPay Management</a></p>";
?>
EOF

echo "✓ Created test_toyyibpay.php test script"

echo
echo "==============================================="
echo "           Setup Complete!"
echo "==============================================="
echo
echo "Next Steps:"
echo "1. Configure the following URLs in your ToyyibPay dashboard:"
echo "   - Return URL: $BASE_URL/customer/payment-return.php"
echo "   - Callback URL: $BASE_URL/includes/toyyibpay-callback.php"
echo
echo "2. Test the integration:"
echo "   - Visit: $BASE_URL/test_toyyibpay.php"
echo "   - Or go to Admin > ToyyibPay Management"
echo
echo "3. Configuration files created:"
echo "   - .env.toyyibpay (keep this secure!)"
echo "   - test_toyyibpay.php (for testing)"
echo
echo "4. For production:"
echo "   - Change environment to 'production'"
echo "   - Update base URL to https://toyyibpay.com"
echo "   - Get production credentials from ToyyibPay"
echo "   - Test thoroughly before going live"
echo
echo "Need help? Check docs/toyyibpay-integration.md for detailed documentation."
echo "==============================================="