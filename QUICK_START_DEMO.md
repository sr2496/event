# 🚀 Quick Start - Event Reliability Platform Demo

## Prerequisites Checklist
- [ ] PHP 8.1+ installed
- [ ] Node.js 18+ installed
- [ ] MySQL running
- [ ] Composer installed

---

## ⚡ Start the Platform (2 Minutes)

### Terminal 1 - Backend Server
```bash
cd /var/www/html/event/server
php artisan serve --host=0.0.0.0 --port=8000
```

### Terminal 2 - Frontend Server
```bash
cd /var/www/html/event/client
npm run dev -- --host 0.0.0.0 --port 5173
```

---

## 🌐 Access URLs

| Application | URL |
|-------------|-----|
| **Frontend** | http://localhost:5174 |
| **API** | http://localhost:8000/api |

---

## 🔐 Demo Accounts

### Client Login (Recommended for Demo)
```
Email: client@test.com
Password: password
```

### Admin Login
```
Email: admin@eventreliability.com
Password: password
```

### Vendor Login (Example)
```
Email: rajesh@photography.com
Password: password
```

---

## 📱 Demo Flow

### 1️⃣ Home Page (Guest)
- [ ] Show hero section - "Book Vendors with Guaranteed Backup"
- [ ] Explain the 3-step process (Find → Book → Relax)
- [ ] Show category icons
- [ ] Show featured vendor card with Reliability Score

### 2️⃣ Vendors Listing
- [ ] Click "Find Vendors"
- [ ] Show 8 vendors with Reliability Scores
- [ ] Demonstrate filters (Category, City, Price)
- [ ] Show "Backup Ready" badge on vendors
- [ ] Explain the Reliability Score vs typical reviews

### 3️⃣ Vendor Profile
- [ ] Click any vendor
- [ ] Highlight the **Reliability Score** (4.8 out of 5)
- [ ] Show stats: 156 events, 0 cancellations, 0 no-shows
- [ ] Point out "This is NOT a review score" disclaimer
- [ ] Show "Book with Assurance" button

### 4️⃣ Login & Role-Specific Dashboard

#### Client Dashboard (Default Demo)
- [ ] Login as `client@test.com` / `password`
- [ ] Show personalized greeting: "Welcome back, [Name] 👋"
- [ ] Point out stats: Upcoming Events, Completed Events
- [ ] Highlight "100% Protected by Backup" badge with shield icon
- [ ] Show "Book New Vendor" CTA button
- [ ] Explain the Upcoming Events section

#### Vendor Dashboard (Vendor Perspective)
- [ ] Login as `rajesh@photography.com` / `password`
- [ ] Show **🏪 Vendor Account** badge
- [ ] Highlight the **Reliability Score** (4.80 prominently displayed)
- [ ] Show vendor-specific stats:
  - Events Completed (45)
  - Total Earnings (₹2,45,000)
  - Emergency Accepts (3)
- [ ] Show Upcoming Assignments list
- [ ] Show Emergency Requests panel (zero tolerance for failures)
- [ ] Quick actions: Edit Profile, Manage Portfolio, Set Availability

#### Admin Dashboard (Platform Overview)
- [ ] Login as `admin@eventreliability.com` / `password`
- [ ] Show **👑 Admin** badge
- [ ] Highlight platform-wide metrics:
  - Total Users (156)
  - Verified Vendors (42)
  - Total Bookings (289)
  - Platform Revenue (₹12,45,000)
- [ ] **Key Demo Point**: "Requires Attention" section:
  - ⚠️ Vendors Pending Verification (with Review button)
  - 🚨 Active Emergencies (with View button)
- [ ] Show Recent Bookings table
- [ ] Show Active Emergencies panel
- [ ] Quick action: "Manage Vendors" button

### 5️⃣ Create Booking
- [ ] Navigate to any vendor
- [ ] Click "Book with Assurance"
- [ ] Fill event details
- [ ] Show price breakdown:
  - Vendor price
  - 5% Assurance Fee (our revenue)
  - Total
- [ ] Explain what "Assurance Fee" provides

### 6️⃣ My Bookings
- [ ] Go to "My Bookings"
- [ ] Show booking status (Confirmed, Pending)
- [ ] Show "Backup Protected" indicator
- [ ] Click a booking to show details

### 7️⃣ Emergency Trigger (Key Feature!)
- [ ] Open a booking detail page
- [ ] Show "🚨 Vendor Failed?" button
- [ ] Explain the emergency flow:
  1. Client reports failure
  2. System contacts backup vendors
  3. First response gets the job
  4. Client is notified
  5. Original vendor is penalized

---

## 💡 Key Talking Points

### Why We're Different
> "Other platforms just connect you with vendors. **We guarantee the service.**"

### The Backup System
> "Every booking automatically has 3 backup vendors on standby. The client never knows unless they're needed."

### Revenue Model
> "5% assurance fee on all bookings - non-refundable regardless of cancellation. This funds our backup network."

### Reliability Score
> "Our scores are based on **actual performance data**, not reviews that can be faked. Events completed, response times, cancellation history."

### Target Market
> "High-value events: Weddings, corporate events, milestone celebrations. People who can't afford vendor failures."

---

## 🔧 Troubleshooting

### API Not Working?
```bash
cd /var/www/html/event/server
php artisan config:clear
php artisan cache:clear
```

### Database Issues?
```bash
# Reset and reseed database
php artisan migrate:fresh --seed
```

### Frontend Not Loading?
```bash
cd /var/www/html/event/client
npm install
npm run dev
```

---

## 📊 Database Stats After Seeding

| Entity | Count |
|--------|-------|
| Users | 10 (1 admin + 8 vendors + 1 client) |
| Vendors | 8 |
| Categories | 8 |
| Roles | 3 (admin, vendor, client) |
| Permissions | 14 |

---

## 🎯 Role-Specific Dashboard Quick Reference

| Role | Badge | Key Features |
|------|-------|-------------|
| **Client** | (none - default) | Upcoming Events, Booking Stats, 100% Backup Protection |
| **Vendor** | 🏪 Vendor Account | Reliability Score, Earnings, Upcoming Assignments, Emergency Requests |
| **Admin** | 👑 Admin | Platform Stats, Pending Verifications, Active Emergencies, Revenue |

---

## ✅ Demo Complete!

**Questions to expect:**
1. How long to find a backup? *< 2 hours average*
2. What if no backup is available? *We have network across cities*
3. How do we verify vendors? *Manual verification process*
4. What happens to vendor penalties? *Reliability score drop + potential suspension*

---

*Good luck with your demo! 🎉*
