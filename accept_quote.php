<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

$business_id = (int)$_SESSION["user_id"];
$quote_id = (int)($_POST["quote_id"] ?? 0);
$request_id = (int)($_POST["request_id"] ?? 0);

if ($quote_id <= 0 || $request_id <= 0) {
    die("Invalid request.");
}

/* Verify this request belongs to this business */
$check = pg_query_params($conn, "
    SELECT request_id FROM installation_requests
    WHERE request_id = $1 AND user_id = $2
    LIMIT 1
", [$request_id, $business_id]);

if (!$check || pg_num_rows($check) === 0) {
    die("Access denied.");
}

/* Get the accepted quote's company */
$quoteRes = pg_query_params($conn, "
    SELECT company_id FROM installation_quotes
    WHERE quote_id = $1 AND request_id = $2
    LIMIT 1
", [$quote_id, $request_id]);

if (!$quoteRes || pg_num_rows($quoteRes) === 0) {
    die("Quote not found.");
}

$quote = pg_fetch_assoc($quoteRes);
$company_id = (int)$quote["company_id"];

pg_query($conn, "BEGIN");

/* Accept this quote */
$ok1 = pg_query_params($conn, "
    UPDATE installation_quotes
    SET status = 'accepted'
    WHERE quote_id = $1
", [$quote_id]);

/* Reject all other quotes for this request */
$ok2 = pg_query_params($conn, "
    UPDATE installation_quotes
    SET status = 'rejected'
    WHERE request_id = $1 AND quote_id != $2
", [$request_id, $quote_id]);

/* Update the request with the winning company and status */
$ok3 = pg_query_params($conn, "
    UPDATE installation_requests
    SET company_id = $1, status = 'accepted'
    WHERE request_id = $2
", [$company_id, $request_id]);

if ($ok1 && $ok2 && $ok3) {
    pg_query($conn, "COMMIT");
} else {
    pg_query($conn, "ROLLBACK");
    die("Failed to accept quote.");
}

header("Location: service_jobs.php");
exit();
?>