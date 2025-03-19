<?php
require_once 'config.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic validations
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";

    // If no errors, attempt registration
    if (empty($errors)) {
        // Check if email is already in use
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "An account with that email already exists.";
        } else {
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password_hash) 
                 VALUES (:username, :email, :password_hash)"
            );
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash
            ]);
            // Redirect to login
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Social Media</title>
</head>
<body>
    <h1>Register</h1>
    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <label>Username: 
            <input type="text" name="username" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
        </label><br><br>
        <label>Email: 
            <input type="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
        </label><br><br>
        <label>Password: 
            <input type="password" name="password">
        </label><br><br>
        <label>Confirm Password: 
            <input type="password" name="confirm_password">
        </label><br><br>
        <button type="submit">Register</button>
    </form>
    <br>
    <a href="login.php">Already have an account? Login here.</a>
</body>
</html>
