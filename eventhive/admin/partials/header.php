<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script>
(function () {
    var saved = localStorage.getItem('theme');
    if (saved === 'dark' || saved === 'light') {
        document.documentElement.setAttribute('data-theme', saved);
    }
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'EventHive Admin') ?></title>
<link rel="icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=<?= @filemtime(__DIR__ . '/../../style.css') ?>">
</head>
<body>
<nav class="navbar">
<a class="brand" href="events.php">
<svg class="brand-logo" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
<polygon points="20,2 36,11 36,29 20,38 4,29 4,11" fill="var(--accent)"/>
<polygon points="20,9 29.5,14.5 29.5,25.5 20,31 10.5,25.5 10.5,14.5" fill="var(--bg)"/>
<polygon points="20,14 25,17 25,23 20,26 15,23 15,17" fill="var(--gold)"/>
</svg>
EventHive Admin</a>
<div class="nav-links">
<a href="events.php" class="<?= $currentPage === 'events.php' ? 'active' : '' ?>">Events</a>
<a href="orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a>
<a href="checkin.php" class="<?= $currentPage === 'checkin.php' ? 'active' : '' ?>">Check-In</a>
<a href="testimonials.php" class="<?= $currentPage === 'testimonials.php' ? 'active' : '' ?>">Testimonials</a>
<a href="messages.php" class="<?= $currentPage === 'messages.php' ? 'active' : '' ?>">Messages</a>
<a href="users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">Users</a>
<a href="../logout.php">Logout</a>
<button id="theme-toggle" class="theme-toggle" type="button" aria-label="Toggle dark mode">&#9728;</button>
</div>
</nav>
<main class="container">
