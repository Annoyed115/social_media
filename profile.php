<?php
require_once 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $postId = (int)$_POST['post_id'];
    
    // Verify the post belongs to the user
    $checkStmt = $pdo->prepare("SELECT post_id FROM posts WHERE post_id = :post_id AND user_id = :user_id");
    $checkStmt->execute([
        'post_id' => $postId,
        'user_id' => $userId
    ]);
    
    if ($checkStmt->rowCount() > 0) {
        $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE post_id = :post_id");
        $deleteStmt->execute(['post_id' => $postId]);
        $successMessage = "Post deleted successfully.";
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate inputs
    if (empty($username)) $errors[] = "Username cannot be empty.";
    if (empty($email)) $errors[] = "Email cannot be empty.";
    
    // Check if email is already in use by another user
    if (!empty($email)) {
        $checkEmailStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
        $checkEmailStmt->execute([
            'email' => $email,
            'user_id' => $userId
        ]);
        if ($checkEmailStmt->rowCount() > 0) {
            $errors[] = "Email is already in use by another account.";
        }
    }
    
    // If changing password, validate it
    if (!empty($currentPassword)) {
        // Verify current password
        $passwordStmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
        $passwordStmt->execute(['user_id' => $userId]);
        $user = $passwordStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $errors[] = "New password must be at least 6 characters.";
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = "New passwords do not match.";
            }
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        $updateFields = [];
        $params = [];
        
        // Update username and email
        $updateFields[] = "username = :username";
        $updateFields[] = "email = :email";
        $params['username'] = $username;
        $params['email'] = $email;
        $params['user_id'] = $userId;
        
        // Update password if provided
        if (!empty($newPassword)) {
            $updateFields[] = "password_hash = :password_hash";
            $params['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }
        
        $updateQuery = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = :user_id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute($params);
        
        $successMessage = "Profile updated successfully.";
    }
}

// Get user info
$userStmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE user_id = :user_id");
$userStmt->execute(['user_id' => $userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Get user posts
$postsStmt = $pdo->prepare("SELECT post_id, content, created_at FROM posts WHERE user_id = :user_id ORDER BY created_at DESC");
$postsStmt->execute(['user_id' => $userId]);
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to format date
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SocialApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }
        .post-card {
            transition: box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
        }
        .post-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .post-actions {
            display: flex;
            justify-content: flex-end;
        }
        .tab-pane {
            padding: 20px 0;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="home.php">SocialApp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($user['username']) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">
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
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12">
                <div class="profile-header text-center">
                    <div class="profile-avatar">
                        <?= substr(htmlspecialchars($user['username']), 0, 1) ?>
                    </div>
                    <h2><?= htmlspecialchars($user['username']) ?></h2>
                    <p class="text-muted">Member since <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">
                            <i class="fas fa-list"></i> My Posts
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                            <i class="fas fa-cog"></i> Settings
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">
                    <!-- Posts tab -->
                    <div class="tab-pane fade show active" id="posts" role="tabpanel">
                        <h3 class="mb-4">My Posts</h3>
                        
                        <?php if (count($posts) > 0): ?>
                            <?php foreach ($posts as $post): ?>
                                <div class="card post-card">
                                    <div class="card-body">
                                        <p class="card-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Posted <?= timeAgo($post['created_at']) ?></small>
                                            <div class="post-actions">
                                                <a href="edit_post.php?id=<?= $post['post_id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form action="profile.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');" class="d-inline">
                                                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                                    <button type="submit" name="delete_post" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                You haven't created any posts yet.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Settings tab -->
                    <div class="tab-pane fade" id="settings" role="tabpanel">
                        <h3 class="mb-4">Account Settings</h3>
                        
                        <div class="card">
                            <div class="card-body">
                                <form action="profile.php" method="POST">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    
                                    <hr class="my-4">
                                    <h5>Change Password</h5>
                                    <p class="text-muted">Leave blank if you don't want to change your password.</p>
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>