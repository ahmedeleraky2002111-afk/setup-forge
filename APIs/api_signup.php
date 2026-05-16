<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header("Content-Type: application/json");

try {
  require_once __DIR__ . "/../db.php";

  if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["ok"=>false, "error"=>"POST only"]);
    exit;
  }

  // -------- REQUIRED --------
  $name = trim($_POST["name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $password = $_POST["password"] ?? "";

  if ($name==="" || $email==="" || $password==="") {
    echo json_encode(["ok"=>false, "error"=>"Missing fields"]);
    exit;
  }

  // -------- OPTIONAL (your users table has these) --------
  $phone   = trim($_POST["phone"] ?? "");
  $country = trim($_POST["country"] ?? "");
  $city    = trim($_POST["city"] ?? "");
  $street  = trim($_POST["street"] ?? "");

  if (!isset($pdo)) {
    echo json_encode(["ok"=>false, "error"=>"DB connection not available (\$pdo missing)"]);
    exit;
  }

  // ✅ email exists check
  $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
  $stmt->execute([":email"=>$email]);
  if ($stmt->fetch()) {
    echo json_encode(["ok"=>false, "error"=>"Email already exists"]);
    exit;
  }

  $hash  = password_hash($password, PASSWORD_BCRYPT);
  $token = bin2hex(random_bytes(32));

  /*
    ✅ Matches your users columns exactly:
    id, name, email, password_hash, user_type, phone, country, city, street, status, created_at, api_token
  */

  $stmt = $pdo->prepare("
    INSERT INTO users (
      name,
      email,
      password_hash,
      user_type,
      phone,
      country,
      city,
      street,
      status,
      api_token,
      created_at
    )
    VALUES (
      :n,
      :e,
      :h,
      :ut,
      :phone,
      :country,
      :city,
      :street,
      :status,
      :t,
      NOW()
    )
    RETURNING id
  ");

  $stmt->execute([
    ":n"      => $name,
    ":e"      => $email,
    ":h"      => $hash,
    ":ut"     => "customer", // your enum value
    ":phone"   => ($phone === "" ? null : $phone),
    ":country" => ($country === "" ? null : $country),
    ":city"    => ($city === "" ? null : $city),
    ":street"  => ($street === "" ? null : $street),
    ":status" => "active",   // ⚠️ must match your account_status enum values
    ":t"      => $token,
  ]);

  $id = $stmt->fetchColumn();

  echo json_encode([
    "ok"=>true,
    "token"=>$token,
    "user"=>["id"=>$id, "name"=>$name, "email"=>$email]
  ]);
} catch (Throwable $e) {
  file_put_contents(__DIR__ . "/api_error.log", date("c") . " api_signup: " . $e->getMessage() . "\n", FILE_APPEND);
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"Server error (check api_error.log)"]);
} finally {
  ob_end_flush();
}