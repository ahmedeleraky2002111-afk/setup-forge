# SetupForge — DECISIONS.md
Last updated: May 2026

---

## Wizard Steps (setup.php)
- Step 0: Business name
- Step 1: Business type
- Step 2: Restaurant type (fast_food, standard_dining, premium_dining, cloud_kitchen)
- Step 3: Seat count (indoor + outdoor) + Area in sqm (used for AC calculation)
- Step 5: Budget (range cards: Under 500k / 500k-1.5M / 1.5M-3M / 3M+) in EGP
- Step 6: Installation services (intent only — POS, Electrical, Network, AC, Kitchen Setup)
- Step 7: Staffing (waiter, chef, cashier, security, barista, busboy, host, kitchen helper — with quantity per role)

### Important Rules for Wizard
- Step 6 collects INTENT only — no pricing, no company selection
- Step 7 collects staff quantities per role (not just yes/no)
- Jobs are NOT created here — only after successful payment
- No tier selection in wizard — tier is auto-derived in packages.php
- Session keys: installation_services, labor (map of role → qty), area_sqm, ac_units
- Modules auto-set in step 3 POST based on restaurant type — user does NOT pick modules

### Module Auto-assignment by Restaurant Type
- fast_food: kitchen, pos, furniture, electronics, ac
- standard_dining: kitchen, pos, furniture, electronics, ac
- premium_dining: kitchen, pos, furniture, electronics, ac
- cloud_kitchen: kitchen, pos

### TODO — Wizard
- [ ] Step 3: Change seats to tables (ask indoor tables + outdoor tables + table size 2/4/6 seater), calculate seats internally (tables × seats_per_table), keep indoor_seats/outdoor_seats in session

---

## Recommendation Engine (packages.php)
- Tier auto-derived from budget allocation:
  - ratio >= 0.35 → Premium
  - ratio >= 0.20 → Balanced
  - else → Starter
- Modules auto-generated based on restaurant type (never user-selected)
- AC units calculated from area: ceil(area_sqm / 40), minimum 1
- ac_units stored in session and saved to installation_data in order
- Never remove existing cart logic

### Module Weights
| Module      | fast_food | standard_dining | premium_dining | cloud_kitchen |
|-------------|-----------|-----------------|----------------|---------------|
| kitchen     | 6         | 5               | 4              | 8             |
| pos         | 3         | 2               | 2              | 4             |
| furniture   | 2         | 3               | 5              | 0             |
| electronics | 1         | 2               | 2              | 0             |
| ac          | 1         | 2               | 3              | 0             |

### TODO — packages.php
- [ ] Dining set ratio per restaurant type (needs 2/4/6/8/10/12-seater products in DB first)
- [ ] Ambience module: add fan, speaker, air purifier product types (Option B — parked)
- [ ] Garrana + EMAJ starting_from review — verify matches new kitchen rates logic

---

## Three Services Logic

### 1. Products
- Budget ceiling controls recommendations
- Cart built in packages.php
- Paid via Paymob

### 2. Labor (individuals)
- provider_type: waiter, chef, barista, cashier, cleaner
- Separate platform with own dashboard
- Jobs created after payment using staffing quantities from wizard Step 7
- Commission based
- Bidding does NOT apply to labor
- Seed labor workers: user IDs 67-76 — NEVER touch these

### 3. Installation Companies
- user_type 'company' added to user_type enum in PostgreSQL
- Companies have quote model (NOT bidding)
- Company signup: Labor/company_signup.php
- Company dashboard: Labor/company_dashboard.php
- Login: auth/login.php handles case "company" → redirects to Labor/company_dashboard.php
- Flow:
  1. Wizard Step 6 → user selects services, saved as installation_services in session
  2. place_order.php → saves installation_data to orders table (includes ac_units + area_sqm + terminal_count + kitchen_item_count)
  3. After payment → paymob_callback.php creates one installation_requests row per service
  4. Business sees ALL matching companies upfront on service_jobs.php (even before quotes)
  5. Companies see open requests on company dashboard → submit quote (price + message + website)
  6. Business sees actual quote on company card → accepts one
  7. Accepted quote → installation_requests.company_id set, status = accepted
  8. All other quotes for that request → status = rejected

---

## Seed Companies
| company_id | user_id | company_name         | service   | notes                        |
|------------|---------|----------------------|-----------|------------------------------|
| —          | 139     | (replaced by EgyPOS) | pos       | old TechPOS — replaced       |
| —          | 140     | ElectroPro Egypt     | electrical| seed company                 |
| —          | 141     | NetSetup Pro         | network   | seed company                 |
| —          | 142     | CoolAir Installations| ac        | seed company                 |
| —          | 146     | Garrana Group        | kitchen   | seed company                 |
| —          | 147     | EMAJ Egypt           | kitchen   | seed company                 |
| 15         | —       | Future Air           | ac        | Giza, added manually         |

### New Real Companies Added
- **Microtech Egypt** — POS, real company, 20+ years experience
- **AIS Egypt** — POS, real company, Sheraton Cairo
- **EgyPOS** — POS, real company, Heliopolis, hardware-agnostic installer (replaced TechPOS)
- **WoodMaker Egypt** — Kitchen installation, real company, Mohandessin (service type needs changing from POS → kitchen)
- **Contistahl Group** — Kitchen installation
- **BIM POS** — removed, replaced by EgyPOS

### TODO — Companies
- [ ] Change WoodMaker Egypt service type from POS to kitchen in DB
- [ ] Insert company images for: Garrana, EMAJ, Contistahl, WoodMaker, Microtech, AIS, Future Air, EgyPOS (insert manually from vendor/edit profile)
- [ ] Update seed company list in DB with correct company_ids after manual inserts

### Passwords for Seed Companies
- All seed company accounts: password is `password`

---

## AC Pricing Logic
- AC units = ceil(area_sqm / 40), minimum 1
- Tonnage derived from area_sqm / ac_units:
  - ≤ 20 m² per unit → 1.5 ton
  - ≤ 30 m² per unit → 2 ton
  - ≤ 45 m² per unit → 2.5 ton
  - > 45 m² per unit → 3 ton
- Per-company rates stored in company_ac_rates table
- service_jobs.php reads area_sqm from orders.installation_data (not session)
- installation_data now saves: { services, area_sqm, ac_units, terminal_count, kitchen_item_count }
- paymob_callback.php reads installation_data["services"] with fallback for old format
- Breakdown modal shows: tonnage, units, rate per unit, total — per company

### AC Rate Table (company_ac_rates)
- Columns: company_id, tonnage, rate_per_unit
- CoolAir starting_from = 600 EGP per unit
- 1.5 ton = 700 EGP confirmed

---

## Database Structure

### users table
- id, name, email, password_hash, user_type, phone, country, city, street, status, created_at
- user_type enum: admin, business, customer, labor, vendor, company

### labors table (individuals only — NO technicians)
- user_id, national_id, dob, skills, experience_level, availability_status
- military_status, hourly_rate, avg_rating, profile_picture, status
- name, provider_type, balance, labor_role
- provider_type values: waiter, chef, barista, cashier, cleaner (NO technician)

### companies table
- company_id, user_id, company_name, description, services (TEXT[])
- base_price, avg_rating, established_year, company_size (small/medium/large)
- image, availability_status, status, location
- website (VARCHAR 255)
- starting_from (INT) — per unit price for AC, flat for others

### company_ac_rates table
- company_id, tonnage (VARCHAR), rate_per_unit (INT)

### company_kitchen_rates table
- company_id, product_type (VARCHAR), rate (INT)

### company_pos_rates table
- company_id, terminal_type (VARCHAR), rate (INT)

### jobs table
- job_id, business_id, title, description, location, budget, status
- created_at, price, worker_id, job_type, company_id
- job_type values: labor

### orders table
- id, status, customer_user_id, business_user_id, service_fees, order_total
- delivery_location, payment_status, paid_at, payment_reference
- preferred_delivery_date, payment_method
- labor_data (JSONB) — map of role → quantity
- installation_data (JSONB) — { services, area_sqm, ac_units, terminal_count, kitchen_item_count }

### installation_requests table
- request_id, user_id, company_id, services (TEXT[])
- status: pending → accepted → completed
- total_price, created_at

### installation_quotes table
- quote_id, request_id, company_id
- price (NUMERIC 10,2), message (TEXT), website_link (VARCHAR 255)
- status: pending → accepted → rejected
- created_at

---

## UI / Design Decisions

### Colors
- Primary blue: #004cac (buttons, selected states, active highlights)
- No teal/turquoise anywhere
- Selected card state: border #004cac, background #f0f6ff
- All buttons: rounded (consistent with existing style)

### service_jobs.php — Installation Section
- Shows all matching companies as cards per service (even before quotes)
- Company card: logo + name side by side (flex row)
- Before quote: shows estimated cost from rate tables (breakdown modal available)
- After quote: shows actual price + message + Accept Quote button
- After accepted: card highlighted, "Accepted" badge
- Other companies after acceptance: "Not Selected" badge
- "View Details" breakdown modal: shows each product/unit with image + rate + subtotal
- Modal header: company logo + name
- AC breakdown modal: tonnage × units × rate → total
- Kitchen breakdown modal: each product type × qty × rate → total
- POS breakdown modal: each terminal type × qty × rate → total

---

## Real Price Ranges (Egypt 2025)

### POS Installation
- Full setup (software + hardware config + training): 5,000 EGP starting
- AIS Egypt, Microtech Egypt, EgyPOS — rates in company_pos_rates

### AC Installation
- CoolAir starting_from = 600 EGP per unit
- 1.5 ton = 700 EGP per unit confirmed

### Electrical
- ElectroPro Egypt starting_from = 2,500 EGP

### Network
- NetSetup Pro starting_from = 3,000 EGP

### Kitchen
- Garrana Group starting_from = 5,000 EGP
- EMAJ Egypt starting_from = 4,500 EGP
- WoodMaker Egypt — TBD (service type being changed to kitchen)
- Contistahl Group — rates in company_kitchen_rates

---

## Security
- Paymob keys in config.php (gitignored)
- HMAC verification rejects with exit
- success.php has ownership check — restores session from order if lost on redirect
- paymob_callback.php has NO session_start() — server to server only
- place_order.php determines business vs customer from $_SESSION["user_type"]

---

## Git Branch Strategy
- Always create a new branch before Claude Code works
- Never commit directly to main
- Commit before every Claude Code session

---

## What NOT to Touch
- Never remove existing cart logic in packages.php
- Never touch labor worker records (IDs 67-76) in labors table
- Never touch bids table structure
- Never touch original seed company users (IDs 139-142, 146-147)
- Always work one change at a time
- Test full flow after every change: wizard → packages → order summary → payment

---

## Bugs Fixed
- success.php Unauthorized — fixed by restoring session from order if lost on ngrok redirect
- paymob_callback.php logging out user — fixed by removing session_start()
- place_order.php wrong business_user_id — fixed by using session user_type instead of DB check
- paymob_callback.php installation_data parsing — handles new JSON format
- merchant_order_id now includes timestamp to prevent Paymob caching
- AC tab not appearing in packages.php — fixed by adding "ac" to modules array in setup.php step 3
- $activeModule undefined warnings — fixed

---

## TODO Before Presentation

### High Priority
- [ ] Wizard step 3: Change seats → tables (indoor tables + outdoor tables + table size 2/4/6), calculate seats internally
- [ ] WoodMaker Egypt: change service type from POS → kitchen in DB
- [ ] Insert company images for all seed companies (manual)
- [ ] Garrana + EMAJ starting_from review
- [ ] AC products: insert from vendor panel (Sharp, Carrier, Midea, LG, Fresh, Tornado — 1.5HP to 3HP)

### Medium Priority
- [ ] Dining set ratio per restaurant type (needs 2/4/6/8/10/12-seater products in DB first)
- [ ] More dining set products: 2-seater, 8-seater, 10-seater, 12-seater (insert from vendor)
- [ ] Ambience module: add fan, speaker, air purifier (Option B — parked until after AC products)

### Low Priority / After Presentation
- [ ] Commission calculation for companies
- [ ] Company rating system
- [ ] Bar seating option for fast food
- [ ] 3D table layout visualization (Three.js)
- [ ] Returning user flow from dashboard
- [ ] Company settings page (update website, starting_from price)
- [ ] User decline flow → match to next company
- [ ] AC tonnage-based dynamic pricing fully implemented
- [ ] Module weights table in database (currently hardcoded)

---

## Parked Features (do not implement until DB products exist)

### Dining / Tables
- Table size ratio per restaurant type:
  - Fast Food: 4-seater 50%, 2-seater 30%, 6-seater 20% + bar seating option
  - Standard Dining: 4-seater 50%, 2-seater 25%, 6-seater 20%, 10-seater 5%
  - Premium Dining: 4-seater 45%, 2-seater 20%, 8-seater 25%, 12-seater 10%
- Requires products in DB: 2-seater, 8-seater, 10-seater, 12-seater dining sets
- After products added → implement ratio recommendation in packages.php

### Restaurant Type Affecting Product Recommendations
- Cloud kitchen: no premium combi oven
- Fast food: no luxury dining sets
- Premium dining: higher-end kitchen equipment preferred