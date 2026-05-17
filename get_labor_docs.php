<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) { echo json_encode([]); exit; }

$labor_user_id = (int)($_GET["labor_user_id"] ?? 0);
if ($labor_user_id <= 0) { echo json_encode([]); exit; }

$res = pg_query_params($conn,
  "SELECT doc_type, status FROM labor_documents WHERE labor_user_id = $1 ORDER BY uploaded_at DESC",
  [$labor_user_id]
);

$docs = [];
if ($res) while ($row = pg_fetch_assoc($res)) $docs[] = $row;

header('Content-Type: application/json');
echo json_encode($docs);