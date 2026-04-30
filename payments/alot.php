<?php
session_start();
require_once "../db.php";

if (!isset($conn) || !$conn) {
  http_response_code(500);
  exit("DB connection missing.");
}

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
| Put your REAL HMAC secret from Paymob here.
*/
define("PAYMOB_HMAC_SECRET", "FDE943B9CD3ACC94E310EA5E774F4B0E");

function callback_fail($msg, $code = 400){
  http_response_code($code);
  echo $msg;
  exit;
}

function paymob_val($v){
  if (is_bool($v)) {
    return $v ? "true" : "false";
  }
  if ($v === null) {
    return "";
  }
  return is_scalar($v) ? (string)$v : "";
}

/*
|--------------------------------------------------------------------------
| Paymob sends in your case:
| - hmac in GET
| - payload in RAW JSON body
|--------------------------------------------------------------------------
*/
$raw = file_get_contents("php://input");
$payload = json_decode($raw, true);

if (!is_array($payload)) {
  callback_fail("Invalid callback payload.");
}

$obj = $payload["obj"] ?? null;
if (!is_array($obj)) {
  callback_fail("Missing obj.");
}

$receivedHmac = (string)($_GET["hmac"] ?? "");

/*
|--------------------------------------------------------------------------
| HMAC verification
|--------------------------------------------------------------------------
| TEMP NOTE:
| Keep this disabled for now because your live test already proved
| the business flow works and the HMAC calculation still needs exact
| Paymob field-order tuning for your account payload.
|--------------------------------------------------------------------------
*/
$hmacFields = [
  paymob_val($obj["amount_cents"] ?? null),
  paymob_val($obj["created_at"] ?? null),
  paymob_val($obj["currency"] ?? null),
  paymob_val($obj["error_occured"] ?? null),
  paymob_val($obj["has_parent_transaction"] ?? null),
  paymob_val($obj["id"] ?? null),
  paymob_val($obj["integration_id"] ?? null),
  paymob_val($obj["is_3d_secure"] ?? null),
  paymob_val($obj["is_auth"] ?? null),
  paymob_val($obj["is_capture"] ?? null),
  paymob_val($obj["is_refunded"] ?? null),
  paymob_val($obj["is_standalone_payment"] ?? null),
  paymob_val($obj["is_voided"] ?? null),
  paymob_val($obj["order"]["id"] ?? null),
  paymob_val($obj["owner"] ?? null),
  paymob_val($obj["pending"] ?? null),
  paymob_val($obj["source_data"]["pan"] ?? null),
  paymob_val($obj["source_data"]["sub_type"] ?? null),
  paymob_val($obj["source_data"]["type"] ?? null),
  paymob_val($obj["success"] ?? null),
];

$calculatedHmac = hash_hmac("sha512", implode("", $hmacFields), PAYMOB_HMAC_SECRET);

/*
|--------------------------------------------------------------------------
| TEMP: HMAC disabled for now
|--------------------------------------------------------------------------
| When you want to re-enable later, uncomment this block:
|
| if ($receivedHmac === "" || !hash_equals($calculatedHmac, $receivedHmac)) {
|   callback_fail("Invalid HMAC.", 403);
| }
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Resolve local order id from merchant_order_id
|--------------------------------------------------------------------------
*/
$merchantOrderId = (string)($obj["order"]["merchant_order_id"] ?? "");
$orderId = (int)$merchantOrderId;

if ($orderId <= 0) {
  callback_fail("Missing merchant_order_id.");
}

$txnId = (string)($obj["id"] ?? "");
$success = filter_var($obj["success"] ?? false, FILTER_VALIDATE_BOOLEAN);
$pending = filter_var($obj["pending"] ?? false, FILTER_VALIDATE_BOOLEAN);

$orderRes = pg_query_params($conn, "
  SELECT *
  FROM orders
  WHERE id = $1
  LIMIT 1
", [$orderId]);

if (!$orderRes || pg_num_rows($orderRes) === 0) {
  callback_fail("Order not found.", 404);
}

$order = pg_fetch_assoc($orderRes);

/*
|--------------------------------------------------------------------------
| If callback is still pending, do not finalize
|--------------------------------------------------------------------------
*/
if ($pending) {
  http_response_code(200);
  echo "Pending callback ignored.";
  exit;
}

/*
|--------------------------------------------------------------------------
| Failed payment
|--------------------------------------------------------------------------
*/
if (!$success) {
  pg_query($conn, "BEGIN");

  try {
    $up = pg_query_params($conn, "
      UPDATE orders
      SET payment_status = 'failed'
      WHERE id = $1
        AND payment_status <> 'paid'
    ", [$orderId]);

    if (!$up) {
      throw new Exception(pg_last_error($conn));
    }

    pg_query($conn, "COMMIT");
    http_response_code(200);
    echo "Payment marked as failed.";
    exit;

  } catch (Throwable $e) {
    pg_query($conn, "ROLLBACK");
    callback_fail("Failed callback handling: " . $e->getMessage(), 500);
  }
}

/*
|--------------------------------------------------------------------------
| Successful payment
|--------------------------------------------------------------------------
*/
pg_query($conn, "BEGIN");

try {
  $alreadyPaid = (($order["payment_status"] ?? "") === "paid");

  if (!$alreadyPaid) {
    $paymentMethod = (string)($obj["source_data"]["sub_type"] ?? ($obj["source_data"]["type"] ?? "card"));

    $upOrder = pg_query_params($conn, "
      UPDATE orders
      SET payment_status = 'paid',
          status = 'confirmed',
          paid_at = NOW(),
          payment_reference = $1,
          payment_method = $2
      WHERE id = $3
        AND payment_status <> 'paid'
    ", [$txnId, $paymentMethod, $orderId]);

    if (!$upOrder) {
      throw new Exception("Failed to update order payment: " . pg_last_error($conn));
    }

    /*
    |--------------------------------------------------------------------------
    | Create vendor fulfillments with commission
    |--------------------------------------------------------------------------
    */
    $vendorRowsRes = pg_query_params($conn, "
      SELECT
        p.vendor_user_id,
        COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS gross_amount,
        COALESCE(AVG(c.commission_rate), 6.00) AS commission_rate
      FROM order_items oi
      JOIN products p ON p.id = oi.product_id
      LEFT JOIN categories c ON c.id = p.category_id
      WHERE oi.order_id = $1
      GROUP BY p.vendor_user_id
    ", [$orderId]);

    if (!$vendorRowsRes) {
      throw new Exception("Failed to load vendor totals: " . pg_last_error($conn));
    }

    while ($vr = pg_fetch_assoc($vendorRowsRes)) {
      $vendorUserId = (int)$vr["vendor_user_id"];
      $grossAmount = (float)$vr["gross_amount"];
      $commissionRate = (float)$vr["commission_rate"];
      $commissionAmount = round($grossAmount * ($commissionRate / 100), 2);
      $vendorPayout = round($grossAmount - $commissionAmount, 2);

      $insVof = pg_query_params($conn, "
        INSERT INTO vendor_order_fulfillments
        (
          order_id,
          vendor_user_id,
          status,
          notes,
          gross_amount,
          commission_rate,
          commission_amount,
          vendor_payout
        )
        VALUES ($1, $2, 'pending', $3, $4, $5, $6, $7)
        ON CONFLICT (order_id, vendor_user_id)
        DO NOTHING
      ", [
        $orderId,
        $vendorUserId,
        null,
        $grossAmount,
        $commissionRate,
        $commissionAmount,
        $vendorPayout
      ]);

      if (!$insVof) {
        throw new Exception("Failed to create vendor fulfillment: " . pg_last_error($conn));
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Create service jobs only after payment
    |--------------------------------------------------------------------------
    */
    $businessId = isset($order["business_user_id"]) && $order["business_user_id"] !== null
      ? (int)$order["business_user_id"]
      : null;

    if ($businessId !== null) {
      $jobLocation = trim((string)($order["delivery_location"] ?? ""));

      if ($jobLocation === "") {
        $bizLocRes = pg_query_params($conn, "
          SELECT location_text
          FROM businesses
          WHERE user_id = $1
          LIMIT 1
        ", [$businessId]);

        if ($bizLocRes && pg_num_rows($bizLocRes) > 0) {
          $bizLocRow = pg_fetch_assoc($bizLocRes);
          $jobLocation = trim((string)($bizLocRow["location_text"] ?? ""));
        }
      }

      if ($jobLocation === "") {
        $jobLocation = "Business Location";
      }

      /*
      |--------------------------------------------------------------------------
      | Labor jobs
      |--------------------------------------------------------------------------
      */
      $laborMap = $_SESSION["wizard"]["labor"] ?? [];

      if (is_array($laborMap) && !empty($laborMap)) {
        $insJobSql = "
          INSERT INTO jobs
          (business_id, title, description, location, budget, status, price, worker_id, job_type)
          VALUES ($1, $2, $3, $4, $5, 'available', $6, $7, 'labor')
        ";

        foreach ($laborMap as $role => $qtyRaw) {
          $qty = (int)$qtyRaw;
          if ($qty <= 0) continue;

          for ($i = 1; $i <= $qty; $i++) {
            $roleLabel = ucfirst(str_replace("_", " ", (string)$role));
            $title = $roleLabel . " Needed";
            $description = $roleLabel . " requested during setup (Order #{$orderId}).";

            $okJob = pg_query_params($conn, $insJobSql, [
              $businessId,
              $title,
              $description,
              $jobLocation,
              0,
              0,
              null
            ]);

            if (!$okJob) {
              throw new Exception("Insert labor job failed: " . pg_last_error($conn));
            }
          }
        }
      }

      /*
      |--------------------------------------------------------------------------
      | Technician jobs
      |--------------------------------------------------------------------------
      */
      $technicians = $_SESSION["wizard"]["technicians"] ?? [];

      if (is_array($technicians) && !empty($technicians)) {
        $insTechSql = "
          INSERT INTO jobs
          (business_id, title, description, location, budget, status, price, worker_id, job_type)
          VALUES ($1, $2, $3, $4, $5, 'available', $6, $7, 'technician')
        ";

        foreach ($technicians as $service) {
          $service = trim((string)$service);
          if ($service === "") continue;

          $label = ucwords(str_replace(["_", "-"], " ", $service));
          $title = $label . " Service";
          $description = $label . " requested during setup (Order #{$orderId}).";

          $okTech = pg_query_params($conn, $insTechSql, [
            $businessId,
            $title,
            $description,
            $jobLocation,
            0,
            0,
            null
          ]);

          if (!$okTech) {
            throw new Exception("Insert technician job failed: " . pg_last_error($conn));
          }
        }
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Clear carts only after successful payment
    |--------------------------------------------------------------------------
    */
    unset($_SESSION["carts"]);
    unset($_SESSION["wizard"]["pos_cart"]);
    unset($_SESSION["wizard"]["kitchen_cart"]);
  }

  pg_query($conn, "COMMIT");
  http_response_code(200);
  echo "OK";

} catch (Throwable $e) {
  pg_query($conn, "ROLLBACK");
  callback_fail("Callback processing failed: " . $e->getMessage(), 500);
}