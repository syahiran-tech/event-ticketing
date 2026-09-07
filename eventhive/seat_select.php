<?php
require 'config.php';
require 'auth.php';
require_login();

$event_id = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);

$stmt = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmt->bind_param('i', $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    die('Event not found.');
}

if (!$event['has_seating']) {
    header('Location: create.php?event_id=' . $event_id);
    exit;
}

$stmt = $conn->prepare('SELECT * FROM ticket_tiers WHERE event_id = ? ORDER BY price');
$stmt->bind_param('i', $event_id);
$stmt->execute();
$tiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($tiers)) {
    die('This event has no ticket tiers configured yet.');
}

$stmt = $conn->prepare('SELECT id, row_label, seat_number, is_booked FROM seats WHERE event_id = ? ORDER BY row_label, seat_number');
$stmt->bind_param('i', $event_id);
$stmt->execute();
$seats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$rows = [];
foreach ($seats as $seat) {
    $rows[$seat['row_label']][] = $seat;
}

$pageTitle = 'Select Seats - EventHive';
require 'partials/header.php';
?>
<div class="form-card" style="max-width:720px;">
<h1>Select Your Seats</h1>
<p><?= htmlspecialchars($event['event_name']) ?> &middot; <?= htmlspecialchars(date('d M Y', strtotime($event['event_date']))) ?> &middot; <?= htmlspecialchars($event['venue']) ?></p>
<p class="form-hint">Seats are shared across tiers &mdash; choose your tier below, then click any open seat on the map.</p>

<form method="post" action="payment.php" id="seat-form">
<input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">

<?php if (count($tiers) === 1): ?>
<input type="hidden" name="tier_id" id="tier-price-holder" value="<?= (int)$tiers[0]['id'] ?>" data-price="<?= (float)$tiers[0]['price'] ?>">
<p class="form-hint">Tier: <?= htmlspecialchars($tiers[0]['tier_name']) ?> &middot; RM<?= number_format($tiers[0]['price'], 2) ?> / seat</p>
<?php else: ?>
<label>Ticket Tier</label>
<div class="tier-options">
<?php foreach ($tiers as $i => $t): ?>
<?php $tierRemaining = $t['total_tickets'] - $t['tickets_sold']; ?>
<label class="tier-option">
<input type="radio" name="tier_id" class="tier-radio" data-price="<?= (float)$t['price'] ?>" value="<?= (int)$t['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> <?= $tierRemaining <= 0 ? 'disabled' : '' ?>>
<div class="tier-option-head"><span><?= htmlspecialchars($t['tier_name']) ?></span><span>RM<?= number_format($t['price'], 2) ?></span></div>
<?php if ($t['perks']): ?><p class="tier-option-perks"><?= htmlspecialchars($t['perks']) ?></p><?php endif; ?>
<p class="tier-option-remaining"><?= $tierRemaining > 0 ? (int)$tierRemaining . ' left' : 'Sold out' ?></p>
</label>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="seat-legend">
<span class="seat-legend-item"><span class="seat-legend-swatch"></span> Available</span>
<span class="seat-legend-item"><span class="seat-legend-swatch selected"></span> Selected</span>
<span class="seat-legend-item"><span class="seat-legend-swatch booked"></span> Taken</span>
</div>

<div class="seat-map">
<?php foreach ($rows as $rowLabel => $rowSeats): ?>
<div class="seat-row">
<span class="seat-row-label"><?= htmlspecialchars($rowLabel) ?></span>
<?php foreach ($rowSeats as $seat): ?>
<?php if ($seat['is_booked']): ?>
<span class="seat booked" title="Taken"><?= (int)$seat['seat_number'] ?></span>
<?php else: ?>
<label class="seat" data-seat-toggle>
<input type="checkbox" name="seat_ids[]" value="<?= (int)$seat['id'] ?>" style="display:none;">
<?= (int)$seat['seat_number'] ?>
</label>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>
<p>Selected: <span id="selected-count">0</span> seat(s) &middot; Total: RM<span id="selected-total">0.00</span></p>
<button type="submit" id="seat-submit" disabled>Continue to Payment</button>
</form>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<script>
(function () {
    var singleTierHolder = document.getElementById('tier-price-holder');
    var tierRadios = document.querySelectorAll('.tier-radio');
    var seatLabels = document.querySelectorAll('[data-seat-toggle]');
    var countEl = document.getElementById('selected-count');
    var totalEl = document.getElementById('selected-total');
    var submitBtn = document.getElementById('seat-submit');

    function currentPrice() {
        if (singleTierHolder) return parseFloat(singleTierHolder.dataset.price);
        var checked = document.querySelector('.tier-radio:checked');
        return checked ? parseFloat(checked.dataset.price) : 0;
    }

    function update() {
        var checked = document.querySelectorAll('[data-seat-toggle] input:checked').length;
        countEl.textContent = checked;
        totalEl.textContent = (checked * currentPrice()).toFixed(2);
        submitBtn.disabled = checked < 1;
    }

    seatLabels.forEach(function (label) {
        var input = label.querySelector('input');
        label.addEventListener('click', function (e) {
            e.preventDefault();
            input.checked = !input.checked;
            label.classList.toggle('selected', input.checked);
            update();
        });
    });

    tierRadios.forEach(function (radio) {
        radio.addEventListener('change', update);
    });

    update();
})();
</script>
<?php require 'partials/footer.php'; ?>
