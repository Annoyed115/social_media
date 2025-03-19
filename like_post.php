<?php
require_once 'config.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($postId > 0) {
        if ($action === 'like') {
            // Insert a like row if not already liked
            $stmt = $pdo->prepare("SELECT like_id FROM likes WHERE post_id = :post_id AND user_id = :user_id");
            $stmt->execute(['post_id' => $postId, 'user_id' => $_SESSION['user_id']]);
            if ($stmt->rowCount() === 0) {
                $stmtInsert = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (:post_id, :user_id)");
                $stmtInsert->execute([
                    'post_id' => $postId,
                    'user_id' => $_SESSION['user_id']
                ]);
            }
        } elseif ($action === 'unlike') {
            // Remove the like
            $stmtDelete = $pdo->prepare("DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id");
            $stmtDelete->execute([
                'post_id' => $postId,
                'user_id' => $_SESSION['user_id']
            ]);
        }
    }
}

// Redirect back to home
header('Location: home.php');
exit;
