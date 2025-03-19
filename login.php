<?php
require_once 'config.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";

    if (empty($errors)) {
        // Find user by email
        $stmt = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password correct; set session
            $_SESSION['user_id'] = $user['user_id'];
            header('Location: home.php');
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Social Media</title>
</head>
<body>
    <h1>Login</h1>
    <?php if (isset($_GET['registered'])): ?>
        <p style="color: green;">Registration successful! You can now log in.</p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label>Email: 
            <input type="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
        </label><br><br>
        <label>Password: 
            <input type="password" name="password">
        </label><br><br>
        <button type="submit">Login</button>
    </form>
    <br>
    <a href="register.php">Don't have an account? Register here.</a>
</body>
</html>
