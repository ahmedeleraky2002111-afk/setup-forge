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

$user_id = (int)$_SESSION['user_id'];

$result = pg_query($conn, "
    SELECT bids.*, jobs.title, jobs.location
    FROM bids
    JOIN jobs ON bids.job_id = jobs.job_id
    WHERE technician_id = $user_id
    ORDER BY created_at DESC
");

$userName = $_SESSION["name"] ?? "User";

/* Small stats */
$totalBids = 0;
$pendingBids = 0;
$acceptedBids = 0;
$rejectedBids = 0;

$statsResult = pg_query($conn, "
    SELECT
        COUNT(*) AS total_bids,
        COUNT(*) FILTER (WHERE status = 'pending') AS pending_bids,
        COUNT(*) FILTER (WHERE status = 'accepted') AS accepted_bids,
        COUNT(*) FILTER (WHERE status = 'rejected') AS rejected_bids
    FROM bids
    WHERE technician_id = $user_id
");

if ($statsResult && pg_num_rows($statsResult) > 0) {
    $statsRow = pg_fetch_assoc($statsResult);
    $totalBids = (int)($statsRow['total_bids'] ?? 0);
    $pendingBids = (int)($statsRow['pending_bids'] ?? 0);
    $acceptedBids = (int)($statsRow['accepted_bids'] ?? 0);
    $rejectedBids = (int)($statsRow['rejected_bids'] ?? 0);
}

function bidStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'badge-pending';
        case 'accepted':
            return 'badge-accepted';
        case 'rejected':
            return 'badge-rejected';
        default:
            return 'badge-default';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bids - SetupForge</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="labor.css?v=103">

    <style>
        .jobs-page{
            max-width:1450px;
            margin:auto;
            padding:34px;
        }

        .jobs-hero{
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

        .jobs-hero h1{
            font-size:2.2rem;
            line-height:1.1;
            margin:0 0 8px 0;
            color:#152033;
            font-weight:900;
            letter-spacing:-.7px;
        }

        .jobs-hero p{
            margin:0;
            color:var(--sf-muted);
            font-size:1rem;
            max-width:760px;
        }

        .jobs-hero-right{
            display:flex;
            gap:12px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }

        .jobs-chip{
            background:#fff;
            border:1px solid var(--sf-border);
            border-radius:16px;
            padding:14px 18px;
            box-shadow:var(--sf-shadow-sm);
            color:#2558a8;
            font-weight:800;
            white-space:nowrap;
        }

        .cards{
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:18px;
            margin-bottom:26px;
        }

        .card-box{
            background:linear-gradient(180deg, #ffffff, #fcfdff);
            padding:20px 20px 18px;
            border-radius:22px;
            box-shadow:var(--sf-shadow-md);
            position:relative;
            overflow:hidden;
            border:1px solid #edf2f7;
            min-height:150px;
            transition:transform .18s ease, box-shadow .18s ease;
        }

        .card-box:hover{
            transform:translateY(-2px);
            box-shadow:0 14px 28px rgba(0,0,0,.09);
        }

        .card-box::before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:5px;
            background:linear-gradient(90deg, var(--sf-primary), var(--sf-secondary));
        }

        .card-box h3{
            color:#6b7280;
            font-size:.82rem;
            margin:0 0 18px 0;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.9px;
        }

        .card-box p{
            font-size:2rem;
            line-height:1;
            font-weight:900;
            color:#1f5bb2;
            margin:0 0 14px 0;
            letter-spacing:-.7px;
        }

        .card-sub{
            color:#6b7280;
            font-size:.93rem;
            line-height:1.45;
        }

        .jobs-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
            gap:20px;
        }

        .job-card{
            background:linear-gradient(180deg, #ffffff, #fcfdff);
            padding:22px;
            border-radius:22px;
            box-shadow:var(--sf-shadow-md);
            border:1px solid #edf2f7;
            transition:transform .18s ease, box-shadow .18s ease;
        }

        .job-card:hover{
            transform:translateY(-2px);
            box-shadow:0 14px 28px rgba(0,0,0,.09);
        }

        .job-card h3{
            color:#162033;
            margin:0 0 12px 0;
            font-size:1.18rem;
            font-weight:900;
        }

        .job-card p{
            margin:8px 0;
            color:#475569;
            line-height:1.55;
            font-size:.95rem;
        }

        .status-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:7px 12px;
            border-radius:999px;
            font-size:.8rem;
            font-weight:800;
            text-transform:capitalize;
            margin-top:12px;
        }

        .badge-pending{
            background:#fef3c7;
            color:#92400e;
        }

        .badge-accepted{
            background:#dcfce7;
            color:#166534;
        }

        .badge-rejected{
            background:#fee2e2;
            color:#991b1b;
        }

        .badge-default{
            background:#f3f4f6;
            color:#374151;
        }

        .empty-box{
            background:#fff;
            padding:40px;
            border-radius:24px;
            text-align:center;
            margin-top:10px;
            box-shadow:var(--sf-shadow-md);
            border:1px solid #edf2f7;
        }

        .empty-box h3{
            margin-bottom:10px;
            color:#162033;
            font-weight:900;
        }

        .empty-box p{
            color:#6b7280;
            margin:0;
        }

        @media (max-width: 1200px){
            .cards{
                grid-template-columns:repeat(2, 1fr);
            }
        }

        @media (max-width: 1100px){
            .jobs-hero{
                grid-template-columns:1fr;
            }
        }

        @media (max-width: 768px){
            .jobs-page{
                padding:22px 16px 28px;
            }

            .jobs-hero{
                padding:22px 18px;
            }

            .jobs-hero h1{
                font-size:1.75rem;
            }

            .jobs-hero-right{
                justify-content:flex-start;
            }

            .jobs-chip{
                width:100%;
            }

            .cards{
                grid-template-columns:1fr;
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
                    <a class="nav-link sf-navlink active" href="mybids.php">My Bids</a>
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

<div class="jobs-page">

    <div class="jobs-hero">
        <div>
            <h1>My Bids</h1>
            <p>
                Review all the bids you submitted for technician jobs and track whether they are pending, accepted, or rejected.
            </p>
        </div>

        <div class="jobs-hero-right">
            <div class="jobs-chip">
                Total Bids: <?php echo $totalBids; ?>
            </div>
            <div class="jobs-chip">
                Accepted: <?php echo $acceptedBids; ?>
            </div>
        </div>
    </div>

    <div class="cards">
        <div class="card-box">
            <h3>Total Bids</h3>
            <p><?php echo $totalBids; ?></p>
            <div class="card-sub">All bids you submitted</div>
        </div>

        <div class="card-box">
            <h3>Pending</h3>
            <p><?php echo $pendingBids; ?></p>
            <div class="card-sub">Waiting for business response</div>
        </div>

        <div class="card-box">
            <h3>Accepted</h3>
            <p><?php echo $acceptedBids; ?></p>
            <div class="card-sub">Bids approved by businesses</div>
        </div>

        <div class="card-box">
            <h3>Rejected</h3>
            <p><?php echo $rejectedBids; ?></p>
            <div class="card-sub">Bids that were not selected</div>
        </div>
    </div>

    <?php if ($result && pg_num_rows($result) > 0): ?>

        <div class="jobs-grid">

            <?php while($row = pg_fetch_assoc($result)): ?>
                <div class="job-card">

                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>

                    <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>

                    <p><strong>Your Price:</strong> <?php echo number_format((float)$row['bid_price'], 2); ?> EGP</p>

                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($row['estimated_duration']); ?></p>

                    <?php $status = $row['status']; ?>

                    <span class="status-badge <?php echo bidStatusClass($status); ?>">
                        <?php echo ucfirst(htmlspecialchars($status)); ?>
                    </span>

                </div>
            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty-box">
            <h3>No Bids Yet</h3>
            <p>Go to Available Jobs and submit your first technician bid.</p>
        </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>