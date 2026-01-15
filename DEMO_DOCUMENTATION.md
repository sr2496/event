# 🛡️ Event Reliability Platform

## Your Events, Protected. Your Vendors, Guaranteed.

**India's First Event Reliability & Vendor Backup System**

---

## 📋 Table of Contents

1. [Platform Overview](#platform-overview)
2. [Unique Value Proposition](#unique-value-proposition)
3. [Key Features](#key-features)
4. [Getting Started](#getting-started)
5. [Demo Walkthrough](#demo-walkthrough)
6. [User Roles & Permissions](#user-roles--permissions)
7. [Technical Architecture](#technical-architecture)
8. [API Documentation](#api-documentation)
9. [Test Credentials](#test-credentials)

---

## 🎯 Platform Overview

The **Event Reliability Platform** is NOT a typical event booking marketplace. It's an **Event Reliability & Vendor Backup System** that focuses on:

- **Trust & Reliability** over just discovery
- **Risk Reduction** for event organizers
- **Guaranteed Service** even if the primary vendor fails

### The Problem We Solve

> "What happens if my wedding photographer doesn't show up?"
> "What if the caterer cancels at the last minute?"

Traditional booking platforms have no answer. We do.

### Our Solution

When you book a vendor through our platform, we:
1. ✅ Verify the vendor's track record
2. ✅ Assign **silent backup vendors** automatically
3. ✅ Provide **real-time reliability scores** (not fake reviews!)
4. ✅ **Guarantee a replacement** if your vendor fails

---

## 💎 Unique Value Proposition

### For Clients (Event Organizers)
- **Peace of Mind**: Never worry about last-minute cancellations
- **Verified Vendors**: All vendors are manually verified
- **Backup Guarantee**: 3 backup vendors assigned to every booking
- **Fast Replacement**: < 2 hours average replacement time
- **Transparent Scoring**: Reliability scores based on REAL data

### For Vendors
- **Premium Clients**: Access to clients who value reliability
- **Fair Rating**: Performance-based scoring, not fake reviews
- **Emergency Income**: Accept emergency assignments for extra earnings
- **Trust Building**: Build a verified reliability track record

### Platform Revenue Model
- **Assurance Fee**: 5% non-refundable fee on each booking
- **Platform Commission**: Standard marketplace commission
- **Emergency Commission**: Higher commission on emergency replacements

---

## ⭐ Key Features

### 1. 📊 Reliability Scoring System
Unlike review scores that can be faked, our reliability score is calculated from:
- Total events completed
- Cancellation rate
- No-show rate
- Average response time
- Emergency acceptance rate

**Score Badges:**
| Score | Badge | Color |
|-------|-------|-------|
| 4.5+ | Excellent | Green |
| 4.0-4.4 | Good | Light Green |
| 3.5-3.9 | Average | Yellow |
| < 3.5 | Poor | Red |

### 2. 🛡️ Silent Backup Assignment
When a client books a vendor:
1. System automatically finds 3 compatible backup vendors
2. Backups are notified silently (no client confusion)
3. Backups remain on standby until event completion
4. If primary fails, backup is activated instantly

### 3. 🚨 Emergency Trigger System
If a vendor fails:
1. Client triggers emergency with one click
2. System notifies all backup vendors
3. First backup to accept gets the job
4. Client is notified of replacement
5. Original vendor's reliability score is penalized

### 4. 🔍 Vendor Verification
All vendors undergo:
- Identity verification
- Portfolio review
- Reference checks
- Experience validation
- Equipment/capability assessment

### 5. 📋 Trust Timeline
Each vendor profile shows a **Trust Timeline** - a chronological history of:
- Events completed (+score)
- Cancellations (-score)
- No-shows (-score)
- Emergency assignments (+score)
- Client feedback (+/-score)

### 6. 👤 Role-Based Access Control
Using **Laravel Spatie Permission** for granular access:
- **Admin**: Full platform control, vendor verification, emergency management
- **Vendor**: Profile management, emergency responses, portfolio management
- **Client**: Booking, cancellation, emergency triggers

### 7. 🎛️ Role-Specific Dashboards
Each user role has a **unique dashboard experience**:

| Role | Dashboard Highlights |
|------|---------------------|
| **Admin** | Platform stats, pending verifications, active emergencies, revenue overview |
| **Vendor** | Reliability score, earnings, upcoming assignments, emergency requests |
| **Client** | Upcoming events, booking stats, backup protection status |

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.1+
- Node.js 18+
- MySQL 8+
- Composer

### Installation

```bash
# Clone the repository
git clone <repository-url>
cd event

# Backend Setup
cd server
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --port=8000

# Frontend Setup (new terminal)
cd client
npm install
npm run dev
```

### Access URLs
| Service | URL |
|---------|-----|
| Frontend | http://localhost:5174 |
| Backend API | http://localhost:8000/api |

---

## 🎬 Demo Walkthrough

### Step 1: Browse as Guest
1. Visit http://localhost:5174
2. Explore the **Home Page**:
   - Hero section with value proposition
   - "How It Works" section
   - Category browsing
   - Featured vendors
   - Trust badges

### Step 2: Find Vendors
1. Click **"Find Vendors"** in navigation
2. Use filters:
   - Category (Photographer, Decorator, etc.)
   - City
   - Price Range
   - Minimum Reliability Score
   - Backup Ready Only
3. Sort by Reliability Score, Price, Experience

### Step 3: View Vendor Profile
1. Click any vendor card
2. Explore the profile:
   - **Reliability Score** (prominently displayed)
   - Performance stats (events, cancellations, no-shows)
   - About section
   - Services offered
   - Contact information
   - **"Book with Assurance"** button

### Step 4: Client Login & Booking
1. Login as client: `client@test.com` / `password`
2. Navigate to any vendor
3. Click **"Book with Assurance"**
4. Fill booking form:
   - Event title
   - Event type
   - Date & time
   - Venue
   - Guest count
5. Review booking summary with assurance fee
6. Confirm booking

### Step 5: Role-Specific Dashboards

#### 5a. Client Dashboard
1. Login as: `client@test.com` / `password`
2. See personalized greeting: "Welcome back, [Name] 👋"
3. View:
   - **Stats Cards**: Upcoming Events, Completed Events, 100% Protected by Backup
   - **Upcoming Events Section**: List of your upcoming bookings
   - **Quick Action**: "Book New Vendor" button
4. Empty state shows "Find Vendors" CTA

#### 5b. Vendor Dashboard
1. Login as: `rajesh@photography.com` / `password`
2. See **🏪 Vendor Account** badge
3. View:
   - **Reliability Score**: Prominently displayed (4.80)
   - **Performance Stats**: Events Completed, Cancellations, No Shows, Emergency Accepts
   - **Stats Cards**: Upcoming Assignments, Total Earnings, Emergency Requests
   - **Upcoming Assignments**: List with event dates and status
   - **Emergency Requests**: Panel for last-minute opportunities
4. Quick Actions: Edit Profile, Manage Portfolio, Set Availability

#### 5c. Admin Dashboard
1. Login as: `admin@eventreliability.com` / `password`
2. See **👑 Admin** badge
3. View:
   - **Platform Stats**: Total Users, Verified Vendors, Total Bookings, Platform Revenue
   - **Requires Attention**: Vendors pending verification, Active emergencies
   - **Recent Bookings**: Table with event details and status
   - **Active Emergencies**: Real-time emergency panel
4. Quick Action: "Manage Vendors" button

### Step 6: Manage Bookings
1. Go to **"My Bookings"**
2. View all bookings with status
3. Click any booking for details:
   - Event information
   - Assigned vendor(s)
   - Payment summary
   - **Backup protection status**
   - Emergency trigger button

### Step 7: Trigger Emergency (Demo)
1. Open a confirmed booking
2. Click **"🚨 Vendor Failed"**
3. Select failed vendor
4. Enter failure reason
5. System searches for backup
6. Replacement assigned

---

## 👥 User Roles & Permissions

### Admin Role
| Permission | Description |
|------------|-------------|
| manage vendors | Full vendor CRUD |
| verify vendors | Approve vendor verification |
| suspend vendors | Suspend problematic vendors |
| manage emergencies | Override backup assignments |
| manage categories | Add/edit vendor categories |
| manage users | Activate/deactivate users |
| view audit log | View admin action history |
| adjust reliability | Manually adjust scores |

**Admin Dashboard Features:**
- 👑 Admin badge prominently displayed
- Platform-wide statistics (Users, Vendors, Bookings, Revenue)
- Requires Attention alerts (pending verifications, emergencies)
- Recent Bookings table with all platform activity
- Active Emergencies monitoring panel
- "Manage Vendors" quick action button

### Vendor Role
| Permission | Description |
|------------|-------------|
| manage vendor profile | Edit business profile |
| view vendor dashboard | Access vendor dashboard |
| manage portfolio | Add/remove portfolio items |
| manage availability | Set availability calendar |
| respond to emergency | Accept/reject backups |

**Vendor Dashboard Features:**
- 🏪 Vendor Account badge
- Reliability Score prominently displayed (e.g., 4.80)
- Performance metrics (Events, Cancellations, No-Shows, Emergency Accepts)
- Total Earnings overview
- Upcoming Assignments list with event details
- Emergency Requests panel for backup opportunities
- Quick actions: Edit Profile, Manage Portfolio, Set Availability

### Client Role
| Permission | Description |
|------------|-------------|
| create booking | Book vendors |
| view own bookings | See booking history |
| cancel own booking | Cancel bookings |
| trigger emergency | Report vendor failure |

**Client Dashboard Features:**
- Personalized welcome greeting
- Stats cards: Upcoming Events, Completed Events, Backup Protection
- Upcoming Events list with booking details
- "Book New Vendor" prominent CTA
- Empty state with "Find Vendors" link

---

## 🏗️ Technical Architecture

### Backend (Laravel 10)
```
server/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── VendorController.php
│   │   │   ├── BookingController.php
│   │   │   ├── EmergencyController.php
│   │   │   └── AdminController.php
│   │   ├── Middleware/
│   │   │   └── CheckRole.php
│   │   └── Resources/
│   │       ├── UserResource.php
│   │       ├── VendorResource.php
│   │       ├── VendorDetailResource.php
│   │       └── EventResource.php
│   ├── Models/
│   │   ├── User.php (with Spatie HasRoles)
│   │   ├── Vendor.php
│   │   ├── Event.php
│   │   ├── BackupAssignment.php
│   │   ├── EmergencyRequest.php
│   │   └── ... (14 models total)
│   └── Services/
│       ├── BookingService.php
│       └── EmergencyService.php
├── database/
│   ├── migrations/ (20 migrations)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── RoleAndPermissionSeeder.php
└── routes/
    └── api.php
```

### Frontend (Vue 3 + Vite)
```
client/
├── src/
│   ├── components/
│   │   ├── common/
│   │   │   ├── Navbar.vue
│   │   │   └── Footer.vue
│   │   └── vendor/
│   │       └── VendorCard.vue
│   ├── views/
│   │   ├── Home.vue
│   │   ├── Vendors.vue
│   │   ├── VendorDetail.vue
│   │   ├── Login.vue
│   │   ├── Register.vue
│   │   ├── Dashboard.vue
│   │   ├── Bookings.vue
│   │   ├── BookingDetail.vue
│   │   └── CreateBooking.vue
│   ├── stores/
│   │   ├── auth.js
│   │   ├── vendors.js
│   │   └── bookings.js
│   ├── services/
│   │   └── api.js
│   ├── router.js
│   ├── App.vue
│   └── main.js
└── index.html
```

### Database Schema (Key Tables)
| Table | Purpose |
|-------|---------|
| users | User accounts |
| vendors | Vendor business profiles |
| vendor_profiles | Contact & service details |
| vendor_portfolios | Portfolio images |
| vendor_availability | Availability calendar |
| events | Event bookings |
| event_vendors | Vendor assignments |
| backup_assignments | Silent backup assignments |
| emergency_requests | Vendor failure reports |
| reliability_logs | Score change audit |
| payments | Payment records |
| categories | Vendor categories |
| roles | Spatie roles |
| permissions | Spatie permissions |
| model_has_roles | User-role mapping |

---

## 🔌 API Documentation

### Authentication
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/auth/register` | POST | Register new user |
| `/api/auth/login` | POST | Login |
| `/api/auth/logout` | POST | Logout |
| `/api/auth/user` | GET | Get current user |

### Vendors (Public)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/vendors` | GET | List vendors (filterable) |
| `/api/vendors/featured` | GET | Get featured vendors |
| `/api/vendors/categories` | GET | Get all categories |
| `/api/vendors/cities` | GET | Get all cities |
| `/api/vendors/{slug}` | GET | Get vendor details |
| `/api/vendors/{slug}/availability` | GET | Check availability |

### Bookings (Authenticated)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/bookings` | GET | List user's bookings |
| `/api/bookings` | POST | Create booking |
| `/api/bookings/{id}` | GET | Get booking detail |
| `/api/bookings/{id}/cancel` | POST | Cancel booking |
| `/api/bookings/{id}/complete` | POST | Mark as completed |

### Emergency
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/bookings/{id}/emergency` | POST | Trigger emergency |
| `/api/bookings/{id}/emergency/status` | GET | Get emergency status |

---

## 🔑 Test Credentials

### Admin Account
```
Email: admin@eventreliability.com
Password: password
```

### Client Account
```
Email: client@test.com
Password: password
```

### Vendor Accounts
| Vendor | Email | Category |
|--------|-------|----------|
| Rajesh Photography | rajesh@photography.com | Photographer |
| Priya's Decorations | priya@decorations.com | Decorator |
| Amit Films | amit@films.com | Videographer |
| Glamour by Sneha | sneha@makeup.com | Makeup Artist |
| Royal Feast | vikram@catering.com | Caterer |
| DJ Arjun | arjun@djmusic.com | DJ/Music |
| Bloom & Blossom | meera@florals.com | Florist |
| Karan Events | karan@events.com | Event Planner |

**All vendor passwords: `password`**

---

## 🎨 Design Highlights

- **Modern UI**: Premium design with gradients and glassmorphism
- **Dark Mode Support**: Toggle between light/dark themes
- **Responsive**: Works on desktop, tablet, and mobile
- **Micro-animations**: Smooth transitions throughout
- **Trust Indicators**: Reliability scores prominently displayed
- **Role-Specific Dashboards**: Tailored UI for Admin, Vendor, and Client roles
- **Visual Role Badges**: 👑 Admin, 🏪 Vendor Account badges for clear role identification
- **Color-Coded Cards**: Different gradients for different metric types
- **Attention Alerts**: Yellow/orange highlights for items requiring action

---

## 📞 Support

For demo support or questions, contact the development team.

---

**© 2026 Event Reliability Platform. All Rights Reserved.**

*"If your vendor cancels, we guarantee a verified backup."*
