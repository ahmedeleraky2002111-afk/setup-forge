<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

$answers = $_SESSION["setup_answers"] ?? null;

echo json_encode([
  "success" => true,
  "answers" => $answers
]);
?>