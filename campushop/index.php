<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $conn->prepare('SELECT *, (capacity - seats_booked) AS remaining FROM trips WHERE route_name LIKE ? ORDER BY trip_date, departure_time');
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param('s', $likeSearch);
    $stmt->execute();
    $trips = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $trips = $conn->query('SELECT *, (capacity - seats_booked) AS remaining FROM trips ORDER BY trip_date, departure_time')->fetch_all(MYSQLI_ASSOC);
}

$myBookings = [];
if ($uid = current_user_id()) {
    $stmt = $conn->prepare('
        SELECT b.id, t.route_name, t.trip_date, t.departure_time, b.quantity
        FROM bookings b
        JOIN trips t ON t.id = b.trip_id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $myBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pageTitle = 'CampusHop';
require 'partials/header.php';
?>
<section class="hero">
<h1>CampusHop Shuttle Booking</h1>
<p>Reserve your free seat on the next campus shuttle trip before it's fully booked.</p>
</section>

<section>
<h2>Upcoming Trips</h2>
<form method="get" class="filter-bar" id="trip-filter-form">
<label>Search <input type="text" name="q" id="trip-search" placeholder="Route name..." value="<?= htmlspecialchars($search) ?>" autocomplete="off"></label>
<button type="submit">Search</button>
<?php if ($search !== ''): ?><a class="btn btn-secondary" href="index.php">Clear</a><?php endif; ?>
</form>
<script>
(function () {
    var input = document.getElementById('trip-search');
    var form = document.getElementById('trip-filter-form');
    if (!input || !form) return;
    var timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            form.submit();
        }, 500);
    });
})();
</script>

<?php if (empty($trips)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128269;</div>
<p>No trips match your search.</p>
<a class="btn btn-small btn-secondary" href="index.php">Clear filters</a>
</div>
<?php else: ?>
<div class="card-grid">
<?php foreach ($trips as $t): ?>
<div class="card">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($t)) ?>" alt="<?= htmlspecialchars($t['route_name']) ?>" loading="lazy">
<h3><?= htmlspecialchars($t['route_name']) ?></h3>
<p><?= htmlspecialchars(date('d M Y', strtotime($t['trip_date']))) ?> &middot; <?= htmlspecialchars(date('g:i A', strtotime($t['departure_time']))) ?></p>
<p>&#128205; <?= htmlspecialchars($t['pickup_point']) ?> &rarr; <?= htmlspecialchars($t['dropoff_point']) ?></p>
<p><?= (int)$t['remaining'] ?> / <?= (int)$t['capacity'] ?> seats left</p>
<?php if ($t['remaining'] <= 0): ?>
<button class="btn" disabled>Fully Booked</button>
<?php elseif (current_user_id()): ?>
<a class="btn" href="create.php?trip_id=<?= (int)$t['id'] ?>">Book Seats</a>
<?php else: ?>
<a class="btn" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<section>
<h2>My Bookings</h2>
<?php if (!current_user_id()): ?>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to view and manage your shuttle bookings.</p>
<?php elseif (empty($myBookings)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128652;</div>
<p>You haven't booked any shuttle trips yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Route</th><th>Date</th><th>Time</th><th>Seats</th><th>Actions</th></tr>
<?php foreach ($myBookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['route_name']) ?></td>
<td><?= htmlspecialchars(date('d M Y', strtotime($b['trip_date']))) ?></td>
<td><?= htmlspecialchars(date('g:i A', strtotime($b['departure_time']))) ?></td>
<td><?= (int)$b['quantity'] ?></td>
<td>
<a class="btn btn-secondary btn-small" href="confirmation.php?id=<?= (int)$b['id'] ?>">View Boarding Pass</a>
<a class="btn btn-secondary btn-small" href="edit.php?id=<?= (int)$b['id'] ?>">Edit</a>
<form action="delete.php" method="post" style="display:inline" onsubmit="return confirm('Cancel this booking?');">
<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<button type="submit" class="btn-small btn-danger">Cancel</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
<?php require 'partials/footer.php'; ?>
