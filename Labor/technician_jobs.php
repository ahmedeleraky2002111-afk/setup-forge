<?php
session_start();
include "../db.php";
$query = pg_query($conn,"
SELECT *
FROM jobs
WHERE job_type='technician'
AND status='available'
");
?>

<h2>Technician Jobs</h2>

<?php
while($job = pg_fetch_assoc($query)){
?>

<div style="border:1px solid #ccc; padding:15px; margin:15px;">

<h3><?php echo $job['title']; ?></h3>
<p><?php echo $job['description']; ?></p>
<p><b>Budget:</b> $<?php echo $job['budget']; ?></p>

<a href="submit_bid.php?job_id=<?php echo $job['job_id']; ?>">
Submit Bid
</a>

</div>

<?php } ?>