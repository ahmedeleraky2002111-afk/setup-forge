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
  die("Database connection failed.");


$user_id = $_SESSION['user_id'];
$success = "";

/* GET USER DATA */

$result = pg_query($conn,"
SELECT name,email,phone,profile_picture,skills,bio
FROM users
WHERE id = $user_id
");

$user = pg_fetch_assoc($result);


/* UPDATE PROFILE */

if($_SERVER["REQUEST_METHOD"] == "POST"){

$name = pg_escape_string($_POST['name']);
$email = pg_escape_string($_POST['email']);
$phone = pg_escape_string($_POST['phone']);
$skills = pg_escape_string($_POST['skills']);
$bio = pg_escape_string($_POST['bio']);

$profile_picture = $user['profile_picture'];

/* HANDLE IMAGE UPLOAD */

if(!empty($_FILES['profile_picture']['name'])){

$target_dir = "uploads/";
$file_name = time() . "_" . $_FILES["profile_picture"]["name"];
$target_file = $target_dir . $file_name;

move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);

$profile_picture = $target_file;
}

$update = pg_query($conn,"
UPDATE users
SET name='$name',
email='$email',
phone='$phone',
skills='$skills',
bio='$bio',
profile_picture='$profile_picture'
WHERE id=$user_id
");

if($update){
    header("Location: profile.php?updated=1");
    exit();
}

$result = pg_query($conn,"
SELECT name,email,phone,profile_picture,skills,bio
FROM users
WHERE id=$user_id
");

$user = pg_fetch_assoc($result);

}

?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Profile - SetupForge</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial;
}

body{
display:flex;
background:#f4f7fb;
}

.sidebar{
width:240px;
background:#1554b3;
color:white;
height:100vh;
padding:30px 20px;
position:fixed;
}

.sidebar h2{
margin-bottom:50px;
font-size:28px;
}

.nav-link{
display:block;
padding:14px 18px;
margin-bottom:15px;
color:white;
text-decoration:none;
border-radius:12px;
font-size:18px;
}

.nav-link:hover{
background:rgba(255,255,255,0.15);
}

.nav-link.active{
background:#16a085;
}

.main{
margin-left:260px;
padding:40px;
width:100%;
}

.card{
background:white;
padding:30px;
border-radius:15px;
box-shadow:0px 5px 15px rgba(0,0,0,0.05);
max-width:700px;
}

.profile-pic{
width:110px;
height:110px;
border-radius:50%;
object-fit:cover;
margin-bottom:15px;
}

.form-group{
margin-bottom:18px;
}

.form-group label{
display:block;
margin-bottom:5px;
font-weight:bold;
}

.form-group input,
.form-group textarea{
width:100%;
padding:10px;
border:1px solid #ddd;
border-radius:8px;
}

textarea{
height:80px;
resize:none;
}

.save-btn{
background:#16a085;
color:white;
border:none;
padding:12px;
width:100%;
border-radius:8px;
cursor:pointer;
font-size:16px;
}

.save-btn:hover{
background:#138d75;
}

.success{
background:#d4edda;
color:#155724;
padding:10px;
margin-bottom:15px;
border-radius:8px;
}

</style>

</head>

<body>

<div class="sidebar">

<h2>SetupForge</h2>

<a href="dashboard.php" class="nav-link">Dashboard</a>
<a href="laborjobs.php" class="nav-link">Available Jobs</a>
<a href="myjobs.php" class="nav-link">My Jobs</a>
<a href="profile.php" class="nav-link active">Profile</a>
<a href="../auth/logout.php" class="nav-link">Logout</a>

</div>

<div class="main">

<div class="card">

<h2>Edit Profile</h2>

<?php if($success){ ?>
<div class="success"><?php echo $success; ?></div>
<?php } ?>

<?php if($user['profile_picture']){ ?>
<img src="<?php echo $user['profile_picture']; ?>" class="profile-pic">
<?php } ?>

<form method="POST" enctype="multipart/form-data">

<div class="form-group">
<label>Profile Picture</label>
<input type="file" name="profile_picture">
</div>

<div class="form-group">
<label>Name</label>
<input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
</div>

<div class="form-group">
<label>Phone</label>
<input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
</div>

<div class="form-group">
<label>Skills</label>
<input type="text" name="skills" placeholder="Electrician, Plumbing..." value="<?php echo htmlspecialchars($user['skills']); ?>">
</div>

<div class="form-group">
<label>Bio</label>
<textarea name="bio"><?php echo htmlspecialchars($user['bio']); ?></textarea>
</div>

<button class="save-btn">Save Changes</button>

</form>

</div>

</div>

</body>
</html>