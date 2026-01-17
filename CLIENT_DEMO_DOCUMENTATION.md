# Event Reliability Platform - Client Demo Documentation

## Executive Summary

**Event Reliability Platform** is India's first event vendor reliability and backup guarantee system. Unlike traditional event booking marketplaces, this platform focuses on **risk reduction and reliability** by guaranteeing verified vendor backups if the primary vendor fails.

**Core Value Proposition:** If a vendor cancels or no-shows, the platform automatically assigns a pre-vetted backup vendor within minutes, ensuring event protection.

---

## Table of Contents

1. [System Architecture](#system-architecture)
2. [User Roles & Access](#user-roles--access)
3. [Core Features](#core-features)
4. [Demo Walkthrough](#demo-walkthrough)
5. [API Endpoints](#api-endpoints)
6. [Technology Stack](#technology-stack)
7. [Test Accounts](#test-accounts)

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND (Vue 3)                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   Client    │  │   Vendor    │  │         Admin           │  │
│  │  Dashboard  │  │  Dashboard  │  │       Dashboard         │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     REST API (Laravel 10)                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │   Auth   │  │ Booking  │  │Emergency │  │      Admin       │ │
│  │Controller│  │Controller│  │Controller│  │   Controller     │ │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      DATABASE (MySQL)                            │
│  Users │ Vendors │ Events │ Bookings │ Backups │ Emergencies    │
└─────────────────────────────────────────────────────────────────┘
```

### Directory Structure

```
event/
├── server/                    # Laravel Backend
│   ├── app/
│   │   ├── Http/Controllers/  # API Controllers
│   │   ├── Models/            # 21 Database Models
│   │   └── Services/          # Business Logic
│   ├── database/migrations/   # 20 Migrations
│   └── routes/api.php         # API Routes
│
├── client/                    # Vue.js Frontend
│   ├── src/
│   │   ├── views/             # 15+ Page Components
│   │   ├── components/        # Reusable UI Components
│   │   ├── stores/            # Pinia State Management
│   │   └── services/          # API Client
│   └── public/                # Static Assets
```

---

## User Roles & Access

### 1. Admin Role
| Feature | Access |
|---------|--------|
| Platform Dashboard | ✅ Full Statistics & Analytics |
| Vendor Management | ✅ Verify, Suspend, Manage |
| User Management | ✅ Activate/Deactivate Users |
| Category Management | ✅ Create, Update, Delete |
| Emergency Override | ✅ Manual Backup Assignment |
| Audit Log | ✅ View All System Actions |
| Reports | ✅ Revenue & Performance Reports |
| Platform Settings | ✅ Configure Fees & Limits |

### 2. Vendor Role
| Feature | Access |
|---------|--------|
| Vendor Dashboard | ✅ Performance & Earnings |
| Profile Management | ✅ Update Business Info |
| Portfolio | ✅ Upload/Manage Images |
| Availability | ✅ Set Available Dates |
| Emergency Requests | ✅ Accept/Reject Backups |
| Assignments | ✅ View Upcoming Events |

### 3. Client Role
| Feature | Access |
|---------|--------|
| Client Dashboard | ✅ Booking Overview |
| Browse Vendors | ✅ Search & Filter |
| Create Bookings | ✅ Book Vendors |
| Payment | ✅ Pay Assurance Fee |
| Emergency Trigger | ✅ Report Vendor Failure |
| Booking History | ✅ Past & Current Bookings |

---

## Core Features

### Feature 1: Reliability Scoring System

**How It Works:**
- Every vendor starts with a **5.0 reliability score**
- Score is based on **actual performance data**, not user reviews
- Score updates automatically based on vendor actions

**Scoring Factors:**

| Action | Score Impact |
|--------|--------------|
| Event Completed Successfully | +0.1 |
| Emergency Backup Accepted | +0.3 |
| Event Cancellation | -0.5 |
| No-Show at Event | -1.0 |
| Emergency Request Rejected | -0.1 |
| Policy Violation | Variable Penalty |

**Badge System:**

| Score Range | Badge |
|-------------|-------|
| 4.5 - 5.0 | Excellent |
| 4.0 - 4.4 | Good |
| 3.0 - 3.9 | Average |
| Below 3.0 | Poor |

---

### Feature 2: Booking System with Assurance

**Pricing Structure:**

| Fee Type | Percentage | Description |
|----------|------------|-------------|
| Assurance Fee | 5% (min ₹500) | Non-refundable protection fee |
| Platform Commission | 10% | Standard booking commission |
| Emergency Commission | 20% | Higher rate for backup bookings |
| Advance Payment | 30% | Informational (paid to vendor) |

**Booking Workflow:**

```
1. Client selects vendor
         ↓
2. Client creates event with details
         ↓
3. Client pays assurance fee (5%)
         ↓
4. System silently assigns 3 backup vendors
         ↓
5. Event proceeds (pending → confirmed → completed)
```

**Supported Event Types:**
- Wedding
- Pre-wedding
- Corporate Events
- Influencer Shoots
- Birthday Parties
- Anniversaries
- Other

---

### Feature 3: Silent Backup Assignment

**Automatic Backup Selection:**

When a booking is confirmed, the system automatically assigns **3 backup vendors** based on:

| Criteria | Requirement |
|----------|-------------|
| Location | Same city as event |
| Category | Same service category |
| Availability | Available on event date |
| Price Range | Similar pricing |
| Reliability Score | ≥ 4.0 |
| Emergency Acceptance | Must accept emergency work |

**Key Points:**
- Backups are assigned **silently** (client doesn't choose)
- Backups remain on **standby** until event completion
- Priority is based on reliability score

---

### Feature 4: Emergency Trigger System

**When Primary Vendor Fails:**

```
Step 1: Client Reports Failure
        ├── Uploads proof (screenshot, message)
        ├── Provides failure reason
        └── Event marked as "emergency"
                    ↓
Step 2: System Response
        ├── Primary vendor marked as "failed"
        ├── Emergency request created
        └── First backup vendor notified
                    ↓
Step 3: Backup Response
        ├── Backup has time window to respond
        ├── First acceptance wins the booking
        └── Backup reliability score +0.3
                    ↓
Step 4: Penalty Applied
        ├── Original vendor penalized (-0.5 to -1.0)
        └── Cancellation/no-show count updated
                    ↓
Step 5: Resolution
        └── Emergency commission (20%) applied
```

---

### Feature 5: Admin Control Panel

**Dashboard Statistics:**
- Total Users, Vendors, Clients
- Active Bookings
- Revenue Analytics
- Emergency Statistics
- Pending Verifications

**Admin Actions:**

| Action | Description |
|--------|-------------|
| Verify Vendor | Approve vendor registration |
| Suspend Vendor | Temporarily disable vendor |
| Adjust Reliability | Manually modify score |
| Override Backup | Assign different backup vendor |
| Manage Categories | Add/Edit service categories |
| View Audit Log | Track all admin actions |

---

### Feature 6: Vendor Management

**Vendor Profile Includes:**
- Business name & contact info
- Service category
- Experience years
- Pricing range
- Portfolio images
- Social media links
- Terms & conditions

**Vendor Dashboard Shows:**
- Current reliability score with badge
- Upcoming assignments
- Pending emergency requests
- Earnings summary
- Performance statistics

---

## Demo Walkthrough

### Demo Scenario 1: Client Books a Vendor

1. **Login as Client** → `client@test.com`
2. **Browse Vendors** → Filter by category/city
3. **Select Vendor** → View profile & portfolio
4. **Create Booking** → Fill event details
5. **Pay Assurance Fee** → 5% of vendor price
6. **View Booking** → See assigned status

### Demo Scenario 2: Emergency Vendor Replacement

1. **Login as Client** → View active booking
2. **Trigger Emergency** → Upload proof of failure
3. **System Notifies Backup** → First backup alerted
4. **Login as Vendor** → Accept emergency request
5. **Booking Updated** → New vendor assigned
6. **Original Vendor Penalized** → Score reduced

### Demo Scenario 3: Admin Vendor Verification

1. **Login as Admin** → `admin@eventreliability.com`
2. **View Pending Vendors** → List of unverified
3. **Review Vendor Profile** → Check details
4. **Verify Vendor** → Approve for bookings
5. **View Audit Log** → Action recorded

### Demo Scenario 4: Vendor Dashboard

1. **Login as Vendor** → `rajesh@photography.com`
2. **View Dashboard** → See reliability score
3. **Update Profile** → Edit business info
4. **Manage Portfolio** → Upload images
5. **Set Availability** → Mark available dates
6. **View Assignments** → See upcoming events

---

## API Endpoints

### Authentication APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | User login |
| POST | `/api/auth/logout` | User logout |
| GET | `/api/auth/user` | Get current user |
| PUT | `/api/auth/profile` | Update profile |
| PUT | `/api/auth/password` | Change password |

### Vendor APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/vendors` | List vendors with filters |
| GET | `/api/vendors/featured` | Featured vendors |
| GET | `/api/vendors/categories` | All categories |
| GET | `/api/vendors/cities` | Available cities |
| GET | `/api/vendors/{slug}` | Vendor details |
| GET | `/api/vendors/{slug}/availability` | Check availability |

### Booking APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/bookings` | Client's bookings |
| POST | `/api/bookings` | Create booking |
| GET | `/api/bookings/{id}` | Booking details |
| POST | `/api/bookings/{id}/confirm-payment` | Confirm payment |
| POST | `/api/bookings/{id}/cancel` | Cancel booking |
| POST | `/api/bookings/{id}/complete` | Mark completed |

### Emergency APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/emergency/{eventId}/trigger` | Report vendor failure |
| GET | `/api/emergency/{eventId}/status` | Emergency status |
| GET | `/api/vendor/emergency-requests` | Pending emergencies |
| POST | `/api/vendor/emergency/{id}/accept` | Accept emergency |
| POST | `/api/vendor/emergency/{id}/reject` | Reject emergency |

### Admin APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/dashboard` | Dashboard stats |
| GET | `/api/admin/vendors` | All vendors |
| POST | `/api/admin/vendors/{id}/verify` | Verify vendor |
| POST | `/api/admin/vendors/{id}/suspend` | Suspend vendor |
| POST | `/api/admin/vendors/{id}/reliability` | Adjust score |
| GET | `/api/admin/emergencies` | All emergencies |
| POST | `/api/admin/emergencies/{id}/override` | Override backup |
| GET | `/api/admin/categories` | Categories |
| POST | `/api/admin/categories` | Create category |
| GET | `/api/admin/users` | All users |
| GET | `/api/admin/audit-log` | Audit log |
| GET | `/api/admin/reports` | Platform reports |
| GET | `/api/admin/settings` | Platform settings |
| PUT | `/api/admin/settings` | Update settings |

---

## Technology Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| Laravel | 10.x | PHP Framework |
| PHP | 8.1+ | Server Language |
| MySQL | 8.x | Database |
| Sanctum | - | API Authentication |
| Spatie Permission | - | Role Management |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| Vue.js | 3.x | JavaScript Framework |
| Vite | 5.x | Build Tool |
| Pinia | - | State Management |
| Vue Router | 4.x | Routing |
| Axios | - | HTTP Client |
| Tailwind CSS | 4.x | Styling |
| SweetAlert2 | - | Notifications |

---

## Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@eventreliability.com | password |
| Client | client@test.com | password |
| Vendor | rajesh@photography.com | password |

---

## Platform Settings (Configurable)

| Setting | Default Value |
|---------|---------------|
| Assurance Fee | 5% |
| Platform Commission | 10% |
| Emergency Commission | 20% |
| Advance Payment | 30% |
| Emergency Response Window | 4 hours |
| Max Backup Assignments | 3 |
| Default Reliability Score | 5.0 |
| Minimum Backup Eligibility | 4.0 |

---

## Unique Selling Points

1. **Performance-Based Reliability Score** - Not fake reviews, actual data
2. **Guaranteed Backup System** - 3 vendors always on standby
3. **Instant Emergency Response** - Automatic vendor replacement
4. **Non-Refundable Assurance Fee** - Platform revenue guaranteed
5. **Vendor Accountability** - Penalties for failures
6. **Complete Audit Trail** - All actions logged
7. **Role-Based Dashboards** - Tailored user experience
8. **Response Time Tracking** - Measures vendor reliability

---

## Contact & Support

For demo support or technical questions, please contact the development team.

---

*Document Version: 1.0*
*Last Updated: January 2026*
