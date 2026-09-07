<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

$events = $conn->query('
    SELECT e.*, COALESCE(SUM(tt.total_tickets), 0) AS total_tickets,
           COALESCE(SUM(tt.tickets_sold), 0) AS tickets_sold,
           COALESCE(SUM(tt.total_tickets - tt.tickets_sold), 0) AS remaining
    FROM events e
    LEFT JOIN ticket_tiers tt ON tt.event_id = e.id
    GROUP BY e.id
    ORDER BY e.event_date
')->fetch_all(MYSQLI_ASSOC);

$tiersByEvent = [];
$tierRows = $conn->query('SELECT * FROM ticket_tiers ORDER BY event_id, price')->fetch_all(MYSQLI_ASSOC);
foreach ($tierRows as $t) {
    $tiersByEvent[$t['event_id']][] = $t;
}

$pageTitle = 'Manage Events - EventHive Admin';
require 'partials/header.php';
?>
<h1>Events</h1>
<p><a class="btn btn-small" href="event_create.php">+ Add Event</a></p>
<?php if ($flashError): ?><p class="alert alert-error"><?= htmlspecialchars($flashError) ?></p><?php endif; ?>
<table>
<tr><th>Photo</th><th>Name</th><th>Category</th><th>Date</th><th>Venue</th><th>Tiers</th><th>Type</th><th>Sold / Total</th><th>Actions</th></tr>
<?php foreach ($events as $e): ?>
<tr>
<td><img class="table-thumb" src="<?= htmlspecialchars(entity_image_url($e)) ?>" alt="<?= htmlspecialchars($e['event_name']) ?>" loading="lazy"></td>
<td><?= htmlspecialchars($e['event_name']) ?></td>
<td><span class="badge badge-neutral"><?= htmlspecialchars($e['category']) ?></span></td>
<td><?= htmlspecialchars($e['event_date']) ?></td>
<td><?= htmlspecialchars($e['venue']) ?></td>
<td>
<?php foreach (($tiersByEvent[$e['id']] ?? []) as $t): ?>
<div class="stat-label"><?= htmlspecialchars($t['tier_name']) ?>: RM<?= number_format($t['price'], 2) ?> (<?= (int)$t['tickets_sold'] ?>/<?= (int)$t['total_tickets'] ?>)</div>
<?php endforeach; ?>
</td>
<td><?php if ($e['has_seating']): ?><span class="badge badge-neutral">Seated (<?= (int)$e['seat_rows'] ?>&times;<?= (int)$e['seats_per_row'] ?>)</span><?php else: ?><span class="badge badge-neutral">General</span><?php endif; ?></td>
<td><?= (int)$e['tickets_sold'] ?> / <?= (int)$e['total_tickets'] ?></td>
<td>
<a class="btn btn-secondary btn-small" href="event_edit.php?id=<?= (int)$e['id'] ?>">Edit</a>
<form action="event_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this event? Any existing reservations for it must be removed first.');">
<input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php require 'partials/footer.php'; ?>
