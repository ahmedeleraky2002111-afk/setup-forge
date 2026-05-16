<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header("Content-Type: application/json");

try {
  require_once __DIR__ . "/../db.php";

  // Get Authorization header (Bearer TOKEN)
  $headers = function_exists('getallheaders') ? getallheaders() : [];
  $auth = $headers["Authorization"] ?? $headers["authorization"] ?? "";

  if (!str_starts_with($auth, "Bearer ")) {
    echo json_encode(["ok" => false, "error" => "No token"]);
    exit;
  }

  $token = trim(substr($auth, 7));
  if ($token === "") {
    echo json_encode(["ok" => false, "error" => "Empty token"]);
    exit;
  }

  if (!isset($pdo)) {
    echo json_encode(["ok"=>false, "error"=>"DB connection not available (\$pdo missing)"]);
    exit;
  }

  $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE api_token = :t LIMIT 1");
  $stmt->execute([":t" => $token]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    echo json_encode(["ok" => false, "error" => "Invalid token"]);
    exit;
  }

  echo json_encode(["ok" => true, "user" => $user]);
} catch (Throwable $e) {
  file_put_contents(__DIR__ . "/api_error.log", date("c") . " api_me: " . $e->getMessage() . "\n", FILE_APPEND);
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error (check api_error.log)"]);
} finally {
  ob_end_flush();
}