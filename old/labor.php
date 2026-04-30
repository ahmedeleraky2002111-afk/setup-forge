<?php
session_start();
include "../db.php";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    // Get user from database
    $query = "SELECT * FROM users WHERE email = $1 LIMIT 1";
    $result = pg_query_params($conn, $query, [$email]);

    if ($result && pg_num_rows($result) > 0) {

        $user = pg_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $user["password_hash"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["user_type"] = $user["user_type"];

            header("Location:dashboard.php");
            exit();

        } else {
            $error = "Wrong password!";
        }

    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SetupForge - Labor Login</title>
    <style>
        body {
            font-family: Arial;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 320px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .login-box h2 {
            text-align: center;
            color: #004cac;
        }

        .login-box input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .login-box button {
            width: 100%;
            padding: 12px;
            background: #004cac;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .login-box button:hover {
            background: #00a994;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        .signup-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .signup-link a {
            color: #004cac;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>SetupForge</h2>

    <?php if ($error != ""): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <div class="signup-link">
        Don't have an account?
        <a href="labor_signup.php">Create Account</a>
    </div>
</div>

</body>
</html>

