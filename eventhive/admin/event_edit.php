<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$uploadDir = __DIR__ . '/../uploads';
$categories = ['Music', 'Sports', 'Workshops', 'Talks', 'Social'];
$maxTiers = 3;

$stmt = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    die('Event not found.');
}

$stmt = $conn->prepare('SELECT * FROM ticket_tiers WHERE event_id = ? ORDER BY id');
$stmt->bind_param('i', $id);
$stmt->execute();
$existingTiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $venue      = trim($_POST['venue']);
    $category   = trim($_POST['category'] ?? 'Social');

    $tierIds    = $_POST['tier_id'] ?? [];
    $tierNames  = $_POST['tier_name'] ?? [];
    $tierPrices = $_POST['tier_price'] ?? [];
    $tierTotals = $_POST['tier_total'] ?? [];
    $tierPerks  = $_POST['tier_perks'] ?? [];

    $soldByTierId = array_column($existingTiers, 'tickets_sold', 'id');

    $tiers = [];
    for ($i = 0; $i < count($tierNames); $i++) {
        $name = trim($tierNames[$i] ?? '');
        if ($name === '') {
            continue;
        }
        $tierId = (int)($tierIds[$i] ?? 0);
        $tiers[] = [
            'id'    => $tierId,
            'name'  => $name,
            'price' => (float)($tierPrices[$i] ?? 0),
            'total' => (int)($tierTotals[$i] ?? 0),
            'perks' => trim($tierPerks[$i] ?? ''),
            'tickets_sold' => (int)($soldByTierId[$tierId] ?? 0),
        ];
    }

    [$newImageUrl, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'event');
    $image_url = $newImageUrl ?? $event['image_url'];

    // A tier that already has sales can't have its inventory cut below what's sold,
    // and a tier that was removed from the form entirely can't have any sales either -
    // otherwise its already-booked tickets would point at nothing.
    $keptTierIds = array_filter(array_column($tiers, 'id'));
    $removedTierIds = array_diff(array_column($existingTiers, 'id'), $keptTierIds);

    $tierError = null;
    foreach ($tiers as $t) {
        if ($t['price'] < 0 || $t['total'] < 1) {
            $tierError = 'Every tier needs a price of 0 or more and at least 1 ticket.';
            break;
        }
        if ($t['id'] && isset($soldByTierId[$t['id']]) && $t['total'] < $soldByTierId[$t['id']]) {
            $tierError = 'Tier "' . $t['name'] . '" cannot have fewer total tickets than already sold (' . (int)$soldByTierId[$t['id']] . ').';
            break;
        }
    }
    foreach ($removedTierIds as $removedId) {
        if ((int)($soldByTierId[$removedId] ?? 0) > 0) {
            $tierError = 'Cannot remove a tier that already has tickets sold. Keep the tier row instead.';
            break;
        }
    }

    $tierTotalSum = array_sum(array_column($tiers, 'total'));

    if ($event_name === '' || $event_date === '' || $venue === '') {
        $error = 'All event fields are required.';
    } elseif (empty($tiers)) {
        $error = 'At least one ticket tier is required.';
    } elseif (count($tiers) > $maxTiers) {
        $error = "A maximum of $maxTiers tiers is allowed.";
    } elseif ($tierError) {
        $error = $tierError;
    } elseif ($event['has_seating'] && $tierTotalSum > (int)$event['seat_rows'] * (int)$event['seats_per_row']) {
        $error = 'The tiers\' combined ticket count (' . $tierTotalSum . ') cannot exceed the seat map capacity (' . ((int)$event['seat_rows'] * (int)$event['seats_per_row']) . ').';
    } elseif ($event_date < date('Y-m-d')) {
        $error = 'Event date cannot be in the past.';
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        if ($newImageUrl) {
            delete_image_file($event['image_url'], $uploadDir);
        }

        $stmt = $conn->prepare('UPDATE events SET event_name=?, event_date=?, venue=?, category=?, image_url=? WHERE id=?');
        $stmt->bind_param('sssssi', $event_name, $event_date, $venue, $category, $image_url, $id);
        $stmt->execute();
        $stmt->close();

        foreach ($removedTierIds as $removedId) {
            $stmt = $conn->prepare('DELETE FROM ticket_tiers WHERE id = ? AND event_id = ?');
            $stmt->bind_param('ii', $removedId, $id);
            $stmt->execute();
            $stmt->close();
        }

        $updateStmt = $conn->prepare('UPDATE ticket_tiers SET tier_name=?, price=?, total_tickets=?, perks=? WHERE id = ? AND event_id = ?');
        $insertStmt = $conn->prepare('INSERT INTO ticket_tiers (event_id, tier_name, price, total_tickets, perks) VALUES (?, ?, ?, ?, ?)');
        foreach ($tiers as $t) {
            $perksValue = $t['perks'] !== '' ? $t['perks'] : null;
            if ($t['id']) {
                $updateStmt->bind_param('sdisii', $t['name'], $t['price'], $t['total'], $perksValue, $t['id'], $id);
                $updateStmt->execute();
            } else {
                $insertStmt->bind_param('isdis', $id, $t['name'], $t['price'], $t['total'], $perksValue);
                $insertStmt->execute();
            }
        }
        $updateStmt->close();
        $insertStmt->close();

        header('Location: events.php');
        exit;
    }

    // Re-render with the submitted values so the admin doesn't lose their edits.
    $event = array_merge($event, compact('event_name', 'event_date', 'venue', 'category', 'image_url'));
    $existingTiers = $tiers ?: $existingTiers;
}

$pageTitle = 'Edit Event - EventHive Admin';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Event</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= (int)$event['id'] ?>">
<label>Event Name <input type="text" name="event_name" value="<?= htmlspecialchars($event['event_name']) ?>" required></label>
<label>Date <input type="date" name="event_date" value="<?= htmlspecialchars($event['event_date']) ?>" min="<?= date('Y-m-d') ?>" required></label>
<label>Venue <input type="text" name="venue" value="<?= htmlspecialchars($event['venue']) ?>" required></label>
<label>Category
<select name="category">
<?php foreach ($categories as $c): ?>
<option value="<?= htmlspecialchars($c) ?>" <?= ($event['category'] ?? 'Social') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
<?php endforeach; ?>
</select>
</label>

<label>Ticket Tiers</label>
<p class="form-hint">At least 1 tier, up to <?= $maxTiers ?>. A tier with tickets already sold can be edited (name/price/perks/increase total) but not removed or cut below its sold count.</p>
<div id="tier-rows">
<?php foreach ($existingTiers as $t): ?>
<div class="tier-row">
<input type="hidden" name="tier_id[]" value="<?= (int)($t['id'] ?? 0) ?>">
<label>Tier Name <input type="text" name="tier_name[]" value="<?= htmlspecialchars($t['tier_name'] ?? $t['name'] ?? '') ?>" placeholder="e.g. Regular"></label>
<label>Price (RM) <input type="number" name="tier_price[]" step="0.01" min="0" value="<?= htmlspecialchars($t['price'] ?? '') ?>"></label>
<label>Total Tickets <input type="number" name="tier_total[]" min="1" value="<?= htmlspecialchars($t['total_tickets'] ?? $t['total'] ?? '') ?>"></label>
<label>Perks (optional) <input type="text" name="tier_perks[]" value="<?= htmlspecialchars($t['perks'] ?? '') ?>" placeholder="e.g. Front row seating + free drink"></label>
<?php $sold = (int)($t['tickets_sold'] ?? 0); ?>
<?php if ($sold > 0): ?>
<span class="stat-label"><?= $sold ?> sold &mdash; cannot remove</span>
<?php else: ?>
<button type="button" class="btn-small btn-danger remove-tier-btn">Remove</button>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<p><button type="button" id="add-tier-btn" class="btn btn-secondary btn-small">+ Add Tier</button></p>

<?php if ($event['has_seating']): ?>
<label>Seat Map <input type="text" value="<?= (int)$event['seat_rows'] ?> rows &times; <?= (int)$event['seats_per_row'] ?> seats = <?= (int)$event['seat_rows'] * (int)$event['seats_per_row'] ?> total capacity" disabled></label>
<p class="form-hint">This event uses assigned seating (tier-agnostic seat map). The seat map cannot be changed after creation; your tiers' combined ticket count must not exceed its capacity.</p>
<?php endif; ?>

<label>Current Photo
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(entity_image_url($event)) ?>" alt="<?= htmlspecialchars($event['event_name']) ?>">
</label>
<label>Replace Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Update Event</button>
</form>
<template id="tier-row-template">
<div class="tier-row">
<input type="hidden" name="tier_id[]" value="0">
<label>Tier Name <input type="text" name="tier_name[]" placeholder="e.g. VIP"></label>
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

    container.querySelectorAll('.remove-tier-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('.tier-row').remove();
            updateAddButton();
        });
    });

    addBtn.addEventListener('click', function () {
        if (tierCount() >= maxTiers) return;
        var node = template.content.cloneNode(true);
        var row = node.querySelector('.tier-row');
        row.querySelector('.remove-tier-btn').addEventListener('click', function () {
            row.remove();
            updateAddButton();
        });
        container.appendChild(row);
        updateAddButton();
    });

    updateAddButton();
})();
</script>
<p><a class="btn btn-secondary btn-small" href="events.php">Back to events</a></p>
</div>
<?php require 'partials/footer.php'; ?>
