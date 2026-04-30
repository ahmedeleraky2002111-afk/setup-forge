<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

$business_id = (int)$_SESSION["user_id"];

/* TECHNICIAN JOBS = SHOW INDIVIDUAL */
$technicianQuery = pg_query_params($conn, "
    SELECT *
    FROM jobs
    WHERE business_id = $1
      AND job_type = 'technician'
    ORDER BY job_id DESC
", [$business_id]);

/* LABOR JOBS = GROUP BY TITLE + LOCATION */
$laborGroupedQuery = pg_query_params($conn, "
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
    GROUP BY title, location
    ORDER BY title ASC
", [$business_id]);

$hasTechnicianJobs = ($technicianQuery && pg_num_rows($technicianQuery) > 0);
$hasLaborJobs = ($laborGroupedQuery && pg_num_rows($laborGroupedQuery) > 0);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Your Service Jobs - SetupForge</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<main class="sf-service-page py-5">
  <div class="container">

    <div class="sf-page-hero mb-4">
      <div>
        <span class="sf-page-kicker">Business Services</span>
        <h1 class="sf-page-title mb-2">Your Setup Services</h1>
        
      </div>
    </div>

    <?php if ($hasTechnicianJobs): ?>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2 class="sf-section-title mb-0">Technician Jobs</h2>
      </div>

      <div class="row g-4 mb-5">
        <?php while ($job = pg_fetch_assoc($technicianQuery)): ?>
          <div class="col-12 col-md-6 col-xl-4">
            <div class="card sf-service-card h-100 border-0 shadow-sm">
              <div class="card-body p-4 d-flex flex-column">

                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                  <div>
                    <h4 class="sf-card-title mb-2">
                      <?= htmlspecialchars($job["title"]); ?>
                    </h4>
                    <p class="text-secondary mb-0 small">
                      <?= htmlspecialchars($job["description"]); ?>
                    </p>
                  </div>

                  <span class="sf-status-badge sf-status-<?= htmlspecialchars($job["status"]); ?>">
                    <?= htmlspecialchars(ucfirst($job["status"])); ?>
                  </span>
                </div>

                <div class="sf-job-detail-list mb-4">
                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Type</span>
                    <span class="sf-job-detail-value">Technician</span>
                  </div>

                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Location</span>
                    <span class="sf-job-detail-value">
                      <?= htmlspecialchars($job["location"]); ?>
                    </span>
                  </div>

                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Budget</span>
                    <span class="sf-job-detail-value">
                      EGP <?= number_format((float)$job["budget"], 2); ?>
                    </span>
                  </div>

                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Job ID</span>
                    <span class="sf-job-detail-value">
                      #<?= (int)$job["job_id"]; ?>
                    </span>
                  </div>
                </div>

                <div class="mt-auto">
                  <a href="job_bids.php?job_id=<?= (int)$job["job_id"]; ?>" class="btn sf-btn-primary w-100 rounded-pill">
                    View Bids
                  </a>
                </div>

              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

    <?php if ($hasLaborJobs): ?>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2 class="sf-section-title mb-0">Labor Hiring</h2>
      </div>

      <div class="row g-4 mb-5">
        <?php while ($labor = pg_fetch_assoc($laborGroupedQuery)): ?>
          <?php
            $title = $labor["title"];
            $location = $labor["location"];
            $description = $labor["description"] ?? "";
            $totalOpenings = (int)$labor["total_openings"];
            $openingsLeft = (int)$labor["openings_left"];
            $filledOpenings = (int)$labor["filled_openings"];

            $encodedTitle = urlencode($title);
            $encodedLocation = urlencode($location);
          ?>
          <div class="col-12 col-md-6 col-xl-4">
            <div class="card sf-service-card h-100 border-0 shadow-sm">
              <div class="card-body p-4 d-flex flex-column">

                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                  <div>
                    <h4 class="sf-card-title mb-2">
                      <?= htmlspecialchars($title); ?>
                    </h4>
                    <p class="text-secondary mb-0 small">
                      <?= htmlspecialchars($description); ?>
                    </p>
                  </div>

                  <?php if ($openingsLeft > 0): ?>
                    <span class="sf-status-badge sf-status-available">
                      Open
                    </span>
                  <?php else: ?>
                    <span class="sf-status-badge sf-status-completed">
                      Filled
                    </span>
                  <?php endif; ?>
                </div>

                <div class="sf-job-detail-list mb-4">
                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Type</span>
                    <span class="sf-job-detail-value">Labor</span>
                  </div>

                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Location</span>
                    <span class="sf-job-detail-value">
                      <?= htmlspecialchars($location); ?>
                    </span>
                  </div>

                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Total Openings</span>
                    <span class="sf-job-detail-value">
                      <?= $totalOpenings; ?>
                    </span>
                  </div>

                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Filled</span>
                    <span class="sf-job-detail-value">
                      <?= $filledOpenings; ?>
                    </span>
                  </div>

                  <div class="sf-job-detail-item">
                    <span class="sf-job-detail-label">Remaining</span>
                    <span class="sf-job-detail-value">
                      <?= $openingsLeft; ?>
                    </span>
                  </div>
                </div>

                <div class="mt-auto">
                  <a href="job_applicants.php?title=<?= $encodedTitle; ?>&location=<?= $encodedLocation; ?>" class="btn sf-btn-primary w-100 rounded-pill">
                    View Applicants
                  </a>
                </div>

              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

    <?php if (!$hasTechnicianJobs && !$hasLaborJobs): ?>
      <div class="row">
        <div class="col-12">
          <div class="sf-empty-box text-center">
            <h4 class="mb-2">No Jobs Yet</h4>
            <p class="text-secondary mb-0">
              Once your setup generates technician or labor jobs, they will appear here.
            </p>
          </div>
        </div>
      </div>
    <?php endif; ?>

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
          From equipment sourcing to installation and optimization — we handle it all.
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
</body>
</html>
