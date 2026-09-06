<?php
require 'config.php';
require 'auth.php';
require_login();

$id  = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare('
    SELECT b.id, b.quantity, b.created_at, t.route_name, t.trip_date, t.departure_time, t.pickup_point, t.dropoff_point
    FROM bookings b
    JOIN trips t ON t.id = b.trip_id
    WHERE b.id = ? AND b.user_id = ?
');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die('Booking not found.');
}

$reference = 'CH-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);

$stmt = $conn->prepare('
    SELECT t.id, t.qr_token, t.checked_in_at
    FROM tickets t
    WHERE t.booking_id = ?
    ORDER BY t.id
');
$stmt->bind_param('i', $id);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Booking Confirmed';
require 'partials/header.php';
?>
<div class="form-card confirmation-card">
<div class="confirmation-icon">&#10003;</div>
<h1>Booking Confirmed</h1>
<p style="color:var(--text-muted);">Reference <strong><?= htmlspecialchars($reference) ?></strong></p>

<table class="confirmation-table">
<tr><th>Route</th><td><?= htmlspecialchars($booking['route_name']) ?></td></tr>
<tr><th>Date</th><td><?= htmlspecialchars(date('d M Y', strtotime($booking['trip_date']))) ?></td></tr>
<tr><th>Departure</th><td><?= htmlspecialchars(date('g:i A', strtotime($booking['departure_time']))) ?></td></tr>
<tr><th>Pickup</th><td><?= htmlspecialchars($booking['pickup_point']) ?></td></tr>
<tr><th>Drop-off</th><td><?= htmlspecialchars($booking['dropoff_point']) ?></td></tr>
<tr><th>Seats Booked</th><td><?= (int)$booking['quantity'] ?></td></tr>
</table>

<div class="card-actions confirmation-actions">
<a class="btn" href="index.php">View My Bookings</a>
<a class="btn btn-secondary" href="trips.php">Browse More Trips</a>
</div>
</div>

<div class="form-card" style="max-width:460px; margin-top:20px;">
<h2>Your Boarding Passes</h2>
<p style="color:var(--text-muted); font-size:0.9rem;">Show a boarding pass's QR code (or its reference code) to the driver/marshal to board.</p>
<?php foreach ($tickets as $i => $t): ?>
<div class="ticket-card">
<div class="qr-code" data-token="<?= htmlspecialchars($t['qr_token']) ?>"></div>
<div class="ticket-card-info">
<strong>Seat <?= $i + 1 ?> of <?= count($tickets) ?></strong>
<p class="ticket-token"><?= htmlspecialchars($t['qr_token']) ?></p>
</div>
</div>
<?php endforeach; ?>
</div>

<script src="assets/js/qrcode.js"></script>
<script>
(function () {
    document.querySelectorAll('.qr-code[data-token]').forEach(function (el) {
        var qr = qrcode(0, 'M');
        qr.addData(el.dataset.token);
        qr.make();
        el.innerHTML = qr.createSvgTag(4, 4);
    });
})();
</script>
<?php require 'partials/footer.php'; ?>
