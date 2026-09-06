<?php
require 'config.php';
require 'auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)$_POST['id'];
    $uid = current_user_id();

    $conn->begin_transaction();

    $stmt = $conn->prepare('SELECT trip_id, quantity FROM bookings WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($booking) {
        $stmt = $conn->prepare('DELETE FROM tickets WHERE booking_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM bookings WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $uid);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE trips SET seats_booked = seats_booked - ? WHERE id = ?');
        $stmt->bind_param('ii', $booking['quantity'], $booking['trip_id']);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
}

header('Location: index.php');
exit;
