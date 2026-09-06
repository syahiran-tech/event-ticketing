<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$uid = current_user_id();
$error = '';

$stmt = $conn->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die('Booking not found or you do not have permission to edit it.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_quantity = (int)$_POST['quantity'];

    $conn->begin_transaction();

    $stmt = $conn->prepare('SELECT capacity, seats_booked FROM trips WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $booking['trip_id']);
    $stmt->execute();
    $trip = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $available = $trip['capacity'] - $trip['seats_booked'] + $booking['quantity'];

    if ($new_quantity < 1 || $new_quantity > $available) {
        $error = 'Invalid quantity. Only ' . $available . ' seats available.';
        $conn->rollback();
    } else {
        $diff = $new_quantity - $booking['quantity'];

        $stmt = $conn->prepare('UPDATE bookings SET quantity=? WHERE id=? AND user_id=?');
        $stmt->bind_param('iii', $new_quantity, $id, $uid);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE trips SET seats_booked = seats_booked + ? WHERE id = ?');
        $stmt->bind_param('ii', $diff, $booking['trip_id']);
        $stmt->execute();
        $stmt->close();

        // Keep the tickets/boarding-pass rows in step with the new seat count:
        // add new ones if the quantity grew, remove extras (never a checked-in
        // one) if it shrank.
        if ($diff > 0) {
            $ticketStmt = $conn->prepare('INSERT INTO tickets (booking_id, qr_token) VALUES (?, ?)');
            for ($i = 0; $i < $diff; $i++) {
                $token = generate_qr_token();
                $ticketStmt->bind_param('is', $id, $token);
                $ticketStmt->execute();
            }
            $ticketStmt->close();
        } elseif ($diff < 0) {
            $toRemove = -$diff;
            $stmt = $conn->prepare('SELECT id FROM tickets WHERE booking_id = ? AND checked_in_at IS NULL ORDER BY id DESC LIMIT ?');
            $stmt->bind_param('ii', $id, $toRemove);
            $stmt->execute();
            $removableIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');
            $stmt->close();

            if ($removableIds) {
                $placeholders = implode(',', array_fill(0, count($removableIds), '?'));
                $stmt = $conn->prepare("DELETE FROM tickets WHERE id IN ($placeholders)");
                $stmt->bind_param(str_repeat('i', count($removableIds)), ...$removableIds);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->commit();
        header('Location: index.php');
        exit;
    }

    $stmt = $conn->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$pageTitle = 'Edit Booking';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Shuttle Booking</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="id" value="<?= (int)$booking['id'] ?>">
<label>Number of Seats <input type="number" name="quantity" min="1" value="<?= (int)$booking['quantity'] ?>" required></label>
<button type="submit">Update Booking</button>
</form>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<?php require 'partials/footer.php'; ?>
