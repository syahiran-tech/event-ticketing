<?php
require 'config.php';
require 'auth.php';

$trips = $conn->query('SELECT *, (capacity - seats_booked) AS remaining FROM trips ORDER BY trip_date, departure_time')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Shuttle Schedule';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Shuttle Schedule</h1>
<p>Full timetable of upcoming shuttle trips, earliest first.</p>
</div>

<?php if (empty($trips)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128197;</div>
<p>No trips scheduled yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Date</th><th>Time</th><th>Route</th><th>Pickup</th><th>Drop-off</th><th>Seats Remaining</th><th>Actions</th></tr>
<?php foreach ($trips as $t): ?>
<tr>
<td><?= htmlspecialchars(date('D, d M Y', strtotime($t['trip_date']))) ?></td>
<td><?= htmlspecialchars(date('g:i A', strtotime($t['departure_time']))) ?></td>
<td><?= htmlspecialchars($t['route_name']) ?></td>
<td><?= htmlspecialchars($t['pickup_point']) ?></td>
<td><?= htmlspecialchars($t['dropoff_point']) ?></td>
<td><?= (int)$t['remaining'] ?> / <?= (int)$t['capacity'] ?></td>
<td>
<?php if ($t['remaining'] <= 0): ?>
<button class="btn btn-small" disabled>Fully Booked</button>
<?php elseif (current_user_id()): ?>
<a class="btn btn-small" href="create.php?trip_id=<?= (int)$t['id'] ?>">Book Now</a>
<?php else: ?>
<a class="btn btn-small btn-secondary" href="login.php">Login to Book</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
