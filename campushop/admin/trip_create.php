<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$error = '';
$uploadDir = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_name     = trim($_POST['route_name']);
    $trip_date      = $_POST['trip_date'];
    $departure_time = $_POST['departure_time'];
    $pickup_point   = trim($_POST['pickup_point']);
    $dropoff_point  = trim($_POST['dropoff_point']);
    $capacity       = (int)($_POST['capacity'] ?? 0);

    [$image_url, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'trip');

    if ($route_name === '' || $trip_date === '' || $departure_time === '' || $pickup_point === '' || $dropoff_point === '') {
        $error = 'All fields are required.';
    } elseif ($capacity < 1) {
        $error = 'Capacity must be at least 1.';
    } elseif ($trip_date < date('Y-m-d')) {
        $error = 'Trip date cannot be in the past.';
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        $stmt = $conn->prepare('INSERT INTO trips (route_name, trip_date, departure_time, pickup_point, dropoff_point, capacity, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssis', $route_name, $trip_date, $departure_time, $pickup_point, $dropoff_point, $capacity, $image_url);
        $stmt->execute();
        $stmt->close();

        header('Location: trips.php');
        exit;
    }
}

$pageTitle = 'Add Trip';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Add Trip</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>Route Name <input type="text" name="route_name" placeholder="e.g. Main Campus -> LRT Station" required></label>
<label>Date <input type="date" name="trip_date" min="<?= date('Y-m-d') ?>" required></label>
<label>Departure Time <input type="time" name="departure_time" required></label>
<label>Pickup Point <input type="text" name="pickup_point" required></label>
<label>Drop-off Point <input type="text" name="dropoff_point" required></label>
<label>Capacity (Seats) <input type="number" name="capacity" min="1" required></label>
<label>Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Add Trip</button>
</form>
<p><a class="btn btn-secondary btn-small" href="trips.php">Back to trips</a></p>
</div>
<?php require 'partials/footer.php'; ?>
