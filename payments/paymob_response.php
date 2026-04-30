<?php
session_start();

$orderId = (int)($_GET["merchant_order_id"] ?? $_GET["order_id"] ?? $_GET["id"] ?? 0);

if ($orderId > 0) {
    header("Location: success.php?order_id=" . urlencode((string)$orderId));
    exit;
}

header("Location: ../order_summary.php");
exit;