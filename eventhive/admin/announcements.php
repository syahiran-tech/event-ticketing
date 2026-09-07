<?php
require '../config.php';
require '../auth.php';
require_admin();

$announcements = $conn->query('
    SELECT a.id, a.title, a.body, a.created_at, u.name AS posted_by_name
    FROM announcements a
    JOIN users u ON u.id = a.posted_by
    ORDER BY a.created_at DESC
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Announcements - EventHive Admin';
require 'partials/header.php';
?>
<h1>Announcements</h1>
<p>Posted here shows up for every visitor on the homepage and the Announcements page.</p>
<p><a class="btn btn-small" href="announcement_create.php">+ New Announcement</a></p>
<?php if (empty($announcements)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128226;</div>
<p>No announcements yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Title</th><th>Body</th><th>Posted By</th><th>Posted</th><th>Actions</th></tr>
<?php foreach ($announcements as $a): ?>
<tr>
<td><?= htmlspecialchars($a['title']) ?></td>
<td><?= htmlspecialchars(mb_strimwidth($a['body'], 0, 80, '...')) ?></td>
<td><?= htmlspecialchars($a['posted_by_name']) ?></td>
<td><?= htmlspecialchars(date('d M Y, g:i A', strtotime($a['created_at']))) ?></td>
<td>
<a class="btn btn-secondary btn-small" href="announcement_edit.php?id=<?= (int)$a['id'] ?>">Edit</a>
<form action="announcement_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this announcement?');">
<input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
