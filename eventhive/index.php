<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = 'e.event_name LIKE ?';
    $params[] = '%' . $search . '%';
    $types .= 's';
}
if ($category !== '') {
    $conditions[] = 'e.category = ?';
    $params[] = $category;
    $types .= 's';
}
$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$sql = "
    SELECT e.*, COALESCE(SUM(tt.total_tickets), 0) AS total_tickets,
           COALESCE(SUM(tt.tickets_sold), 0) AS tickets_sold,
           COALESCE(SUM(tt.total_tickets - tt.tickets_sold), 0) AS remaining,
           MIN(tt.price) AS min_price
    FROM events e
    LEFT JOIN ticket_tiers tt ON tt.event_id = e.id
    $where
    GROUP BY e.id
    ORDER BY e.event_date
";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = $conn->query('SELECT DISTINCT category FROM events ORDER BY category')->fetch_all(MYSQLI_ASSOC);

$myOrders = [];
if ($uid = current_user_id()) {
    $stmt = $conn->prepare('
        SELECT o.id, e.event_name, e.has_seating, tt.tier_name, o.quantity, o.total_price
        FROM orders o
        JOIN events e ON e.id = o.event_id
        JOIN ticket_tiers tt ON tt.id = o.tier_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $myOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pageTitle = 'EventHive - Campus Event Tickets';
require 'partials/header.php';
?>
<section class="hero">
<h1>EventHive</h1>
<p>Find your campus club or society's next event, pick Regular or VIP, and grab your ticket before it sells out.</p>
</section>

<section>
<h2>Upcoming Events</h2>
<form method="get" class="filter-bar" id="event-filter-form">
<label>Search <input type="text" name="q" id="event-search" placeholder="Event name..." value="<?= htmlspecialchars($search) ?>" autocomplete="off"></label>
<input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
<button type="submit">Search</button>
<?php if ($search !== '' || $category !== ''): ?><a class="btn btn-secondary" href="index.php">Clear</a><?php endif; ?>
</form>
<script>
(function () {
    var input = document.getElementById('event-search');
    var form = document.getElementById('event-filter-form');
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

<div class="category-filter">
<a class="category-chip<?= $category === '' ? ' active' : '' ?>" href="index.php?q=<?= urlencode($search) ?>">All</a>
<?php foreach ($categories as $c): ?>
<a class="category-chip<?= $category === $c['category'] ? ' active' : '' ?>" href="index.php?q=<?= urlencode($search) ?>&category=<?= urlencode($c['category']) ?>"><?= htmlspecialchars($c['category']) ?></a>
<?php endforeach; ?>
</div>

<?php if (empty($events)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128269;</div>
<p>No events match your filters.</p>
<a class="btn btn-small btn-secondary" href="index.php">Clear filters</a>
</div>
<?php else: ?>
<div class="card-grid">
<?php foreach ($events as $e): ?>
<div class="card">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($e)) ?>" alt="<?= htmlspecialchars($e['event_name']) ?>" loading="lazy">
<span class="badge badge-accent"><?= htmlspecialchars($e['category']) ?></span>
<h3><?= htmlspecialchars($e['event_name']) ?></h3>
<p><?= htmlspecialchars($e['event_date']) ?> &middot; <?= htmlspecialchars($e['venue']) ?></p>
<p>From RM<?= number_format((float)$e['min_price'], 2) ?> &middot; <?= (int)$e['remaining'] ?> / <?= (int)$e['total_tickets'] ?> left</p>
<?php if ($e['remaining'] <= 0): ?>
<button class="btn" disabled>Sold Out</button>
<?php elseif (current_user_id()): ?>
<?php if ($e['has_seating']): ?>
<a class="btn" href="seat_select.php?event_id=<?= (int)$e['id'] ?>">Select Seats</a>
<?php else: ?>
<a class="btn" href="create.php?event_id=<?= (int)$e['id'] ?>">Book Now</a>
<?php endif; ?>
<?php else: ?>
<a class="btn" href="login.php">Login to Book</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<section>
<h2>My Tickets</h2>
<?php if (!current_user_id()): ?>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to view and manage your reservations.</p>
<?php elseif (empty($myOrders)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#127903;</div>
<p>You haven't booked any tickets yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Event</th><th>Tier</th><th>Qty</th><th>Total (RM)</th><th>Actions</th></tr>
<?php foreach ($myOrders as $o): ?>
<tr>
<td><?= htmlspecialchars($o['event_name']) ?></td>
<td><?= htmlspecialchars($o['tier_name']) ?></td>
<td><?= (int)$o['quantity'] ?></td>
<td><?= number_format($o['total_price'], 2) ?></td>
<td>
<a class="btn btn-secondary btn-small" href="confirmation.php?id=<?= (int)$o['id'] ?>">View Tickets</a>
<?php if (!$o['has_seating']): ?>
<a class="btn btn-secondary btn-small" href="edit.php?id=<?= (int)$o['id'] ?>">Edit</a>
<?php endif; ?>
<form action="delete.php" method="post" style="display:inline" onsubmit="return confirm('Cancel this reservation?');">
<input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
<button type="submit" class="btn-small btn-danger">Cancel</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
<?php require 'partials/footer.php'; ?>
