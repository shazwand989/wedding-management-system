# ToyyibPay Integration - Quick Start Guide

This guide provides step-by-step instructions to integrate ToyyibPay payment gateway into your Wedding Management System.

## 🚀 Quick Setup

### 1. Prerequisites
- ToyyibPay merchant account (register at https://dev.toyyibpay.com for sandbox)
- PHP 7.4+ with cURL and JSON extensions
- MySQL database
- HTTPS-enabled website (required for production)

### 2. Run Automated Setup
```bash
cd /var/www/shazwan-danial.my/public/wedding-management-system
./setup_toyyibpay.sh
```

Follow the prompts to enter your ToyyibPay credentials.

### 3. Manual Configuration (Alternative)

If you prefer manual setup:

1. **Update ToyyibPay Configuration**
   Edit `includes/toyyibpay.php` and replace:
   ```php
   $this->secretKey = 'your_actual_secret_key';
   $this->categoryCode = 'your_actual_category_code';
   $this->callbackSecret = 'generate_secure_random_string';
   ```

2. **Run Database Updates**
   ```sql
   mysql -u your_username -p your_database < toyyibpay_database_updates.sql
   ```

### 4. Configure ToyyibPay Dashboard

Login to your ToyyibPay dashboard and set:

- **Return URL**: `https://yourdomain.com/wedding-management-system/customer/payment-return.php`
- **Callback URL**: `https://yourdomain.com/wedding-management-system/includes/toyyibpay-callback.php`

### 5. Test Integration

Visit: `https://yourdomain.com/wedding-management-system/test_toyyibpay.php`

## 📁 Files Created/Modified

### New Files:
- `includes/toyyibpay.php` - Main ToyyibPay integration class
- `includes/toyyibpay-callback.php` - Webhook handler
- `customer/payment.php` - Payment page for customers
- `customer/payment-return.php` - Payment result page
- `admin/toyyibpay-management.php` - Admin management interface
- `toyyibpay_database_updates.sql` - Database schema updates
- `test_toyyibpay.php` - Integration test page

### Modified Files:
- `customer/bookings.php` - Added payment links

## 🔧 Configuration Options

### Environment Settings
```php
// Sandbox (Testing)
$this->baseUrl = 'https://dev.toyyibpay.com';

// Production (Live)
$this->baseUrl = 'https://toyyibpay.com';
```

### Security Settings
- Generate a strong callback secret (32+ characters)
- Use HTTPS for all payment URLs
- Validate all webhook callbacks
- Implement rate limiting on payment endpoints

## 💳 Payment Flow

1. **Customer initiates payment** → `customer/payment.php`
2. **System creates bill** → ToyyibPay API
3. **Customer redirected** → ToyyibPay payment page
4. **Payment processed** → Bank/Payment provider
5. **Webhook received** → `includes/toyyibpay-callback.php`
6. **Customer returns** → `customer/payment-return.php`
7. **Admin verification** → `admin/toyyibpay-management.php`

## 🛠️ Admin Features

### ToyyibPay Management (`admin/toyyibpay-management.php`)
- View payment statistics
- Verify bill status
- Manually sync payments
- View transaction logs
- Configuration guidance

### Payment Management (`admin/payments.php`)
- Enhanced with ToyyibPay tracking
- Transaction ID display
- Gateway-specific filtering
- Status management

## 🧪 Testing Guide

### Sandbox Testing
1. Create ToyyibPay sandbox account
2. Use sandbox credentials in configuration
3. Test with small amounts (RM 1.00 - RM 10.00)
4. Verify webhook delivery
5. Check payment status updates

### Test Cases
- [ ] Successful payment flow
- [ ] Failed/cancelled payments
- [ ] Partial payments
- [ ] Duplicate payment prevention
- [ ] Webhook callback handling
- [ ] Return URL handling
- [ ] Admin verification tools

## 🔐 Security Best Practices

### Webhook Security
```php
// Verify webhook authenticity
if (!$toyyibpay->verifyCallback($callbackData)) {
    http_response_code(400);
    exit('Invalid callback');
}
```

### Duplicate Prevention
- Database unique constraints on transaction IDs
- File-based locking for callback processing
- Idempotent payment processing

### Data Protection
- Never log sensitive payment data
- Encrypt stored configuration values
- Use environment variables for credentials
- Implement proper access controls

## 🚨 Troubleshooting

### Common Issues

1. **"Failed to create bill"**
   - Check API credentials
   - Verify internet connectivity
   - Check ToyyibPay service status

2. **"Callback not received"**
   - Verify callback URL is accessible
   - Check server logs for errors
   - Ensure HTTPS is working

3. **"Payment not updated"**
   - Check webhook processing logs
   - Verify transaction ID matching
   - Use manual sync tool in admin

### Debug Tools
```php
// Enable debug logging
error_log("ToyyibPay Debug: " . json_encode($data));

// Test webhook manually
curl -X POST https://yourdomain.com/includes/toyyibpay-callback.php \
     -d "billcode=TEST123&order_id=1"
```

## 📊 Monitoring & Analytics

### Key Metrics to Track
- Payment success rate
- Average processing time
- Failed payment reasons
- Customer drop-off points
- Revenue per payment method

### Log Analysis
Check these log files:
- PHP error logs
- Web server access logs
- Database slow query logs
- ToyyibPay webhook logs

## 🔄 Going Live Checklist

### Pre-Production
- [ ] Update to production credentials
- [ ] Change base URL to production
- [ ] Test with real small amounts
- [ ] Verify webhook URLs are accessible
- [ ] Set up monitoring and alerts
- [ ] Backup database
- [ ] Document rollback procedure

### Production Monitoring
- [ ] Monitor payment success rates
- [ ] Set up error alerting
- [ ] Regular reconciliation with ToyyibPay
- [ ] Customer support procedures
- [ ] Performance monitoring

## 📞 Support

### ToyyibPay Support
- Documentation: https://docs.toyyibpay.com
- Support: support@toyyibpay.com
- Sandbox: https://dev.toyyibpay.com

### Integration Support
For issues with this integration:
1. Check the troubleshooting section
2. Review server error logs
3. Test with the debug tools
4. Contact your development team

## 📝 Changelog

### Version 1.0.0
- Initial ToyyibPay integration
- Payment processing workflow
- Admin management interface
- Webhook handling
- Security implementations
- Testing tools

---

**Important**: Always test thoroughly in sandbox mode before deploying to production. Keep your API credentials secure and never commit them to version control.