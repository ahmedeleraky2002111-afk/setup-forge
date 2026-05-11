<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) { header("Location: auth/login.php"); exit; }

$business_id    = (int)$_SESSION["user_id"];
$application_id = (int)($_POST["application_id"] ?? 0);
$labor_user_id  = (int)($_POST["labor_user_id"] ?? 0);
$job_id         = (int)($_POST["job_id"] ?? 0);
$title          = trim($_POST["title"] ?? "");
$location       = trim($_POST["location"] ?? "");

if (!$application_id || !$labor_user_id || !$job_id) {
    header("Location: service_jobs.php?error=invalid");
    exit;
}

pg_query($conn, "BEGIN");

// 1. Accept this application
pg_query_params($conn,
  "UPDATE job_applications SET status = 'accepted' WHERE id = $1",
  [$application_id]
);

// 2. Assign worker to the job slot and mark active
pg_query_params($conn,
  "UPDATE jobs SET worker_id = $1, status = 'active' WHERE job_id = $2",
  [$labor_user_id, $job_id]
);

// 3. Check remaining open slots for this title+location
$slotsRes = pg_query_params($conn,
  "SELECT COUNT(*) FROM jobs
   WHERE business_id = $1
     AND title = $2
     AND location = $3
     AND job_type = 'labor'
     AND status = 'available'
     AND worker_id IS NULL",
  [$business_id, $title, $location]
);

$slotsLeft = $slotsRes ? (int)pg_fetch_result($slotsRes, 0, 0) : 1;

// 4. If no slots left, reject all remaining pending applications for this role
if ($slotsLeft === 0) {
    pg_query_params($conn,
      "UPDATE job_applications ja
       SET status = 'rejected'
       FROM jobs j
       WHERE ja.job_id = j.job_id
         AND j.business_id = $1
         AND j.title = $2
         AND j.location = $3
         AND j.job_type = 'labor'
         AND ja.status = 'pending'",
      [$business_id, $title, $location]
    );
}

pg_query($conn, "COMMIT");

header("Location: service_jobs.php?hired=1");
exit;