<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$error = '';
$uploadDir = __DIR__ . '/../uploads';
$categories = ['Music', 'Sports', 'Workshops', 'Talks', 'Social'];
$maxTiers = 3;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name    = trim($_POST['event_name']);
    $event_date    = $_POST['event_date'];
    $venue         = trim($_POST['venue']);
    $category      = trim($_POST['category'] ?? 'Social');
    $has_seating   = isset($_POST['has_seating']);
    $seat_rows     = (int)($_POST['seat_rows'] ?? 0);
    $seats_per_row = (int)($_POST['seats_per_row'] ?? 0);

    $tierNames  = $_POST['tier_name'] ?? [];
    $tierPrices = $_POST['tier_price'] ?? [];
    $tierTotals = $_POST['tier_total'] ?? [];
    $tierPerks  = $_POST['tier_perks'] ?? [];

    $tiers = [];
    for ($i = 0; $i < count($tierNames); $i++) {
        $name = trim($tierNames[$i] ?? '');
        if ($name === '') {
            continue;
        }
        $tiers[] = [
            'name'  => $name,
            'price' => (float)($tierPrices[$i] ?? 0),
            'total' => (int)($tierTotals[$i] ?? 0),
            'perks' => trim($tierPerks[$i] ?? ''),
        ];
    }

    [$image_url, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'event');

    $tierTotalSum = array_sum(array_column($tiers, 'total'));

    if ($event_name === '' || $event_date === '' || $venue === '') {
        $error = 'All event fields are required.';
    } elseif (empty($tiers)) {
        $error = 'At least one ticket tier is required.';
    } elseif (count($tiers) > $maxTiers) {
        $error = "A maximum of $maxTiers tiers is allowed.";
    } elseif (array_filter($tiers, fn($t) => $t['price'] < 0 || $t['total'] < 1)) {
        $error = 'Every tier needs a price of 0 or more and at least 1 ticket.';
    } elseif ($has_seating && ($seat_rows < 1 || $seats_per_row < 1)) {
        $error = 'Rows and seats per row must each be at least 1.';
    } elseif ($has_seating && $tierTotalSum > $seat_rows * $seats_per_row) {
        $error = 'The tiers\' combined ticket count (' . $tierTotalSum . ') cannot exceed the seat map capacity (' . ($seat_rows * $seats_per_row) . ').';
    } elseif ($event_date < date('Y-m-d')) {
        $error = 'Event date cannot be in the past.';
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        $stmt = $conn->prepare('INSERT INTO events (event_name, event_date, venue, category, image_url, has_seating, seat_rows, seats_per_row) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssiii', $event_name, $event_date, $venue, $category, $image_url, $has_seating, $seat_rows, $seats_per_row);
        $stmt->execute();
        $eventId = $stmt->insert_id;
        $stmt->close();

        $tierStmt = $conn->prepare('INSERT INTO ticket_tiers (event_id, tier_name, price, total_tickets, perks) VALUES (?, ?, ?, ?, ?)');
        foreach ($tiers as $t) {
            $perksValue = $t['perks'] !== '' ? $t['perks'] : null;
            $tierStmt->bind_param('isdis', $eventId, $t['name'], $t['price'], $t['total'], $perksValue);
            $tierStmt->execute();
        }
        $tierStmt->close();

        if ($has_seating) {
            $stmt = $conn->prepare('INSERT INTO seats (event_id, row_label, seat_number) VALUES (?, ?, ?)');
            for ($r = 0; $r < $seat_rows; $r++) {
                $rowLabel = seat_row_label($r);
                for ($n = 1; $n <= $seats_per_row; $n++) {
                    $stmt->bind_param('isi', $eventId, $rowLabel, $n);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        header('Location: events.php');
        exit;
    }
}

$pageTitle = 'Add Event - EventHive Admin';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Add Event</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>Event Name <input type="text" name="event_name" value="<?= htmlspecialchars($_POST['event_name'] ?? '') ?>" required></label>
<label>Date <input type="date" name="event_date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>" required></label>
<label>Venue <input type="text" name="venue" value="<?= htmlspecialchars($_POST['venue'] ?? '') ?>" required></label>
<label>Category
<select name="category">
<?php foreach ($categories as $c): ?>
<option value="<?= htmlspecialchars($c) ?>" <?= ($_POST['category'] ?? 'Social') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
<?php endforeach; ?>
</select>
</label>

<label>Ticket Tiers</label>
<p class="form-hint">At least 1 tier, up to <?= $maxTiers ?>. Most events just need one "Regular" tier; add a second row (e.g. "VIP") for a higher-priced tier with its own inventory and perks.</p>
<div id="tier-rows"></div>
<p><button type="button" id="add-tier-btn" class="btn btn-secondary btn-small">+ Add Tier</button></p>

<label class="checkbox-label"><input type="checkbox" name="has_seating" id="has_seating"> Allow buyers to pick a specific seat</label>

<div id="seating-fields" style="display:none;">
<div class="card-row">
<label>Rows <input type="number" name="seat_rows" id="seat_rows" min="1" max="26"></label>
<label>Seats per Row <input type="number" name="seats_per_row" id="seats_per_row" min="1"></label>
</div>
<p class="form-hint">Total seat map capacity: <span id="seat-total">0</span> seats. Rows are lettered A, B, C... Seats are shared across tiers (not tier-specific) and the seat map cannot be changed after the event is created. Your tiers' combined ticket count must not exceed this capacity.</p>
</div>

<label>Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Add Event</button>
</form>
<template id="tier-row-template">
<div class="tier-row">
<label>Tier Name <input type="text" name="tier_name[]" placeholder="e.g. Regular"></label>
<label>Price (RM) <input type="number" name="tier_price[]" step="0.01" min="0"></label>
<label>Total Tickets <input type="number" name="tier_total[]" min="1"></label>
<label>Perks (optional) <input type="text" name="tier_perks[]" placeholder="e.g. Front row seating + free drink"></label>
<button type="button" class="btn-small btn-danger remove-tier-btn">Remove</button>
</div>
</template>
<script>
(function () {
    var container = document.getElementById('tier-rows');
    var template = document.getElementById('tier-row-template');
    var addBtn = document.getElementById('add-tier-btn');
    var maxTiers = <?= $maxTiers ?>;

    function tierCount() { return container.querySelectorAll('.tier-row').length; }

    function updateAddButton() {
        addBtn.disabled = tierCount() >= maxTiers;
    }

    function addRow(values) {
        if (tierCount() >= maxTiers) return;
        var node = template.content.cloneNode(true);
        var row = node.querySelector('.tier-row');
        if (values) {
            row.querySelector('[name="tier_name[]"]').value = values.name || '';
            row.querySelector('[name="tier_price[]"]').value = values.price || '';
            row.querySelector('[name="tier_total[]"]').value = values.total || '';
            row.querySelector('[name="tier_perks[]"]').value = values.perks || '';
        }
        row.querySelector('.remove-tier-btn').addEventListener('click', function () {
            row.remove();
            updateAddButton();
        });
        container.appendChild(row);
        updateAddButton();
    }

    addBtn.addEventListener('click', function () { addRow(null); });

    // Seed with one Regular tier row by default.
    addRow({ name: 'Regular' });

    var seatingCheckbox = document.getElementById('has_seating');
    var seatingFields = document.getElementById('seating-fields');
    var rowsInput = document.getElementById('seat_rows');
    var perRowInput = document.getElementById('seats_per_row');
    var seatTotal = document.getElementById('seat-total');

    function updateSeating() {
        var seated = seatingCheckbox.checked;
        seatingFields.style.display = seated ? '' : 'none';
        rowsInput.required = seated;
        perRowInput.required = seated;
        seatTotal.textContent = (parseInt(rowsInput.value, 10) || 0) * (parseInt(perRowInput.value, 10) || 0);
    }

    seatingCheckbox.addEventListener('change', updateSeating);
    rowsInput.addEventListener('input', updateSeating);
    perRowInput.addEventListener('input', updateSeating);
    updateSeating();
})();
</script>
<p><a class="btn btn-secondary btn-small" href="events.php">Back to events</a></p>
</div>
<?php require 'partials/footer.php'; ?>
