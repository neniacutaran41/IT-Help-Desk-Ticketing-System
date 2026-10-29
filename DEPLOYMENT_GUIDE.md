# DeskHero IT Help Desk - Deployment & Setup Guide

This guide explains how to view and run the IT Help Desk Ticketing System both **locally on your computer (using XAMPP)** and **live on Hostinger Shared Hosting**.

---

## Method 1: View Locally using XAMPP (Fastest & Free)

Since your project is stored inside `C:\xampp\htdocs\final-project`, you can run it right now on your machine.

### Step 1: Start Apache and MySQL in XAMPP
1. Open the **XAMPP Control Panel** from your Windows Start Menu or run `C:\xampp\xampp-control.exe`.
2. Next to **Apache**, click **Start**.
3. Next to **MySQL**, click **Start**.
4. Both should turn green.

### Step 2: Create Database and Import `database.sql`
1. Open your web browser and go to: **[http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)**
2. In the left sidebar, click **"New"**.
3. Enter the database name: `helpdesk_db` and collation `utf8mb4_unicode_ci`, then click **Create**.
4. Click on the newly created `helpdesk_db` database on the left.
5. In the top navigation tabs, click **Import**.
6. Click **Choose File** (or "Browse") and select:
   ```
   C:\xampp\htdocs\final-project\database.sql
   ```
7. Scroll to the bottom and click **Import** (or "Go").
8. You should see a green success notification that all tables and seed records were created.

### Step 3: Open in Browser
Visit this URL in Chrome, Edge, or Firefox:
👉 **[http://localhost/final-project/](http://localhost/final-project/)**

You can also run the built-in diagnostic tool at:
👉 **[http://localhost/final-project/setup_check.php](http://localhost/final-project/setup_check.php)**

---

## Method 2: Host & View on Hostinger (Live Website)

Follow these steps to upload your project to your Hostinger domain (e.g., `https://yourdomain.com` or `https://helpdesk.yourdomain.com`).

### Step 1: Create a MySQL Database in Hostinger
1. Log in to your **Hostinger hPanel** ([hpanel.hostinger.com](https://hpanel.hostinger.com)).
2. Go to **Databases** > **MySQL Databases**.
3. Under **Create a New MySQL Database and Database User**:
   - **Database Name**: e.g., `helpdesk` (Hostinger will prefix it, e.g., `u123456789_helpdesk`)
   - **Username**: e.g., `helpdesk_user` (Hostinger will prefix it, e.g., `u123456789_user`)
   - **Password**: Set a secure password and save it somewhere handy.
4. Click **Create**.

### Step 2: Import `database.sql` in Hostinger phpMyAdmin
1. In Hostinger hPanel under **MySQL Databases**, scroll down to **List of Current MySQL Databases And Users**.
2. Click **Enter phpMyAdmin** next to your newly created database.
3. In phpMyAdmin, click the **Import** tab at the top.
4. Click **Choose File** and select `database.sql` from your computer.
5. Click **Go** / **Import** at the bottom.

### Step 3: Upload Project Files via Hostinger File Manager
1. In Hostinger hPanel, go to **Files** > **File Manager** (for your chosen domain/subdomain).
2. Navigate to `public_html/` (or your subdomain folder).
3. Upload all the files and folders from `C:\xampp\htdocs\final-project/`:
   ```
   public_html/
   ├── assets/
   ├── config/
   ├── includes/
   ├── uploads/
   ├── index.php
   ├── login.php
   ├── register.php
   ├── logout.php
   ├── dashboard.php
   ├── tickets.php
   ├── ticket-create.php
   ├── ticket-detail.php
   ├── ticket-action.php
   ├── users.php
   ├── user-form.php
   ├── categories.php
   ├── profile.php
   ├── setup_check.php
   └── database.sql
   ```
   *Tip: You can zip the contents of `final-project`, upload the `.zip` to `public_html`, and use Hostinger File Manager's "Extract" feature for fast upload.*

### Step 4: Configure Database Connection on Hostinger
1. In Hostinger File Manager, navigate to `config/database.php`.
2. Right-click and choose **Edit**.
3. Update the constants with your Hostinger database details:
   ```php
   define('DB_HOST', 'localhost');                  // Usually 'localhost' on Hostinger
   define('DB_NAME', 'u123456789_helpdesk');        // Your Hostinger DB Name
   define('DB_USER', 'u123456789_user');            // Your Hostinger DB User
   define('DB_PASS', 'YourActualPasswordHere');     // Your Hostinger DB Password
   ```
4. Save the file.

### Step 5: Verify Permissions & Test Live
1. Open your browser and go to your website:
   `https://yourdomain.com/setup_check.php`
2. If all checks are green, go to:
   `https://yourdomain.com/`

---

## 🔑 Demo Accounts (Ready to Test All 3 Roles)

On the login page, you can either click the **1-Click Demo Fill buttons** or enter these credentials manually:

| Role | Email Address | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@helpdesk.com` | `admin123` | Full access, assign staff, manage users & categories, view metrics |
| **IT Support Staff** | `support@helpdesk.com` | `staff123` | View assigned/queue tickets, update status, write internal IT notes |
| **Employee** | `employee@helpdesk.com` | `user123` | Submit issues, track progress, add replies, confirm resolution |
