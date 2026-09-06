<?php
require '../config.php';
require '../auth.php';
require_admin();

$token = trim($_GET['token'] ?? '');
$justMarked = isset($_GET['msg']) && $_GET['msg'] === 'marked';
$ticket = null;
$notFound = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $postToken = trim($_POST['token'] ?? '');
    $stmt = $conn->prepare('UPDATE tickets SET checked_in_at = NOW() WHERE id = ? AND checked_in_at IS NULL');
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $stmt->close();
    header('Location: checkin.php?msg=marked&token=' . urlencode($postToken));
    exit;
}

if ($token !== '') {
    $stmt = $conn->prepare('
        SELECT tk.id, tk.qr_token, tk.checked_in_at, b.id AS booking_id,
               u.name AS user_name, u.email AS user_email,
               t.route_name, t.trip_date, t.departure_time, t.pickup_point, t.dropoff_point
        FROM tickets tk
        JOIN bookings b ON b.id = tk.booking_id
        JOIN users u ON u.id = b.user_id
        JOIN trips t ON t.id = b.trip_id
        WHERE tk.qr_token = ?
    ');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $notFound = !$ticket;
}

$pageTitle = 'Boarding Pass Check-In';
require 'partials/header.php';
?>
<h1>Boarding Pass Check-In</h1>

<?php if ($justMarked): ?><p class="alert alert-success">Marked as boarded successfully.</p><?php endif; ?>

<div class="form-card" style="max-width:480px;">
<form method="get" action="checkin.php">
<label>Boarding Pass Code <input type="text" name="token" id="checkin-token" placeholder="Scan or paste the boarding pass's QR code" autocomplete="off" autofocus></label>
<button type="submit">Look Up</button>
</form>

<?php if ($notFound): ?>
<p class="alert alert-error">No boarding pass found for that code.</p>
<?php elseif ($ticket): ?>
<div class="summary-panel" style="margin-top:20px;">
<div class="summary-panel-row"><span>Passenger</span><span><?= htmlspecialchars($ticket['user_name']) ?></span></div>
<div class="summary-panel-row"><span>Email</span><span><?= htmlspecialchars($ticket['user_email']) ?></span></div>
<div class="summary-panel-row"><span>Route</span><span><?= htmlspecialchars($ticket['route_name']) ?></span></div>
<div class="summary-panel-row"><span>Date / Departure</span><span><?= htmlspecialchars(date('d M Y', strtotime($ticket['trip_date']))) ?> &middot; <?= htmlspecialchars(date('g:i A', strtotime($ticket['departure_time']))) ?></span></div>
<div class="summary-panel-row"><span>Pickup</span><span><?= htmlspecialchars($ticket['pickup_point']) ?></span></div>
</div>

<?php if ($ticket['checked_in_at']): ?>
<p class="alert" style="margin-top:16px;">Already boarded at <?= htmlspecialchars(date('d M Y, H:i', strtotime($ticket['checked_in_at']))) ?>.</p>
<?php else: ?>
<form method="post" action="checkin.php" style="margin-top:16px;">
<input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
<input type="hidden" name="token" value="<?= htmlspecialchars($ticket['qr_token']) ?>">
<button type="submit">Mark Boarded</button>
</form>
<?php endif; ?>
<?php endif; ?>
</div>
<script>
(function () {
    var input = document.getElementById('checkin-token');
    if (input) { input.select(); }
})();
</script>
<?php require 'partials/footer.php'; ?>
