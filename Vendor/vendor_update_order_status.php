<?php
session_start();
require_once "../db.php";
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_type"] ?? "") !== "vendor") {
  header("Location: ../auth/login.php");
  exit;
}

if (!isset($conn) || !$conn) {
  http_response_code(500);
  die("DB connection missing. Check db.php (\$conn).");
}

$vendorId = (int)$_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: vendor_orders.php");
  exit;
}

$orderId = (int)($_POST["order_id"] ?? 0);
$newStatus = trim((string)($_POST["status"] ?? ""));

$allowed = ["accepted","rejected","processing","delivered"];
if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) {
  header("Location: vendor_orders.php");
  exit;
}

// ensure belongs to vendor
$res = pg_query_params($conn,
  "SELECT 1 FROM vendor_order_fulfillments WHERE order_id=$1 AND vendor_user_id=$2 LIMIT 1",
  [$orderId, $vendorId]
);
if (!$res || pg_num_rows($res) === 0) {
  header("Location: vendor_orders.php");
  exit;
}

// update
$upd = pg_query_params($conn,
  "UPDATE vendor_order_fulfillments SET status=$1 WHERE order_id=$2 AND vendor_user_id=$3",
  [$newStatus, $orderId, $vendorId]
);

header("Location: vendor_order_details.php?order_id=" . urlencode((string)$orderId));
exit;