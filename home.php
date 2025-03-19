<?php
require_once 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all posts with user info
$sql = "SELECT posts.post_id, posts.content, posts.created_at, users.username, users.user_id
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

// Format date as "time ago"
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    } elseif ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'just now';
    }
}

// Get current user info
$stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .post-card {
            transition: box-shadow 0.3s ease;
        }
        .post-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .like-button {
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .liked {
            color: #e74c3c;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .comment-section {
            max-height: 300px;
            overflow-y: auto;
        }
        .comment-form {
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
            margin-top: 10px;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation bar -->
    <!-- Update the navigation bar in home.php -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="home.php">PHP Experts</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($currentUser['username']) ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="home.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

    <div class="container">
        <!-- Create new post form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Create a Post</h5>
                <form action="create_post.php" method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" name="content" rows="3" placeholder="What's on your mind?" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Post
                    </button>
                </form>
            </div>
        </div>

        <!-- Display posts -->
        <?php foreach ($posts as $post): ?>
            <?php $liked = userLikedPost($pdo, $post['post_id'], $_SESSION['user_id']); ?>
            <div class="card post-card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <?= substr(htmlspecialchars($post['username']), 0, 1) ?>
                        </div>
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($post['username']) ?></h6>
                            <small class="text-muted">
                                <?= timeAgo($post['created_at']) ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <p class="card-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                </div>
                
                <div class="card-footer bg-white">
                    <!-- Like functionality -->
                    <?php
                        $stmtLikes = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = :post_id");
                        $stmtLikes->execute(['post_id' => $post['post_id']]);
                        $likeCount = $stmtLikes->fetchColumn();
                    ?>
                    <form action="like_post.php" method="POST" class="d-inline">
                        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                        <?php if ($liked): ?>
                            <button type="submit" name="action" value="unlike" class="btn btn-sm btn-link text-decoration-none like-button liked">
                                <i class="fas fa-heart"></i> <?= $likeCount ?> Likes
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="like" class="btn btn-sm btn-link text-decoration-none like-button">
                                <i class="far fa-heart"></i> <?= $likeCount ?> Likes
                            </button>
                        <?php endif; ?>
                    </form>

                    <!-- Comment button -->
                    <button class="btn btn-sm btn-link text-decoration-none" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#comments-<?= $post['post_id'] ?>">
                        <i class="far fa-comment"></i> Comments
                    </button>

                    <!-- Comments section -->
                    <div class="collapse mt-3" id="comments-<?= $post['post_id'] ?>">
                        <?php
                            $stmtC = $pdo->prepare("SELECT comments.content, comments.created_at, users.username 
                                                    FROM comments
                                                    JOIN users ON comments.user_id = users.user_id
                                                    WHERE post_id = :post_id
                                                    ORDER BY comments.created_at ASC");
                            $stmtC->execute(['post_id' => $post['post_id']]);
                            $comments = $stmtC->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <div class="comment-section">
                            <?php if (count($comments) > 0): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="d-flex mb-2">
                                        <div class="avatar">
                                            <?= substr(htmlspecialchars($comment['username']), 0, 1) ?>
                                        </div>
                                        <div class="ms-2">
                                            <div class="bg-light p-2 rounded">
                                                <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                                <p class="mb-1"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                            </div>
                                            <small class="text-muted"><?= timeAgo($comment['created_at']) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No comments yet. Be the first to comment!</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Add comment form -->
                        <form action="add_comment.php" method="POST" class="comment-form">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <div class="input-group">
                                <textarea class="form-control" name="content" placeholder="Write a comment..." required></textarea>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($posts) == 0): ?>
            <div class="alert alert-info">
                No posts yet. Be the first to create a post!
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>