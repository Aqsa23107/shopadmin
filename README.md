# ShopSphere Admin - E-Commerce Operations Platform

A modern, high-performance web dashboard for e-commerce store administration built with **Core PHP**, **MySQL (`shop_db`)**, **Vanilla CSS & Design System**, and **JavaScript**.

---

## 📁 Folder Structure

```
shopadmin/
├── config/
│   ├── database.php          # PDO connection singleton & auto-table initializers
│   └── config.php            # Dynamic BASE_URL, session, flash & formatting helpers
├── includes/
│   ├── auth_check.php        # Session verification gatekeeper
│   ├── header.php            # HTML head, fonts, CDN assets & layout start
│   ├── sidebar.php           # Collapsible side navigation with active page state
│   ├── topbar.php            # Top header with global search, theme toggle & profile
│   └── footer.php            # Closing tags, scripts & toast alerts container
├── api/
│   ├── products.php          # RESTful Core PHP API for Product CRUD
│   ├── categories.php        # RESTful Core PHP API for Category CRUD
│   ├── orders.php            # Order status updates & line item queries
│   └── customers.php         # Customer management API
├── assets/
│   ├── css/
│   │   ├── style.css         # Complete design system (Dark/Light themes, glassmorphism)
│   │   └── dashboard.css     # KPI cards, invoices, charts & login styles
│   └── js/
│       ├── app.js            # UI interactions, theme toggle, modals & toasts
│       └── dashboard.js      # Chart.js line and doughnut visualizations
├── modules/
│   ├── products/
│   │   └── index.php         # Product catalog with filters, modals & CSV export
│   ├── categories/
│   │   └── index.php         # Category management with product counts
│   ├── orders/
│   │   ├── index.php         # Order list with status tabs & instant status changer
│   │   └── view.php          # Printable order invoice & line items breakdown
│   ├── customers/
│   │   └── index.php         # Customer directory & lifetime spend tracker
│   └── reports/
│       └── index.php         # Sales analytics, AOV & category revenue share
├── index.php                 # Store overview dashboard with live charts & KPIs
├── login.php                 # Sleek glassmorphism login screen with demo auto-fill
├── logout.php                # Session termination handler
├── profile.php               # Admin profile settings & password updater
├── setup.php                 # System diagnostics & database verification tool
├── database.sql              # Database schema dump and initial seed data
└── README.md                 # Project documentation
```

---

## 🚀 How to Run the Application

### Option 1: Using XAMPP Apache (Recommended)
1. Make sure **Apache** and **MySQL** are running in your **XAMPP Control Panel**.
2. Copy or link the `shopadmin` folder into your `htdocs` directory:
   - Target path: `C:\xampp\htdocs\shopadmin`
3. Open your browser and navigate to:
   - **`http://localhost/shopadmin`**

### Option 2: Using PHP Built-in Server
1. Open PowerShell or Command Prompt.
2. Navigate to the `shopadmin` directory:
   ```powershell
   cd c:\Users\TICER\Desktop\projects\shopadmin
   ```
3. Run the PHP built-in server:
   ```powershell
   & "C:\xampp\php\php.exe" -S localhost:8000
   ```
4. Open your browser and visit:
   - **`http://localhost:8000`**

---

## 🔑 Default Administrator Credentials

| Field | Value |
|---|---|
| **Username** | `admin` |
| **Password** | `admin123` |
| **Email** | `admin@shop.com` |
| **Role** | `Super Admin` |

*(A 1-click **"Auto Fill"** button is provided directly on the login screen for testing convenience).*

---

## 🛠️ Database Configuration

- **Database Name**: `shop_db`
- **Host**: `localhost`
- **User**: `root`
- **Password**: *(blank by default in XAMPP)*
- **Port**: `3306`

Database connection settings can be adjusted in [`config/database.php`](file:///c:/Users/TICER/Desktop/projects/shopadmin/config/database.php).

---

## ✨ Features Included

1. **Store Overview Dashboard (`index.php`)**:
   - Total Gross Revenue with trend comparison
   - Orders KPI with real-time pending breakdown
   - Products KPI with low-stock alerts
   - Registered Customer count
   - Monthly Revenue & Orders Trend line chart (Chart.js)
   - Sales by Category doughnut chart (Chart.js)
   - Recent orders table with quick view
   - Top selling products ranking
   - Low inventory progress bars

2. **Catalog & Products (`modules/products/index.php`)**:
   - Filter by Category, Visibility (Active/Inactive), and Stock (Low Stock / Out of Stock)
   - Real-time search by product name and description
   - Add & Edit products with modal forms and validation
   - Soft-delete protection for products tied to historical orders
   - 1-click CSV export

3. **Categories (`modules/categories/index.php`)**:
   - Total products and inventory units per category
   - Add / Edit / Delete category modals with foreign-key safety checks

4. **Order Management (`modules/orders/index.php` & `view.php`)**:
   - Status tabs (All, Pending, Shipped, Delivered, Cancelled)
   - Inline status updates via AJAX
   - Detailed invoice view with customer contact information, line items, and print button

5. **Customer Directory (`modules/customers/index.php`)**:
   - Complete list of customers with lifetime order counts and total spent
   - Search by name, email, or city
   - Add & Edit customer modal
   - Direct link to customer purchase history

6. **Financial Reports (`modules/reports/index.php`)**:
   - Gross Revenue, Average Order Value (AOV), and total units sold
   - Monthly revenue performance table
   - Category revenue share percentage breakdown

7. **Admin Profile & Security (`profile.php`)**:
   - Update administrator name and email
   - Secure password change with `password_hash()` (Bcrypt) and current password verification

8. **Design & UX**:
   - Dark Mode and Light Mode with instant toggle and `localStorage` persistence
   - Glassmorphic card styling with tailored color schemes
   - Responsive sidebar with mobile drawer support
   - Toast notification feedback system
