<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';

$events = $conn->query('
    SELECT e.*, COALESCE(SUM(tt.total_tickets), 0) AS total_tickets,
           COALESCE(SUM(tt.tickets_sold), 0) AS tickets_sold,
           COALESCE(SUM(tt.total_tickets - tt.tickets_sold), 0) AS remaining,
           MIN(tt.price) AS min_price, MAX(tt.price) AS max_price, COUNT(tt.id) AS tier_count
    FROM events e
    LEFT JOIN ticket_tiers tt ON tt.event_id = e.id
    GROUP BY e.id
    ORDER BY e.event_date
')->fetch_all(MYSQLI_ASSOC);

$totalEvents = count($events);
$totalRemaining = array_sum(array_column($events, 'remaining'));

$pageTitle = 'Browse Events - EventHive';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Browse Events</h1>
<p>Every upcoming club and society event, with live ticket availability across Regular and VIP tiers.</p>
</div>

<section>
<div class="card-grid">
<div class="card"><h3><?= (int)$totalEvents ?></h3><p>Upcoming events</p></div>
<div class="card"><h3><?= (int)$totalRemaining ?></h3><p>Tickets still available</p></div>
</div>
</section>

<?php foreach ($events as $e): ?>
<section>
<div class="card" style="max-width:720px;">
<img class="card-thumb" src="<?= htmlspecialchars(entity_image_url($e)) ?>" alt="<?= htmlspecialchars($e['event_name']) ?>" loading="lazy">
<span class="badge badge-accent"><?= htmlspecialchars(date('d M Y', strtotime($e['event_date']))) ?></span>
<span class="badge badge-neutral"><?= htmlspecialchars($e['category']) ?></span>
<h3><?= htmlspecialchars($e['event_name']) ?></h3>
<p>&#128205; <?= htmlspecialchars($e['venue']) ?></p>
<p>
<?php if ((int)$e['tier_count'] > 1): ?>
RM<?= number_format((float)$e['min_price'], 2) ?> - RM<?= number_format((float)$e['max_price'], 2) ?> (Regular / VIP)
<?php else: ?>
RM<?= number_format((float)$e['min_price'], 2) ?> per ticket
<?php endif; ?>
&middot; <?= (int)$e['remaining'] ?> / <?= (int)$e['total_tickets'] ?> tickets remaining
</p>
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
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
