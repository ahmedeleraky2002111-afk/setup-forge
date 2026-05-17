<?php
session_start();
require "db.php";

if (!isset($_GET["id"])) {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = (int) $_GET["id"];

/* reject vendor in vendors table */
pg_query_params($conn, "
    UPDATE vendors
    SET status = 'suspended',
        verification_status = 'rejected'
    WHERE user_id = $1
", [$user_id]);

/* reject user in users table */
pg_query_params($conn, "
    UPDATE users
    SET status = 'suspended'
    WHERE id = $1
", [$user_id]);

header("Location: admin_dashboard.php");
exit();
?>