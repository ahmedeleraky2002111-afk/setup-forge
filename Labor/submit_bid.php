<?php
session_start();

include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION["user_type"]) ||
   ($_SESSION["user_type"] !== "labor" && $_SESSION["user_type"] !== "technician")) {
    header("Location: ../home.php");
    exit();
}

$technician_id = (int)$_SESSION['user_id'];
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

/* GET PROVIDER TYPE */
$type_query = pg_query($conn, "
    SELECT provider_type
    FROM labors
    WHERE user_id = $technician_id
    LIMIT 1
");

$type_row = $type_query ? pg_fetch_assoc($type_query) : null;
$provider_type = $type_row['provider_type'] ?? '';

if ($provider_type !== 'technician') {
    header("Location: laborjobs.php");
    exit();
}

$userName = $_SESSION["name"] ?? "User";
$errorMessage = "";

/* OPTIONAL JOB INFO */
$jobInfo = null;
if ($job_id > 0) {
    $jobInfoRes = pg_query($conn, "
        SELECT title, location, budget, description
        FROM jobs
        WHERE job_id = $job_id
        LIMIT 1
    ");

    if ($jobInfoRes && pg_num_rows($jobInfoRes) > 0) {
        $jobInfo = pg_fetch_assoc($jobInfoRes);
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $message = trim($_POST['message'] ?? '');
    $duration = trim($_POST['duration'] ?? '');

    if ($job_id <= 0 || $price <= 0) {
        $errorMessage = "Please enter a valid bid price.";
    } else {
        pg_query($conn, "
            INSERT INTO bids (job_id, technician_id, bid_price, message, estimated_duration, status)
            VALUES ($job_id, $technician_id, $price, '$message', '$duration', 'pending')
        ");

        header("Location: laborjobs.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Bid - SetupForge</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="labor.css?v=105">

<style>
    .bid-page{
        max-width:1100px;
        margin:auto;
        padding:34px;
    }

    .bid-hero{
        display:grid;
        grid-template-columns:1.5fr auto;
        gap:20px;
        align-items:center;
        margin-bottom:28px;
        background:linear-gradient(135deg, rgba(255,255,255,.95), rgba(248,250,252,.95));
        border:1px solid #eef2f7;
        border-radius:24px;
        padding:26px 28px;
        box-shadow:var(--sf-shadow-md);
    }

    .bid-hero h1{
        font-size:2.15rem;
        line-height:1.1;
        margin:0 0 8px 0;
        color:#152033;
        font-weight:900;
        letter-spacing:-.7px;
    }

    .bid-hero p{
        margin:0;
        color:var(--sf-muted);
        font-size:1rem;
        max-width:700px;
    }

    .bid-chip{
        background:#fff;
        border:1px solid var(--sf-border);
        border-radius:16px;
        padding:14px 18px;
        box-shadow:var(--sf-shadow-sm);
        color:#2558a8;
        font-weight:800;
        white-space:nowrap;
    }

    .bid-layout{
        display:grid;
        grid-template-columns:1.1fr .9fr;
        gap:22px;
        align-items:start;
    }

    .panel-box{
        background:#fff;
        border-radius:24px;
        box-shadow:var(--sf-shadow-md);
        border:1px solid #edf2f7;
        overflow:hidden;
    }

    .panel-head{
        padding:22px 22px 18px;
        border-bottom:1px solid #eef2f7;
        background:linear-gradient(180deg, #ffffff, #fbfcfe);
    }

    .panel-head h2{
        margin:0;
        font-size:1.45rem;
        font-weight:900;
        color:#172033;
        letter-spacing:-.4px;
    }

    .panel-head p{
        margin:8px 0 0;
        color:#6b7280;
        font-size:.95rem;
    }

    .panel-body{
        padding:22px;
    }

    .job-summary{
        display:grid;
        gap:14px;
    }

    .summary-item{
        background:linear-gradient(180deg, #ffffff, #fbfdff);
        border:1px solid #e6eefc;
        border-radius:18px;
        padding:16px;
        box-shadow:var(--sf-shadow-sm);
    }

    .summary-item h4{
        margin:0 0 8px;
        color:#1554b3;
        font-size:.82rem;
        font-weight:900;
        text-transform:uppercase;
        letter-spacing:.7px;
    }

    .summary-item p{
        margin:0;
        color:#4b5563;
        line-height:1.6;
        font-size:.96rem;
    }

    .form-grid{
        display:grid;
        gap:16px;
    }

    .field-group label{
        display:block;
        margin-bottom:8px;
        font-weight:800;
        color:#1f2937;
        font-size:.95rem;
    }

    .field-group input,
    .field-group textarea{
        width:100%;
        border:1px solid #dbe3ee;
        border-radius:16px;
        padding:14px 16px;
        font-size:.96rem;
        color:#1f2937;
        background:#fff;
        transition:border-color .18s ease, box-shadow .18s ease;
        outline:none;
    }

    .field-group input:focus,
    .field-group textarea:focus{
        border-color:#004cac;
        box-shadow:0 0 0 4px rgba(0,76,172,.10);
    }

    .field-group textarea{
        min-height:140px;
        resize:vertical;
    }

    .helper-text{
        margin-top:6px;
        color:#6b7280;
        font-size:.86rem;
    }

    .submit-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:100%;
        background:linear-gradient(135deg, var(--sf-primary), #0a63c8);
        color:#fff;
        text-decoration:none;
        padding:14px 18px;
        border-radius:14px;
        font-size:.95rem;
        font-weight:800;
        transition:.2s ease;
        border:none;
        box-shadow:0 8px 18px rgba(0,76,172,.18);
    }

    .submit-btn:hover{
        background:linear-gradient(135deg, var(--sf-primary-dark), var(--sf-primary));
        color:#fff;
        transform:translateY(-1px);
    }

    .back-link{
        display:inline-flex;
        align-items:center;
        gap:8px;
        text-decoration:none;
        color:#004cac;
        font-weight:800;
        margin-bottom:16px;
    }

    .error-box{
        padding:16px 18px;
        border-radius:16px;
        margin-bottom:18px;
        font-weight:700;
        box-shadow:var(--sf-shadow-sm);
        background:#fee2e2;
        color:#991b1b;
        border:1px solid #fecaca;
    }

    @media (max-width: 1000px){
        .bid-layout{
            grid-template-columns:1fr;
        }

        .bid-hero{
            grid-template-columns:1fr;
        }
    }

    @media (max-width: 768px){
        .bid-page{
            padding:22px 16px 28px;
        }

        .bid-hero{
            padding:22px 18px;
        }

        .bid-hero h1{
            font-size:1.75rem;
        }

        .bid-chip{
            width:100%;
        }
    }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sf-navbar">
    <div class="container">

        <a class="navbar-brand sf-brand-wrap" href="dashboard.php">
            <div class="sf-logo">
                <img src="../assets/images/Logo.png" alt="SetupForge Logo">
            </div>
            <span class="fw-bold">SetupForge</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#laborNavbar" aria-controls="laborNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="laborNavbar">
            <ul class="navbar-nav gap-3">
                <li class="nav-item">
                    <a class="nav-link sf-navlink" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sf-navlink" href="laborjobs.php">Available Jobs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sf-navlink" href="mybids.php">My Bids</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sf-navlink" href="myjobs.php">My Jobs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sf-navlink" href="profile.php">Profile</a>
                </li>
            </ul>
        </div>

        <div class="sf-nav-actions">
            <div class="dropdown">
                <button class="btn sf-profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-fill"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end sf-dropdown">
                    <li class="px-3 py-2 fw-semibold">
                        <?php echo htmlspecialchars($userName); ?>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>

    </div>
</nav>

<div class="bid-page">

    <div class="bid-hero">
        <div>
            <h1>Submit Your Bid</h1>
            <p>
                Send your price, estimated duration, and a short message to the business for this technician job.
            </p>
        </div>
       
    </div>

    <a href="laborjobs.php" class="back-link">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Available Jobs</span>
    </a>

    <?php if ($errorMessage): ?>
        <div class="error-box"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="bid-layout">

        <div class="panel-box">
            <div class="panel-head">
                <h2>Bid Form</h2>
                <p>Fill in your offer carefully before sending it.</p>
            </div>

            <div class="panel-body">
                <form method="POST" class="form-grid">

                    <div class="field-group">
                        <label for="price">Your Price</label>
                        <input id="price" name="price" type="number" step="0.01" min="1" placeholder="Enter your bid price in EGP" required>
                        <div class="helper-text">Example: 2500 EGP</div>
                    </div>

                    <div class="field-group">
                        <label for="duration">Estimated Duration</label>
                        <input id="duration" name="duration" placeholder="Example: 2 days">
                        <div class="helper-text">Write how long the work may take.</div>
                    </div>

                    <div class="field-group">
                        <label for="message">Message to Business</label>
                        <textarea id="message" name="message" placeholder="Introduce your experience, explain your offer, and add anything important..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Send Bid</button>
                </form>
            </div>
        </div>

        <div class="panel-box">
            <div class="panel-head">
                <h2>Job Summary</h2>
                <p>Quick details about the job you are bidding on.</p>
            </div>

            <div class="panel-body">
                <div class="job-summary">
                    <div class="summary-item">
                        <h4>Job Title</h4>
                        <p><?php echo htmlspecialchars($jobInfo['title'] ?? 'Technician Job'); ?></p>
                    </div>

                    <div class="summary-item">
                        <h4>Location</h4>
                        <p><?php echo htmlspecialchars($jobInfo['location'] ?? 'Not specified'); ?></p>
                    </div>

                    <div class="summary-item">
                        <h4>Budget</h4>
                        <p>
                            <?php
                            if (isset($jobInfo['budget'])) {
                                echo number_format((float)$jobInfo['budget'], 2) . " EGP";
                            } else {
                                echo "Not specified";
                            }
                            ?>
                        </p>
                    </div>

                    <div class="summary-item">
                        <h4>Description</h4>
                        <p><?php echo htmlspecialchars($jobInfo['description'] ?? 'No description available.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>