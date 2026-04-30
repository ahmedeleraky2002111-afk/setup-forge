<?php
// vendor_login.php  ✅ ONE FILE (UI + BACKEND) — PostgreSQL ($conn)
// Uses: db.php that defines $conn = pg_connect(...)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../db.php";
if (!isset($conn) || !$conn) {
  http_response_code(500);
  die("Database connection missing. Check db.php (\$conn).");
}

function go($url){
  header("Location: " . $url);
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

// -------------------- HANDLE POST (LOGIN) --------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $email = trim((string)($_POST["email"] ?? ""));
  $password = (string)($_POST["password"] ?? "");

  if ($email === "" || $password === "") {
    go("vendor_login.php?error=" . urlencode("Please enter email and password."));
  }

  // Fetch user (vendor only)
  $sql = "
    SELECT id, name, password_hash, user_type, status
    FROM users
    WHERE email = $1
    LIMIT 1
  ";
  $res = pg_query_params($conn, $sql, [$email]);

  if (!$res) {
    go("vendor_login.php?error=" . urlencode("Server error: " . pg_last_error($conn)));
  }

  $user = pg_fetch_assoc($res);

  if (!$user) {
    go("vendor_login.php?error=" . urlencode("Invalid email or password."));
  }

  // Only vendors can login here
  if (($user["user_type"] ?? "") !== "vendor") {
    go("vendor_login.php?error=" . urlencode("This login is for vendors only."));
  }

  // Optional account status check (your enum: active / pending / suspended)
  $st = (string)($user["status"] ?? "");
  if ($st !== "active" && $st !== "pending") {
    go("vendor_login.php?error=" . urlencode("Account status does not allow login."));
  }

  // Verify password (bcrypt)
  if (!password_verify($password, (string)$user["password_hash"])) {
    go("vendor_login.php?error=" . urlencode("Invalid email or password."));
  }

  // ✅ Success: create session
  $_SESSION["user_id"]     = (int)$user["id"];
  $_SESSION["user_type"]   = "vendor";
  $_SESSION["vendor_name"] = (string)($user["name"] ?? "");

  go("vendor_dashboard.php");
}

// -------------------- UI (GET) --------------------
$error = (string)($_GET["error"] ?? "");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vendor Login</title>

  <link rel="stylesheet" href="vendor_ui.css?v=7">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Optional: Inter font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
</head>
<body>
      <!-- VENDOR NAVBAR (website style + vendor links) -->
    <nav class="navbar navbar-expand-lg navbar-dark sf-navbar">
      <div class="container d-flex align-items-center">

        <!-- LEFT -->
        <div class="d-flex align-items-center flex-grow-1">
          <a class="navbar-brand d-flex align-items-center gap-2" href="../home.php">
            <div class="sf-logo"><img src="../assets/images/Logo.png" alt="SetupForge Logo"></div>
            <span class="fw-bold text-white">SetupForge</span>
          </a>
        </div>

        <!-- CENTER -->
        <div class="d-none d-lg-flex justify-content-center flex-grow-1">
          <ul class="navbar-nav align-items-center gap-3">
            <li class="nav-item">
              <a class="nav-link sf-navlink" href="vendor_dashboard.php">Dashboard</a>
            </li>

            <li class="nav-item">
              <a class="nav-link sf-navlink" href="vendor_orders.php">Orders</a>
            </li>

            <li class="nav-item">
              <a class="nav-link sf-navlink" href="vendor_products.php">My Products</a>
            </li>

            <li class="nav-item">
              <a class="nav-link sf-navlink" href="vendor_add_product.php">Add Product</a>
            </li>
          </ul>
        </div>

        <!-- RIGHT -->
        <div class="d-flex justify-content-end flex-grow-1 gap-2">
          <a href="../home.php" class="btn btn-light btn-sm px-3 fw-semibold">Main Site</a>
          <a href="vendor_logout.php" class="btn btn-outline-light btn-sm px-3 fw-semibold">Logout</a>
        </div>

      </div>
    </nav>
  <div class="v-wrap">

    <!-- TOP BAR -->
    <div class="v-topbar">
      <div>
        <h1>Vendor Portal</h1>
        <div class="v-subtitle">Login to manage products and orders</div>
      </div>

      <div class="v-links">
        <a class="v-btn v-btn-outline" href="vendor_signup_step1.php">Create account</a>
      </div>
    </div>

    <!-- LOGIN CARD -->
    <div class="v-section">
      <h3 class="v-section-title">Vendor Login</h3>
      <div class="v-section-desc">Login with your vendor email and password.</div>

      <?php if ($error !== ""): ?>
        <div class="v-alert v-alert-danger"><?= h($error) ?></div>
      <?php endif; ?>

      <form class="v-form" method="POST" action="vendor_login.php">

        <div class="v-form-grid">
          <div class="v-field">
            <label class="v-label" for="email">Email</label>
            <input class="v-input" id="email" type="email" name="email" required placeholder="vendor@email.com">
          </div>

          <div class="v-field">
            <label class="v-label" for="password">Password</label>
            <input class="v-input" id="password" type="password" name="password" required placeholder="••••••••">
          </div>
        </div>

        <div class="v-actions">
          <button class="v-btn v-btn-teal" type="submit">Login</button>
          <a class="v-btn v-btn-outline" href="vendor_signup_step1.php">Sign up</a>
        </div>

        <div class="v-hint">
          If you don’t have an account, <a href="vendor_signup_step1.php">create one here</a>.
        </div>

      </form>
    </div>

  </div>

</body>
</html>