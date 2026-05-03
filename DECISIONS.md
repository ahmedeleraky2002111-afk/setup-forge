# SetupForge — Project Decisions

## Project Overview
SetupForge is a business setup platform for Egypt. Three services: products (e-commerce), labor hiring, technician bidding. Built with PHP, PostgreSQL, HTML/CSS/JS.

## Wizard Flow (setup.php) — Changes Needed
- Remove tier selection from step 4 completely
- Replace Small/Medium/Large size with seat count (number input)
- Replace blank budget input with budget range cards (Under 50k / 50k-150k / 150k-300k / 300k+)
- Remove logo upload step 8 entirely
- Remove modules selection from wizard — auto-generate modules based on restaurant type instead
- Show auto-generated modules as a review screen (pre-checked, user can uncheck)
- Add location question (city dropdown)
- Add timeline question (Under 1 month / 1-3 months / 3+ months)
- Add new vs renovation question

## Restaurant Types (4 only)
- fast_food
- standard_dining
- premium_dining
- cloud_kitchen

## Recommendation Engine (packages.php) — Changes Needed
- Remove tier-from-wizard logic completely
- Derive tier automatically from budget allocation using this function:
  if ratio >= 0.35 → Premium
  if ratio >= 0.20 → Balanced
  else → Starter
- Replace guess_kitchen_type() with direct product_type column query
- Replace guess_pos_type() with direct product_type column query
- Replace guess_furniture_type() with direct product_type column query
- Add furniture_cart to order_summary.php and place_order.php

## Database — Changes Needed
- Add seat_count integer column to businesses table
- Change labor_data and technician_data columns to JSONB in orders table

## Security — Already Fixed
- Paymob keys moved to config.php (gitignored)
- HMAC verification now rejects with exit
- success.php has ownership check
- payment_failed.php paths fixed

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