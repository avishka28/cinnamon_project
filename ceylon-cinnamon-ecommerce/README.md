# Ceylon Cinnamon E-commerce Website

A complete, production-ready e-commerce website for selling premium Ceylon cinnamon products. Built with PHP 8, MySQL, and Bootstrap 5 following MVC architecture.

![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)
![License](https://img.shields.io/badge/License-MIT-green)

## Features

### 🛒 E-commerce Functionality
- Product catalog with categories, filtering, and search
- Shopping cart with session persistence
- Guest and registered user checkout
- Order tracking and history
- Wishlist functionality

### 💳 Payment Processing
- **Stripe** integration (demo/live mode)
- **PayPal** integration (sandbox/live mode)
- Bank transfer option
- Secure payment handling (PCI compliant)

### 👤 User Management
- Customer registration and login
- Role-based access control (Admin, Content Manager, Customer)
- Wholesale customer support
- Profile and address management

### 📦 Admin Dashboard
- Product management (CRUD, bulk import)
- Order management and fulfillment
- Content management (blog, certificates, gallery)
- Shipping zone and rate configuration
- Sales analytics and reporting

### 🌐 Multi-language Support
- English and Sinhala languages
- Easy translation system
- Language preference persistence

### 🔒 Security Features
- CSRF protection on all forms
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- Secure session management
- Password hashing (bcrypt)
- File upload validation

### 📧 Email Notifications
- Order confirmation emails
- Shipping notifications
- Status update alerts
- Wholesale inquiry notifications

### 🔍 SEO Optimization
- Meta tags and Open Graph data
- JSON-LD structured data
- Sitemap generation
- SEO-friendly URLs

## Quick Start

### Prerequisites

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite
- Composer (for dependencies)

### Installation

1. **Clone or download the repository**
   ```bash
   git clone https://github.com/yourusername/ceylon-cinnamon-ecommerce.git
   cd ceylon-cinnamon-ecommerce
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Create database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE ceylon_cinnamon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   ```

4. **Import schema and seed data**
   ```bash
   mysql -u root -p ceylon_cinnamon < sql/schema.sql
   mysql -u root -p ceylon_cinnamon < sql/seed_data.sql
   ```

5. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

6. **Set permissions**
   ```bash
   chmod -R 775 public/uploads
   ```

7. **Access the application**
   - Frontend: `http://localhost/ceylon-cinnamon-ecommerce/public/`
   - Admin: `http://localhost/ceylon-cinnamon-ecommerce/public/admin`

### Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ceyloncinnamon.com | Admin@123 |
| Content Manager | content@ceyloncinnamon.com | Admin@123 |
| Customer | customer@example.com | Admin@123 |

⚠️ **Change these passwords immediately in production!**

## Documentation

- [Installation Guide](INSTALL.md) - Detailed setup instructions
- [Translation Guide](TRANSLATION_GUIDE.md) - Adding new languages
- [API Documentation](docs/API.md) - REST API endpoints

## Project Structure

```
ceylon-cinnamon-ecommerce/
├── config/             # Configuration files
├── controllers/        # MVC Controllers
│   └── admin/         # Admin controllers
├── models/            # Database models
├── views/             # View templates
│   ├── admin/        # Admin views
│   ├── auth/         # Authentication views
│   ├── layouts/      # Layout templates
│   └── pages/        # Public pages
├── includes/          # Helper classes and utilities
├── public/            # Web root
│   ├── assets/       # CSS, JS, images
│   └── uploads/      # User uploads
├── sql/               # Database schema and seeds
├── tests/             # PHPUnit tests
└── lang/              # Translation files
```

## Configuration

### Environment Variables

Key configuration options in `.env`:

```ini
# Application
APP_ENV=development|production
APP_DEBUG=true|false
APP_URL=http://localhost

# Database
DB_HOST=localhost
DB_NAME=ceylon_cinnamon
DB_USER=root
DB_PASS=

# Payment Gateways
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
PAYPAL_CLIENT_ID=...
PAYPAL_SECRET=...
PAYPAL_MODE=sandbox|live

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

See [.env.example](.env.example) for all available options.

## Testing

Run the test suite:

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite Property

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

## Payment Gateway Testing

### Stripe Test Cards

| Card Number | Description |
|-------------|-------------|
| 4242 4242 4242 4242 | Successful payment |
| 4000 0000 0000 0002 | Card declined |
| 4000 0025 0000 3155 | Requires authentication |

### PayPal Sandbox

Use PayPal sandbox accounts from the [Developer Dashboard](https://developer.paypal.com/dashboard/accounts).

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security

If you discover a security vulnerability, please send an email to security@ceyloncinnamon.com instead of using the issue tracker.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- [Bootstrap](https://getbootstrap.com/) - Frontend framework
- [Stripe](https://stripe.com/) - Payment processing
- [PayPal](https://developer.paypal.com/) - Payment processing
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Email sending
- [Eris](https://github.com/giorgiosironi/eris) - Property-based testing

---

Made with ❤️ in Sri Lanka 🇱🇰
