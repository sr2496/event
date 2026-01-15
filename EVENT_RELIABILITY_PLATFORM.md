# Event Reliability Platform (Vendor Failure Insurance)
## Vue.js + Laravel – Full Practical Build Specification

---

## 1. PRODUCT DEFINITION (READ FIRST)

This is NOT a normal event booking marketplace.

This platform is an **Event Reliability & Vendor Backup System**.

Primary value proposition:
> “If your vendor cancels or fails, we guarantee a verified backup.”

Booking vendors is OPTIONAL.
Reliability + backup guarantee is CORE.

---

## 2. CORE PROBLEMS THIS PLATFORM SOLVES

- Vendors cancel at the last moment
- Clients panic during high-stress events (weddings, shoots, corporate events)
- No fast replacement options exist
- Reviews do not predict reliability
- Marketplaces do NOT handle failure

---

## 3. CORE ENTITIES (IMPORTANT)

### 3.1 Users
- Clients (event owners)
- Vendors (photographers, decorators, etc.)
- Admin (platform owner)

### 3.2 Vendors
- Primary Vendor (initial booking)
- Backup Vendor (standby replacement)

### 3.3 Events
- Wedding
- Pre-wedding
- Corporate
- Influencer shoot
- Any date-based service event

---

## 4. HIGH-LEVEL FLOW (SUMMARY)

1. Client browses vendors
2. Client selects vendor
3. Client books vendor + pays assurance fee
4. System silently assigns backup vendors
5. Event countdown starts
6. If event completes → platform still earns
7. If vendor cancels → emergency replacement flow triggers
8. Backup vendor is assigned
9. Platform earns higher emergency commission

---

## 5. USER FLOWS (DETAILED)

---

### 5.1 CLIENT FLOW – NORMAL (NO FAILURE)

1. Client lands on website
2. Selects city + vendor category
3. Views vendor list
4. Clicks vendor profile
5. Sees:
   - Portfolio
   - Price range
   - Reliability score
   - “Backup Guaranteed” badge
6. Clicks “Book with Assurance”
7. Pays:
   - Partial advance
   - Assurance fee
8. Booking confirmed
9. Event completes successfully
10. Client marks event as completed

Platform earns:
- Assurance fee
- Booking commission

---

### 5.2 CLIENT FLOW – FAILURE CASE

1. Vendor cancels before event
2. Client clicks “Vendor Failed” button
3. Uploads proof (screenshot / message)
4. System validates proof
5. Backup vendors notified
6. One backup vendor accepts
7. Backup assigned
8. Event continues
9. Client relieved
10. Event completed

Platform earns:
- Emergency commission (higher %)

---

## 6. VENDOR FLOW

### 6.1 Vendor Onboarding

Vendor must:
- Register account
- Upload identity proof
- Upload portfolio
- Select categories
- Select city
- Define availability rules
- Accept emergency replacement terms

Admin manually verifies vendor initially.

---

### 6.2 Vendor Dashboard

Vendor sees:
- Upcoming events
- Backup standby events
- Emergency requests
- Reliability score
- Cancellation penalties
- Subscription tier (optional)

---

### 6.3 Backup Vendor Flow

1. Vendor marked as backup
2. Receives emergency request
3. Accepts or rejects within time window
4. If accepts → assigned to event
5. Earns premium booking
6. Reliability score increases

---

## 7. VENDOR PROFILE PAGE (VERY IMPORTANT)

### 7.1 MUST SHOW

- Name / brand
- City + service radius
- Experience (years)
- Portfolio (8–12 best images)
- Services offered
- Price range (not exact price)

---

### 7.2 RELIABILITY SECTION (CORE DIFFERENTIATOR)

This section MUST exist.

Show:
- Reliability Score (0–5)
- Total events completed
- Last-minute cancellations count
- Average response time
- Backup readiness status

Example:
Reliability Score: 4.6 / 5
Events Completed: 72
Last-Minute Cancellations: 0
Avg Response Time: 18 min
Backup Ready: YES


---

### 7.3 TRUST TIMELINE (NOT FAKE REVIEWS)

Show real event history:
- Completed
- Rescheduled
- Cancelled

No fake testimonials.

---

## 8. BOOKING + ASSURANCE LOGIC

Booking ALWAYS includes:
- Vendor selection
- Event date
- Assurance fee

Assurance fee:
- Covers guaranteed replacement
- Is NON-REFUNDABLE
- Earned even if no failure occurs

Backup vendors:
- Assigned silently
- Client does NOT choose backup manually

---

## 9. EMERGENCY (VENDOR FAILURE) FLOW – TECH LOGIC

When client clicks “Vendor Failed”:

System must:
1. Freeze current vendor
2. Log failure reason
3. Notify admin
4. Notify backup vendors
5. Apply priority rules:
   - Same city
   - Same category
   - Available on date
   - Similar price range
6. First acceptance wins
7. Assign backup
8. Update event status
9. Penalize original vendor

---

## 10. RELIABILITY SCORE LOGIC (IMPORTANT)

Reliability score is NOT a review score.

It is calculated using:
- Completed events (+)
- Response time (+)
- Emergency acceptance (+)
- Last-minute cancellation (-)
- No-shows (- heavy penalty)

Score affects:
- Visibility
- Emergency priority
- Trust badge

---

## 11. ADMIN FLOW

Admin must be able to:
- Verify vendors
- Override backup assignment
- Penalize vendors
- Resolve disputes
- Manually assign backup if needed
- Adjust commissions
- Disable unreliable vendors

---

## 12. MONETIZATION (MULTIPLE STREAMS)

Platform earns from:

1. Assurance fee (always)
2. Booking commission (normal)
3. Emergency commission (higher)
4. Vendor subscriptions (optional)
5. Featured vendor listings

Platform MUST earn even if:
- No vendor ever cancels

---

## 13. TECH STACK (MANDATORY)

### Backend
- Laravel (latest)
- MySQL
- Laravel Sanctum
- Queue jobs (for notifications)

### Frontend
- Vue 3
- Vite
- Tailwind CSS
- Axios

---

## 14. DATABASE TABLES (MINIMUM REQUIRED)

- users
- vendors
- vendor_profiles
- vendor_portfolios
- vendor_availability
- events
- event_vendors
- backup_assignments
- payments
- reliability_logs
- penalties
- admin_actions

---

## 15. NON-GOALS (DO NOT BUILD)

- No public chat initially
- No AI recommendations initially
- No escrow initially
- No fake reviews
- No price bidding wars

---

## 16. MVP SCOPE (STRICT)

MVP must include:
- Vendor profiles
- Booking + assurance
- Backup assignment
- Emergency trigger
- Reliability scoring
- Admin override

Everything else is secondary.

---

## 17. PHILOSOPHY (IMPORTANT FOR AI)

This product is about:
- Trust
- Reliability
- Risk reduction

NOT about:
- Cheapest vendors
- Maximum listings
- Discounts

---

## 18. SUCCESS CRITERIA

The platform is successful if:
- Users stop searching after booking
- Vendors value reliability badge
- Backup replacement happens fast
- Platform earns even without failures

---

## 19. FINAL NOTE (DO NOT IGNORE)

This MUST NOT look or behave like a normal event booking site.

If reliability is removed, the product fails.

END OF SPECIFICATION.
