<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header("Content-Type: application/json");

try {
    require_once __DIR__ . "/../db.php";

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode(["ok" => false, "error" => "POST only"]);
        exit;
    }

    if (!isset($pdo)) {
        echo json_encode(["ok" => false, "error" => "DB connection not available (\$pdo missing)"]);
        exit;
    }

    // ---------- Auth ----------
    $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
    if (!$authHeader && function_exists("getallheaders")) {
        $headers = getallheaders();
        $authHeader = $headers["Authorization"] ?? $headers["authorization"] ?? "";
    }

    if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
        echo json_encode(["ok" => false, "error" => "Missing bearer token"]);
        exit;
    }

    $token = trim($m[1]);

    $userStmt = $pdo->prepare("
        SELECT id, name, email, user_type
        FROM users
        WHERE api_token = :token
        LIMIT 1
    ");
    $userStmt->execute([":token" => $token]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["ok" => false, "error" => "Invalid token"]);
        exit;
    }

    // ---------- Input ----------
    $raw = file_get_contents("php://input");
    $json = json_decode($raw, true);

    $input = is_array($json) ? $json : $_POST;

    $businessName = trim((string)($input["business_name"] ?? ""));
    $phone = trim((string)($input["phone"] ?? ""));
    $address = trim((string)($input["address"] ?? ""));
    $notes = trim((string)($input["notes"] ?? ""));
    $businessType = trim((string)($input["business_type"] ?? ""));
    $size = trim((string)($input["size"] ?? ""));
    $budget = (float)($input["budget"] ?? 0);
    $preferredDeliveryDate = trim((string)($input["preferred_delivery_date"] ?? ""));

    $items = $input["items"] ?? null;

    if ($businessName === "") {
        echo json_encode(["ok" => false, "error" => "Business name is required"]);
        exit;
    }

    if ($phone === "") {
        echo json_encode(["ok" => false, "error" => "Phone is required"]);
        exit;
    }

    if ($address === "") {
        echo json_encode(["ok" => false, "error" => "Address is required"]);
        exit;
    }

    if (!is_array($items) || empty($items)) {
        echo json_encode(["ok" => false, "error" => "Order items are required"]);
        exit;
    }

    // ---------- Normalize items ----------
    $normalizedItems = [];
    $orderTotal = 0.0;

    foreach ($items as $idx => $item) {
        if (!is_array($item)) {
            echo json_encode(["ok" => false, "error" => "Invalid item format at index $idx"]);
            exit;
        }

        $productId = isset($item["product_id"]) && $item["product_id"] !== ""
            ? (int)$item["product_id"]
            : null;

        $name = trim((string)($item["name"] ?? ""));
        $module = trim((string)($item["module"] ?? ""));
        $qty = (int)($item["qty"] ?? 0);
        $unitPrice = (float)($item["price"] ?? $item["unit_price"] ?? 0);

        if ($qty <= 0) {
            continue;
        }

        if ($unitPrice <= 0) {
            echo json_encode(["ok" => false, "error" => "Invalid price for item: " . ($name ?: "#$idx")]);
            exit;
        }

        if ($productId === null) {
            // Mobile-generated packages may not have real DB product ids yet.
            // We allow null product ids only if your order_items.product_id column accepts null.
            // If it does NOT accept null, return a clear error:
            echo json_encode([
                "ok" => false,
                "error" => "Missing product_id for item: " . ($name ?: "#$idx") . ". Mobile items must include real DB product ids."
            ]);
            exit;
        }

        $lineTotal = $qty * $unitPrice;
        $orderTotal += $lineTotal;

        $normalizedItems[] = [
            "product_id" => $productId,
            "name" => $name,
            "module" => $module,
            "qty" => $qty,
            "unit_price" => $unitPrice,
        ];
    }

    if ($orderTotal <= 0 || empty($normalizedItems)) {
        echo json_encode(["ok" => false, "error" => "Your order is empty"]);
        exit;
    }

    // ---------- Business / Customer logic ----------
    $userId = (int)$user["id"];

    $bizStmt = $pdo->prepare("SELECT 1 FROM businesses WHERE user_id = :uid LIMIT 1");
    $bizStmt->execute([":uid" => $userId]);
    $businessExists = (bool)$bizStmt->fetchColumn();

    $customerUserId = $businessExists ? null : $userId;
    $businessUserId = $businessExists ? $userId : null;

    $serviceFees = 0.00;

    // ---------- Transaction ----------
    $pdo->beginTransaction();

    try {
        $orderStmt = $pdo->prepare("
            INSERT INTO orders (
                status,
                customer_user_id,
                business_user_id,
                service_fees,
                order_total,
                delivery_location,
                payment_status,
                preferred_delivery_date
            )
            VALUES (
                'pending',
                :customer_user_id,
                :business_user_id,
                :service_fees,
                :order_total,
                :delivery_location,
                'pending',
                :preferred_delivery_date
            )
            RETURNING id
        ");

        $orderStmt->execute([
            ":customer_user_id" => $customerUserId,
            ":business_user_id" => $businessUserId,
            ":service_fees" => $serviceFees,
            ":order_total" => $orderTotal,
            ":delivery_location" => $address,
            ":preferred_delivery_date" => $preferredDeliveryDate !== "" ? $preferredDeliveryDate : null,
        ]);

        $orderId = (int)$orderStmt->fetchColumn();

        if ($orderId <= 0) {
            throw new Exception("Failed to create order");
        }

        // Save extra mobile info into orders if columns exist later.
        // For now we keep them in a JSON blob only if your DB supports these columns.
        // If not, ignore safely.

        // Optional updates, wrapped in try so they won't break core flow.
        try {
            $extraStmt = $pdo->prepare("
                UPDATE orders
                SET labor_data = :labor_data,
                    technician_data = :technician_data
                WHERE id = :id
            ");
            $extraStmt->execute([
                ":labor_data" => json_encode([]),
                ":technician_data" => json_encode([]),
                ":id" => $orderId,
            ]);
        } catch (Throwable $ignored) {
            // Ignore if columns do not exist or are not needed for mobile yet.
        }

        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price)
            VALUES (:order_id, :product_id, :quantity, :unit_price)
        ");

        foreach ($normalizedItems as $item) {
            $itemStmt->execute([
                ":order_id" => $orderId,
                ":product_id" => $item["product_id"],
                ":quantity" => $item["qty"],
                ":unit_price" => $item["unit_price"],
            ]);
        }

        $pdo->commit();

        echo json_encode([
            "ok" => true,
            "message" => "Order placed successfully",
            "order_id" => $orderId,
            "order_total" => $orderTotal,
            "user" => [
                "id" => $user["id"],
                "name" => $user["name"],
                "email" => $user["email"],
                "user_type" => $user["user_type"],
            ],
            "meta" => [
                "business_name" => $businessName,
                "phone" => $phone,
                "address" => $address,
                "notes" => $notes,
                "business_type" => $businessType,
                "size" => $size,
                "budget" => $budget,
                "items_count" => count($normalizedItems),
            ]
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
} catch (Throwable $e) {
    file_put_contents(
        __DIR__ . "/api_error.log",
        date("c") . " api_place_order: " . $e->getMessage() . "\n",
        FILE_APPEND
    );

    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => "Server error (check api_error.log)"
    ]);
} finally {
    ob_end_flush();
}