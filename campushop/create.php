<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$uid = current_user_id();
$error = '';

$trip_id = (int)($_POST['trip_id'] ?? $_GET['trip_id'] ?? 0);
$confirmed = isset($_POST['confirm']);

if ($trip_id < 1 && !$confirmed) {
    // No trip pre-selected yet - fall through to show the picker below.
}

$stmt = $conn->prepare('SELECT * FROM trips WHERE id = ?');
$stmt->bind_param('i', $trip_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($trip_id > 0 && !$trip) {
    die('Trip not found.');
}

$quantity = (int)($_POST['quantity'] ?? 1);

if ($confirmed) {
    if (!$trip) {
        $error = 'Please choose a trip.';
    } elseif ($quantity < 1) {
        $error = 'Please choose at least 1 seat.';
    }

    if (!$error) {
        $conn->begin_transaction();

        $stmt = $conn->prepare('SELECT capacity, seats_booked FROM trips WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $trip_id);
        $stmt->execute();
        $lockedTrip = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$lockedTrip) {
            $error = 'Trip not found.';
            $conn->rollback();
        } elseif ($lockedTrip['seats_booked'] + $quantity > $lockedTrip['capacity']) {
            $error = 'Not enough seats remaining on this trip.';
            $conn->rollback();
        } else {
            $stmt = $conn->prepare('INSERT INTO bookings (user_id, trip_id, quantity) VALUES (?, ?, ?)');
            $stmt->bind_param('iii', $uid, $trip_id, $quantity);
            $stmt->execute();
            $bookingId = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare('UPDATE trips SET seats_booked = seats_booked + ? WHERE id = ?');
            $stmt->bind_param('ii', $quantity, $trip_id);
            $stmt->execute();
            $stmt->close();

            $ticketStmt = $conn->prepare('INSERT INTO tickets (booking_id, qr_token) VALUES (?, ?)');
            for ($i = 0; $i < $quantity; $i++) {
                $token = generate_qr_token();
                $ticketStmt->bind_param('is', $bookingId, $token);
                $ticketStmt->execute();
            }
            $ticketStmt->close();

            $conn->commit();
            header('Location: confirmation.php?id=' . $bookingId);
            exit;
        }
    }
}

$trips = $conn->query('SELECT *, (capacity - seats_booked) AS remaining FROM trips ORDER BY trip_date, departure_time');

$pageTitle = 'Book Seats';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Book Shuttle Seats</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" action="create.php">
<label>Trip
<select name="trip_id" required>
<option value="">-- Select a trip --</option>
<?php while ($t = $trips->fetch_assoc()): ?>
<?php $fullyBooked = $t['remaining'] <= 0; ?>
<option value="<?= (int)$t['id'] ?>" <?= $t['id'] == $trip_id ? 'selected' : '' ?> <?= $fullyBooked ? 'disabled' : '' ?>><?= htmlspecialchars($t['route_name']) ?> - <?= htmlspecialchars(date('d M Y', strtotime($t['trip_date']))) ?> <?= htmlspecialchars(date('g:i A', strtotime($t['departure_time']))) ?> (<?= $fullyBooked ? 'Fully Booked' : (int)$t['remaining'] . ' left' ?>)</option>
<?php endwhile; ?>
</select>
</label>
<label>Number of Seats <input type="number" name="quantity" min="1" value="<?= (int)max(1, $quantity) ?>" required></label>
<input type="hidden" name="confirm" value="1">
<button type="submit">Confirm Booking</button>
</form>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<?php require 'partials/footer.php'; ?>
