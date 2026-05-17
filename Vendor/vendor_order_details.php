<?php
// /vendor/vendor_order_details.php  ✅ FULL FILE (pg_* / $conn)

session_start();
require_once "../db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_type"] ?? "") !== "vendor") {
  header("Location: ../auth/login.php?error=" . urlencode("Please login as vendor."));
  exit;
}

if (!isset($conn) || !$conn) {
  die("DB connection missing.");
}

$vendorId = (int)$_SESSION["user_id"];
$orderId = (int)($_GET["id"] ?? $_GET["order_id"] ?? 0);
if ($orderId <= 0) {
  http_response_code(400);
  die("Invalid order id.");
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }
function egp($n){ return number_format((float)$n, 0) . " EGP"; }

$allowedStatuses = ["pending","accepted","rejected","processing","ready_for_delivery","delivered"];
$message = "";
$errorMsg = "";

/* 1) Make sure this order belongs to this vendor + get fulfillment row */
$chk = pg_query_params($conn, "
  SELECT
    status,
    estimated_delivery_date,
    accepted_at,
    processing_at,
    delivered_at,
    gross_amount,
    commission_rate,
    commission_amount,
    vendor_payout
  FROM vendor_order_fulfillments
  WHERE order_id = $1 AND vendor_user_id = $2
  LIMIT 1
", [$orderId, $vendorId]);

if (!$chk || pg_num_rows($chk) === 0) {
  http_response_code(403);
  die("Not authorized: this order does not belong to you.");
}
$fulfillment = pg_fetch_assoc($chk);
$currentStatus = (string)($fulfillment["status"] ?? "pending");

/* 2) Handle status update / delivery date update */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $newStatus = trim((string)($_POST["status"] ?? ""));
  $estimatedDeliveryDate = trim((string)($_POST["estimated_delivery_date"] ?? ""));

  $timeField = null;
  if ($newStatus === "accepted") {
    $timeField = "accepted_at";
  } elseif ($newStatus === "processing") {
    $timeField = "processing_at";
  } elseif ($newStatus === "delivered") {
    $timeField = "delivered_at";
  }

  if ($newStatus !== "" && !in_array($newStatus, $allowedStatuses, true)) {
    $errorMsg = "Invalid status value.";
  } else {
    if ($newStatus !== "") {
      if ($timeField) {
        $sql = "
          UPDATE vendor_order_fulfillments
          SET
            status = $1,
            estimated_delivery_date = $2,
            $timeField = NOW()
          WHERE order_id = $3 AND vendor_user_id = $4
        ";
        $up = pg_query_params($conn, $sql, [
          $newStatus,
          $estimatedDeliveryDate !== "" ? $estimatedDeliveryDate : null,
          $orderId,
          $vendorId
        ]);
      } else {
        $sql = "
          UPDATE vendor_order_fulfillments
          SET
            status = $1,
            estimated_delivery_date = $2
          WHERE order_id = $3 AND vendor_user_id = $4
        ";
        $up = pg_query_params($conn, $sql, [
          $newStatus,
          $estimatedDeliveryDate !== "" ? $estimatedDeliveryDate : null,
          $orderId,
          $vendorId
        ]);
      }

      if (!$up) {
        $errorMsg = "Update failed: " . pg_last_error($conn);
      } else {
        $currentStatus = $newStatus;
        $message = "Order updated successfully.";
      }
    } else {
      $up = pg_query_params($conn, "
        UPDATE vendor_order_fulfillments
        SET estimated_delivery_date = $1
        WHERE order_id = $2 AND vendor_user_id = $3
      ", [
        $estimatedDeliveryDate !== "" ? $estimatedDeliveryDate : null,
        $orderId,
        $vendorId
      ]);

      if (!$up) {
        $errorMsg = "Update failed: " . pg_last_error($conn);
      } else {
        $message = "Estimated delivery date updated successfully.";
      }
    }

    // Reload fulfillment after update
    $chk = pg_query_params($conn, "
      SELECT
        status,
        estimated_delivery_date,
        accepted_at,
        processing_at,
        delivered_at,
        gross_amount,
        commission_rate,
        commission_amount,
        vendor_payout
      FROM vendor_order_fulfillments
      WHERE order_id = $1 AND vendor_user_id = $2
      LIMIT 1
    ", [$orderId, $vendorId]);

    if ($chk && pg_num_rows($chk) > 0) {
      $fulfillment = pg_fetch_assoc($chk);
      $currentStatus = (string)($fulfillment["status"] ?? $currentStatus);
    }
  }
}

/* 3) Order header */
$ord = pg_query_params($conn, "
  SELECT
    id,
    order_date,
    delivery_location,
    status,
    payment_status,
    payment_reference,
    paid_at,
    preferred_delivery_date
  FROM orders
  WHERE id = $1
  LIMIT 1
", [$orderId]);

if (!$ord || pg_num_rows($ord) === 0) {
  http_response_code(404);
  die("Order not found.");
}
$order = pg_fetch_assoc($ord);

/* 4) Vendor-only items */
$itemsRes = pg_query_params($conn, "
  SELECT
    p.product_name,
    oi.quantity,
    oi.unit_price,
    (oi.quantity * oi.unit_price) AS line_total
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = $1 AND p.vendor_user_id = $2
  ORDER BY p.product_name
", [$orderId, $vendorId]);

$items = [];
if ($itemsRes) {
  while ($r = pg_fetch_assoc($itemsRes)) $items[] = $r;
}

/* 5) Vendor subtotal */
$subRes = pg_query_params($conn, "
  SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS vendor_subtotal
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = $1 AND p.vendor_user_id = $2
", [$orderId, $vendorId]);

$vendorSubtotal = 0;
if ($subRes) $vendorSubtotal = (float)pg_fetch_result($subRes, 0, "vendor_subtotal");

$grossAmount = (float)($fulfillment["gross_amount"] ?? 0);
$commissionRate = (float)($fulfillment["commission_rate"] ?? 0);
$commissionAmount = (float)($fulfillment["commission_amount"] ?? 0);
$vendorPayout = (float)($fulfillment["vendor_payout"] ?? 0);
$estimatedDeliveryDate = (string)($fulfillment["estimated_delivery_date"] ?? "");
$acceptedAt = (string)($fulfillment["accepted_at"] ?? "");
$processingAt = (string)($fulfillment["processing_at"] ?? "");
$deliveredAt = (string)($fulfillment["delivered_at"] ?? "");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Order #<?= (int)$orderId ?> — Vendor</title>
  <link rel="stylesheet" href="vendor_ui.css?v=<?= time() ?>">
</head>
<body>

<div class="v-wrap">

  <div class="v-head">
    <div>
      <h1 class="v-title">Order #<?= (int)$orderId ?></h1>
      <div class="v-sub">Manage your part of this order.</div>
    </div>
    <div class="v-actions">
      <a class="v-btn v-btn-outline" href="vendor_orders.php">Back</a>
    </div>
  </div>

  <div class="v-section">
    <div class="v-kv">
      <div class="v-kv-item"><span>Order Date</span><b><?= h($order["order_date"] ?? "") ?></b></div>
      <div class="v-kv-item"><span>Delivery Location</span><b><?= h($order["delivery_location"] ?? "—") ?></b></div>
      <div class="v-kv-item"><span>Your Status</span><b><?= h($currentStatus) ?></b></div>
      <div class="v-kv-item"><span>Payment Status</span><b><?= h($order["payment_status"] ?? "—") ?></b></div>
      <div class="v-kv-item"><span>Payment Ref</span><b><?= h($order["payment_reference"] ?? "—") ?></b></div>
      <div class="v-kv-item"><span>Paid At</span><b><?= h($order["paid_at"] ?? "—") ?></b></div>
      <div class="v-kv-item"><span>Preferred Delivery</span><b><?= h($order["preferred_delivery_date"] ?? "—") ?></b></div>
      <div class="v-kv-item"><span>Estimated Delivery</span><b><?= h($estimatedDeliveryDate !== "" ? $estimatedDeliveryDate : "—") ?></b></div>
    </div>

    <?php if ($message): ?>
      <div class="v-alert v-alert-success"><?= h($message) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="v-alert v-alert-danger"><?= h($errorMsg) ?></div>
    <?php endif; ?>
  </div>

  <div class="v-grid-3">
    <div class="v-card">
      <div class="v-metric">
        <div>
          <div class="v-label">Gross Amount</div>
          <div class="v-value"><?= egp($grossAmount) ?></div>
        </div>
        <span class="v-pill">Sales</span>
      </div>
    </div>

    <div class="v-card">
      <div class="v-metric">
        <div>
          <div class="v-label">Commission</div>
          <div class="v-value"><?= egp($commissionAmount) ?></div>
        </div>
        <span class="v-pill"><?= h(number_format($commissionRate, 2)) ?>%</span>
      </div>
    </div>

    <div class="v-card">
      <div class="v-metric">
        <div>
          <div class="v-label">Net Payout</div>
          <div class="v-value"><?= egp($vendorPayout) ?></div>
        </div>
        <span class="v-pill">Your Revenue</span>
      </div>
    </div>
  </div>

  <div class="v-section">
    <div class="v-card-head">
      <div>
        <div class="v-card-title">Items (Your Products Only)</div>
        <div class="v-card-sub">Only your vendor items appear here.</div>
      </div>
    </div>

    <div class="v-table-wrap">
      <table class="v-table">
        <thead>
          <tr>
            <th>Product</th>
            <th class="t-right">Qty</th>
            <th class="t-right">Unit</th>
            <th class="t-right">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr><td colspan="4" class="v-empty">No items for you in this order.</td></tr>
          <?php else: ?>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= h($it["product_name"]) ?></td>
                <td class="t-right"><?= (int)$it["quantity"] ?></td>
                <td class="t-right"><?= egp($it["unit_price"]) ?></td>
                <td class="t-right"><?= egp($it["line_total"]) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="v-total">
      <span>Vendor Subtotal</span>
      <b><?= egp($vendorSubtotal) ?></b>
    </div>
  </div>

  <div class="v-section">
    <div class="v-card-head">
      <div>
        <div class="v-card-title">Timeline</div>
        <div class="v-card-sub">Track your order fulfillment progress.</div>
      </div>
    </div>

    <div class="v-kv">
      <div class="v-kv-item"><span>Accepted At</span><b><?= h($acceptedAt !== "" ? $acceptedAt : "—") ?></b></div>
      <div class="v-kv-item"><span>Processing At</span><b><?= h($processingAt !== "" ? $processingAt : "—") ?></b></div>
      <div class="v-kv-item"><span>Delivered At</span><b><?= h($deliveredAt !== "" ? $deliveredAt : "—") ?></b></div>
    </div>
  </div>

  <div class="v-section">
    <div class="v-card-head">
      <div>
        <div class="v-card-title">Approve / Update</div>
        <div class="v-card-sub">This updates vendor_order_fulfillments details and status.</div>
      </div>
    </div>

    <form method="POST" class="v-stack" style="display:flex; flex-direction:column; gap:16px;">
      <div>
        <label style="display:block; margin-bottom:8px; font-weight:600;">Estimated Delivery Date</label>
        <input
          type="date"
          name="estimated_delivery_date"
          value="<?= h($estimatedDeliveryDate) ?>"
          class="v-input"
          style="max-width:260px; padding:10px 12px; border-radius:12px; border:1px solid #d9e2ec;"
        >
      </div>

      <div class="v-actions">
        <button class="v-btn v-btn-outline" type="submit" name="status" value="accepted">Accept</button>
        <button class="v-btn v-btn-outline" type="submit" name="status" value="rejected">Reject</button>
        <button class="v-btn v-btn-outline" type="submit" name="status" value="processing">Processing</button>
        <button class="v-btn v-btn-outline" type="submit" name="status" value="ready_for_delivery">Ready for Delivery</button>
        <button class="v-btn v-btn-teal" type="submit" name="status" value="delivered">Delivered</button>
      </div>

      <div>
        <button class="v-btn v-btn-outline" type="submit">Save Delivery Date Only</button>
      </div>
    </form>
  </div>

</div>
</body>
</html>