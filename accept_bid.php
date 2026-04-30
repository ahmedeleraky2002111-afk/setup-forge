<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$bid_id = (int)($_POST["bid_id"] ?? 0);
$job_id = (int)($_POST["job_id"] ?? 0);

if ($bid_id <= 0 || $job_id <= 0) {
    die("Invalid request.");
}

$res = pg_query_params($conn, "
    SELECT technician_id, bid_price
FROM bids
WHERE bid_id = $1
LIMIT 1
", [$bid_id]);

if (!$res || pg_num_rows($res) === 0) {
    die("Bid not found.");
}

$row = pg_fetch_assoc($res);
$technician_id = (int)$row["technician_id"];
$bid_price = (float)$row["bid_price"];
pg_query($conn, "BEGIN");

$ok1 = pg_query_params($conn, "
    UPDATE bids
    SET status = 'accepted'
    WHERE bid_id = $1
", [$bid_id]);

$ok2 = pg_query_params($conn, "
    UPDATE bids
    SET status = 'rejected'
    WHERE job_id = $1
      AND bid_id != $2
", [$job_id, $bid_id]);

$ok3 = pg_query_params($conn, "
    UPDATE jobs
    SET worker_id = $1,
        status = 'active',
        price = $2
    WHERE job_id = $3
", [$technician_id, $bid_price, $job_id]);

if ($ok1 && $ok2 && $ok3) {
    pg_query($conn, "COMMIT");
} else {
    pg_query($conn, "ROLLBACK");
    die("Failed to accept bid.");
}

header("Location: job_bids.php?job_id=" . $job_id);
exit();
?>