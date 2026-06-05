# Project Report: ENSA Campus Hub
### Digital Platform for Club & Event Management

## 1. Executive Summary
The **ENSA Campus Hub** is a full-stack web application designed to digitize the student life ecosystem at ENSA Khouribga. It provides a centralized platform for managing student organizations, event scheduling, and campus-wide participation analytics.

---

## 2. Functionalities & Features

### 👤 Student Features
*   **Secure Authentication:** Registration and login using CNE (Student ID) with password hashing (Bcrypt).
*   **Profile Recovery:** Security questions-based password reset system.
*   **Club Discovery:** Browse available clubs and submit membership applications.
*   **Event Participation:** Discover upcoming events, register for them, and manage a personal schedule.
*   **Feedback System:** Rate past events after they conclude to provide data for analytics.

### 🎖️ Club Admin Features
*   **Membership Management:** Approve or reject student applications to join the club.
*   **Event Lifecycle:** Create event proposals (pending Super Admin approval).
*   **Participation Tracking:** Manage attendance and participation requests for club-hosted events.
*   **Leadership Transfer:** Ability to promote another member to leader and step down.
*   **Club Analytics:** Visual dashboard showing event participation trends.

### 👑 Super Admin (System Admin) Features
*   **Organizational Control:** Create or delete clubs and assign initial leaders.
*   **Global Event Moderation:** Approve, deny, or delete events across all clubs.
*   **Role Management:** Promote students to Super Admins or demote existing admins.
*   **Student Lookup:** Search profiles by CNE to view their club memberships and event history.
*   **System-wide Analytics:** High-level charts tracking club popularity, event rankings by participation, rating, or budget.

---

## 3. Technology Stack

| Layer | Technology | Usage in Project |
| :--- | :--- | :--- |
| **Backend** | **PHP 8.2+** | Core logic, session management, and server-side routing. |
| **Database** | **MySQL 8.0** | Relational data storage for users, clubs, and events. |
| **DB Access** | **PHP PDO** | Secure database interactions using prepared statements to prevent SQL Injection. |
| **Frontend** | **HTML5 / CSS3** | Custom-built responsive UI with a focus on modern aesthetics and dark-themed components. |
| **Interactivity** | **Vanilla JS** | Dynamic form handling, UI toggles, and client-side validation. |
| **Data Viz** | **Chart.js 4.4** | Interactive bar, line, and doughnut charts for the admin dashboards. |
| **Icons/Fonts** | **Boxicons & Google Fonts** | Visual styling and typography (Poppins). |

---

## 4. Architectural Implementation

### 📂 Directory Structure
The project follows a modular organization:
*   `/admin`: Centralized logic for system-wide administration.
*   `/pages/auth`: Isolated authentication flow (Login, Register, Recovery).
*   `/pages/club`: Specialized tools for club-level management.
*   `/pages/general`: Student-facing dashboard and public pages.
*   `/includes`: Shared backend components (e.g., database connection).

### 🔒 Security Measures
1.  **Password Hashing:** Utilizing `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt) for all user credentials.
2.  **SQL Protection:** 100% usage of PDO prepared statements to mitigate SQL injection risks.
3.  **Role-Based Access Control (RBAC):** Every page validates the `$_SESSION['privilege']` to ensure users only access authorized modules.
4.  **Session Security:** Usage of `session_regenerate_id()` during login to prevent session fixation.

### 📊 Database Design
A normalized relational schema consisting of:
*   **ETUDIANT:** User accounts and privileges.
*   **CLUB:** Organization data linked to a student leader.
*   **EVENEMENT:** Event details with status-based workflow (Draft -> Pending -> Approved).
*   **Linking Tables:** `ETUDIANT_CLUB` and `EVENEMENT_ETUDIANT` manage many-to-many relationships with status tracking.
*   **PASSWORD_RESET:** Stores hashed answers to security questions for recovery.

---

## 5. Deployment & Scalability
The project is containerized using **Docker** and **Docker Compose**, allowing for consistent environments across development and production. The database is automatically initialized and seeded using SQL scripts during the build process.

**Developed by:** MUGIWARA37
