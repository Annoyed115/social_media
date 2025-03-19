<?php
require_once 'config.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($postId > 0 && !empty($content)) {
        // Insert new comment
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) 
                               VALUES (:post_id, :user_id, :content)");
        $stmt->execute([
            'post_id' => $postId,
            'user_id' => $_SESSION['user_id'],
            'content' => $content
        ]);
    }
}

// Redirect back to home
header('Location: home.php');
exit;
