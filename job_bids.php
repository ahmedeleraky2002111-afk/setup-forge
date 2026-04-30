<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

$business_id = (int)$_SESSION["user_id"];
$job_id = (int)($_GET["job_id"] ?? 0);

if ($job_id <= 0) {
    die("Invalid job.");
}

/* Make sure this technician job belongs to the current business */
$jobRes = pg_query_params($conn, "
    SELECT *
    FROM jobs
    WHERE job_id = $1
      AND business_id = $2
      AND job_type = 'technician'
    LIMIT 1
", [$job_id, $business_id]);

if (!$jobRes || pg_num_rows($jobRes) === 0) {
    die("Job not found or access denied.");
}

$job = pg_fetch_assoc($jobRes);

/* Get all bids + technician profile info */
$query = pg_query_params($conn, "
    SELECT 
        b.bid_id,
        b.bid_price,
        b.message,
        b.estimated_duration,
        b.status AS bid_status,
        u.id AS technician_user_id,
        u.name AS technician_name,
        l.profile_picture,
        l.skills,
        l.experience_level,
        l.availability_status
    FROM bids b
    JOIN users u ON b.technician_id = u.id
    LEFT JOIN labors l ON l.user_id = u.id
    WHERE b.job_id = $1
    ORDER BY b.bid_price ASC, b.bid_id DESC
", [$job_id]);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Technician Bids - SetupForge</title>
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
        <span class="sf-page-kicker">Technician Bids</span>
        <h1 class="sf-page-title mb-2"><?= htmlspecialchars($job["title"]); ?></h1>
        <p class="sf-page-sub mb-0"><?= htmlspecialchars($job["description"]); ?></p>
      </div>

      <div class="sf-job-meta-box">
        <div><strong>Status:</strong> <span class="sf-status-badge sf-status-<?= htmlspecialchars($job["status"]); ?>"><?= htmlspecialchars(ucfirst($job["status"])); ?></span></div>
        <div><strong>Location:</strong> <?= htmlspecialchars($job["location"]); ?></div>
        <div><strong>Budget:</strong> EGP <?= number_format((float)$job["budget"], 2); ?></div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
      <h2 class="sf-section-title mb-0">All Bids</h2>
      <a href="service_jobs.php" class="btn btn-outline-secondary rounded-pill px-4">← Back to Service Jobs</a>
    </div>

    <div class="row g-4">
      <?php if ($query && pg_num_rows($query) > 0): ?>
        <?php while ($bid = pg_fetch_assoc($query)): ?>
          <?php
            $profilePic = trim((string)($bid["profile_picture"] ?? ""));
            $skills = trim((string)($bid["skills"] ?? ""));
            $experience = trim((string)($bid["experience_level"] ?? ""));
            $availability = trim((string)($bid["availability_status"] ?? ""));
            $message = trim((string)($bid["message"] ?? ""));
          ?>
          <div class="col-12 col-lg-6">
            <div class="card sf-bid-card h-100 border-0 shadow-sm">
              <div class="card-body p-4">

                <div class="sf-bid-top mb-3">
  <div class="sf-bid-avatar-wrap">
    <div class="sf-bid-avatar">
      <?php if ($profilePic !== ""): ?>
        <img src="<?= htmlspecialchars($profilePic); ?>" alt="Technician">
      <?php else: ?>
        <div class="sf-bid-avatar-fallback">
          <?= strtoupper(substr((string)$bid["technician_name"], 0, 1)); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="sf-bid-main">
    <h5 class="sf-bid-name mb-2"><?= htmlspecialchars($bid["technician_name"]); ?></h5>

    <div class="sf-bid-meta-grid">
      <div class="sf-mini-info">
        <span class="sf-mini-label">Price</span>
        <span class="sf-mini-value">EGP <?= number_format((float)$bid["bid_price"], 2); ?></span>
      </div>

      <div class="sf-mini-info">
        <span class="sf-mini-label">Duration</span>
        <span class="sf-mini-value"><?= htmlspecialchars((string)$bid["estimated_duration"]); ?></span>
      </div>

      <div class="sf-mini-info">
        <span class="sf-mini-label">Experience</span>
        <span class="sf-mini-value"><?= $experience !== "" ? htmlspecialchars(ucfirst($experience)) : "—"; ?></span>
      </div>

      <div class="sf-mini-info">
        <span class="sf-mini-label">Availability</span>
        <span class="sf-mini-value"><?= $availability !== "" ? htmlspecialchars(ucfirst($availability)) : "—"; ?></span>
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
                    <div class="sf-bid-info-label">Message</div>
                    <div class="sf-bid-info-value">
                      <?= $message !== "" ? htmlspecialchars($message) : "No message provided."; ?>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                  <span class="sf-status-badge sf-bid-status-<?= htmlspecialchars($bid["bid_status"]); ?>">
                    <?= htmlspecialchars(ucfirst($bid["bid_status"])); ?>
                  </span>

                  <?php if ($job["status"] === "available" && $bid["bid_status"] === "pending"): ?>
                    <form action="accept_bid.php" method="POST" class="m-0">
                      <input type="hidden" name="bid_id" value="<?= (int)$bid["bid_id"]; ?>">
                      <input type="hidden" name="job_id" value="<?= (int)$job_id; ?>">
                      <button type="submit" class="btn sf-btn-primary rounded-pill px-4">
                        Accept Bid
                      </button>
                    </form>
                  <?php else: ?>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" disabled>
                      <?= $bid["bid_status"] === "accepted" ? "Accepted" : "Closed"; ?>
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
            <h4 class="mb-2">No Bids Yet</h4>
            <p class="text-secondary mb-0">Technicians have not submitted any bids for this service yet.</p>
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