<?php
session_start();
require "db.php";

if (!isset($_GET["id"])) {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = (int) $_GET["id"];

/* approve vendor in vendors table */
pg_query_params($conn, "
    UPDATE vendors
    SET status = 'active',
        verification_status = 'verified'
    WHERE user_id = $1
", [$user_id]);

/* approve user in users table */
pg_query_params($conn, "
    UPDATE users
    SET status = 'active'
    WHERE id = $1
", [$user_id]);

header("Location: admin_dashboard.php");
exit();
?>