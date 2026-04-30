<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

$business_id = (int)$_SESSION["user_id"];

$title = trim($_GET["title"] ?? $_POST["title"] ?? "");
$location = trim($_GET["location"] ?? $_POST["location"] ?? "");

if ($title === "" || $location === "") {
    die("Invalid labor group.");
}

/* Make sure this grouped labor role belongs to current business */
$groupCheck = pg_query_params($conn, "
    SELECT
        title,
        location,
        MIN(description) AS description,
        COUNT(*) AS total_openings,
        COUNT(*) FILTER (WHERE worker_id IS NULL) AS openings_left,
        COUNT(*) FILTER (WHERE worker_id IS NOT NULL) AS filled_openings
    FROM jobs
    WHERE business_id = $1
      AND job_type = 'labor'
      AND title = $2
      AND location = $3
    GROUP BY title, location
    LIMIT 1
", [$business_id, $title, $location]);

if (!$groupCheck || pg_num_rows($groupCheck) === 0) {
    die("Labor group not found or access denied.");
}

$group = pg_fetch_assoc($groupCheck);

$successMessage = "";
$errorMessage = "";

/* ACCEPT APPLICANT INTO ONE OPEN SLOT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accept_applicant"])) {
    $application_id = (int)($_POST["application_id"] ?? 0);

    if ($application_id <= 0) {
        $errorMessage = "Invalid application.";
    } else {

        /* Find one open labor job slot for this grouped role */
        $openJobRes = pg_query_params($conn, "
            SELECT job_id
            FROM jobs
            WHERE business_id = $1
              AND job_type = 'labor'
              AND title = $2
              AND location = $3
              AND worker_id IS NULL
              AND status = 'available'
            ORDER BY job_id ASC
            LIMIT 1
        ", [$business_id, $title, $location]);

        if (!$openJobRes || pg_num_rows($openJobRes) === 0) {
            $errorMessage = "No openings left for this role.";
        } else {

            $openJob = pg_fetch_assoc($openJobRes);
            $selected_job_id = (int)$openJob["job_id"];

            /* Get selected applicant */
            $appRes = pg_query_params($conn, "
                SELECT id, labor_user_id, status
                FROM job_applications
                WHERE id = $1
                LIMIT 1
            ", [$application_id]);

            if (!$appRes || pg_num_rows($appRes) === 0) {
                $errorMessage = "Application not found.";
            } else {

                $application = pg_fetch_assoc($appRes);
                $selected_worker_id = (int)$application["labor_user_id"];

                /* Prevent accepting same worker twice for same grouped role */
                $alreadyAssignedRes = pg_query_params($conn, "
                    SELECT job_id
                    FROM jobs
                    WHERE business_id = $1
                      AND job_type = 'labor'
                      AND title = $2
                      AND location = $3
                      AND worker_id = $4
                    LIMIT 1
                ", [$business_id, $title, $location, $selected_worker_id]);

                if ($alreadyAssignedRes && pg_num_rows($alreadyAssignedRes) > 0) {
                    $errorMessage = "This applicant is already assigned to this role.";
                } else {

                    pg_query($conn, "BEGIN");

                    /* Accept selected application */
                    $ok1 = pg_query_params($conn, "
                        UPDATE job_applications
                        SET status = 'accepted'
                        WHERE id = $1
                    ", [$application_id]);

                    /* Assign applicant to one open hidden slot */
                    $ok2 = pg_query_params($conn, "
                        UPDATE jobs
                        SET worker_id = $1,
                            status = 'active',
                            price = budget
                        WHERE job_id = $2
                    ", [$selected_worker_id, $selected_job_id]);

                    if ($ok1 && $ok2) {
                        pg_query($conn, "COMMIT");
                        $successMessage = "Applicant accepted successfully.";
                    } else {
                        pg_query($conn, "ROLLBACK");
                        $errorMessage = "Failed to accept applicant.";
                    }
                }
            }
        }
    }

    /* Refresh grouped info after accept */
    $groupCheck = pg_query_params($conn, "
        SELECT
            title,
            location,
            MIN(description) AS description,
            COUNT(*) AS total_openings,
            COUNT(*) FILTER (WHERE worker_id IS NULL) AS openings_left,
            COUNT(*) FILTER (WHERE worker_id IS NOT NULL) AS filled_openings
        FROM jobs
        WHERE business_id = $1
          AND job_type = 'labor'
          AND title = $2
          AND location = $3
        GROUP BY title, location
        LIMIT 1
    ", [$business_id, $title, $location]);

    $group = $groupCheck ? pg_fetch_assoc($groupCheck) : $group;


    /* Refresh grouped info after accept */
$groupCheck = pg_query_params($conn, "
    SELECT
        title,
        location,
        MIN(description) AS description,
        COUNT(*) AS total_openings,
        COUNT(*) FILTER (WHERE worker_id IS NULL) AS openings_left,
        COUNT(*) FILTER (WHERE worker_id IS NOT NULL) AS filled_openings
    FROM jobs
    WHERE business_id = $1
      AND job_type = 'labor'
      AND title = $2
      AND location = $3
    GROUP BY title, location
    LIMIT 1
", [$business_id, $title, $location]);

$group = $groupCheck ? pg_fetch_assoc($groupCheck) : $group;

/* If no openings remain, reject all other pending applicants in this group */
if ((int)$group["openings_left"] === 0) {
    pg_query_params($conn, "
        UPDATE job_applications
        SET status = 'rejected'
        WHERE id IN (
            SELECT ja.id
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.job_id
            WHERE j.business_id = $1
              AND j.job_type = 'labor'
              AND j.title = $2
              AND j.location = $3
              AND ja.status = 'pending'
        )
    ", [$business_id, $title, $location]);
}

}

/* Get applicants for this grouped role
   We show applicants who applied to any matching hidden slot */
$query = pg_query_params($conn, "
    SELECT DISTINCT ON (u.id)
        ja.id AS application_id,
        ja.status AS application_status,
        ja.applied_at,
        u.id AS labor_user_id,
        u.name AS labor_name,
        u.email,
        u.phone,
        l.profile_picture,
        l.skills,
        l.experience_level,
        l.availability_status,
        l.hourly_rate,
        l.avg_rating,
        l.provider_type
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.job_id
    JOIN users u ON ja.labor_user_id = u.id
    LEFT JOIN labors l ON l.user_id = u.id
    WHERE j.business_id = $1
      AND j.job_type = 'labor'
      AND j.title = $2
      AND j.location = $3
    ORDER BY
        u.id,
        CASE
            WHEN ja.status = 'accepted' THEN 1
            WHEN ja.status = 'pending' THEN 2
            ELSE 3
        END,
        ja.id DESC
", [$business_id, $title, $location]);

$openingsLeft = (int)($group["openings_left"] ?? 0);
$filledOpenings = (int)($group["filled_openings"] ?? 0);
$totalOpenings = (int)($group["total_openings"] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Labor Applicants - SetupForge</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<main class="sf-service-page py-5">
  <div class="container">

    <div class="sf-page-hero mb-4">
      <div>
        <span class="sf-page-kicker">Labor Applicants</span>
        <h1 class="sf-page-title mb-2"><?= htmlspecialchars($group["title"]); ?></h1>
        <p class="sf-page-sub mb-0"><?= htmlspecialchars($group["description"]); ?></p>
      </div>

      <div class="sf-job-meta-box">
        <div><strong>Location:</strong> <?= htmlspecialchars($group["location"]); ?></div>
        <div><strong>Total Openings:</strong> <?= $totalOpenings; ?></div>
        <div><strong>Filled:</strong> <?= $filledOpenings; ?></div>
        <div><strong>Remaining:</strong> <?= $openingsLeft; ?></div>
      </div>
    </div>

    <?php if ($successMessage !== ""): ?>
      <div class="alert alert-success rounded-4 mb-4"><?= htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ""): ?>
      <div class="alert alert-danger rounded-4 mb-4"><?= htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
      <h2 class="sf-section-title mb-0">All Applicants</h2>
      <a href="service_jobs.php" class="btn btn-outline-secondary rounded-pill px-4">← Back to Service Jobs</a>
    </div>

    <div class="row g-4">
      <?php if ($query && pg_num_rows($query) > 0): ?>
        <?php while ($app = pg_fetch_assoc($query)): ?>
          <?php
            $profilePic = trim((string)($app["profile_picture"] ?? ""));
            $skills = trim((string)($app["skills"] ?? ""));
            $experience = trim((string)($app["experience_level"] ?? ""));
            $availability = trim((string)($app["availability_status"] ?? ""));
            $phone = trim((string)($app["phone"] ?? ""));
            $email = trim((string)($app["email"] ?? ""));
            $role = trim((string)($app["provider_type"] ?? ""));
            $rating = $app["avg_rating"];
            $hourlyRate = $app["hourly_rate"];
            $applicationStatus = trim((string)$app["application_status"]);
          ?>
          <div class="col-12 col-lg-6">
            <div class="card sf-bid-card h-100 border-0 shadow-sm">
              <div class="card-body p-4">

                <div class="sf-bid-top mb-3">
                  <div class="sf-bid-avatar-wrap">
                    <div class="sf-bid-avatar">
                      <?php if ($profilePic !== ""): ?>
                        <img src="<?= htmlspecialchars($profilePic); ?>" alt="Applicant">
                      <?php else: ?>
                        <div class="sf-bid-avatar-fallback">
                          <?= strtoupper(substr((string)$app["labor_name"], 0, 1)); ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="sf-bid-main">
                    <h5 class="sf-bid-name mb-2"><?= htmlspecialchars($app["labor_name"]); ?></h5>

                    <div class="sf-bid-meta-grid">
                      <div class="sf-mini-info">
                        <span class="sf-mini-label">Role</span>
                        <span class="sf-mini-value"><?= $role !== "" ? htmlspecialchars(ucfirst($role)) : "—"; ?></span>
                      </div>

                      <div class="sf-mini-info">
                        <span class="sf-mini-label">Experience</span>
                        <span class="sf-mini-value"><?= $experience !== "" ? htmlspecialchars(ucfirst($experience)) : "—"; ?></span>
                      </div>

                      <div class="sf-mini-info">
                        <span class="sf-mini-label">Availability</span>
                        <span class="sf-mini-value"><?= $availability !== "" ? htmlspecialchars(ucfirst($availability)) : "—"; ?></span>
                      </div>

                      <div class="sf-mini-info">
                        <span class="sf-mini-label">Rating</span>
                        <span class="sf-mini-value"><?= ($rating !== null && $rating !== "") ? htmlspecialchars(number_format((float)$rating, 1)) : "—"; ?></span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="sf-bid-info-grid mb-3">
                  <div class="sf-bid-info-box">
                    <div class="sf-bid-info-label">Skills</div>
                    <div class="sf-bid-info-value">
                      <?= $skills !== "" ? htmlspecialchars($skills) : "No skills added yet."; ?>
                    </div>
                  </div>

                  <div class="sf-bid-info-box">
                    <div class="sf-bid-info-label">Contact</div>
                    <div class="sf-bid-info-value">
                      <div><strong>Email:</strong> <?= $email !== "" ? htmlspecialchars($email) : "—"; ?></div>
                      <div><strong>Phone:</strong> <?= $phone !== "" ? htmlspecialchars($phone) : "—"; ?></div>
                      <div><strong>Hourly Rate:</strong> <?= ($hourlyRate !== null && $hourlyRate !== "") ? "EGP " . number_format((float)$hourlyRate, 2) : "—"; ?></div>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                  <span class="sf-status-badge sf-bid-status-<?= htmlspecialchars($applicationStatus); ?>">
                    <?= htmlspecialchars(ucfirst($applicationStatus)); ?>
                  </span>

                  <?php if ($openingsLeft > 0 && $applicationStatus !== "accepted"): ?>
                    <form method="POST" class="m-0">
                      <input type="hidden" name="title" value="<?= htmlspecialchars($title); ?>">
                      <input type="hidden" name="location" value="<?= htmlspecialchars($location); ?>">
                      <input type="hidden" name="application_id" value="<?= (int)$app["application_id"]; ?>">
                      <button type="submit" name="accept_applicant" class="btn sf-btn-primary rounded-pill px-4">
                        Accept Applicant
                      </button>
                    </form>
                  <?php else: ?>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" disabled>
                      <?= $applicationStatus === "accepted" ? "Accepted" : "No Openings"; ?>
                    </button>
                  <?php endif; ?>
                </div>

              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="sf-empty-box text-center">
            <h4 class="mb-2">No Applicants Yet</h4>
            <p class="text-secondary mb-0">Labor workers have not applied for this role yet.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<footer class="sf-footer mt-5">
  <div class="container py-5">

    <div class="row g-4">

      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="sf-footer-logo">
            <img src="assets/images/Logo.png" alt="SetupForge Logo">
          </div>
          <h5 class="mb-0 text-white fw-bold">SetupForge</h5>
        </div>

        <p class="sf-footer-text">
          SetupForge helps entrepreneurs launch, furnish, and fully prepare their businesses.
          From equipment sourcing to installation and staffing — we handle it all.
        </p>

        <div class="sf-socials mt-3">
          <a href="#">Facebook</a>
          <a href="#">Instagram</a>
          <a href="#">LinkedIn</a>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <h6 class="sf-footer-title">Products</h6>
        <ul class="sf-footer-links">
          <li><a href="#">Kitchen Equipment</a></li>
          <li><a href="#">Furniture</a></li>
          <li><a href="#">POS Systems</a></li>
          <li><a href="#">Security Systems</a></li>
          <li><a href="#">Packaging</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-2">
        <h6 class="sf-footer-title">Services</h6>
        <ul class="sf-footer-links">
          <li><a href="#">Installation</a></li>
          <li><a href="#">Interior Design</a></li>
          <li><a href="#">Branding</a></li>
          <li><a href="#">Consultation</a></li>
          <li><a href="#">Maintenance</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-2">
        <h6 class="sf-footer-title">Resources</h6>
        <ul class="sf-footer-links">
          <li><a href="help-center.php">Help Center</a></li>
          <li><a href="faq.php">FAQ</a></li>
          <li><a href="about.php">About Us</a></li>
          <li><a href="#">Blog</a></li>
          <li><a href="#">Guides</a></li>
        </ul>
      </div>

      <div class="col-12 col-lg-2">
        <h6 class="sf-footer-title">Stay Updated</h6>
        <p class="sf-footer-text small">
          Get updates, product releases, and startup tips.
        </p>

        <form>
          <input type="email" class="sf-footer-input mb-2" placeholder="Your email">
          <button type="submit" class="btn btn-light w-100 btn-sm fw-semibold">
            Subscribe
          </button>
        </form>
      </div>

    </div>
  </div>

  <div class="sf-footer-bottom">
    <div class="container d-flex justify-content-between flex-wrap gap-2">
      <span>© 2026 SetupForge. All rights reserved.</span>
      <div>
        <a href="#">Privacy Policy</a>
        <a href="#" class="ms-3">Terms</a>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/site.js"></script>
</body>
</html>
