<?php
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: auth/login.php?next=" . urlencode("order_summary.php"));
  exit;
}

require_once "db.php";
function egp($n){ return number_format((float)$n, 0) . " EGP"; }

/**
 * UNIVERSAL CART LOADER
 * - Prefer $_SESSION["carts"]
 * - If not set, fallback to wizard carts
 */
if (!isset($_SESSION["carts"]) || !is_array($_SESSION["carts"])) {
  $_SESSION["carts"] = [];
}
if (!empty($_SESSION["wizard"]["pos_cart"])) {
  $_SESSION["carts"]["pos"] = $_SESSION["wizard"]["pos_cart"];
}
if (!empty($_SESSION["wizard"]["kitchen_cart"])) {
  $_SESSION["carts"]["kitchen"] = $_SESSION["wizard"]["kitchen_cart"];
}

$carts = $_SESSION["carts"];

/** helpers */
function cart_items($cart){
  return ($cart && isset($cart["items"]) && is_array($cart["items"])) ? $cart["items"] : [];
}
function cart_total($cart){
  $sum = 0;
  foreach(cart_items($cart) as $it){
    $sum += ((int)($it["qty"] ?? 0)) * ((float)($it["unit"] ?? 0));
  }
  return $sum;
}

/**
 * Flatten all carts into one list for summary table (universal)
 * Each row will keep:
 * - module
 * - name
 * - vendor_name (if exists)
 * - product_id (if exists)
 * - qty/unit
 */
$allRows = [];
$grandTotal = 0;

foreach($carts as $module => $cart){
  $items = cart_items($cart);
  if (!$items) continue;

  foreach($items as $type => $it){
    $qty  = (int)($it["qty"] ?? 0);
    $unit = (float)($it["unit"] ?? 0);
    if ($qty <= 0 || $unit <= 0) continue;

    $rowTotal = $qty * $unit;
    $grandTotal += $rowTotal;

    $allRows[] = [
      "module" => $module,
      "type" => $type,
      "name" => (string)($it["name"] ?? ""),
      "vendor_name" => (string)($it["vendor_name"] ?? ""),
      "product_id" => $it["product_id"] ?? null,
      "qty" => $qty,
      "unit" => $unit,
      "total" => $rowTotal,
    ];
  }
}

$hasAnyItems = ($grandTotal > 0);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SetupForge - Order Summary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<main class="container py-4">
  <div class="p-3 p-lg-4 border rounded-4 bg-white">

    <div class="d-flex justify-content-between align-items-start gap-3">
      <div>
        <h2 class="fw-bold mb-1">Order Summary</h2>
        <div class="text-secondary">Review everything before placing the order.</div>
      </div>
      <div class="text-end">
        <div class="text-secondary small">Grand Total</div>
        <div class="fw-bold fs-4"><?= egp($grandTotal) ?></div>
      </div>
    </div>

    <hr>

    <?php if(!$hasAnyItems): ?>
      <div class="alert alert-warning">Your order is empty. Go back and add items.</div>
    <?php else: ?>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Module</th>
              <th>Item</th>
              <th class="text-end">Unit</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($allRows as $r): ?>
              <tr>
                <td class="text-uppercase fw-semibold"><?= htmlspecialchars($r["module"]) ?></td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($r["name"]) ?></div>
                  <?php if(!empty($r["vendor_name"])): ?>
                    <div class="text-secondary small">Vendor: <?= htmlspecialchars($r["vendor_name"]) ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-end"><?= egp($r["unit"]) ?></td>
                <td class="text-end"><?= (int)$r["qty"] ?></td>
                <td class="text-end fw-semibold"><?= egp($r["total"]) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="text-end text-secondary small">
        Grand Total: <span class="fw-semibold"><?= egp($grandTotal) ?></span>
      </div>

      <hr>

      <!-- optional delivery location -->
      <form method="post" action="place_order.php" class="m-0">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-lg-8">
            <label class="form-label text-secondary small mb-1">Delivery location (optional)</label>
            <input type="text" name="delivery_location" class="form-control" placeholder="Ex: Cairo, Nasr City, Street ...">
          </div>
          <div class="col-12 col-lg-4 text-lg-end">
            <button type="submit" class="btn btn-dark px-4">Confirm & Place Order</button>
          </div>
        </div>
      </form>

    <?php endif; ?>

    <div class="mt-3">
      <a href="packages.php" class="btn btn-outline-secondary px-4">← Back</a>
    </div>

  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>