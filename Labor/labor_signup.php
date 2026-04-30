<?php
include "../db.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $country = $_POST['country'];
    $city = $_POST['city'];
    $street = $_POST['street'];

    $national_id = $_POST['national_id'];
    $dob = $_POST['dob'];
    $skills = $_POST['skills'];
    $experience = $_POST['experience'];
    $hourly_rate = $_POST['hourly_rate'];

    $provider_type = $_POST['provider_type']; // NEW

    // 1) Insert into users table
    $queryUser = "
        INSERT INTO users (name, email, password_hash, user_type, phone, country, city, street, status)
        VALUES ($1, $2, $3, 'labor', $4, $5, $6, $7, 'active')
        RETURNING id
    ";

    $resultUser = pg_query_params($conn, $queryUser, [
        $name, $email, $password, $phone, $country, $city, $street
    ]);

    if (!$resultUser) {
        die("Error inserting user.");
    }

    $userRow = pg_fetch_assoc($resultUser);
    $user_id = $userRow['id'];

    // 2) Insert into labors table
    $queryLabor = "
        INSERT INTO labors (user_id, national_id, dob, skills, experience_level, hourly_rate, avg_rating, profile_picture, status, provider_type)
        VALUES ($1, $2, $3, $4, $5, $6, 0, '', 'active', $7)
    ";

    $resultLabor = pg_query_params($conn, $queryLabor, [
        $user_id, $national_id, $dob, $skills, $experience, $hourly_rate, $provider_type
    ]);

    if ($resultLabor) {
        header("Location: labor.php");
        exit();
    } else {
        echo "❌ Error inserting labor.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Labor Signup - SetupForge</title>
    <style>
        body { font-family: Arial; background:#f5f7fa; }
        .box { width:400px; margin:50px auto; padding:20px; background:white; border-radius:10px; }
        input, textarea, select { width:100%; padding:10px; margin:8px 0; }
        button { background:#004cac; color:white; padding:10px; border:none; width:100%; }
    </style>
</head>
<body>

<div class="box">
    <h2>Labor Signup</h2>
    <form method="POST">
        <input name="name" placeholder="Full Name" required>
        <input name="email" type="email" placeholder="Email" required>
        <input name="password" type="password" placeholder="Password" required>
        <input name="phone" placeholder="Phone" required>
        <input name="country" placeholder="Country" required>
        <input name="city" placeholder="City" required>
        <input name="street" placeholder="Street" required>

        <input name="national_id" placeholder="National ID" required>
        <input name="dob" type="date" required>
        <textarea name="skills" placeholder="Skills"></textarea>
        <input name="experience" placeholder="Experience Level (junior/mid/senior)">
        <input name="hourly_rate" type="number" step="0.01" placeholder="Hourly Rate">

        <!-- NEW FIELD -->
        <select name="provider_type" required>
            <option value="">Select Type</option>
            <option value="labor">Labor</option>
            <option value="technician">Technician</option>
        </select>

        <button type="submit">Create Account</button>
    </form>
</div>

</body>
</html>
