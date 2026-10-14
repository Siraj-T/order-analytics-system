# Order Analytics System

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql)
![Design](https://img.shields.io/badge/Design-Apple%20HIG%20%26%20WWDC-0066cc?style=for-the-badge&logo=apple)

The **Order Analytics System** is a high-performance web platform built to navigate, explore, and analyze an enterprise e-commerce dataset containing **4,025,000 records** (186 MB SQL dump):
- **Customers:** 25,000 global user records.
- **Orders:** 1,000,000 transaction orders (`completed`, `pending`, `refunded`, `cancelled`).
- **Order Items:** 3,000,000 product line items with unit pricing and quantities.

Designed strictly as a read-only interaction system, it demonstrates sub-10ms query execution via deferred-join server-side pagination, consolidated metric extraction, and Apple's WWDC fluid interface design language.

---

## 🌟 Key Features

1. **Executive Analytics Dashboard:**
   - Single-query consolidated raw aggregates (`selectRaw`) for 6 financial KPIs in a single pass.
   - Background live auto-sync (`/admin/dashboard/live?v={version}`) updating via Blade fragments in <1ms without full-page reloads.
   - Visual status distribution breakdown across 1,000,000 transactions.

2. **Orders Explorer (Server-Side 10-Item Pagination):**
   - High-performance deferred-join pagination ($O(\log N) / O(1)$) preventing browser freezing.
   - Filter by status tabs (Completed, Pending, Refunded, Cancelled), shipping country, date range, or customer search.
   - **Slide-Over Item Inspector Drawer:** Loads 3M line items on demand in <2ms with interruptible critically damped spring physics.

3. **Customers Directory:**
   - Search across 25,000 customer accounts with real-time Lifetime Value (LTV) calculation.
   - Interactive profile modal detailing customer lifetime order history.

4. **Product Insights Leaderboard:**
   - Top 25 products by gross revenue and sales volume aggregated across 3,000,000 items.
   - Satisfied via composite covering index `idx_items_product_metrics`.

5. **Apple HIG & WWDC Fluid Motion:**
   - Apple design tokens: Action Blue (`#0066cc`), SF Pro typography with negative optical letter-spacing, and tabular lining figures.
   - Translucent glassmorphism materials (`backdrop-filter: blur(20px) saturate(180%)`).
   - Zero-latency press feedback (`:active { transform: scale(0.98); }`).

---

## 🚀 Installation & Local Setup

### 1. Clone Repository:
```bash
git clone https://github.com/Siraj-T/order-analytics-system.git
cd order-analytics-system
```

### 2. Install Dependencies:
```bash
composer install
npm install
```

### 3. Environment Configuration:
```bash
cp .env.example .env
php artisan key:generate
```
*Configure your MySQL credentials in `.env` (default: `order_analytics_db` on `127.0.0.1:3306`).*

### 4. Import the 186 MB SQL Dump (4,025,000 Records):
Stream-load the full dataset with automated index optimization:
```bash
php artisan app:import-dump
```

### 5. Launch the Application:
```bash
php artisan serve
```
Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## 🧪 Testing

Run the automated test suite:
```bash
php artisan test
```

---

## 🛡️ License

MIT License. Designed and engineered for high-scale database analytics.
