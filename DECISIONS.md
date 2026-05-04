# SetupForge — Project Decisions

## Project Overview
SetupForge is a business setup platform for Egypt. Three services: products (recommendation packages), labor hiring, technician bidding. Built with PHP, PostgreSQL, HTML/CSS/JS.

## Wizard Flow (setup.php) — Changes Needed
- [x] Remove tier selection from step 4 completely
- [x] Replace Small/Medium/Large size with seat count (number input)
- [x] Replace blank budget input with budget range cards (Under 50k / 50k-150k / 150k-300k / 300k+)
- [x] Remove logo upload step 8 entirely
- [x] Remove modules selection from wizard — auto-generate modules based on restaurant type instead
- [ ] Show auto-generated modules as a review screen (pre-checked, user can uncheck)
- [ ] Add location question (city dropdown)
- [ ] Add timeline question (Under 1 month / 1-3 months / 3+ months)
- [ ] Add new vs renovation question

## Restaurant Types (4 only) — Done
- [x] fast_food
- [x] standard_dining → renamed Casual Dining
- [x] premium_dining
- [x] cloud_kitchen → renamed Delivery Only

## Recommendation Engine (packages.php) — Changes Needed
- [x] Derive tier automatically from budget allocation (ratio >= 0.35 → Premium, >= 0.20 → Balanced, else → Starter)
- [x] Replace guess_kitchen_type() with direct product_type column query
- [x] Replace guess_pos_type() with direct product_type column query
- [x] Replace guess_furniture_type() with direct product_type column query
- [x] Add furniture_cart to order_summary.php and place_order.php

## Database — Changes Needed
- [x] Add seat_count integer column to businesses table
- [x] Change labor_data and technician_data columns to JSONB in orders table

## Security — Already Fixed
- [x] Paymob keys moved to config.php (gitignored)
- [x] HMAC verification now rejects with exit
- [x] success.php has ownership check
- [x] payment_failed.php paths fixed

## Three Services Logic
- Products: budget ceiling controls recommendations
- Labor: separate platform, no budget ceiling, commission based
- Technicians: bidding system, separate from products, commission based
- Jobs only created AFTER successful payment (already implemented correctly)

## Future Features (not now)
- 3D wizard experience using Three.js
- Company accounts for technician teams (instead of individual only)
- Cuisine type as profile field (affects kitchen equipment and decor later)
- Module weights table in database
- Product requirements table in database
- Returning user flow from dashboard

## Important Rules When Editing
- Never remove existing cart logic in packages.php
- Always work one change at a time
- Test full flow after every change: wizard → packages → order summary → place order
- Commit before every session