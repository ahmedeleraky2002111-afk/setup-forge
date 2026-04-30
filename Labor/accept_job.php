<?php
session_start();
require_once "../db.php";

/* CHECK LOGIN */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

/* CHECK USER TYPE */
if (!isset($_SESSION["user_type"]) || 
   ($_SESSION["user_type"] !== "labor" && $_SESSION["user_type"] !== "technician")) {

    header("Location: ../home.php");
    exit();
}
?>
$user_id = $_SESSION["user_id"];
$job_id = $_POST["job_id"];

pg_query($conn,"
UPDATE jobs
SET worker_id = $user_id,
    status = 'active'
WHERE job_id = $job_id
");

header("Location: myjobs.php");
exit();
?>
<?php
session_start();
$conn = pg_connect("host=localhost dbname=SetupForge user=postgres password=1234");

if(!isset($_SESSION["user_id"])){
    header("Location: labor.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$job_id = $_POST["job_id"];

pg_query($conn,"
UPDATE jobs
SET worker_id = $user_id,
    status = 'active'
WHERE job_id = $job_id
");

header("Location: myjobs.php");
exit();
?>