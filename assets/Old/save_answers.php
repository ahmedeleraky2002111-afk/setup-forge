<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Invalid JSON"]);
  exit;
}

$_SESSION["setup_answers"] = $data;

echo json_encode(["success" => true, "message" => "Saved to session"]);
?>