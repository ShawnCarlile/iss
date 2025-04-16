<?php
session_start();
require '../database/db_connection.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM iss_persons WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            $salt = trim($user['pwd_salt']);
            $hashed_password = md5($password . $salt);
            
            if ($hashed_password === $user['pwd_hash']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fname'] = $user['fname'];
                $_SESSION['lname'] = $user['lname'];
                $_SESSION['admin'] = $user['admin'];
                header("Location: issues_list.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - DSR</title>
    <link rel="stylesheet" type="text/css" href="styles/login.css">
</head>
<body>
    <div class="container">
        <h2>Login to Department Status Report (DSR)</h2>
        <?php if (!empty($error)) echo "<p class='error-message'>" . htmlspecialchars($error) . "</p>"; ?>
        <form method="POST" action="login.php">
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
            </div>
            <div class="input-group">
                <input type="submit" value="Login">
            </div>
        </form>
    </div>
</body>
</html>