# DeskHero IT Help Desk - Deployment & Setup Guide

This guide explains how to view, test, and deploy the IT Help Desk Ticketing System:
- **Option 1 (Recommended)**: Deploy live using **GitHub + Hostinger Git Auto-Deploy**
- **Option 2**: Deploy live using **Hostinger File Manager (Manual Upload)**
- **Option 3**: Run locally on your PC using **XAMPP**

---

## 🚀 Option 1: Live Deployment with GitHub + Hostinger (Recommended)

This workflow gives you seamless version control and automated deployments: whenever you run `git push origin main`, Hostinger will automatically update your live website!

### Prerequisites:
1. A **GitHub account** ([github.com](https://github.com)).
2. A **Hostinger Web Hosting plan** with a domain or subdomain pointed to it.

---

### Phase 1: Push Local Code to GitHub

1. Open your browser and go to [github.com/new](https://github.com/new).
2. Create a new repository:
   - **Repository name**: `helpdesk-system` (or any name you prefer)
   - **Visibility**: Choose **Public** or **Private** (Hostinger supports both!)
   - **Do NOT** check "Add a README file", "Add .gitignore", or license (we already have them ready).
   - Click **Create repository**.
3. Copy your GitHub repository URL (e.g. `https://github.com/YOUR_USERNAME/helpdesk-system.git`).
4. In your terminal / command prompt inside `C:\xampp\htdocs\final-project`:
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/helpdesk-system.git
   git branch -M main
   git push -u origin main
   ```
   *(If prompted, log in with your GitHub account or Personal Access Token).*

---

### Phase 2: Create Database in Hostinger

1. Log in to **Hostinger hPanel** ([hpanel.hostinger.com](https://hpanel.hostinger.com)).
2. Navigate to **Databases** > **MySQL Databases**.
3. Under **Create a New MySQL Database and Database User**:
   - **Database Name**: e.g., `helpdesk` (Hostinger adds a prefix, e.g., `u123456789_helpdesk`)
   - **Username**: e.g., `helpdesk_user` (Hostinger adds a prefix, e.g., `u123456789_user`)
   - **Password**: Create a strong password and save it in a safe place.
4. Click **Create**.
5. Scroll down to **List of Current MySQL Databases and Users** and click **Enter phpMyAdmin** next to your new database.
6. In phpMyAdmin, click the **Import** tab at the top.
7. Click **Choose File**, select `database.sql` from your project folder, and click **Import** (or **Go**).
8. Verify that tables (`users`, `tickets`, `categories`, etc.) appear on the left.

---

### Phase 3: Connect Hostinger to your GitHub Repository

1. In **Hostinger hPanel**, navigate to **Advanced** > **Git** (or search "Git" in the hPanel search bar).
2. Fill in the deployment form:
   - **Repository**: Enter your repository URL (e.g. `https://github.com/YOUR_USERNAME/helpdesk-system.git`)
   - **Branch**: `main`
   - **Install Path**: 
     - For your primary domain: leave blank or set to `public_html`
     - For a subdomain (e.g. `helpdesk.yourdomain.com`): select the subdomain's folder
3. *Note for Private GitHub Repositories:*
   - If your repository is **Private**, Hostinger will show an **SSH Deploy Key**.
   - Copy that key.
   - Go to your GitHub repository > **Settings** > **Deploy Keys** > **Add deploy key**.
   - Paste the key, give it a title (e.g., `Hostinger Deploy`), and save.
4. In Hostinger, click **Create** / **Deploy**. Hostinger will pull the project files from GitHub directly into your `public_html` directory!

---

### Phase 4: Configure Live Database Credentials on Hostinger

To ensure Git pulls never overwrite your live database password or leak it onto GitHub, the system uses an isolated config override (`database.local.php`).

1. In Hostinger hPanel, go to **Files** > **File Manager**.
2. Navigate to `public_html/config/`.
3. You will see `database.local.php.example`:
   - Duplicate or rename it to `database.local.php` (or create a new file named `database.local.php`).
4. Right-click `database.local.php` and click **Edit**.
5. Update with the Hostinger MySQL details you created in Phase 2:
   ```php
   <?php
   define('DB_HOST', 'localhost');                  // Standard for Hostinger
   define('DB_NAME', 'u123456789_helpdesk');        // Your actual DB Name
   define('DB_USER', 'u123456789_user');            // Your actual DB User
   define('DB_PASS', 'YourActualHostingerPassword'); // Your DB Password
   define('DB_CHARSET', 'utf8mb4');
   ```
6. Click **Save & Close**.

---

### Phase 5: Enable Automatic Deployment (Webhook)

Hostinger can update your website automatically every time you push code to GitHub:

1. In Hostinger hPanel > **Advanced** > **Git**, scroll down to your deployed repository.
2. Look for the **Auto-Deployment Webhook** and copy the webhook URL.
3. Open your repository on GitHub.
4. Go to **Settings** > **Webhooks** > **Add webhook**.
5. Set:
   - **Payload URL**: Paste the Hostinger Webhook URL.
   - **Content type**: `application/json`
   - **Secret**: Leave blank (unless Hostinger provided one)
   - **Which events would you like to trigger this webhook?**: `Just the push event`
   - **Active**: Checked
6. Click **Add webhook**.
7. Now, whenever you push any update (`git push`), Hostinger automatically deploys it!

---

### Phase 6: Verify Live Installation

1. In your browser, open the health diagnostic tool:
   👉 `https://yourdomain.com/setup_check.php`
2. Ensure all 5 checks show green:
   - ✅ PHP Version (>= 7.4 / 8.x)
   - ✅ PDO MySQL Extension
   - ✅ Database Connection
   - ✅ Database Schema & Tables
   - ✅ Uploads Directory Permissions (0755)
3. Go to `https://yourdomain.com/` and test logging in!

---

## 📦 Option 2: Live Deployment via Hostinger File Manager (Manual ZIP)

If you prefer not using Git:
1. Create database in Hostinger & import `database.sql` (same as Phase 2 above).
2. Zip the contents of `C:\xampp\htdocs\final-project` (select all files inside the folder, right click -> Compress to ZIP).
3. In Hostinger **File Manager**, upload the `.zip` file into `public_html/`.
4. Right-click the uploaded `.zip` and choose **Extract**.
5. In `public_html/config/`, create `database.local.php` with your Hostinger database credentials.
6. Verify via `https://yourdomain.com/setup_check.php`.

---

## 💻 Option 3: View Locally Using XAMPP

1. Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open browser: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
3. Create database `helpdesk_db` and import `database.sql`.
4. Open the system at:
   👉 **[http://localhost/final-project/](http://localhost/final-project/)**
5. Diagnostic check:
   👉 **[http://localhost/final-project/setup_check.php](http://localhost/final-project/setup_check.php)**

---

## 🔑 Demo Accounts

| Role | Email Address | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@helpdesk.com` | `admin123` | Full access, assign staff, manage users & categories, view metrics |
| **IT Support Staff** | `support@helpdesk.com` | `staff123` | View assigned/queue tickets, update status, write internal IT notes |
| **Employee** | `employee@helpdesk.com` | `user123` | Submit issues, track progress, add replies, confirm resolution |
