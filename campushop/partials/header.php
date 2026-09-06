<?php
$loggedIn = current_user_id() !== null;
$currentPage = basename($_SERVER['PHP_SELF']);
function nav_active($page, $current) {
    return $page === $current ? ' active' : '';
}
?>
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
<meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'Book a free seat on a TAR UMT campus shuttle trip - pick a route, reserve seats, get an instant QR boarding pass.') ?>">
<title><?= htmlspecialchars($pageTitle ?? 'CampusHop') ?></title>
<link rel="icon" type="image/png" href="assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__ . '/../style.css') ?>">
</head>
<body>
<nav class="navbar">
<a class="brand" href="index.php">
<svg class="brand-logo" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
<rect x="4" y="10" width="32" height="19" rx="5" fill="var(--accent)"/>
<rect x="8" y="14" width="8" height="7" rx="1.5" fill="var(--bg)"/>
<rect x="18" y="14" width="8" height="7" rx="1.5" fill="var(--bg)"/>
<rect x="28" y="14" width="4" height="7" rx="1.5" fill="var(--bg)"/>
<circle cx="12" cy="31" r="3.4" fill="var(--bg)" stroke="var(--accent)" stroke-width="2"/>
<circle cx="28" cy="31" r="3.4" fill="var(--bg)" stroke="var(--accent)" stroke-width="2"/>
<path d="M4 24 h32" stroke="var(--bg)" stroke-width="1.5"/>
</svg>
CampusHop</a>
<div class="nav-links">
<a href="index.php" class="<?= trim(nav_active('index.php', $currentPage)) ?>">Home</a>
<a href="trips.php" class="<?= trim(nav_active('trips.php', $currentPage)) ?>">Trips</a>
<a href="schedule.php" class="<?= trim(nav_active('schedule.php', $currentPage)) ?>">Schedule</a>
<a href="testimonials.php" class="<?= trim(nav_active('testimonials.php', $currentPage)) ?>">Testimonials</a>
<a href="about.php" class="<?= trim(nav_active('about.php', $currentPage)) ?>">About</a>
<a href="contact.php" class="<?= trim(nav_active('contact.php', $currentPage)) ?>">Contact</a>
<?php if ($loggedIn): ?>
<div class="user-menu">
<button type="button" class="nav-user user-menu-trigger" aria-haspopup="true" aria-expanded="false">
<span class="user-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr(current_user_name(), 0, 1))) ?></span> Hi, <?= htmlspecialchars(current_user_name()) ?>
</button>
<div class="user-menu-dropdown">
<a href="account.php">My Account</a>
<a href="logout.php">Logout</a>
</div>
</div>
<?php else: ?>
<a href="login.php">Login</a>
<a href="register.php">Register</a>
<?php endif; ?>
<button id="theme-toggle" class="theme-toggle" type="button" aria-label="Toggle dark mode">&#9728;</button>
</div>
</nav>
<main class="container">
