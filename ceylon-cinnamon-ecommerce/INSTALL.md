# Ceylon Cinnamon E-commerce - Installation Guide

This guide provides step-by-step instructions for installing and configuring the Ceylon Cinnamon e-commerce website on XAMPP (Windows/Mac) or LAMP (Linux) environments.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [XAMPP Installation (Windows/Mac)](#xampp-installation-windowsmac)
3. [LAMP Installation (Linux)](#lamp-installation-linux)
4. [Database Setup](#database-setup)
5. [Application Configuration](#application-configuration)
6. [Payment Gateway Setup](#payment-gateway-setup)
7. [Email Configuration](#email-configuration)
8. [File Permissions](#file-permissions)
9. [Testing the Installation](#testing-the-installation)
10. [Production Deployment](#production-deployment)
11. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.3+)
- **Web Server**: Apache 2.4+ with mod_rewrite enabled
- **Memory**: 256MB RAM minimum (512MB recommended)
- **Disk Space**: 100MB for application + space for uploads

### Required PHP Extensions

- `pdo_mysql` - Database connectivity
- `mbstring` - Multi-byte string support
- `json` - JSON encoding/decoding
- `openssl` - SSL/TLS support
- `curl` - HTTP requests (for payment gateways)
- `fileinfo` - File type detection
- `gd` or `imagick` - Image processing (optional)

---

## XAMPP Installation (Windows/Mac)

### Step 1: Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Run the installer and select at minimum:
   - Apache
   - MySQL
   - PHP
   - phpMyAdmin
3. Complete the installation

### Step 2: Start Services

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Verify services are running (green indicators)

### Step 3: Enable Required PHP Extensions

1. Open `C:\xampp\php\php.ini` (Windows) or `/Applications/XAMPP/xamppfiles/etc/php.ini` (Mac)
2. Uncomment (remove `;`) the following lines:
   ```ini
   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   ```
3. Restart Apache from XAMPP Control Panel

### Step 4: Enable mod_rewrite

1. Open `C:\xampp\apache\conf\httpd.conf`
2. Find and uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
3. Find `<Directory "C:/xampp/htdocs">` section
4. Change `AllowOverride None` to `AllowOverride All`
5. Restart Apache

### Step 5: Deploy Application

1. Copy the `ceylon-cinnamon-ecommerce` folder to `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
2. The application should be accessible at `http://localhost/ceylon-cinnamon-ecommerce/public/`

---

## LAMP Installation (Linux)

### Ubuntu/Debian

```bash
# Update package list
sudo apt update

# Install Apache, MySQL, PHP and required extensions
sudo apt install apache2 mysql-server php8.1 php8.1-mysql php8.1-mbstring php8.1-curl php8.1-xml php8.1-gd libapache2-mod-php8.1

# Enable mod_rewrite
sudo a2enmod rewrite

# Restart Apache
sudo systemctl restart apache2
```

### CentOS/RHEL

```bash
# Install EPEL and Remi repositories
sudo dnf install epel-release
sudo dnf install https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Enable PHP 8.1
sudo dnf module enable php:remi-8.1

# Install packages
sudo dnf install httpd mysql-server php php-mysqlnd php-mbstring php-curl php-xml php-gd

# Start and enable services
sudo systemctl start httpd mysqld
sudo systemctl enable httpd mysqld
```

### Deploy Application

```bash
# Copy application to web root
sudo cp -r ceylon-cinnamon-ecommerce /var/www/html/

# Set ownership
sudo chown -R www-data:www-data /var/www/html/ceylon-cinnamon-ecommerce  # Ubuntu/Debian
sudo chown -R apache:apache /var/www/html/ceylon-cinnamon-ecommerce      # CentOS/RHEL
```

---

## Database Setup

### Step 1: Create Database

#### Using phpMyAdmin

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" in the left sidebar
3. Enter database name: `ceylon_cinnamon`
4. Select collation: `utf8mb4_unicode_ci`
5. Click "Create"

#### Using Command Line

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE ceylon_cinnamon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create dedicated user (recommended for production)
CREATE USER 'ceylon_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ceylon_cinnamon.* TO 'ceylon_user'@'localhost';
FLUSH PRIVILEGES;

EXIT;
```

### Step 2: Import Schema

```bash
# Navigate to application directory
cd /path/to/ceylon-cinnamon-ecommerce

# Import schema
mysql -u root -p ceylon_cinnamon < sql/schema.sql

# Import seed data (optional, for testing)
mysql -u root -p ceylon_cinnamon < sql/seed_data.sql
```

Or using phpMyAdmin:
1. Select `ceylon_cinnamon` database
2. Click "Import" tab
3. Choose `sql/schema.sql` file
4. Click "Go"
5. Repeat for `sql/seed_data.sql` if desired

---

## Application Configuration

### Step 1: Create Environment File

```bash
# Copy example environment file
cp .env.example .env
```

### Step 2: Configure Environment Variables

Edit `.env` file with your settings:

```ini
# Application Settings
APP_NAME="Ceylon Cinnamon"
APP_URL=http://localhost/ceylon-cinnamon-ecommerce/public
APP_ENV=development
APP_DEBUG=true

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ceylon_cinnamon
DB_USER=root
DB_PASS=

# For production, use dedicated user:
# DB_USER=ceylon_user
# DB_PASS=your_secure_password
```

### Step 3: Verify Configuration

1. Open `http://localhost/ceylon-cinnamon-ecommerce/public/` in your browser
2. You should see the homepage
3. If you imported seed data, try logging in with:
   - **Email**: admin@ceyloncinnamon.com
   - **Password**: Admin@123

---

## Payment Gateway Setup

### Stripe Configuration

#### Requirements: 4.1 (Stripe API integration in demo mode)

1. **Create Stripe Account**
   - Go to [https://dashboard.stripe.com/register](https://dashboard.stripe.com/register)
   - Complete registration

2. **Get API Keys**
   - Go to [https://dashboard.stripe.com/apikeys](https://dashboard.stripe.com/apikeys)
   - Copy your **Publishable key** (starts with `pk_test_`)
   - Copy your **Secret key** (starts with `sk_test_`)

3. **Configure in .env**
   ```ini
   STRIPE_PUBLIC_KEY=pk_test_your_publishable_key_here
   STRIPE_SECRET_KEY=sk_test_your_secret_key_here
   STRIPE_CURRENCY=usd
   ```

4. **Test Cards for Development**
   - Success: `4242 4242 4242 4242`
   - Decline: `4000 0000 0000 0002`
   - Requires Auth: `4000 0025 0000 3155`
   - Use any future expiry date and any 3-digit CVC

5. **Webhook Setup (Optional)**
   - Go to [https://dashboard.stripe.com/webhooks](https://dashboard.stripe.com/webhooks)
   - Add endpoint: `https://yourdomain.com/webhook/stripe`
   - Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
   - Copy webhook signing secret to `.env`:
     ```ini
     STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
     ```

### PayPal Configuration

#### Requirements: 4.2 (PayPal API integration in demo mode)

1. **Create PayPal Developer Account**
   - Go to [https://developer.paypal.com/](https://developer.paypal.com/)
   - Log in with your PayPal account or create one

2. **Create Sandbox Application**
   - Go to [https://developer.paypal.com/dashboard/applications/sandbox](https://developer.paypal.com/dashboard/applications/sandbox)
   - Click "Create App"
   - Enter app name: "Ceylon Cinnamon Store"
   - Click "Create App"

3. **Get Credentials**
   - Copy **Client ID**
   - Copy **Secret** (click "Show")

4. **Configure in .env**
   ```ini
   PAYPAL_CLIENT_ID=your_client_id_here
   PAYPAL_SECRET=your_secret_here
   PAYPAL_MODE=sandbox
   PAYPAL_CURRENCY=USD
   ```

5. **Sandbox Test Accounts**
   - Go to [https://developer.paypal.com/dashboard/accounts](https://developer.paypal.com/dashboard/accounts)
   - Use the default sandbox buyer account for testing
   - Or create new test accounts

### Bank Transfer Configuration

```ini
BANK_NAME=Bank of Ceylon
BANK_ACCOUNT_NAME=Ceylon Cinnamon Exports Ltd
BANK_ACCOUNT_NUMBER=1234567890
BANK_SWIFT_CODE=BABORLK
BANK_BRANCH=Colombo Main Branch
```

---

## Email Configuration

### Using Gmail SMTP

1. **Enable 2-Factor Authentication** on your Google account
2. **Generate App Password**:
   - Go to [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
   - Select "Mail" and your device
   - Copy the generated password

3. **Configure in .env**:
   ```ini
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=your-email@gmail.com
   SMTP_PASS=your-app-password
   SMTP_FROM_EMAIL=your-email@gmail.com
   SMTP_FROM_NAME="Ceylon Cinnamon"
   SMTP_ENCRYPTION=tls
   ```

### Using Mailgun

```ini
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_USER=postmaster@your-domain.mailgun.org
SMTP_PASS=your-mailgun-password
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME="Ceylon Cinnamon"
SMTP_ENCRYPTION=tls
```

### Local Development (Mailhog)

For local testing without sending real emails:

1. Install Mailhog: [https://github.com/mailhog/MailHog](https://github.com/mailhog/MailHog)
2. Configure:
   ```ini
   SMTP_HOST=localhost
   SMTP_PORT=1025
   SMTP_USER=
   SMTP_PASS=
   SMTP_ENCRYPTION=
   ```
3. View emails at `http://localhost:8025`

---

## File Permissions

### Linux/Mac

```bash
cd /path/to/ceylon-cinnamon-ecommerce

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make uploads directory writable
chmod -R 775 public/uploads

# Set ownership (adjust user/group as needed)
sudo chown -R www-data:www-data .  # Ubuntu/Debian
sudo chown -R apache:apache .       # CentOS/RHEL
```

### Windows (XAMPP)

XAMPP typically handles permissions automatically. If you encounter issues:
1. Right-click `public/uploads` folder
2. Properties → Security → Edit
3. Add "Everyone" with Full Control (development only)

---

## Testing the Installation

### 1. Homepage Test
- Visit: `http://localhost/ceylon-cinnamon-ecommerce/public/`
- Verify homepage loads with products

### 2. Admin Login Test
- Visit: `http://localhost/ceylon-cinnamon-ecommerce/public/admin`
- Login with:
  - Email: `admin@ceyloncinnamon.com`
  - Password: `Admin@123`

### 3. Product Catalog Test
- Browse products page
- Test category filtering
- View product details

### 4. Cart Test
- Add products to cart
- View cart
- Update quantities

### 5. Checkout Test (with test payment)
- Proceed to checkout
- Use Stripe test card: `4242 4242 4242 4242`
- Complete order

---

## Production Deployment

### Security Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `FORCE_HTTPS=true`
- [ ] Use strong database password
- [ ] Configure proper `ALLOWED_HOSTS`
- [ ] Use live payment gateway credentials
- [ ] Configure proper SMTP settings
- [ ] Set up SSL certificate
- [ ] Remove seed data / test accounts
- [ ] Configure proper file permissions
- [ ] Set up regular backups
- [ ] Enable error logging (not display)

### Apache Virtual Host Example

```apache
<VirtualHost *:443>
    ServerName ceyloncinnamon.com
    DocumentRoot /var/www/ceylon-cinnamon-ecommerce/public
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/ceylon-cinnamon-ecommerce/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ceylon-error.log
    CustomLog ${APACHE_LOG_DIR}/ceylon-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName ceyloncinnamon.com
    Redirect permanent / https://ceyloncinnamon.com/
</VirtualHost>
```

---

## Troubleshooting

### Common Issues

#### 1. "Page Not Found" or 404 Errors on Login/Routes

**Cause**: Incorrect URL path or mod_rewrite not enabled

**Solution**:

**For XAMPP/Local Development:**
- Use the full path: `http://localhost/ceylon-cinnamon-ecommerce/public/login`
- Or access via root redirect: `http://localhost/login` (if root redirect is set up)

**Check mod_rewrite:**
```bash
# Enable mod_rewrite
sudo a2enmod rewrite

# Check AllowOverride in Apache config
# Should be: AllowOverride All

# Restart Apache
sudo systemctl restart apache2
```

**For XAMPP on Windows:**
1. Open `C:\xampp\apache\conf\httpd.conf`
2. Find and uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
3. Find `<Directory "C:/xampp/htdocs">` section
4. Change `AllowOverride None` to `AllowOverride All`
5. Restart Apache from XAMPP Control Panel

#### 2. Database Connection Error

**Cause**: Wrong credentials or MySQL not running

**Solution**:
- Verify MySQL is running
- Check `.env` database credentials
- Test connection: `mysql -u root -p ceylon_cinnamon`

#### 3. Blank Page / 500 Error

**Cause**: PHP error with display_errors off

**Solution**:
- Check PHP error log: `/var/log/apache2/error.log`
- Temporarily set `APP_DEBUG=true` in `.env`
- Check PHP extensions are installed

#### 4. File Upload Errors

**Cause**: Permission issues or PHP limits

**Solution**:
```bash
# Fix permissions
chmod -R 775 public/uploads

# Check php.ini settings
upload_max_filesize = 10M
post_max_size = 12M
```

#### 5. Email Not Sending

**Cause**: SMTP configuration issues

**Solution**:
- Verify SMTP credentials
- Check firewall allows outbound port 587
- Test with Mailhog locally first
- Check spam folder

### Getting Help

If you encounter issues not covered here:
1. Check the error logs
2. Enable debug mode temporarily
3. Search existing issues on GitHub
4. Create a new issue with:
   - Error message
   - Steps to reproduce
   - Environment details (OS, PHP version, etc.)

---

## Default Credentials

After importing seed data, use these credentials:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ceyloncinnamon.com | Admin@123 |
| Content Manager | content@ceyloncinnamon.com | Admin@123 |
| Customer | customer@example.com | Admin@123 |
| Wholesale | wholesale@example.com | Admin@123 |

**⚠️ IMPORTANT**: Change these passwords immediately in production!

---

## Next Steps

1. ✅ Complete installation
2. 🔐 Change default passwords
3. 🎨 Customize branding and content
4. 📦 Add your products
5. 💳 Configure live payment gateways
6. 📧 Set up production email
7. 🚀 Deploy to production server
8. 🔒 Enable SSL/HTTPS
9. 📊 Set up analytics
10. 🔄 Configure backups

---

*Last updated: December 2024*
