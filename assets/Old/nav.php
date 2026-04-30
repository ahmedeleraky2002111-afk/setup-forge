<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Navbar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg" style="background-color: #004cac;">
  <div class="container">
    
    <!-- Logo -->
    <a class="navbar-brand d-flex align-items-center text-white" href="#">
      <img src="https://via.placeholder.com/40x40" alt="Logo" width="40" height="40" class="me-2">
      <span class="fw-bold">MyLogo</span>
    </a>

    <!-- Mobile Toggle Button -->
    <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Right Side Buttons -->
    <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
      <div class="d-flex gap-2">
        <a href="#" class="btn btn-outline-light">Login</a>
        <a href="#" class="btn btn-light text-primary fw-semibold">Sign Up</a>
      </div>
    </div>

  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
