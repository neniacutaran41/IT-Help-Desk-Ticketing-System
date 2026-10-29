# DeskHero IT Help Desk - Deployment & Setup Guide

This guide explains how to view, test, and deploy the IT Help Desk Ticketing System:
- **Option 1**: Automated CI/CD Deployment with **GitHub Actions & InfinityFree (FTP)**
- **Option 2**: Live Deployment with **Hostinger & GitHub**
- **Option 3**: Run locally on your PC using **XAMPP**

---

## ⚡ Option 1: Automated CI/CD Deployment to InfinityFree via GitHub Actions

InfinityFree is a popular free hosting service. Because InfinityFree does not have built-in Git, we use **GitHub Actions CI/CD** with FTP to automatically deploy changes whenever you push to GitHub!

### Prerequisites
1. A **GitHub Account** ([github.com](https://github.com)).
2. An **InfinityFree Account** ([infinityfree.com](https://www.infinityfree.com)) with an active hosting account.

---

### Step 1: Find your InfinityFree FTP Details

1. Log in to the **[InfinityFree Client Area](https://dash.infinityfree.com/)**.
2. Click on your active hosting account.
3. Look for the **FTP Details** section:
   - **FTP Hostname**: Usually `ftpupload.net`
   - **FTP Username**: Looks like `if0_38123456`
   - **FTP Password**: Click "Show" to view and copy your account password.
   - **FTP Port**: `21`

---

### Step 2: Add GitHub Secrets to your Repository

1. Open your repository on GitHub.
2. Go to **Settings** > **Secrets and variables** > **Actions**.
3. Under **Repository secrets**, click **New repository secret** and add the following 3 secrets:

| Secret Name | Value | Description |
| :--- | :--- | :--- |
| `FTP_HOSTNAME` | `ftpupload.net` | Your InfinityFree FTP host |
| `FTP_USERNAME` | `if0_38123456` | Your InfinityFree FTP username |
| `FTP_PASSWORD` | `YourPassword` | Your InfinityFree account password |

*(Optional)* If you are deploying to an Addon Domain or Subdomain instead of the main domain, add:
- `FTP_SERVER_DIR`: `yourdomain.ext/htdocs/` (defaults to `htdocs/` for primary domain).

---

### Step 3: Create MySQL Database in InfinityFree

1. In InfinityFree Client Area, click **Control Panel** (vPanel).
2. Find and click **MySQL Databases**.
3. Under **Create New Database**, type a name (e.g. `helpdesk`) and click **Create Database**.
4. Note the database details displayed on that page:
   - **MySQL Hostname**: Looks like `sql100.infinityfree.com` or `sql204.epizy.com` *(⚠️ NOTE: In InfinityFree, this is NEVER `localhost`!)*
   - **MySQL Database Name**: Looks like `if0_38123456_helpdesk`
   - **MySQL Username**: Looks like `if0_38123456`
   - **MySQL Password**: Same as your vPanel/account password
5. Return to Control Panel and click **phpMyAdmin**.
6. Click **Connect** next to your database.
7. Click the **Import** tab at the top.
8. Click **Choose File**, select `database.sql` from your project folder, and click **Import** (or **Go**).

---

### Step 4: Create Live Config on InfinityFree

To keep your live credentials secure without committing them to GitHub:

1. In InfinityFree Control Panel, click **Online File Manager** (Monsta FTP).
2. Open the **`htdocs`** folder, then open the **`config`** folder.
3. Click **New File** and name it: `database.local.php`.
4. Edit the file and paste:
   ```php
   <?php
   define('DB_HOST', 'sql100.infinityfree.com');     // Your InfinityFree MySQL Hostname
   define('DB_NAME', 'if0_38123456_helpdesk');       // Your InfinityFree Database Name
   define('DB_USER', 'if0_38123456');                // Your InfinityFree Database Username
   define('DB_PASS', 'YourInfinityFreePassword');    // Your vPanel Password
   define('DB_CHARSET', 'utf8mb4');
   ```
5. Save the file.
*(Because `database.local.php` is ignored by Git, future CI/CD deployments will never overwrite it or delete it).*

---

### Step 5: Push to GitHub to Trigger CI/CD Deployment

In your local command prompt / terminal inside `C:\xampp\htdocs\final-project`:
```bash
git push origin main
```
1. Open your GitHub repo and click the **Actions** tab.
2. You will see the **"Deploy to InfinityFree via FTP"** workflow running!
3. Within 1-2 minutes, all files will be automatically synced to InfinityFree.

---

### Step 6: Test & Verify Live

1. Open your browser and visit:
   👉 `https://your-domain.infinityfreeapp.com/setup_check.php`
2. Confirm that all 5 diagnostic checks are green.
3. Visit `https://your-domain.infinityfreeapp.com/` to start using DeskHero!

---

## 🚀 Option 2: Live Deployment with GitHub + Hostinger

### Step 1: Database Setup on Hostinger
1. In Hostinger hPanel > **Databases** > **MySQL Databases**, create DB and User.
2. In Hostinger phpMyAdmin, import `database.sql`.

### Step 2: Deploy from GitHub in Hostinger
1. In Hostinger hPanel > **Advanced** > **Git**:
   - Repository: `https://github.com/YOUR_USERNAME/deskhero-helpdesk.git`
   - Branch: `main`
   - Install Path: `public_html`
2. Click **Create** / **Deploy**.

### Step 3: Configure Live Database
1. In Hostinger File Manager under `public_html/config/`, rename `database.local.php.example` to `database.local.php`.
2. Set `DB_HOST` to `'localhost'`, and enter your Hostinger DB name, user, and password.

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
