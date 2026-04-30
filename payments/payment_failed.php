<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../auth/login.php");
  exit;
}

$orderId = (int)($_GET["order_id"] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SetupForge - Payment Failed</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<main class="container py-5">
  <div class="p-4 border rounded-4 bg-white text-center">
    <h1 class="fw-bold mb-2">❌ Payment Failed</h1>
    <p class="text-secondary mb-4">
      Your payment was not completed. Please try again.
    </p>

    <?php if ($orderId > 0): ?>
      <div class="mb-4">
        <span class="badge text-bg-dark">Order #<?= htmlspecialchars((string)$orderId) ?></span>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-center gap-2 flex-wrap">
      <?php if ($orderId > 0): ?>
        <a href="paymob_checkout.php?order_id=<?= urlencode((string)$orderId) ?>" class="btn btn-dark px-4">
          Try Again
        </a>
      <?php endif; ?>

      <a href="../order_summary.php" class="btn btn-outline-secondary px-4">
        Back to Summary
      </a>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>