<?php
require 'config.php';
require 'auth.php';

$pageTitle = 'About - EventHive';
require 'partials/header.php';
?>
<div class="page-header">
<h1>About EventHive</h1>
<p>What EventHive is, and how it works.</p>
</div>

<section>
<h2>Our Mission</h2>
<p>EventHive gives every registered campus club and society a single hive for selling
tickets to their events — cultural nights, band battles, charity dinners and more — with
Regular and VIP tiers, instead of relying on manual sign-up sheets or spreadsheets that run
out of seats.</p>
</section>

<section>
<h2>How It Works</h2>
<div class="card-grid">
<div class="card">
<div class="card-icon">&#128197;</div>
<h3>1. Browse Events</h3>
<p>Filter by category or search by name to see every upcoming event with its date, venue and live ticket availability.</p>
</div>
<div class="card">
<div class="card-icon">&#127903;</div>
<h3>2. Pick a Tier</h3>
<p>Choose Regular or VIP (where available) and how many tickets you need — the system checks availability per tier in real time.</p>
</div>
<div class="card">
<div class="card-icon">&#9989;</div>
<h3>3. Manage My Tickets</h3>
<p>Change the quantity or cancel a reservation anytime from your homepage.</p>
</div>
</div>
</section>

<section>
<h2>Who Runs This</h2>
<p>EventHive is a sample project built for the AMIT3253 Cloud Computing for Business
capstone assignment at TAR UMT, demonstrating a tiered booking/ticketing system deployed on AWS.</p>
</section>
<?php require 'partials/footer.php'; ?>
