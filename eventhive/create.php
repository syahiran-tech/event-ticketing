<?php
require 'config.php';
require 'auth.php';
require_login();

$error = '';
$selectedEvent = (int)($_GET['event_id'] ?? 0);

if ($selectedEvent) {
    $stmt = $conn->prepare('SELECT has_seating FROM events WHERE id = ?');
    $stmt->bind_param('i', $selectedEvent);
    $stmt->execute();
    $selected = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($selected && $selected['has_seating']) {
        header('Location: seat_select.php?event_id=' . $selectedEvent);
        exit;
    }
}

$events = $conn->query('
    SELECT e.*, COALESCE(SUM(tt.total_tickets - tt.tickets_sold), 0) AS remaining
    FROM events e
    LEFT JOIN ticket_tiers tt ON tt.event_id = e.id
    WHERE e.has_seating = 0
    GROUP BY e.id
    ORDER BY e.event_date
')->fetch_all(MYSQLI_ASSOC);

$tiersByEvent = [];
$tierRows = $conn->query('SELECT * FROM ticket_tiers ORDER BY event_id, price')->fetch_all(MYSQLI_ASSOC);
foreach ($tierRows as $t) {
    $tiersByEvent[$t['event_id']][] = $t;
}

$pageTitle = 'Book Tickets - EventHive';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Book Event Tickets</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" action="payment.php" id="booking-form">
<label>Event
<select name="event_id" id="event-select" required>
<option value="">-- Choose an event --</option>
<?php foreach ($events as $e): ?>
<?php $soldOut = $e['remaining'] <= 0; ?>
<option value="<?= (int)$e['id'] ?>" <?= $e['id'] == $selectedEvent ? 'selected' : '' ?> <?= $soldOut ? 'disabled' : '' ?>><?= htmlspecialchars($e['event_name']) ?> (<?= $soldOut ? 'Sold Out' : (int)$e['remaining'] . ' left' ?>)</option>
<?php endforeach; ?>
</select>
</label>

<div id="tier-container">
<p class="form-hint">Select an event above to see its ticket tiers.</p>
</div>

<label>Quantity <input type="number" name="quantity" min="1" value="1" required></label>
<button type="submit">Continue to Payment</button>
</form>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<script>
(function () {
    var tiersByEvent = <?= str_replace('</', '<\/', json_encode($tiersByEvent)) ?>;
    var select = document.getElementById('event-select');
    var container = document.getElementById('tier-container');

    function esc(s) {
        var div = document.createElement('div');
        div.textContent = String(s == null ? '' : s);
        return div.innerHTML;
    }

    function render() {
        var eventId = select.value;
        var tiers = tiersByEvent[eventId] || [];
        if (!eventId || tiers.length === 0) {
            container.innerHTML = '<p class="form-hint">Select an event above to see its ticket tiers.</p>';
            return;
        }
        if (tiers.length === 1) {
            container.innerHTML = '<input type="hidden" name="tier_id" value="' + tiers[0].id + '">'
                + '<p class="form-hint">Tier: ' + esc(tiers[0].tier_name) + ' &middot; RM' + parseFloat(tiers[0].price).toFixed(2) + ' each</p>';
            return;
        }
        var html = '<label>Ticket Tier</label><div class="tier-options">';
        tiers.forEach(function (t, i) {
            var remaining = t.total_tickets - t.tickets_sold;
            html += '<label class="tier-option">'
                + '<input type="radio" name="tier_id" value="' + t.id + '" ' + (i === 0 ? 'checked' : '') + (remaining <= 0 ? ' disabled' : '') + '>'
                + '<div class="tier-option-head"><span>' + esc(t.tier_name) + '</span><span>RM' + parseFloat(t.price).toFixed(2) + '</span></div>'
                + (t.perks ? '<p class="tier-option-perks">' + esc(t.perks) + '</p>' : '')
                + '<p class="tier-option-remaining">' + (remaining > 0 ? remaining + ' left' : 'Sold out') + '</p>'
                + '</label>';
        });
        html += '</div>';
        container.innerHTML = html;
    }

    select.addEventListener('change', render);
    render();
})();
</script>
<?php require 'partials/footer.php'; ?>
