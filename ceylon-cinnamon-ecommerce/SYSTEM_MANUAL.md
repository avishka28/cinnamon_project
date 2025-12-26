# Ceylon Cinnamon E-commerce System Manual

## Quick Start URLs

**Base URL:** `http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public`

---

## ⚠️ IMPORTANT: Database Setup (Run First!)

Before using the system, you MUST set up the database with seed data:

### Option 1: Browser Setup (Recommended)
1. Open your browser and go to:
   ```
   http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/setup_database.php
   ```
2. Click "Yes, Import Seed Data Anyway" if prompted
3. Wait for the setup to complete
4. You should see "Database setup completed successfully!"

### Option 2: Manual MySQL Import
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database: `ceylon_cinnamon`
3. Import schema: `sql/schema.sql`
4. Import seed data: `sql/seed_data.sql`

### Option 3: Command Line
```bash
cd C:\xampp\htdocs\Cinnamon\cinnamon_project\ceylon-cinnamon-ecommerce
C:\xampp\mysql\bin\mysql -u root -p ceylon_cinnamon < sql/schema.sql
C:\xampp\mysql\bin\mysql -u root -p ceylon_cinnamon < sql/seed_data.sql
```

---

## Public Pages (No Login Required)

| Page | URL |
|------|-----|
| Home | `/` |
| Products | `/products` |
| Product Details | `/products/{slug}` |
| Category | `/category/{slug}` |
| Blog | `/blog` |
| Blog Post | `/blog/{slug}` |
| Blog Category | `/blog/category/{slug}` |
| About Us | `/about` |
| Contact | `/contact` |
| Privacy Policy | `/privacy` |
| Terms & Conditions | `/terms` |
| Shipping Info | `/shipping` |
| Wholesale | `/wholesale` |
| Certificates | `/certificates` |
| Gallery | `/gallery` |
| Cart | `/cart` |
| Checkout | `/checkout` |
| Order Tracking | `/order/track` |

---

## Authentication URLs

| Page | URL |
|------|-----|
| Login | `/login` |
| Register | `/register` |
| Logout | `/logout` |

---

## Customer Dashboard (Login Required)

| Page | URL |
|------|-----|
| Dashboard | `/dashboard` |
| My Orders | `/dashboard/orders` |
| Order Details | `/dashboard/orders/{id}` |
| Profile | `/dashboard/profile` |
| Addresses | `/dashboard/addresses` |

---

## Admin Panel URLs

### Admin Authentication
| Page | URL |
|------|-----|
| Admin Login | `/admin/login` |
| Admin Logout | `/admin/logout` |
| Admin Dashboard | `/admin` |

### Product Management
| Page | URL |
|------|-----|
| Products List | `/admin/products` |
| Create Product | `/admin/products/create` |
| Edit Product | `/admin/products/{id}/edit` |
| Import Products | `/admin/products/import` |
| Download Template | `/admin/products/template` |

### Order Management
| Page | URL |
|------|-----|
| Orders List | `/admin/orders` |
| Order Details | `/admin/orders/{id}` |
| Order Invoice | `/admin/orders/{id}/invoice` |

### Shipping Management
| Page | URL |
|------|-----|
| Shipping Zones | `/admin/shipping` |
| Create Zone | `/admin/shipping/zones/create` |
| Edit Zone | `/admin/shipping/zones/{id}/edit` |
| Create Method | `/admin/shipping/zones/{id}/methods/create` |
| Edit Method | `/admin/shipping/methods/{id}/edit` |

### Content Management
| Page | URL |
|------|-----|
| Blog Posts | `/admin/content/posts` |
| Create Post | `/admin/content/posts/create` |
| Edit Post | `/admin/content/posts/{id}/edit` |
| Blog Categories | `/admin/content/categories` |
| Certificates | `/admin/content/certificates` |
| Gallery | `/admin/content/gallery` |

---

## API Endpoints

### Products API
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | List all products |
| GET | `/api/products/{id}` | Get product details |
| GET | `/api/categories` | List all categories |

### Cart API
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/cart` | Get cart contents |
| POST | `/api/cart/add` | Add item to cart |
| POST | `/api/cart/update` | Update cart item |
| DELETE | `/api/cart/{id}` | Remove item from cart |

### Shipping API
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/shipping/rates` | Get shipping rates |
| GET | `/api/shipping/delivery-estimate` | Get delivery estimate |

### Wholesale API
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/wholesale/pricing/{id}` | Get wholesale pricing |

---

## Default User Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@ceyloncinnamon.com | password |
| Content Manager | content@ceyloncinnamon.com | password |
| Customer | customer@example.com | password |
| Wholesale | wholesale@example.com | password |

---

## Full URL Examples

Your application is located at: `C:\xampp\htdocs\Cinnamon\cinnamon_project\ceylon-cinnamon-ecommerce\`

```
Home:           http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/
Login:          http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/login
Register:       http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/register
Products:       http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/products
Cart:           http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/cart
Checkout:       http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/checkout
Dashboard:      http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/dashboard
Admin:          http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/admin
Admin Login:    http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public/admin/login
```

---

## SEO URLs

| File | URL |
|------|-----|
| Sitemap | `/sitemap.xml` |
| Robots.txt | `/robots.txt` |

---

## Troubleshooting

### Products Not Showing on Home Page
1. **Run database setup first!** Go to:
   ```
   http://localhost/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/setup_database.php
   ```
2. Verify products exist in database via phpMyAdmin
3. Check that products have `is_active = 1` and `stock_quantity > 0`

### 404 Error on Routes
1. Ensure Apache mod_rewrite is enabled
2. Check `.htaccess` file exists in `/public` folder
3. Verify `AllowOverride All` in Apache config
4. Restart Apache after changes

### Database Connection Error
1. Check `.env` file has correct credentials
2. Verify MySQL is running
3. Ensure database `ceylon_cinnamon` exists
4. Run setup_database.php to create tables

### Login Not Working
1. Run database setup: `setup_database.php`
2. Use correct credentials from table above
3. Clear browser cookies and try again

---

## Configuration Files

| File | Purpose |
|------|---------|
| `.env` | Environment variables |
| `config/config.php` | Application settings |
| `config/database.php` | Database connection |
| `public/.htaccess` | URL rewriting rules |

---

*Last Updated: December 2024*
