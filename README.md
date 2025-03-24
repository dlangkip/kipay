# My Payment Gateway

A custom payment gateway built on top of Pesapal's API, designed for integration with phpnuxbill and other systems.

## Features

- **REST API**: Clean and well-documented API for integrating with any application
- **Pesapal Integration**: Leverages Pesapal's payment processing capabilities for African markets
- **phpnuxbill Module**: Seamless integration with phpnuxbill billing system
- **Secure**: Implements API key authentication and other security best practices
- **Customizable**: Easy to brand and modify for your specific needs
- **Comprehensive Logging**: Detailed logs for debugging and auditing
- **Webhook Support**: Real-time notifications when payment statuses change
- **Email Notifications**: Automatic email notifications for payment events

## Requirements

- PHP 7.2 or higher
- MySQL 5.7 or higher
- Composer
- SSL certificate (for production)
- Pesapal merchant account

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/my-payment-gateway.git
cd my-payment-gateway
```

### 2. Install dependencies

```bash
composer install
```

### 3. Create the database

Create a new MySQL database for your payment gateway, then import the database schema:

```bash
mysql -u username -p your_database_name < database/migrations/schema.sql
```

### 4. Configure the gateway

Copy the example configuration file and edit it with your settings:

```bash
cp config/app.example.php config/app.php
```

Edit `config/app.php` with your Pesapal API credentials, database settings, and other configuration values.

### 5. Set up web server

Configure your web server (Apache, Nginx) to point to the `public` directory as the document root.

Example Nginx configuration:

```nginx
server {
    listen 80;
    server_name payment-gateway.yourdomain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name payment-gateway.yourdomain.com;
    
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    
    root /path/to/my-payment-gateway/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # Adjust to your PHP version
    }
}
```

### 6. Set up phpnuxbill integration

Copy the phpnuxbill payment module to your phpnuxbill installation:

```bash
cp phpnuxbill-module/PaymentGateway.php /path/to/phpnuxbill/system/payments/
cp phpnuxbill-module/my_payment_logo.png /path/to/phpnuxbill/system/payments/
```

## API Documentation

### Authentication

All API requests must include an API key in the Authorization header:

```
Authorization: Bearer YOUR_API_KEY
```

### Endpoints

#### Create Payment

```
POST /api/payments
```

Request body:
```json
{
  "amount": 1000.00,
  "description": "Payment for Product XYZ",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "currency": "KES",
  "payment_metadata": {
    "order_id": "12345",
    "customer_id": "67890"
  }
}
```

Response:
```json
{
  "success": true,
  "message": "Payment created successfully",
  "data": {
    "reference": "PAY-12345-6789012345",
    "payment_url": "https://pesapal.com/payment/abc123",
    "status": "PENDING",
    "created_at": "2023-01-01 12:00:00",
    "currency": "KES",
    "amount": 1000.00,
    "description": "Payment for Product XYZ"
  }
}
```

#### Check Payment Status

```
GET /api/payments/status?reference=PAY-12345-6789012345
```

Response:
```json
{
  "success": true,
  "message": "Transaction status retrieved",
  "data": {
    "reference": "PAY-12345-6789012345",
    "status": "COMPLETED",
    "amount": 1000.00,
    "currency": "KES",
    "payment_method": "MPESA",
    "tracking_id": "PESAPAL-TRX-12345",
    "created_at": "2023-01-01 12:00:00",
    "updated_at": "2023-01-01 12:10:00",
    "checked_at": "2023-01-01 12:15:00"
  }
}
```

#### IPN (Instant Payment Notification) Endpoint

```
POST /api/webhook/ipn
```

This endpoint is called by Pesapal when a payment status changes. It does not require authentication as it uses Pesapal's verification mechanism.

## Benfex Integration

After installing the phpnuxbill module, you need to:

1. Log in to your phpnuxbill admin panel
2. Go to Settings > Payment Gateways
3. Enable "My Payment Gateway"
4. Configure the gateway settings:
   - API URL: Your payment gateway API URL (e.g., https://payment-gateway.yourdomain.com/api)
   - API Key: Your payment gateway API key
   - Currency Code: Default currency (e.g., KES)
   - Environment: sandbox or production

## Customization

### Branding

To customize the branding:

1. Update the gateway name and description in `config/app.php`
2. Replace the logo at `phpnuxbill-module/my_payment_logo.png`
3. Update email templates in the `templates` directory

### Adding Payment Methods

The gateway currently uses Pesapal's available payment methods. To add custom payment methods:

1. Extend the `Gateway` class with your new payment processor
2. Update the API to support the new payment method
3. Modify the phpnuxbill module to display the new payment option

## Security Considerations

- Always use HTTPS in production
- Keep your API keys secure
- Regularly update dependencies
- Monitor logs for suspicious activity
- Implement rate limiting for API endpoints

## Troubleshooting

Common issues and their solutions:

### API returns 401 Unauthorized

- Check if the API key is correct
- Ensure the API key is included in the Authorization header

### Payments not showing in phpnuxbill

- Check the IPN endpoint is correctly configured in Pesapal
- Verify the callback URL is accessible from the internet
- Check the logs for any errors

### Database connection errors

- Verify database credentials in config/app.php
- Ensure the database user has the necessary permissions

## Support

If you need help with this payment gateway, please contact:

- Email: support@yourdomain.com
- Phone: +1234567890

## License

This project is licensed under the MIT License - see the LICENSE file for details.