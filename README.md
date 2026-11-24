# 🥬 Grocery Basket - Online Grocery Store

A full-featured Online Grocery Store web application built using **PHP**, **MySQL**, and **Bootstrap 5**.

---

## 🚀 Features

- **Customer Storefront:**
  - Browse featured products and categories.
  - Category-based product filtering.
  - Real-time shopping cart management (add, update quantities, remove).
  - Checkout system with order placement and Cash on Delivery (COD).
  - Order tracking and history.
  - Customer registration and login authentication.
  - Contact form and About page.

- **Admin Management Panel:**
  - Secure admin login and session management.
  - Product management (Add, Edit, Delete, Image upload).
  - Category management (Add, Rename, Delete).
  - Order management (View incoming orders, update order status: Processing, Shipped, Delivered, Cancelled).
  - Customer contact inquiries management.

- **Automated Database Setup:**
  - `setup.php` automatically provisions the MySQL database schema, tables, and sample data.

---

## 🛠️ Tech Stack

- **Backend:** PHP 7.4+ / PHP 8.x
- **Database:** MySQL / MariaDB (via MySQLi)
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **Local Server:** XAMPP / WAMP / LAMP

---

## 📦 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/<your-username>/<your-repo-name>.git "grocery store"
   ```
2. **Move to web server directory:**
   Place the project folder inside your web server's root (e.g., `C:/xampp/htdocs/grocery store/`).

3. **Start Apache & MySQL:**
   Open the XAMPP Control Panel and start both **Apache** and **MySQL**.

4. **Initialize the Database:**
   Open your browser and navigate to:
   ```
   http://localhost/grocery store/setup.php
   ```
   This script creates the `grocery` database, required tables (`categories`, `product`, `users`, `orders`, `order_items`, `contact`), and seeds sample categories, products, and default accounts.

5. **Access the Store:**
   - **Storefront:** `http://localhost/grocery store/index.php`
   - **Admin Login:** `http://localhost/grocery store/admin_login.php`

---

## 🔑 Default Credentials

- **Admin Account:**
  - **Email:** `admin@gmail.com`
  - **Password:** `admin123`
- **Customer Account:**
  - Can register a new account on the register page or use seeded test accounts created by `setup.php`.

---

## 📂 Project Structure

```text
├── upload/             # Uploaded product images
├── about.php           # About page
├── admin.php           # Admin dashboard
├── admin_login.php     # Admin login
├── cart.php            # Shopping cart
├── category.php        # Category-wise product view
├── contact.php         # Customer inquiry form
├── db.php              # Database connection
├── delete.php          # Admin deletion handler
├── footer.php          # Reusable footer
├── functions.php       # Common helper functions
├── header.php          # Reusable header and navbar
├── index.php           # Main landing page
├── login.php           # User login
├── logout.php          # Session termination
├── orders.php          # Order management & user order tracking
├── product.php         # Product listing & details
├── register.php        # User registration
├── setup.php           # One-click DB setup script
├── style.css           # Custom stylesheets
└── update.php          # Admin update handler
```

---

## 📄 License
This project is open source and available under the [MIT License](LICENSE).
