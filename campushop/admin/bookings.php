<?php
require '../config.php';
require '../auth.php';
require_admin();

$bookings = $conn->query('
    SELECT b.id, t.route_name, t.trip_date, t.departure_time, b.quantity, u.name AS user_name, u.email AS user_email,
           COUNT(tk.id) AS checked_in
    FROM bookings b
    JOIN trips t ON t.id = b.trip_id
    JOIN users u ON u.id = b.user_id
    LEFT JOIN tickets tk ON tk.booking_id = b.id AND tk.checked_in_at IS NOT NULL
    GROUP BY b.id, t.route_name, t.trip_date, t.departure_time, b.quantity, u.name, u.email
    ORDER BY b.created_at DESC
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'All Bookings';
require 'partials/header.php';
?>
<h1>All Bookings</h1>
<?php if (empty($bookings)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128652;</div>
<p>No shuttle bookings yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Route</th><th>Date</th><th>Time</th><th>Seats</th><th>Booked By</th><th>Email</th><th>Boarded</th><th>Actions</th></tr>
<?php foreach ($bookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['route_name']) ?></td>
<td><?= htmlspecialchars(date('d M Y', strtotime($b['trip_date']))) ?></td>
<td><?= htmlspecialchars(date('g:i A', strtotime($b['departure_time']))) ?></td>
<td><?= (int)$b['quantity'] ?></td>
<td><?= htmlspecialchars($b['user_name']) ?></td>
<td><?= htmlspecialchars($b['user_email']) ?></td>
<td><?php if ((int)$b['checked_in'] === (int)$b['quantity']): ?><span class="badge badge-good"><?= (int)$b['checked_in'] ?> / <?= (int)$b['quantity'] ?></span><?php else: ?><span class="badge badge-neutral"><?= (int)$b['checked_in'] ?> / <?= (int)$b['quantity'] ?></span><?php endif; ?></td>
<td>
<form action="booking_cancel.php" method="post" style="display:inline" onsubmit="return confirm('Cancel this booking?');">
<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<button type="submit" class="btn-small btn-danger">Cancel</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
