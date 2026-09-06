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
<title><?= htmlspecialchars($pageTitle ?? 'Admin') ?></title>
<link rel="icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=<?= @filemtime(__DIR__ . '/../../style.css') ?>">
</head>
<body>
<nav class="navbar">
<a class="brand" href="trips.php">
<svg class="brand-logo" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
<rect x="4" y="10" width="32" height="19" rx="5" fill="var(--accent)"/>
<rect x="8" y="14" width="8" height="7" rx="1.5" fill="var(--bg)"/>
<rect x="18" y="14" width="8" height="7" rx="1.5" fill="var(--bg)"/>
<rect x="28" y="14" width="4" height="7" rx="1.5" fill="var(--bg)"/>
<circle cx="12" cy="31" r="3.4" fill="var(--bg)" stroke="var(--accent)" stroke-width="2"/>
<circle cx="28" cy="31" r="3.4" fill="var(--bg)" stroke="var(--accent)" stroke-width="2"/>
<path d="M4 24 h32" stroke="var(--bg)" stroke-width="1.5"/>
</svg>
Admin &middot; CampusHop</a>
<div class="nav-links">
<a href="trips.php" class="<?= $currentPage === 'trips.php' ? 'active' : '' ?>">Trips</a>
<a href="bookings.php" class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">Bookings</a>
<a href="checkin.php" class="<?= $currentPage === 'checkin.php' ? 'active' : '' ?>">Check-In</a>
<a href="testimonials.php" class="<?= $currentPage === 'testimonials.php' ? 'active' : '' ?>">Testimonials</a>
<a href="messages.php" class="<?= $currentPage === 'messages.php' ? 'active' : '' ?>">Messages</a>
<a href="users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">Users</a>
<a href="../logout.php">Logout</a>
<button id="theme-toggle" class="theme-toggle" type="button" aria-label="Toggle dark mode">&#9728;</button>
</div>
</nav>
<main class="container">
