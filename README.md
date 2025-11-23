## 🚀 How to Run the Project

1. Import the SQL file located at:
   `db/volunteerconnect_complete.sql`
   into your MySQL database.
   
2. Run the project on a local PHP server (XAMPP) and open:
   `http://localhost/VolunteerConnect/`
   
## 👤 User Roles & Capabilities

### Admin info:
`email` - admin@volunteer.com
`password` - password

### Organization info:
`email` - green@volunteer.com
`password` - password

### Demo-user info:
`email` - arta@volunteer.com
`password` - password

### 1. Volunteers
- `profile.php` – volunteer's profile, stats, badges, levels using houses icons
- `public_browse.php` – browse all available opportunities  
- `my_applications.php` – track application status   
- `leaderboard.php` – view ranking based on hours  

### 2. Organizations
- `post_opportunity.php` – create a new volunteer opportunity  
- `manage_events.php` – edit events and review signups  
- `ajax_manage.php` – approve or deny volunteer applications and hours via AJAX  

### 3. Admin
Located in `/admin/`:
- `admin_dashboard.php` – main admin interface, shows the site's stats
- `manage_users.php` – manage user accounts  
- `manage_organizations.php` – manage organization accounts  

---

## ⚙️ Core System Features

### 🔐 Authentication (auth/)
- `login.php` – user login
- `register.php` - volunteer signup
- `logout.php` – session logout  
- `auth_guard.php` – protects pages and enforces role access  

### 📝 Opportunities & Applications
- `public_browse.php` – view all opportunities and events
- `includes/apply_handler.php` – processes applications  
- `ajax_manage.php` – orgs approve/deny requests  
- `my_applications.php` – volunteers monitor application status  

### ⏱ Hour Tracking + Leaderboard
- `confirm_hours.php` – volunteers confirm completed hours  
- `recalculate_hours.php` – updates the system’s recorded hours  
- `leaderboard.php` – ranks volunteers by total confirmed hours  

### 📡 AJAX (ajax/)
Provides:
- Real-time application status updates  
- Event editing  
- Faster interaction without page reloads  

### 🧩 Shared Components (includes/)
- `header.php` / `footer.php` – site layout  
- `auth_guard.php` – role/session validation  
- `matching_engine.php` – opportunity matching  
- `apply_handler.php` – application logic  

---

## 🧪 Quick Judge Testing Guide

1. **Volunteer Flow:**  
   Create a volunteer → Browse events → Apply  → Check Profile → Check Leaderboard

2. **Organization Flow:**  
   Org login → Post a new opportunity → Review signups → Approve/deny applications and hours

3. **Admin Flow:**  
   Admin login → View dashboard → Manage users → Manage organizations

To check the leaderboards:
Login as the demo-user and apply, after that login as an org and approve of the application and hours. Check the Leaderboard tab and the demo-user will be there.

This sequence demonstrates every major feature of the system.

---
