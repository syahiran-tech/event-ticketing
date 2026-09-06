<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$trips = $conn->query('SELECT *, (capacity - seats_booked) AS remaining FROM trips ORDER BY trip_date, departure_time')->fetch_all(MYSQLI_ASSOC);

$totalTrips = count($trips);
$totalRemaining = array_sum(array_column($trips, 'remaining'));

$pageTitle = 'All Trips';
require 'partials/header.php';
?>
<div class="page-header">
<h1>All Trips</h1>
<p>Every upcoming shuttle trip, with live seat availability.</p>
</div>

<section>
<div class="card-grid">
<div class="card"><h3><?= (int)$totalTrips ?></h3><p>Upcoming trips</p></div>
<div class="card"><h3><?= (int)$totalRemaining ?></h3><p>Seats still available</p></div>
</div>
</section>

<?php foreach ($trips as $t): ?>
<section>
<div class="card" style="max-width:720px;">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($t)) ?>" alt="<?= htmlspecialchars($t['route_name']) ?>" loading="lazy">
<span class="badge badge-accent"><?= htmlspecialchars(date('d M Y', strtotime($t['trip_date']))) ?> &middot; <?= htmlspecialchars(date('g:i A', strtotime($t['departure_time']))) ?></span>
<h3><?= htmlspecialchars($t['route_name']) ?></h3>
<p>&#128205; Pickup: <?= htmlspecialchars($t['pickup_point']) ?></p>
<p>&#127937; Drop-off: <?= htmlspecialchars($t['dropoff_point']) ?></p>
<p><?= (int)$t['remaining'] ?> / <?= (int)$t['capacity'] ?> seats remaining</p>
<?php if ($t['remaining'] <= 0): ?>
<button class="btn" disabled>Fully Booked</button>
<?php elseif (current_user_id()): ?>
<a class="btn" href="create.php?trip_id=<?= (int)$t['id'] ?>">Book Seats</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
