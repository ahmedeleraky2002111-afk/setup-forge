<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

$business_id = (int)$_SESSION["user_id"];

/* CHECK BUSINESS EXISTS */
$businessRes = pg_query_params($conn, "
    SELECT
        b.user_id,
        b.business_name,
        b.business_type,
        b.place_size,
        b.budget_egp,
        b.location_text,
        u.name,
        u.email,
        u.phone,
        u.city,
        u.country
    FROM businesses b
    JOIN users u ON b.user_id = u.id
    WHERE b.user_id = $1
    LIMIT 1
", [$business_id]);

if (!$businessRes || pg_num_rows($businessRes) === 0) {
    die("Business account not found.");
}

$business = pg_fetch_assoc($businessRes);

/* LOAD ORDER */
$order_id = (int)($_GET["order_id"] ?? 0);

if ($order_id > 0) {
    $orderRes = pg_query_params($conn, "
        SELECT *
        FROM orders
        WHERE id = $1
          AND business_user_id = $2
        LIMIT 1
    ", [$order_id, $business_id]);
} else {
    $orderRes = pg_query_params($conn, "
        SELECT *
        FROM orders
        WHERE business_user_id = $1
        ORDER BY id DESC
        LIMIT 1
    ", [$business_id]);
}

$order = null;
if ($orderRes && pg_num_rows($orderRes) > 0) {
    $order = pg_fetch_assoc($orderRes);
    $order_id = (int)$order["id"];
}

/* ORDER PRODUCTS */
$productRows = false;
if ($order) {
    $productRows = pg_query_params($conn, "
        SELECT
            oi.quantity,
            oi.unit_price,
            p.product_name,
            p.brand,
            p.module,
            u.name AS vendor_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN users u ON p.vendor_user_id = u.id
        WHERE oi.order_id = $1
        ORDER BY p.module, p.product_name
    ", [$order_id]);
}

/* LABOR SUMMARY */
$laborSummary = pg_query_params($conn, "
    SELECT
        j.title,
        j.location,
        COUNT(*) AS total_openings,
        COUNT(*) FILTER (WHERE j.worker_id IS NOT NULL) AS filled_openings,
        COUNT(*) FILTER (WHERE j.worker_id IS NULL) AS remaining_openings,
        COALESCE(
            STRING_AGG(DISTINCT uw.name, ', ') FILTER (WHERE j.worker_id IS NOT NULL),
            ''
        ) AS hired_workers
    FROM jobs j
    LEFT JOIN users uw ON j.worker_id = uw.id
    WHERE j.business_id = $1
      AND j.job_type = 'labor'
    GROUP BY j.title, j.location
    ORDER BY j.title ASC
", [$business_id]);

/* TECHNICIAN SUMMARY */
$technicianSummary = pg_query_params($conn, "
    SELECT
        j.job_id,
        j.title,
        j.location,
        j.status,
        j.budget,
        COALESCE(COUNT(b.bid_id), 0) AS bids_count,
        uw.name AS assigned_technician
    FROM jobs j
    LEFT JOIN bids b ON j.job_id = b.job_id
    LEFT JOIN users uw ON j.worker_id = uw.id
    WHERE j.business_id = $1
      AND j.job_type = 'technician'
    GROUP BY j.job_id, j.title, j.location, j.status, j.budget, uw.name
    ORDER BY j.job_id DESC
", [$business_id]);

function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function egp($n){
    return number_format((float)$n, 0) . " EGP";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Business Overview - SetupForge</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<main class="container py-5">

  <div class="sf-overview-hero mb-4">
    <div>
      <span class="sf-page-kicker">Business Control Panel</span>
      <h1 class="sf-page-title mb-2">Business Overview</h1>
      <p class="sf-overview-sub mb-0">
        Review your setup, products, labor hiring, and technician progress in one place.
      </p>
    </div>

    <?php if ($order): ?>
      <div class="text-end">
        <div class="sf-overview-chip mb-2">Order #<?= (int)$order_id; ?></div>
        <div class="small text-secondary">Latest tracked order</div>
      </div>
    <?php endif; ?>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
      <div class="sf-mini-stat">
        <div class="sf-mini-stat-label">Business Type</div>
        <div class="sf-mini-stat-value"><?= h($business["business_type"] ?: "—"); ?></div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="sf-mini-stat">
        <div class="sf-mini-stat-label">Place Size</div>
        <div class="sf-mini-stat-value"><?= h($business["place_size"] ?: "—"); ?></div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="sf-mini-stat">
        <div class="sf-mini-stat-label">Budget</div>
        <div class="sf-mini-stat-value"><?= $business["budget_egp"] ? egp($business["budget_egp"]) : "—"; ?></div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="sf-mini-stat">
        <div class="sf-mini-stat-label">Location</div>
        <div class="sf-mini-stat-value"><?= h($business["location_text"] ?: $business["city"] ?: "—"); ?></div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <div class="col-12 col-lg-5">
      <div class="card sf-overview-card h-100">
        <div class="card-body p-4">
          <div class="sf-overview-title">Business Information</div>

          <div class="sf-info-row">
            <div class="sf-info-label">Business Name</div>
            <div class="sf-info-value"><?= h($business["business_name"] ?: $business["name"]); ?></div>
          </div>

          <div class="sf-info-row">
            <div class="sf-info-label">Owner / Account Name</div>
            <div class="sf-info-value"><?= h($business["name"]); ?></div>
          </div>

          <div class="sf-info-row">
            <div class="sf-info-label">Email</div>
            <div class="sf-info-value"><?= h($business["email"]); ?></div>
          </div>

          <div class="sf-info-row">
            <div class="sf-info-label">Phone</div>
            <div class="sf-info-value"><?= h($business["phone"] ?: "—"); ?></div>
          </div>

          <div class="sf-info-row">
            <div class="sf-info-label">Country / City</div>
            <div class="sf-info-value"><?= h(trim(($business["country"] ?? "") . " " . ($business["city"] ?? "")) ?: "—"); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-7">
      <div class="card sf-overview-card h-100">
        <div class="card-body p-4">
          <div class="sf-overview-title">Latest Order Summary</div>

          <?php if ($order): ?>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="sf-mini-stat">
                  <div class="sf-mini-stat-label">Order ID</div>
                  <div class="sf-mini-stat-value">#<?= (int)$order["id"]; ?></div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="sf-mini-stat">
                  <div class="sf-mini-stat-label">Order Total</div>
                  <div class="sf-mini-stat-value"><?= egp($order["order_total"]); ?></div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="sf-mini-stat">
                  <div class="sf-mini-stat-label">Order Status</div>
                  <div class="sf-mini-stat-value"><?= h(ucfirst($order["status"])); ?></div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="sf-mini-stat">
                  <div class="sf-mini-stat-label">Payment Status</div>
                  <div class="sf-mini-stat-value"><?= h(ucfirst($order["payment_status"])); ?></div>
                </div>
              </div>
            </div>

            <div class="mt-3">
              <?php if (!empty($order["payment_reference"])): ?>
                <div class="small text-secondary mb-1">Payment Reference: <strong><?= h($order["payment_reference"]); ?></strong></div>
              <?php endif; ?>
              <?php if (!empty($order["paid_at"])): ?>
                <div class="small text-secondary">Paid At: <strong><?= h($order["paid_at"]); ?></strong></div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="text-secondary">No order found yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card sf-overview-card">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="sf-overview-title mb-0">Ordered Products</div>
            <?php if ($order): ?>
              <div class="small text-secondary">For order #<?= (int)$order_id; ?></div>
            <?php endif; ?>
          </div>

          <?php if ($productRows && pg_num_rows($productRows) > 0): ?>
            <div class="table-responsive">
              <table class="table align-middle sf-overview-table">
                <thead>
                  <tr>
                    <th>Module</th>
                    <th>Product</th>
                    <th>Vendor</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = pg_fetch_assoc($productRows)): ?>
                    <tr>
                      <td class="text-uppercase fw-semibold"><?= h($row["module"] ?: "general"); ?></td>
                      <td>
                        <div class="fw-semibold"><?= h($row["product_name"]); ?></div>
                        <?php if (!empty($row["brand"])): ?>
                          <div class="small text-secondary"><?= h($row["brand"]); ?></div>
                        <?php endif; ?>
                      </td>
                      <td><?= h($row["vendor_name"] ?: "—"); ?></td>
                      <td class="text-end"><?= egp($row["unit_price"]); ?></td>
                      <td class="text-end"><?= (int)$row["quantity"]; ?></td>
                      <td class="text-end fw-semibold"><?= egp(((float)$row["unit_price"]) * ((int)$row["quantity"])); ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-secondary">No products found for this order.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="card sf-overview-card h-100">
        <div class="card-body p-4">
          <div class="sf-overview-title">Labor Hiring Summary</div>

          <?php if ($laborSummary && pg_num_rows($laborSummary) > 0): ?>
            <div class="table-responsive">
              <table class="table align-middle sf-overview-table">
                <thead>
                  <tr>
                    <th>Role</th>
                    <th>Location</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Filled</th>
                    <th class="text-center">Remaining</th>
                    <th>Hired Workers</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = pg_fetch_assoc($laborSummary)): ?>
                    <tr>
                      <td class="fw-semibold"><?= h($row["title"]); ?></td>
                      <td><?= h($row["location"]); ?></td>
                      <td class="text-center"><?= (int)$row["total_openings"]; ?></td>
                      <td class="text-center"><?= (int)$row["filled_openings"]; ?></td>
                      <td class="text-center"><?= (int)$row["remaining_openings"]; ?></td>
                      <td><?= h($row["hired_workers"] ?: "—"); ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-secondary">No labor jobs generated yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="card sf-overview-card h-100">
        <div class="card-body p-4">
          <div class="sf-overview-title">Technician Services Summary</div>

          <?php if ($technicianSummary && pg_num_rows($technicianSummary) > 0): ?>
            <div class="table-responsive">
              <table class="table align-middle sf-overview-table">
                <thead>
                  <tr>
                    <th>Service</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th class="text-center">Bids</th>
                    <th>Assigned Technician</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = pg_fetch_assoc($technicianSummary)): ?>
                    <tr>
                      <td class="fw-semibold"><?= h($row["title"]); ?></td>
                      <td><?= h($row["location"]); ?></td>
                      <td><?= h(ucfirst($row["status"])); ?></td>
                      <td class="text-center"><?= (int)$row["bids_count"]; ?></td>
                      <td><?= h($row["assigned_technician"] ?: "—"); ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-secondary">No technician jobs generated yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
