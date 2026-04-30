<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["finish_signup"])) {

  // save answers from POST into session
  $_SESSION["setup_answers"] = [
    "business_type"  => $_POST["business_type"] ?? null,
    "business_name"  => $_POST["business_name"] ?? null,
    "budget"         => $_POST["budget_egp"] ?? null,
    "place_size"     => $_POST["place_size"] ?? null,
    "location_text"       => $_POST["location_text"] ?? null,
    "modules"        => $_POST["modules"] ?? []  // if checkboxes
  ];

  header("Location: signup.php");
  exit;
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SetupForge - Questions</title>

  <!-- Bootstrap CSS (needed for navbar + carousel) -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />

  <!-- Bootstrap Icons (needed for profile/cart icons) -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
  />

  <style>
    :root{
      --bg:#ffffff;
      --text:#121212;
      --muted:#5a5a5a;
      --border:#e9e9ee;
      --card:#ffffff;
      --shadow: 0 18px 50px rgba(0,0,0,0.08);
      --accent:#ffd523;
      --accentDark:#2c2e43;
      --soft:#f7f7fb;
      --radius:18px;
      --blue:#004cac;     /* used as primary */
      --green:#00a994;    /* used as secondary */
    }
    *{ box-sizing:border-box; }
    body{
      margin:0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    /* =========================
       NAVBAR + BANNER + CATEGORY BAR
       (ported from File 1, but using File 2 variables)
    ========================= */
    .topbar-primary { background: var(--blue); }
    .navlink-strong { font-weight: 600; }

    .navbar-toggler { border: 1px solid rgba(255,255,255,.4); }
    .navbar-toggler-icon { filter: invert(1); }

    .banner-wrap { width: 100%; }

    .banner-slide {
      position: relative;
      min-height: 505px;
      display: flex;
      align-items: center;
      background-size: cover;
      background-position: center;
    }

    .banner-1 { background-image: url("images/Banner.jpeg"); }
    .banner-2 { background-image: url("images/banner/b2.jpg"); }
    .banner-3 { background-image: url("images/banner/b3.jpg"); }

    .banner-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,.45);
    }

    .banner-content {
      position: relative;
      z-index: 2;
      color: white;
    }

    .banner-title {
      font-size: 34px;
      font-weight: 800;
    }

    .banner-subtitle {
      font-size: 16px;
      margin-bottom: 16px;
    }

    .categorybar-secondary { background: var(--green); }

    .categorybar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
    }

    .categorybar-left {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
    }

    .catlink {
      color: white;
      text-decoration: none;
      font-weight: 600;
      padding: 6px 10px;
      border-radius: 10px;
    }

    .catlink:hover { background: rgba(255,255,255,.2); }

    .cartlink {
      position: relative;
      color: white;
      font-size: 22px;
      padding: 6px 10px;
      border-radius: 10px;
      text-decoration: none;
    }

    .cartlink:hover { background: rgba(255,255,255,.2); }

    .cartCount {
      position: absolute;
      top: 2px;
      right: 2px;
      background: var(--blue);
      color: white;
      font-size: 11px;
      font-weight: 800;
      border-radius: 999px;
      padding: 2px 6px;
    }

    /* =========================
       YOUR ORIGINAL FILE 2 STYLES (unchanged)
    ========================= */

    .wrap{
      max-width: 980px;
      margin: 0 auto;
      padding: 28px 18px 70px;
    }

    /* HERO */
    .hero{
      min-height: 82vh;
      display:flex;
      align-items:center;
      justify-content:center;
      text-align:center;
    }
    .heroInner{
      width: 100%;
      max-width: 760px;
      padding: 10px;
    }
    @keyframes popIn {
      0% { opacity:0; transform: translateY(12px) scale(0.98); filter: blur(10px); }
      100% { opacity:1; transform: translateY(0) scale(1); filter: blur(0); }
    }
    @keyframes underlineGrow {
      from { transform: scaleX(0); opacity: 0; }
      to   { transform: scaleX(1); opacity: 1; }
    }
    h1{
      font-size: clamp(30px, 6vw, 50px);
      margin: 0 0 10px;
      letter-spacing: -1px;
      animation: popIn 650ms ease-out both;
      color: var(--accentDark);
    }
    .brand{
      position: relative;
      display:inline-block;
    }
    .brand::after{
      content:"";
      position:absolute;
      left:0;
      right:0;
      height: 10px;
      bottom: 6px;
      background: rgba(255,213,35,0.55);
      z-index:-1;
      border-radius: 999px;
      transform-origin: left;
      animation: underlineGrow 700ms ease-out 220ms both;
    }
    .desc{
      margin: 0 auto 22px;
      max-width: 680px;
      font-size: 17px;
      line-height: 1.75;
      color: var(--muted);
      animation: popIn 650ms ease-out 120ms both;
    }
    .cta{
      display:flex;
      justify-content:center;
      gap: 12px;
      flex-wrap: wrap;
      animation: popIn 650ms ease-out 220ms both;
    }
    .btn{
      border: 0;
      cursor:pointer;
      border-radius: 16px;
      padding: 14px 18px;
      font-weight: 750;
      font-size: 16px;
      transition: transform 120ms ease, box-shadow 120ms ease, filter 120ms ease;
      user-select:none;
    }
    .btnPrimary{
      background: var(--blue);
      color:white;
      box-shadow: 0 16px 40px rgba(0,0,0,0.10);
      min-width: 220px;
    }
    .btnPrimary:hover{ filter: brightness(1.05); }
    .btnPrimary:active{ transform: translateY(1px); }
    .btnGhost{
      background: #fff;
      border: 1px solid var(--border);
      color: var(--accentDark);
      padding: 12px 14px;
    }
    .btnGhost:hover{ box-shadow: 0 12px 24px rgba(0,0,0,0.06); }

    /* WIZARD */
    .wizardShell{
      display:none;
      margin-top: 28px;
      background: var(--soft);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 18px;
      box-shadow: var(--shadow);
      text-align:left;
    }
    .wizardTop{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }
    .wizardTitle{
      font-size: 16px;
      font-weight: 800;
      margin: 0;
      color: var(--blue);
    }
    .hiddenHint{
      margin-top: 6px;
      font-size: 12px;
      color: #777;
    }
    .progressWrap{
      flex:1;
      min-width: 220px;
      height: 10px;
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 999px;
      overflow:hidden;
    }
    .progressBar{
      height:100%;
      width:0%;
      background: var(--green);
      transition: width 260ms ease;
    }
    .step{ display:none; animation: popIn 350ms ease-out both; }
    .step.active{ display:block; }
    .qTitle{
      font-size: 20px;
      margin: 6px 0 12px;
      letter-spacing:-0.3px;
      font-weight: 900;
    }
    .grid{
      display:grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    @media (max-width: 620px){ .grid{ grid-template-columns: 1fr; } }
    .card{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 14px 14px;
      cursor:pointer;
      transition: transform 120ms ease, box-shadow 120ms ease, border-color 120ms ease;
      display:flex;
      gap: 12px;
      align-items:flex-start;
      min-height: 78px;
    }
    .card:hover{
      transform: translateY(-1px);
      box-shadow: 0 14px 30px rgba(0,0,0,0.08);
      border-color: rgba(44,46,67,0.25);
    }
    .icon{
      width: 44px;
      height: 44px;
      border-radius: 14px;
      font-size: 28px;
      display:grid;
      place-items:center;
      font-weight: 900;
      color: var(--blue);
      background: #eef4ff;
      flex: 0 0 auto;
    }
    .cText .t{ font-weight: 900; margin-bottom: 3px; }
    .cText .s{ color: var(--muted); font-size: 13px; line-height: 1.35; }
    .navRow{
      display:flex;
      justify-content:space-between;
      gap: 10px;
      margin-top: 14px;
      flex-wrap: wrap;
    }
    .box{
      background:#fff;
      border:1px solid var(--border);
      border-radius: 16px;
      padding: 14px;
    }
    .lbl{ display:block; font-weight:900; margin-bottom: 8px; }
    .inputRow{
      display:flex;
      align-items:center;
      gap: 10px;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px 12px;
      background:#fff;
    }
    .prefix{
      font-weight:900;
      color: white;
      background: var(--green);
      padding: 8px 10px;
      border-radius: 12px;
    }
    .inp{
      border:0;
      outline:none;
      width: 100%;
      font-size: 16px;
      font-weight: 750;
    }
    .hint{
      margin-top: 10px;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.45;
    }

    /* Checkbox cards */
    .checks{
      display:grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
      margin-top: 10px;
    }
    @media (max-width: 620px){ .checks{ grid-template-columns: 1fr; } }
    .checkCard{
      display:flex;
      align-items:center;
      gap: 10px;
      padding: 12px;
      border: 1px solid var(--border);
      border-radius: 14px;
      background:#fff;
      cursor:pointer;
      user-select:none;
      transition: box-shadow 120ms ease, transform 120ms ease;
    }
    .checkCard:hover{ box-shadow: 0 12px 24px rgba(0,0,0,0.06); transform: translateY(-1px); }
    .checkCard input{ width:18px; height:18px; }
    .checkText .t{ font-weight:900; }
    .checkText .s{ font-size:12px; color: var(--muted); margin-top:2px; }

    .reveal{ display:block; animation: popIn 420ms ease-out both; }

    /* small responsive for banner title */
    @media (max-width: 768px){
      .banner-title{ font-size: 26px; }
    }
  </style>
</head>

<body>

  <!-- TOP NAV (Primary) -->
  <nav class="navbar navbar-expand-lg topbar-primary">
    <div class="container">
      <a class="navbar-brand text-white fw-bold" href="#">SetupForge</a>

      <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
          <li class="nav-item">
            <a class="nav-link text-white navlink-strong" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white navlink-strong" href="#">About</a>
          </li>

          <!-- Contact + Profile icon -->
          <li class="nav-item d-flex align-items-center gap-2">
            <a class="nav-link text-white navlink-strong" href="#">Contact Us</a>
            <a class="nav-link text-white navlink-strong d-flex align-items-center gap-1" href="#" title="Profile">
              <i class="bi bi-person-circle fs-5"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- BANNER SLIDER -->
  <section class="banner-wrap">
    <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="2"></button>
      </div>

      <div class="carousel-inner">

        <!-- Slide 1 -->
        <div class="carousel-item active">
          <div class="banner-slide banner-1">
            <div class="container banner-content">
              <!-- empty -->
            </div>
          </div>
        </div>

        <!-- Slide 2 -->
        <div class="carousel-item">
          <div class="banner-slide banner-2">
            <div class="banner-overlay"></div>
            <div class="container banner-content">
              <h1 class="banner-title">Everything you need in one place</h1>
              <p class="banner-subtitle">Furniture, electronics, appliances, and setup services.</p>
              <button class="btn btn-light fw-semibold" type="button">Browse Categories</button>
            </div>
          </div>
        </div>

        <!-- Slide 3 -->
        <div class="carousel-item">
          <div class="banner-slide banner-3">
            <div class="banner-overlay"></div>
            <div class="container banner-content">
              <h1 class="banner-title">Get technicians when you need them</h1>
              <p class="banner-subtitle">Installation, wiring, maintenance, and on-site support.</p>
              <button class="btn btn-light fw-semibold" type="button">Request Service</button>
            </div>
          </div>
        </div>

      </div>

      <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
        <span class="visually-hidden">Previous</span>
      </button>

      <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
  </section>

  <!-- CATEGORY BAR (Secondary) -->
  <nav class="categorybar-secondary">
    <div class="container">
      <div class="categorybar">
        <div class="categorybar-left">
          <a href="#" class="catlink">Furniture</a>
          <a href="#" class="catlink">Electronic Devices</a>
          <a href="#" class="catlink">Home Appliances</a>
          <a href="#" class="catlink">Homewares</a>
          <a href="#" class="catlink">Business Services</a>
        </div>

        <!-- Cart on the RIGHT -->
        <div class="categorybar-right">
          <a href="#" class="cartlink" title="Cart">
            <i class="bi bi-cart3"></i>
            <span class="cartCount">0</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="wrap">
    <section class="hero">
      <div class="heroInner">
        <h1><span class="brand">Welcome to SetupForge</span></h1>
        <p class="desc">
          Answer a few quick questions and we’ll start building your setup path.
          Today we’ll focus on the Restaurant flow first (others later).
        </p>
        <div class="cta">
          <button class="btn btnPrimary" id="beginBtn" type="button">Begin your setup</button>
        </div>

        <!-- FORM: send answers to signup.php -->
        <form id="qForm" method="POST" action="questions.php" enctype="multipart/form-data">
          <!-- hidden fields that we fill from JS before submit -->
          <input type="hidden" name="business_type" id="h_business_type">
          <input type="hidden" name="business_name" id="h_business_name">
          <input type="hidden" name="budget_egp" id="h_budget_egp">
          <input type="hidden" name="place_size" id="h_place_size">
          <input type="hidden" name="location_text" id="h_location_text">
          <input type="hidden" name="restaurant_modules" id="h_restaurant_modules">

          <!-- WIZARD -->
          <div class="wizardShell" id="wizard">
            <div class="wizardTop">
              <div>
                <div class="wizardTitle">Quick Setup Questions</div>
                <div class="hiddenHint">We’ll save your answers in session, then ask you to sign up.</div>
              </div>
              <div class="progressWrap" aria-label="progress">
                <div class="progressBar" id="progressBar"></div>
              </div>
            </div>

            <!-- STEP 1 -->
            <div class="step active" data-step="1">
              <div class="qTitle">What type of business are you setting up?</div>
              <div class="grid">
                <div class="card" onclick="pickBusiness('cafe')">
                  <div class="icon">☕</div>
                  <div class="cText">
                    <div class="t">Café</div>
                    <div class="s">Coffee station, seating, POS, essentials.</div>
                  </div>
                </div>

                <div class="card" onclick="pickBusiness('restaurant')">
                  <div class="icon">🍽️</div>
                  <div class="cText">
                    <div class="t">Restaurant</div>
                    <div class="s">Kitchen, dining, hygiene, staff, safety.</div>
                  </div>
                </div>

                <div class="card" onclick="pickBusiness('gym')">
                  <div class="icon">🏋️</div>
                  <div class="cText">
                    <div class="t">Gym</div>
                    <div class="s">Equipment, reception, lockers, safety.</div>
                  </div>
                </div>

                <div class="card" onclick="pickBusiness('office')">
                  <div class="icon">🏢</div>
                  <div class="cText">
                    <div class="t">Office</div>
                    <div class="s">Desks, meeting rooms, network, IT.</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- STEP 2 (Business Name) -->
            <div class="step" data-step="2">
              <div class="qTitle">What’s your business name?</div>
              <div class="box">
                <label class="lbl" for="businessName">Business name</label>
                <div class="inputRow">
                  <input id="businessName" class="inp" type="text" placeholder="e.g. Home Café, Blue Fork, ..." />
                </div>
                <div class="hint">We’ll use this later for your account and setup dashboard.</div>
              </div>
              <div class="navRow">
                <button class="btn btnGhost" type="button" onclick="prevStep()">Back</button>
                <button class="btn btnPrimary" type="button" onclick="saveNameAndContinue()">Continue</button>
              </div>
            </div>

            <!-- STEP 3 (Restaurant modules - only if restaurant) -->
            <div class="step" data-step="3">
              <div class="qTitle">Restaurant setup: what do you want included?</div>
              <div class="box">
                <div class="hint" style="margin-top:0;">
                  Choose what you want to customize now (you can edit later).
                </div>

                <div class="checks" id="restaurantOnly">
                  <label class="checkCard">
                    <input type="checkbox" value="kitchen_equipment">
                    <div class="checkText">
                      <div class="t">Kitchen Equipment</div>
                      <div class="s">Ovens, fridges, prep tables, etc.</div>
                    </div>
                  </label>

                  <label class="checkCard">
                    <input type="checkbox" value="ventilation">
                    <div class="checkText">
                      <div class="t">Ventilation & Gas</div>
                      <div class="s">Hoods, ducts, cylinders, safety.</div>
                    </div>
                  </label>

                  <label class="checkCard">
                    <input type="checkbox" value="dining_area">
                    <div class="checkText">
                      <div class="t">Dining Area (Lobby)</div>
                      <div class="s">Tables, chairs, décor, lighting.</div>
                    </div>
                  </label>

                  <label class="checkCard">
                    <input type="checkbox" value="pos">
                    <div class="checkText">
                      <div class="t">POS & Tech</div>
                      <div class="s">Cashier system, printer, tablets, etc.</div>
                    </div>
                  </label>

                  <label class="checkCard">
                    <input type="checkbox" value="safety">
                    <div class="checkText">
                      <div class="t">Safety & Hygiene</div>
                      <div class="s">Extinguishers, first aid, cleaning.</div>
                    </div>
                  </label>

                  <label class="checkCard">
                    <input type="checkbox" value="labor">
                    <div class="checkText">
                      <div class="t">Labor / Staff</div>
                      <div class="s">Chefs, waiters, cleaners, etc.</div>
                    </div>
                  </label>
                </div>

                <div class="navRow">
                  <button class="btn btnGhost" type="button" onclick="prevStep()">Back</button>
                  <button class="btn btnPrimary" type="button" onclick="saveModulesAndContinue()">Continue</button>
                </div>
              </div>
            </div>

            <!-- STEP 4 (Budget) -->
            <div class="step" data-step="4">
              <div class="qTitle">What’s your estimated budget (EGP)?</div>
              <div class="box">
                <label class="lbl" for="budgetInput">Enter a number</label>
                <div class="inputRow">
                  <span class="prefix">EGP</span>
                  <input id="budgetInput" class="inp" type="number" inputmode="numeric" min="0" placeholder="e.g. 750000" />
                </div>
                <div class="hint">We’ll use this later to show you a cost summary (optional).</div>
              </div>
              <div class="navRow">
                <button class="btn btnGhost" type="button" onclick="prevStep()">Back</button>
                <button class="btn btnPrimary" type="button" onclick="saveBudgetAndContinue()">Continue</button>
              </div>
            </div>

            <!-- STEP 5 (Size) -->
            <div class="step" data-step="5">
              <div class="qTitle">How big is your place?</div>
              <div class="grid">
                <div class="card" onclick="pick('size','small')">
                  <div class="icon">S</div>
                  <div class="cText">
                    <div class="t">Small</div>
                    <div class="s">Compact setup</div>
                  </div>
                </div>

                <div class="card" onclick="pick('size','medium')">
                  <div class="icon">M</div>
                  <div class="cText">
                    <div class="t">Medium</div>
                    <div class="s">Balanced setup</div>
                  </div>
                </div>

                <div class="card" onclick="pick('size','large')">
                  <div class="icon">L</div>
                  <div class="cText">
                    <div class="t">Large</div>
                    <div class="s">More capacity</div>
                  </div>
                </div>

                <div class="card" onclick="pick('size','multi_floor')">
                  <div class="icon">↟</div>
                  <div class="cText">
                    <div class="t">Multiple floors</div>
                    <div class="s">Service zones</div>
                  </div>
                </div>
              </div>

              <div class="navRow">
                <button class="btn btnGhost" type="button" onclick="prevStep()">Back</button>
                <button class="btn btnPrimary" type="button" onclick="nextStep()">Continue</button>
              </div>
            </div>

            <!-- STEP 6 (Location - optional) -->
            <div class="step" data-step="6">
              <div class="qTitle">Business location (optional)</div>
              <div class="box">
                <label class="lbl" for="locInput">Location</label>
                <div class="inputRow">
                  <input id="locInput" class="inp" type="text" placeholder="e.g. Nasr City, Cairo (optional)" />
                </div>
                <div class="hint">You can skip this. It helps future delivery/installation planning.</div>
              </div>

              <div class="navRow">
                <button class="btn btnGhost" type="button" onclick="prevStep()">Back</button>
                <button class="btn btnPrimary" type="button" onclick="saveLocationAndContinue()">Continue</button>
              </div>
            </div>

            <!-- STEP 7 (Logo - optional upload) -->
            <div class="step" data-step="7">
              <div class="qTitle">Upload logo (optional)</div>
              <div class="box">
                <label class="lbl" for="logoFile">Choose file</label>
                <div class="inputRow">
                  <input id="logoFile" class="inp" type="file" name="logo_file" accept="image/*" />
                </div>
                <div class="hint">For now we only send the file to signup.php (we’ll store it later).</div>
              </div>

              <div class="navRow">
                <button class="btn btnGhost" type="button" onclick="prevStep()">Back</button>
                <button type="submit" name="finish_signup">Finish & Sign up</button>
              </div>
            </div>

          </div>
        </form>
      </div>
    </section>
  </div>

  <script>
    const beginBtn = document.getElementById('beginBtn');
    const wizard = document.getElementById('wizard');
    const progressBar = document.getElementById('progressBar');
    const qForm = document.getElementById('qForm');

    const totalSteps = 7;
    let currentStep = 1;

    const answers = {
      business_type: null,
      business_name: null,
      restaurant_modules: [],
      budget_egp: null,
      size: null,
      location_text: null
    };

    function showWizard(){
      wizard.classList.add('reveal');
      wizard.style.display = 'block';
      wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
      updateProgress();
    }
    beginBtn.addEventListener('click', showWizard);

    function setStep(n){
      currentStep = n;
      document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
      const target = document.querySelector(`.step[data-step="${n}"]`);
      if (target) target.classList.add('active');
      updateProgress();
    }
    function nextStep(){ if (currentStep < totalSteps) setStep(currentStep + 1); }
    function prevStep(){ if (currentStep > 1) setStep(currentStep - 1); }

    function updateProgress(){
      const pct = Math.round(((currentStep - 1) / (totalSteps - 1)) * 100);
      progressBar.style.width = pct + "%";
    }

    function pickBusiness(type){
      answers.business_type = type;
      setStep(2);
    }

    function saveNameAndContinue(){
      const v = document.getElementById('businessName').value.trim();
      if (!v){
        alert("Please enter your business name.");
        return;
      }
      answers.business_name = v;

      if (answers.business_type === 'restaurant') setStep(3);
      else setStep(4);
    }

    function saveModulesAndContinue(){
      const checks = document.querySelectorAll('#restaurantOnly input[type="checkbox"]:checked');
      const arr = Array.from(checks).map(c => c.value);

      if (arr.length === 0){
        alert("Choose at least one module (or pick a module to continue).");
        return;
      }
      answers.restaurant_modules = arr;
      setStep(4);
    }

    function saveBudgetAndContinue(){
      const v = document.getElementById("budgetInput").value;
      const n = Number(v);

      if (!v || Number.isNaN(n) || n <= 0){
        alert("Please enter a valid budget number (EGP).");
        return;
      }
      answers.budget_egp = n;
      setStep(5);
    }

    function pick(key, value){
      answers[key] = value;
      if (key === 'size') setStep(6);
    }

    function saveLocationAndContinue(){
      const v = document.getElementById("locInput").value.trim();
      answers.location_text = v || null;
      setStep(7);
    }

    qForm.addEventListener('submit', (e) => {
      if (!answers.business_type) { alert("Pick business type first."); e.preventDefault(); return; }
      if (!answers.business_name) { alert("Enter business name."); e.preventDefault(); return; }
      if (answers.business_type === 'restaurant' && (!answers.restaurant_modules || answers.restaurant_modules.length === 0)){
        alert("Choose at least one restaurant module."); e.preventDefault(); return;
      }
      if (!answers.budget_egp) { alert("Enter budget."); e.preventDefault(); return; }
      if (!answers.size) { alert("Choose place size."); e.preventDefault(); return; }

      document.getElementById('h_business_type').value = answers.business_type || '';
      document.getElementById('h_business_name').value = answers.business_name || '';
      document.getElementById('h_budget_egp').value = answers.budget_egp || '';
      document.getElementById('h_place_size').value = answers.size || '';
      document.getElementById('h_location_text').value = answers.location_text || '';
      document.getElementById('h_restaurant_modules').value =
        (answers.restaurant_modules && answers.restaurant_modules.length)
          ? answers.restaurant_modules.join(',')
          : '';
    });
  </script>

  <!-- Bootstrap JS (needed for navbar collapse + carousel) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
