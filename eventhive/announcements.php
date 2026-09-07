<?php
require 'config.php';
require 'auth.php';

$announcements = $conn->query('
    SELECT a.id, a.title, a.body, a.created_at, u.name AS posted_by_name
    FROM announcements a
    JOIN users u ON u.id = a.posted_by
    ORDER BY a.created_at DESC
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Announcements - EventHive';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Announcements</h1>
<p>Updates and notices from the EventHive team.</p>
</div>

<?php if (empty($announcements)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128226;</div>
<p>No announcements yet.</p>
</div>
<?php else: ?>
<div class="testimonial-list">
<?php foreach ($announcements as $a): ?>
<div class="testimonial-card">
<h3><?= htmlspecialchars($a['title']) ?></h3>
<p><?= nl2br(htmlspecialchars($a['body'])) ?></p>
<div class="testimonial-meta">
<span>Posted by <?= htmlspecialchars($a['posted_by_name']) ?> &middot; <?= htmlspecialchars(date('d M Y, g:i A', strtotime($a['created_at']))) ?></span>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
