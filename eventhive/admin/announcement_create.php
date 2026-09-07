<?php
require '../config.php';
require '../auth.php';
require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');
    $uid   = current_user_id();

    if ($title === '' || $body === '') {
        $error = 'Both a title and a message are required.';
    } else {
        $stmt = $conn->prepare('INSERT INTO announcements (title, body, posted_by) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $title, $body, $uid);
        $stmt->execute();
        $stmt->close();

        header('Location: announcements.php');
        exit;
    }
}

$pageTitle = 'New Announcement - EventHive Admin';
require 'partials/header.php';
?>
<div class="form-card">
<h1>New Announcement</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<label>Title <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" maxlength="150" required></label>
<label>Message <textarea name="body" rows="5" required><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea></label>
<button type="submit">Post Announcement</button>
</form>
<p><a class="btn btn-secondary btn-small" href="announcements.php">Back to announcements</a></p>
</div>
<?php require 'partials/footer.php'; ?>
