<?php
require '../config.php';
require '../auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $myId = (int)current_user_id();

    if ($id === $myId) {
        $_SESSION['flash_error'] = 'You cannot delete your own account.';
    } else {
        $conn->begin_transaction();

        // Restore seat capacity for every booking this user made before deleting them.
        $stmt = $conn->prepare('SELECT trip_id, quantity FROM bookings WHERE user_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($bookings as $booking) {
            $stmt = $conn->prepare('UPDATE trips SET seats_booked = seats_booked - ? WHERE id = ?');
            $stmt->bind_param('ii', $booking['quantity'], $booking['trip_id']);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM tickets WHERE booking_id IN (SELECT id FROM bookings WHERE user_id = ?)');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM bookings WHERE user_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM testimonials WHERE user_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    }
}

header('Location: users.php');
exit;
