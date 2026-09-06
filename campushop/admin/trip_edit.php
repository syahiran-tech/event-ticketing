<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$uploadDir = __DIR__ . '/../uploads';

$stmt = $conn->prepare('SELECT * FROM trips WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trip) {
    die('Trip not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_name     = trim($_POST['route_name']);
    $trip_date      = $_POST['trip_date'];
    $departure_time = $_POST['departure_time'];
    $pickup_point   = trim($_POST['pickup_point']);
    $dropoff_point  = trim($_POST['dropoff_point']);
    $capacity       = (int)$_POST['capacity'];

    [$newImageUrl, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'trip');
    $image_url = $newImageUrl ?? $trip['image_url'];

    if ($route_name === '' || $trip_date === '' || $departure_time === '' || $pickup_point === '' || $dropoff_point === '' || $capacity < $trip['seats_booked']) {
        $error = 'All fields are required and capacity cannot be less than seats already booked (' . (int)$trip['seats_booked'] . ').';
        $trip = array_merge($trip, compact('route_name', 'trip_date', 'departure_time', 'pickup_point', 'dropoff_point', 'capacity', 'image_url'));
    } elseif ($trip_date < date('Y-m-d')) {
        $error = 'Trip date cannot be in the past.';
        $trip = array_merge($trip, compact('route_name', 'trip_date', 'departure_time', 'pickup_point', 'dropoff_point', 'capacity', 'image_url'));
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        if ($newImageUrl) {
            delete_image_file($trip['image_url'], $uploadDir);
        }

        $stmt = $conn->prepare('UPDATE trips SET route_name=?, trip_date=?, departure_time=?, pickup_point=?, dropoff_point=?, capacity=?, image_url=? WHERE id=?');
        $stmt->bind_param('sssssisi', $route_name, $trip_date, $departure_time, $pickup_point, $dropoff_point, $capacity, $image_url, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: trips.php');
        exit;
    }
}

$pageTitle = 'Edit Trip';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Trip</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= (int)$trip['id'] ?>">
<label>Route Name <input type="text" name="route_name" value="<?= htmlspecialchars($trip['route_name']) ?>" required></label>
<label>Date <input type="date" name="trip_date" value="<?= htmlspecialchars($trip['trip_date']) ?>" min="<?= date('Y-m-d') ?>" required></label>
<label>Departure Time <input type="time" name="departure_time" value="<?= htmlspecialchars(substr($trip['departure_time'], 0, 5)) ?>" required></label>
<label>Pickup Point <input type="text" name="pickup_point" value="<?= htmlspecialchars($trip['pickup_point']) ?>" required></label>
<label>Drop-off Point <input type="text" name="dropoff_point" value="<?= htmlspecialchars($trip['dropoff_point']) ?>" required></label>
<label>Capacity (Seats) <input type="number" name="capacity" min="1" value="<?= (int)$trip['capacity'] ?>" required></label>
<label>Current Photo
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(entity_image_url($trip)) ?>" alt="<?= htmlspecialchars($trip['route_name']) ?>">
</label>
<label>Replace Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<p>Seats booked so far: <?= (int)$trip['seats_booked'] ?></p>
<button type="submit">Update Trip</button>
</form>
<p><a class="btn btn-secondary btn-small" href="trips.php">Back to trips</a></p>
</div>
<?php require 'partials/footer.php'; ?>
