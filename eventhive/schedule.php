<?php
require 'config.php';
require 'auth.php';

$events = $conn->query('
    SELECT e.*, COALESCE(SUM(tt.total_tickets), 0) AS total_tickets,
           COALESCE(SUM(tt.tickets_sold), 0) AS tickets_sold,
           COALESCE(SUM(tt.total_tickets - tt.tickets_sold), 0) AS remaining,
           MIN(tt.price) AS min_price
    FROM events e
    LEFT JOIN ticket_tiers tt ON tt.event_id = e.id
    GROUP BY e.id
    ORDER BY e.event_date
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Event Schedule - EventHive';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Event Schedule</h1>
<p>Full timetable of upcoming club and society events, earliest first.</p>
</div>

<?php if (empty($events)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128197;</div>
<p>No events scheduled yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Date</th><th>Event</th><th>Category</th><th>Venue</th><th>From (RM)</th><th>Seats Remaining</th><th>Actions</th></tr>
<?php foreach ($events as $e): ?>
<tr>
<td><?= htmlspecialchars(date('D, d M Y', strtotime($e['event_date']))) ?></td>
<td><?= htmlspecialchars($e['event_name']) ?></td>
<td><span class="badge badge-neutral"><?= htmlspecialchars($e['category']) ?></span></td>
<td><?= htmlspecialchars($e['venue']) ?></td>
<td><?= number_format((float)$e['min_price'], 2) ?></td>
<td><?= (int)$e['remaining'] ?> / <?= (int)$e['total_tickets'] ?></td>
<td>
<?php if (current_user_id()): ?>
<a class="btn btn-small" href="create.php?event_id=<?= (int)$e['id'] ?>">Book Now</a>
<?php else: ?>
<a class="btn btn-small btn-secondary" href="login.php">Login to Book</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
