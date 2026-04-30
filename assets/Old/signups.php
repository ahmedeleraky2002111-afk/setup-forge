<?php
session_start();

require "db.php"; // MUST create $conn using pg_connect()

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Must come from questions flow
if (!isset($_SESSION['setup_answers'])) {
  die("Please start from questions page.");
}

$answers = $_SESSION['setup_answers'];
$error = "";

// Handle POST (form submit)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // Safe reads (avoid undefined array keys)
  $name     = trim($_POST['name'] ?? "");
  $email    = trim($_POST['email'] ?? "");
  $passRaw  = $_POST['password'] ?? "";
  $phone    = trim($_POST['phone'] ?? "");
  $country  = trim($_POST['country'] ?? "");
  $city     = trim($_POST['city'] ?? "");
  $street   = trim($_POST['street'] ?? "");

  // Required fields
  if ($name === "" || $email === "" || $passRaw === "") {
    $error = "Missing required fields (name/email/password).";
  } else {

    // From session (questions)
    $business_name = $answers['business_name'] ?? null;
    $business_type = $answers['business_type'] ?? null;
    $budget        = $answers['budget_egp'] ?? ($answers['budget'] ?? null); // supports both keys
    $place_size    = $answers['place_size'] ?? null;
    $location      = $answers['location_text'] ?? ($answers['location'] ?? null);

    // Hash password
    $password_hash = password_hash($passRaw, PASSWORD_DEFAULT);

    // OPTIONAL: basic duplicate email check
    $check = pg_query_params($conn, "SELECT 1 FROM users WHERE email = $1 LIMIT 1", [$email]);
    if ($check && pg_num_rows($check) > 0) {
      $error = "This email is already registered. Please use another email.";
    } else {

      // 1) INSERT USER
      $sqlUser = "INSERT INTO users
      (name, email, password_hash, user_type, phone, country, city, street)
      VALUES ($1,$2,$3,'business',$4,$5,$6,$7)
      RETURNING id";

      $resUser = pg_query_params($conn, $sqlUser, [
        $name, $email, $password_hash, $phone, $country, $city, $street
      ]);

      if (!$resUser) {
        $error = "User insert failed: " . pg_last_error($conn);
      } else {

        $user_id = pg_fetch_result($resUser, 0, 0);

        // 2) INSERT BUSINESS
        // NOTE: adjust column list here ONLY if your businesses table has different column names.
        $sqlBiz = "INSERT INTO businesses
        (user_id, business_name, business_type, place_size, budget_egp, location_text)
        VALUES ($1,$2,$3,$4,$5,$6)";

        $resBiz = pg_query_params($conn, $sqlBiz, [
          $user_id,
          $business_name,
          $business_type,
          $place_size,
          $budget,
          $location
        ]);

        if (!$resBiz) {
          $error = "Business insert failed: " . pg_last_error($conn);
        } else {
          // Success: clear answers so user can't resubmit accidentally
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = 'business';

            header("Location: setup.php");
            exit;

        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SetupForge - Sign Up</title>
  <style>
    body{font-family:Arial;background:#f5f7fb;margin:0;padding:24px}
    .card{max-width:440px;margin:40px auto;background:white;padding:26px;border-radius:14px;border:1px solid #eee}
    h2{margin:0 0 10px}
    .muted{color:#666;font-size:13px;margin:0 0 14px}
    input{width:100%;padding:10px;margin:8px 0;border:1px solid #ddd;border-radius:10px}
    button{background:#3b5cff;color:white;padding:12px;border:none;width:100%;border-radius:12px;font-weight:800;cursor:pointer}
    button:hover{filter:brightness(1.05)}
    .err{background:#ffe5e5;color:#b10000;padding:10px;border-radius:10px;margin:10px 0;border:1px solid #ffb5b5}
    .box{background:#f7f7fb;border:1px solid #e9e9ee;border-radius:12px;padding:12px;margin-bottom:12px}
    .kv{display:flex;justify-content:space-between;gap:10px;font-size:13px;color:#333;margin:6px 0}
    .k{color:#666}
  </style>
</head>
<body>

<div class="card">
  <h2>Create your account</h2>
  <p class="muted">Your setup answers are saved. Now create the business account.</p>

  <!-- Show setup summary from session -->
  <div class="box">
    <div class="kv"><span class="k">Business Name</span><span><?php echo htmlspecialchars($answers['business_name'] ?? ''); ?></span></div>
    <div class="kv"><span class="k">Business Type</span><span><?php echo htmlspecialchars($answers['business_type'] ?? ''); ?></span></div>
    <div class="kv"><span class="k">Budget</span><span><?php echo htmlspecialchars((string)($answers['budget_egp'] ?? ($answers['budget'] ?? ''))); ?></span></div>
    <div class="kv"><span class="k">Size</span><span><?php echo htmlspecialchars($answers['place_size'] ?? ''); ?></span></div>
    <div class="kv"><span class="k">Location</span><span><?php echo htmlspecialchars($answers['location_text'] ?? ($answers['location'] ?? '')); ?></span></div>
  </div>

  <?php if ($error): ?>
    <div class="err"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" action="signup.php">
    <input name="name" placeholder="Owner name" required>
    <input name="email" type="email" placeholder="Email" required>
    <input name="password" type="password" placeholder="Password" required>
    <input name="phone" placeholder="Phone">
    <input name="country" placeholder="Country">
    <input name="city" placeholder="City">
    <input name="street" placeholder="Street">
    <button type="submit">Create Account</button>
  </form>
</div>

</body>
</html>
