<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: ../auth/login.php");
  exit;
}

if (!isset($conn) || !$conn) {
  http_response_code(500);
  die("DB connection missing.");
}

$orderId = (int)($_GET["order_id"] ?? 0);
if ($orderId <= 0) {
  die("Invalid order id.");
}

function fail_checkout($msg){
  http_response_code(400);
  echo "<h3>PAYMENT ERROR</h3><p>" . htmlspecialchars($msg) . "</p>";
  exit;
}

function post_json($url, $payload){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
      "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 60,
  ]);
  $raw = curl_exec($ch);
  $err = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false) {
    fail_checkout("cURL error: " . $err);
  }

  $json = json_decode($raw, true);
  if (!is_array($json)) {
    fail_checkout("Invalid Paymob response. HTTP $code");
  }

  return [$json, $code, $raw];
}

/*
|--------------------------------------------------------------------------
| PAYMOB CONFIG
|--------------------------------------------------------------------------
| Replace these with your real values from Paymob dashboard
*/
define("PAYMOB_API_KEY", "ZXlKaGJHY2lPaUpJVXpVeE1pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SmpiR0Z6Y3lJNklrMWxjbU5vWVc1MElpd2ljSEp2Wm1sc1pWOXdheUk2TVRFMU1UUTVOeXdpYm1GdFpTSTZJbWx1YVhScFlXd2lmUS5lRDd6eVZVb2hNLW02QXU5NDg0NTBJd1lwS0J1QjFFR2U4c3kyc0tpXzAyb0lWWDFRaTdNZDBZZTNwTjB6TkFyZHRsZVk5Qmx3cklSQ2FocjFfM0Fsdw==");
define("PAYMOB_INTEGRATION_ID", 5609912); // payment integration id
define("PAYMOB_IFRAME_ID", 1030719);      // hosted iframe id
define("PAYMOB_HMAC_SECRET", "FDE943B9CD3ACC94E310EA5E774F4B0E");

/*
|--------------------------------------------------------------------------
| Load order + ownership
|--------------------------------------------------------------------------
*/
$orderRes = pg_query_params($conn, "
  SELECT o.*, u.name, u.email, u.phone, u.city, u.country, u.street
  FROM orders o
  LEFT JOIN users u
    ON (
      (o.customer_user_id IS NOT NULL AND u.id = o.customer_user_id)
      OR
      (o.business_user_id IS NOT NULL AND u.id = o.business_user_id)
    )
  WHERE o.id = $1
  LIMIT 1
", [$orderId]);

if (!$orderRes || pg_num_rows($orderRes) === 0) {
  fail_checkout("Order not found.");
}

$order = pg_fetch_assoc($orderRes);

$userId = (int)$_SESSION["user_id"];
$orderCustomerId = isset($order["customer_user_id"]) && $order["customer_user_id"] !== null ? (int)$order["customer_user_id"] : 0;
$orderBusinessId = isset($order["business_user_id"]) && $order["business_user_id"] !== null ? (int)$order["business_user_id"] : 0;

if ($orderCustomerId > 0 && $orderCustomerId !== $userId) {
  fail_checkout("Unauthorized.");
}
if ($orderBusinessId > 0 && $orderBusinessId !== $userId) {
  fail_checkout("Unauthorized.");
}

if (($order["payment_status"] ?? "") === "paid") {
  header("Location: success.php?order_id=" . urlencode((string)$orderId));
  exit;
}

$amountCents = (int) round(((float)$order["order_total"]) * 100);
if ($amountCents <= 0) {
  fail_checkout("Invalid order total.");
}

/*
|--------------------------------------------------------------------------
| Billing data
|--------------------------------------------------------------------------
*/
$fullName = trim((string)($order["name"] ?? "Customer"));
$nameParts = preg_split('/\s+/', $fullName, 2);
$firstName = $nameParts[0] ?? "Customer";
$lastName  = $nameParts[1] ?? "User";

$email = trim((string)($order["email"] ?? ""));
$phone = trim((string)($order["phone"] ?? ""));
$city = trim((string)($order["city"] ?? ""));
$country = trim((string)($order["country"] ?? ""));
$street = trim((string)($order["street"] ?? ""));

if ($email === "")   $email = "no-reply@example.com";
if ($phone === "")   $phone = "+201000000000";
if ($city === "")    $city = "Cairo";
if ($country === "") $country = "EG";
if ($street === "")  $street = "NA";

/*
|--------------------------------------------------------------------------
| 1) Auth token
|--------------------------------------------------------------------------
*/
list($authJson) = post_json(
  "https://accept.paymob.com/api/auth/tokens",
  [
    "api_key" => PAYMOB_API_KEY
  ]
);

$authToken = $authJson["token"] ?? null;
if (!$authToken) {
  fail_checkout("Failed to get Paymob auth token.");
}

/*
|--------------------------------------------------------------------------
| 2) Register order at Paymob
|--------------------------------------------------------------------------
| merchant_order_id = your local orders.id
*/
list($paymobOrderJson) = post_json(
  "https://accept.paymob.com/api/ecommerce/orders",
  [
    "auth_token" => $authToken,
    "delivery_needed" => false,
    "amount_cents" => $amountCents,
    "currency" => "EGP",
    "merchant_order_id" => (string)$orderId,
    "items" => []
  ]
);

$paymobOrderId = $paymobOrderJson["id"] ?? null;
if (!$paymobOrderId) {
  fail_checkout("Failed to register Paymob order.");
}

/*
|--------------------------------------------------------------------------
| 3) Payment key
|--------------------------------------------------------------------------
*/
list($paymentKeyJson) = post_json(
  "https://accept.paymob.com/api/acceptance/payment_keys",
  [
    "auth_token" => $authToken,
    "amount_cents" => $amountCents,
    "expiration" => 3600,
    "order_id" => (int)$paymobOrderId,
    "billing_data" => [
      "apartment" => "NA",
      "email" => $email,
      "floor" => "NA",
      "first_name" => $firstName,
      "street" => $street,
      "building" => "NA",
      "phone_number" => $phone,
      "shipping_method" => "PKG",
      "postal_code" => "NA",
      "city" => $city,
      "country" => $country,
      "last_name" => $lastName,
      "state" => "NA"
    ],
    "currency" => "EGP",
    "integration_id" => (int)PAYMOB_INTEGRATION_ID
  ]
);

$paymentToken = $paymentKeyJson["token"] ?? null;
if (!$paymentToken) {
  fail_checkout("Failed to get Paymob payment token.");
}

/*
|--------------------------------------------------------------------------
| 4) Redirect customer to Paymob hosted card page
|--------------------------------------------------------------------------
*/
$iframeUrl = "https://accept.paymob.com/api/acceptance/iframes/" . rawurlencode((string)PAYMOB_IFRAME_ID) . "?payment_token=" . rawurlencode($paymentToken);
header("Location: " . $iframeUrl);
exit;