<?php
// vendor_logout.php
session_start();

// Clear session safely
$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), "", time() - 42000,
    $params["path"], $params["domain"], $params["secure"], $params["httponly"]
  );
}
session_destroy();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Logged Out</title>

  <!-- Use same vendor UI css everywhere -->
  <link rel="stylesheet" href="vendor_ui.css?v=7">

  <!-- Optional: Inter font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
</head>
<body>

  <div class="v-wrap">

    <div class="v-topbar">
      <div>
        <h1>Vendor Portal</h1>
        <div class="v-subtitle">Session ended successfully</div>
      </div>

      <div class="v-links">
        <a class="v-btn v-btn-outline" href="vendor_login.php">Back to Login</a>
      </div>
    </div>

    <div class="v-section">
      <h3 class="v-section-title">You’re logged out ✅</h3>
      <div class="v-section-desc">Your session ended successfully.</div>

      <div class="v-divider"></div>

      <div class="v-actions">
        <a class="v-btn v-btn-teal" href="vendor_login.php">Login Again</a>
        <a class="v-btn v-btn-outline" href="vendor_signup_step1.php">Create Account</a>
      </div>

      <div class="v-sub" style="margin-top:12px;">
        If this was a shared device, logging out was the right choice.
      </div>
    </div>

  </div>

</body>
</html>