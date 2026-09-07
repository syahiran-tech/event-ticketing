<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $uploadDir = __DIR__ . '/../uploads';

    $stmt = $conn->prepare('SELECT image_url FROM events WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Only removes seats/tiers that have no tickets/orders referencing them.
    // If any reservation (and therefore tickets/orders) exist for this event,
    // the ticket_tiers DELETE below silently does nothing (still referenced by
    // orders) and the events DELETE fails with the usual FK message.
    $stmt = $conn->prepare('DELETE FROM seats WHERE event_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM ticket_tiers WHERE event_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        if ($event) {
            delete_image_file($event['image_url'], $uploadDir);
        }
    } else {
        $_SESSION['flash_error'] = 'Cannot delete this event: it still has reservations referencing it.';
    }
    $stmt->close();
}

header('Location: events.php');
exit;
