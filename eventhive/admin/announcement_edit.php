<?php
require '../config.php';
require '../auth.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';

$stmt = $conn->prepare('SELECT * FROM announcements WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$announcement = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$announcement) {
    die('Announcement not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');

    if ($title === '' || $body === '') {
        $error = 'Both a title and a message are required.';
    } else {
        $stmt = $conn->prepare('UPDATE announcements SET title = ?, body = ? WHERE id = ?');
        $stmt->bind_param('ssi', $title, $body, $id);
        $stmt->execute();
        $stmt->close();

        header('Location: announcements.php');
        exit;
    }

    $announcement = array_merge($announcement, compact('title', 'body'));
}

$pageTitle = 'Edit Announcement - EventHive Admin';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Announcement</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="id" value="<?= (int)$announcement['id'] ?>">
<label>Title <input type="text" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" maxlength="150" required></label>
<label>Message <textarea name="body" rows="5" required><?= htmlspecialchars($announcement['body']) ?></textarea></label>
<button type="submit">Save Changes</button>
</form>
<p><a class="btn btn-secondary btn-small" href="announcements.php">Back to announcements</a></p>
</div>
<?php require 'partials/footer.php'; ?>
