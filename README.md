# DeskHero - IT Help Desk Ticketing System

A modern, responsive, and functional IT Help Desk Ticketing System built using **PHP, MySQL, HTML5, CSS3, JavaScript, and Bootstrap 5**. Designed specifically for shared hosting (Hostinger) and local development (XAMPP).

## 🚀 Key Features

- **Submit Tickets**: Employees submit technical issues with title, rich description, category, priority (Low, Medium, High, Urgent), and file attachments.
- **Manage & Search Tickets**: Comprehensive filtering by status, priority, category, assigned staff member, keyword search, and pagination.
- **Assign Tickets**: Administrators and IT staff can assign tickets to specialists or claim tickets from the unassigned pool.
- **Status Workflow**: Track issues seamlessly through `Open`, `Assigned`, `In Progress`, `Resolved`, and `Closed`.
- **Role-Based Access Control (RBAC)**:
  - **Administrator**: Complete control over all tickets, staff assignments, user management, and category classification.
  - **IT Support Staff**: Triage queue, manage assigned workload, update ticket status, post public responses and confidential internal notes.
  - **Employee**: Self-service portal to submit tickets, review personal request history, and follow up.
- **Interactive Dashboard**: Real-time counter cards (Total, Open, Assigned, In Progress, Resolved, Closed), quick actions, and recent activity.
- **Activity & Audit History**: Detailed timeline recording status transitions, reassignments, and timestamps.
- **Built-in Security**: PDO prepared statements (anti-SQLi), CSRF protection tokens, XSS escaping, secure `password_hash()` encryption, and upload restrictions (`.htaccess`).

## 📁 Project Structure

```
final-project/
├── assets/
│   ├── css/style.css            # Custom responsive styles & badges
│   └── js/app.js                # Auto-alerts, tooltips, demo auto-fill
├── config/
│   └── database.php             # Database credentials & PDO connection
├── includes/
│   ├── auth.php                 # RBAC, session guards & CSRF verification
│   ├── functions.php            # Flash messages, status badges, upload handler
│   ├── header.php               # HTML head, Bootstrap 5 CDN & navigation
│   ├── navbar.php               # Responsive menu with role-aware links
│   └── footer.php               # Scripts and footer
├── uploads/                     # Secure storage for attachments
│   └── .htaccess                # Disables script execution in uploads
├── index.php                    # Route handler
├── login.php                    # Login page with 1-click demo logins
├── register.php                 # Employee registration
├── logout.php                   # Secure session destroyer
├── dashboard.php                # Role-specific analytics dashboard
├── tickets.php                  # Filterable & searchable tickets directory
├── ticket-create.php            # Ticket submission form
├── ticket-detail.php            # Ticket view, replies, assignment & timeline
├── ticket-action.php            # POST action handler (status, assign, reply)
├── users.php                    # User management table (Admin)
├── user-form.php                # Create / edit user (Admin)
├── categories.php               # Ticket categories management (Admin)
├── profile.php                  # User profile & password changer
├── setup_check.php              # Health check & Hostinger diagnostic tool
├── database.sql                 # MySQL schema with tables & sample seed data
├── DEPLOYMENT_GUIDE.md          # Setup instructions for XAMPP & Hostinger
└── README.md
```

## 📖 How to Run & View

Please refer to [DEPLOYMENT_GUIDE.md](file:///c:/xampp/htdocs/final-project/DEPLOYMENT_GUIDE.md) for full instructions on viewing:
1. **Locally on XAMPP**: `http://localhost/final-project/`
2. **Live on Hostinger**: Upload via File Manager & import `database.sql` in phpMyAdmin.
