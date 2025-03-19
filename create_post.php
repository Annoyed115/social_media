<?php
require_once 'config.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    if (!empty($content)) {
        // Insert new post
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (:user_id, :content)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'content' => $content
        ]);
    }
}

// Go back to home
header('Location: home.php');
exit;
