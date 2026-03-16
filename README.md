# ENSA Campus Hub
### Club & Event Management Platform for ENSA Khouribga

## Objective

This project is a full-stack web application built to digitize and streamline the management of student clubs and events at **ENSA Khouribga**.

The platform allows students to discover and join clubs, register for events, and rate past experiences — while giving administrators and club managers powerful tools to organize, approve, and monitor all campus activity from a centralized dashboard.

---

## What You Learn

- PHP session management and role-based access control
- MySQL relational database design with PDO prepared statements
- Responsive frontend design with pure CSS and vanilla JavaScript
- MVC-inspired file organization for maintainable web projects
- Form handling, input validation, and secure query execution
- Data visualization using Chart.js (bar, line, doughnut charts)

---

## Features

### Student
- Register and log in securely with CNE and password
- Browse and apply to join clubs
- Discover and register for upcoming events
- View personal schedule of accepted events
- Rate past events after they conclude
- Track pending club and event applications

### Club Admin
- Manage club membership requests (accept / reject)
- Create and submit events for admin approval
- View club member list and event attendance

### Super Admin
- Full role management (promote / demote users)
- Create and delete clubs
- Approve or deny event requests
- Delete approved events (up to 3 days before start)
- View performance analytics with interactive charts
- Look up any student profile by CNE

---

## Learning Outcome

By the end of this project, you should be able to:

- Design and implement a multi-role authentication system
- Build a relational database schema and query it efficiently
- Structure a PHP web application across multiple files and folders
- Create dynamic, data-driven pages with clean and maintainable code
- Apply access control logic to protect routes based on user privilege

This project develops the core skills required for **full-stack web development and database-driven application design**.

---

## Folder Structure

```
/var/www/html/
│
├── index.php                        # Entry point (login & register)
├── index.html
├── style.css                        # Global stylesheet
├── script.js                        # Global scripts
│
├── admin/                           # Super admin panel
│   ├── admin.php
│   └── admin.css
│
├── pages/
│   ├── auth/                        # Authentication flow
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── register_basic.php
│   │   ├── forgot_password.php
│   │   ├── setup_recovery.php
│   │   └── save_recovery.php
│   │
│   ├── club/                        # Club admin management
│   │   ├── club_admin.php
│   │   ├── join_club.php
│   │   └── participate.php
│   │
│   └── general/                     # Student-facing pages
│       ├── home.php
│       ├── home.css
│       └── success.php
│
└── includes/                        # Shared backend logic
    └── db.php
```

---

## Tech Stack

| Layer    | Technology          |
|----------|---------------------|
| Frontend | HTML, CSS, JavaScript |
| Backend  | PHP 8+              |
| Database | MySQL (PDO)         |
| Charts   | Chart.js 4.4        |
| Icons    | Boxicons 2.1        |
| Fonts    | Google Fonts (Poppins) |

---

## Setup

```bash
# 1. Clone the repository
git clone https://github.com/MUGIWARA37/ENSA-CAMPUS-HUB.git

# 2. Move to your web server root
cp -r ENSA-CAMPUS-HUB /var/www/html/

# 3. Import the database
mysql -u root -p < database/schema.sql

# 4. Configure your DB connection
nano /var/www/html/includes/db.php

# 5. Start Apache and visit
http://localhost/index.php
```
