<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$trips = $conn->query('SELECT *, (capacity - seats_booked) AS remaining FROM trips ORDER BY trip_date, departure_time');

$pageTitle = 'Manage Trips';
require 'partials/header.php';
?>
<h1>Trips</h1>
<p><a class="btn btn-small" href="trip_create.php">+ Add Trip</a></p>
<?php if ($flashError): ?><p class="alert alert-error"><?= htmlspecialchars($flashError) ?></p><?php endif; ?>
<table>
<tr><th>Photo</th><th>Route</th><th>Date</th><th>Time</th><th>Pickup</th><th>Drop-off</th><th>Booked / Capacity</th><th>Actions</th></tr>
<?php while ($t = $trips->fetch_assoc()): ?>
<tr>
<td><img class="table-thumb" src="<?= htmlspecialchars(entity_image_url($t)) ?>" alt="<?= htmlspecialchars($t['route_name']) ?>" loading="lazy"></td>
<td><?= htmlspecialchars($t['route_name']) ?></td>
<td><?= htmlspecialchars($t['trip_date']) ?></td>
<td><?= htmlspecialchars(date('g:i A', strtotime($t['departure_time']))) ?></td>
<td><?= htmlspecialchars($t['pickup_point']) ?></td>
<td><?= htmlspecialchars($t['dropoff_point']) ?></td>
<td><?= (int)$t['seats_booked'] ?> / <?= (int)$t['capacity'] ?></td>
<td>
<a class="btn btn-secondary btn-small" href="trip_edit.php?id=<?= (int)$t['id'] ?>">Edit</a>
<form action="trip_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this trip? Any existing bookings for it must be removed first.');">
<input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
<?php require 'partials/footer.php'; ?>
