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

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        echo json_encode(["ok" => false, "error" => "Missing email/password"]);
        exit;
    }

    if (!isset($pdo)) {
        echo json_encode(["ok" => false, "error" => "DB connection not available (\$pdo missing)"]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, name, email, user_type, password_hash
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([":email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["ok" => false, "error" => "User not found"]);
        exit;
    }

    if (!password_verify($password, $user["password_hash"])) {
        echo json_encode([
            "ok" => false,
            "error" => "Wrong password",
            "stored_hash" => $user["password_hash"]
        ]);
        exit;
    }

    $token = bin2hex(random_bytes(32));

    $upd = $pdo->prepare("UPDATE users SET api_token = :t WHERE id = :id");
    $upd->execute([
        ":t" => $token,
        ":id" => $user["id"]
    ]);

    echo json_encode([
        "ok" => true,
        "token" => $token,
        "user" => [
            "id" => $user["id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "user_type" => $user["user_type"]
        ]
    ]);
} catch (Throwable $e) {
    file_put_contents(
        __DIR__ . "/api_error.log",
        date("c") . " api_login: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Server error (check api_error.log)"]);
} finally {
    ob_end_flush();
}