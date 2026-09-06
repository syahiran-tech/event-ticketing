<?php
require 'config.php';
require 'auth.php';

$pageTitle = 'About';
require 'partials/header.php';
?>
<div class="page-header">
<h1>About This Platform</h1>
<p>What CampusHop is, and how it works.</p>
</div>

<section>
<h2>Our Mission</h2>
<p>CampusHop gives every TAR UMT student and staff member a single place to book a free
seat on the campus shuttle bus — trips to the LRT station, the city mall, the train
terminal and between hostel blocks and Main Campus — without relying on manual
sign-up sheets or guessing whether the next bus still has room.</p>
</section>

<section>
<h2>How It Works</h2>
<div class="card-grid">
<div class="card">
<div class="card-icon">&#128652;</div>
<h3>1. Browse Trips</h3>
<p>See every upcoming shuttle trip with its route, date, departure time and live seat availability.</p>
</div>
<div class="card">
<div class="card-icon">&#127903;</div>
<h3>2. Book Seats</h3>
<p>Choose how many seats you need — the system checks capacity in real time. Booking is free and instant.</p>
</div>
<div class="card">
<div class="card-icon">&#9989;</div>
<h3>3. Manage Bookings</h3>
<p>Change the seat count or cancel a booking anytime from your homepage, and board with your QR boarding pass.</p>
</div>
</div>
</section>

<section>
<h2>Who Runs This</h2>
<p>This platform is a sample project built for the AMIT3253 Cloud Computing for Business
capstone assignment, demonstrating a simple booking system deployed on AWS.</p>
</section>
<?php require 'partials/footer.php'; ?>
