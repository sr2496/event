# Demo Quick Reference - Event Reliability Platform

## Quick Start URLs

| Page | URL |
|------|-----|
| Frontend | `http://localhost:5173` |
| API Base | `http://localhost:8000/api` |

---

## Test Credentials

```
Admin:  admin@eventreliability.com / password
Client: client@test.com / password
Vendor: rajesh@photography.com / password
```

---

## Key Features to Demonstrate

### 1. Reliability Score System
- Show vendor profile with score badge
- Explain: Score 0-5.0 based on performance, not reviews
- Show reliability log history

### 2. Booking Flow
- Client browses vendors → Filters by category/city
- Creates booking → Pays 5% assurance fee
- System auto-assigns 3 backup vendors (silent)

### 3. Emergency System
- Client triggers emergency → Uploads proof
- Backup vendor notified → Accepts/Rejects
- Original vendor penalized → Score reduced

### 4. Admin Panel
- Dashboard with statistics
- Vendor verification workflow
- Manual reliability adjustment
- Audit log of all actions

---

## Pricing at a Glance

| Fee | Rate | Notes |
|-----|------|-------|
| Assurance Fee | 5% (min ₹500) | Non-refundable |
| Commission | 10% | Standard booking |
| Emergency Commission | 20% | Backup booking |

---

## Reliability Score Impact

| Action | Score Change |
|--------|--------------|
| Complete Event | +0.1 |
| Accept Emergency | +0.3 |
| Cancel Event | -0.5 |
| No-Show | -1.0 |

---

## Demo Flow Script

### Part 1: Client Experience (5 min)
1. Login as client
2. Browse vendors with filters
3. View vendor profile & portfolio
4. Create a booking
5. Show booking confirmation

### Part 2: Vendor Experience (3 min)
1. Login as vendor
2. Show dashboard with reliability score
3. View upcoming assignments
4. Demonstrate profile/portfolio management

### Part 3: Emergency Flow (5 min)
1. Client triggers emergency
2. Vendor receives notification
3. Vendor accepts backup assignment
4. Show updated booking status

### Part 4: Admin Control (4 min)
1. Login as admin
2. Show dashboard statistics
3. Verify a pending vendor
4. View audit log
5. Show platform settings

---

## Key Talking Points

**Problem Solved:**
> "Traditional event booking has no protection if vendor fails. We guarantee backup vendors are always ready."

**Revenue Model:**
> "5% non-refundable assurance fee on every booking + 10-20% commission"

**Trust Factor:**
> "Reliability scores based on actual performance data, not fake reviews"

**Automation:**
> "3 backup vendors assigned automatically on every booking"

---

## Technical Highlights

- **Backend:** Laravel 10 + MySQL
- **Frontend:** Vue 3 + Tailwind CSS
- **Auth:** Token-based (Sanctum)
- **Roles:** Spatie Permission Package
- **State:** Pinia Store

---

## Common Demo Questions & Answers

**Q: How are backup vendors selected?**
> Same city, same category, available on date, reliability score ≥ 4.0, accepts emergency work

**Q: What if all 3 backups reject?**
> Admin can manually override and assign any available vendor

**Q: Is the assurance fee refundable?**
> No, it's the platform's revenue for providing backup guarantee

**Q: How is reliability score calculated?**
> Based on actual performance: events completed, cancellations, no-shows, emergency acceptances

---

## Database Models (21 Total)

**Core:** Users, Vendors, Events, Categories
**Booking:** EventVendor, Payment, BackupAssignment
**Reliability:** ReliabilityLog, Penalty
**Emergency:** EmergencyRequest
**Admin:** AdminAction, Setting

---

## API Quick Reference

```
Auth:     POST /api/auth/login
Vendors:  GET  /api/vendors
Booking:  POST /api/bookings
Emergency: POST /api/emergency/{id}/trigger
Admin:    GET  /api/admin/dashboard
```

---

*Keep this reference handy during the demo!*
