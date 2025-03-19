<?php
require_once 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all posts with user info
$sql = "SELECT posts.post_id, posts.content, posts.created_at, users.username
        FROM posts 
        JOIN users ON posts.user_id = users.user_id
        ORDER BY posts.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For quickly checking if a user has liked a post
function userLikedPost($pdo, $postId, $userId) {
    $stmt = $pdo->prepare("SELECT like_id FROM likes WHERE post_id = :post_id AND user_id = :user_id");
    $stmt->execute(['post_id' => $postId, 'user_id' => $userId]);
    return $stmt->fetchColumn() ? true : false;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Home - Social Media</title>
</head>
<body>
    <h1>Welcome to the Social Media Feed</h1>
    <p><a href="logout.php">Logout</a></p>
    
    <!-- Create new post form -->
    <form action="create_post.php" method="POST">
        <textarea name="content" rows="3" cols="50" placeholder="What's on your mind?"></textarea><br>
        <button type="submit">Post</button>
    </form>
    <hr>

    <!-- Display posts -->
    <?php foreach ($posts as $post): ?>
        <div style="border:1px solid #ccc; margin-bottom:20px; padding:10px;">
            <p><strong><?= htmlspecialchars($post['username']) ?></strong> says:</p>
            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            <p><small>Posted at: <?= $post['created_at'] ?></small></p>

            <!-- Like functionality -->
            <?php
                $liked = userLikedPost($pdo, $post['post_id'], $_SESSION['user_id']);
            ?>
            <form action="like_post.php" method="POST" style="display:inline;">
                <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                <?php if ($liked): ?>
                    <button type="submit" name="action" value="unlike">Unlike</button>
                <?php else: ?>
                    <button type="submit" name="action" value="like">Like</button>
                <?php endif; ?>
            </form>

            <!-- Count likes -->
            <?php
                $stmtLikes = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");
                $stmtLikes->execute(['post_id' => $post['post_id']]);
                $likeCount = $stmtLikes->fetchColumn();
            ?>
            <span><?= $likeCount ?> Likes</span>

            <hr>
            <!-- Show comments for each post -->
            <?php
                $stmtC = $pdo->prepare("SELECT comments.content, comments.created_at, users.username 
                                        FROM comments
                                        JOIN users ON comments.user_id = users.user_id
                                        WHERE post_id = :post_id
                                        ORDER BY comments.created_at ASC");
                $stmtC->execute(['post_id' => $post['post_id']]);
                $comments = $stmtC->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div style="margin-left:20px;">
                <?php foreach ($comments as $comment): ?>
                    <p>
                        <strong><?= htmlspecialchars($comment['username']) ?></strong>: 
                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
                        <br><small>Posted at: <?= $comment['created_at'] ?></small>
                    </p>
                <?php endforeach; ?>

                <!-- Add a comment form -->
                <form action="add_comment.php" method="POST">
                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                    <textarea name="content" rows="2" cols="40" placeholder="Write a comment..."></textarea><br>
                    <button type="submit">Comment</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

</body>
</html>
