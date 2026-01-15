# 🛡️ Event Reliability Platform

> **India's First Event Reliability & Vendor Backup System**
> *Your Events, Protected. Your Vendors, Guaranteed.*

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat-square&logo=vue.js)](https://vuejs.org)
[![Vite](https://img.shields.io/badge/Vite-5.x-646CFF?style=flat-square&logo=vite)](https://vitejs.dev)
[![Spatie Permission](https://img.shields.io/badge/Spatie-Permission-blue?style=flat-square)](https://spatie.be/docs/laravel-permission)

---

## 🎯 What Makes Us Different

Unlike typical event booking marketplaces, we focus on **reliability and risk reduction**:

| Traditional Platforms | Event Reliability Platform |
|-----------------------|---------------------------|
| Connect clients with vendors | **Guarantee the service** |
| Review-based ratings | **Performance-based Reliability Scores** |
| No backup plan | **3 silent backup vendors per booking** |
| Client alone if vendor fails | **Instant replacement guarantee** |

---

## ⚡ Quick Start

### Prerequisites
- PHP 8.1+
- Node.js 18+
- MySQL 8+
- Composer

### Installation

```bash
# Clone and setup
git clone <repository-url>
cd event

# Backend Setup
cd server
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=0.0.0.0 --port=8000

# Frontend Setup (new terminal)
cd client
npm install
npm run dev -- --host 0.0.0.0 --port 5173
```

### Access URLs
| Service | URL |
|---------|-----|
| 🌐 Frontend | http://localhost:5174 |
| 🔌 API | http://localhost:8000/api |

---

## 🔐 Test Accounts

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@eventreliability.com | password |
| **Client** | client@test.com | password |
| **Vendor** | rajesh@photography.com | password |

---

## 🎛️ Role-Specific Dashboards

Each user role gets a **tailored dashboard experience**:

### 👑 Admin Dashboard
- Platform-wide statistics (Users, Vendors, Bookings, Revenue)
- **Requires Attention** alerts (pending verifications, active emergencies)
- Recent Bookings table
- Active Emergencies monitoring
- Quick action: "Manage Vendors"

### 🏪 Vendor Dashboard
- **Reliability Score** prominently displayed
- Performance metrics (Events, Cancellations, No-Shows, Emergency Accepts)
- Total Earnings overview
- Upcoming Assignments list
- Emergency Requests panel
- Quick actions: Edit Profile, Manage Portfolio, Set Availability

### 👤 Client Dashboard
- Personalized welcome greeting
- Stats: Upcoming Events, Completed Events, 100% Backup Protection
- Upcoming Events list
- "Book New Vendor" CTA button

---

## ⭐ Key Features

### 📊 Reliability Scoring System
Performance-based scoring (not fake reviews):
- Events completed
- Cancellation rate
- No-show rate
- Response time
- Emergency acceptance rate

### 🛡️ Silent Backup Assignment
- 3 backup vendors automatically assigned per booking
- Backups on standby until event completion
- Instant activation if primary fails

### 🚨 Emergency Trigger System
1. Client reports vendor failure
2. System notifies backup vendors
3. First backup to accept gets the job
4. Client notified of replacement
5. Original vendor penalized

### 🔍 Vendor Verification
All vendors undergo:
- Identity verification
- Portfolio review
- Reference checks
- Experience validation

---

## 🏗️ Tech Stack

### Backend
- **Laravel 10** - PHP Framework
- **MySQL 8** - Database
- **Spatie Permission** - Role & Permission Management
- **Laravel Sanctum** - API Authentication

### Frontend
- **Vue 3** - JavaScript Framework
- **Vite 5** - Build Tool
- **Pinia** - State Management
- **Vue Router** - Routing

---

## 📁 Project Structure

```
event/
├── server/                    # Laravel Backend
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Http/Resources/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/api.php
│
├── client/                    # Vue.js Frontend
│   ├── src/
│   │   ├── components/
│   │   │   └── dashboard/     # Role-specific dashboards
│   │   │       ├── AdminDashboard.vue
│   │   │       ├── VendorDashboard.vue
│   │   │       └── ClientDashboard.vue
│   │   ├── views/
│   │   ├── stores/
│   │   └── services/
│   └── index.html
│
├── QUICK_START_DEMO.md        # Quick demo guide
├── DEMO_DOCUMENTATION.md      # Full demo documentation
└── README.md                  # This file
```

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [QUICK_START_DEMO.md](./QUICK_START_DEMO.md) | 2-minute setup guide with demo checklist |
| [DEMO_DOCUMENTATION.md](./DEMO_DOCUMENTATION.md) | Comprehensive platform documentation |

---

## 💰 Revenue Model

- **5% Assurance Fee**: Non-refundable fee on each booking
- **Platform Commission**: Standard marketplace commission
- **Emergency Commission**: Higher commission on emergency replacements

---

## 🎨 Design Highlights

- ✨ Modern UI with gradients and glassmorphism
- 🌙 Dark Mode support
- 📱 Fully responsive design
- 🎭 Role-specific dashboards with visual badges
- 🎯 Trust indicators prominently displayed

---

## 📞 Support

For demo support or questions, contact the development team.

---

**© 2026 Event Reliability Platform. All Rights Reserved.**

*"If your vendor cancels, we guarantee a verified backup."*
