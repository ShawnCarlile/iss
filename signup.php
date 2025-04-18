<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require '../database/db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// If user is already logged in, send them away
if (isset($_SESSION['user_id'])) {
    header("Location: issues_list.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $pwd = trim($_POST['pwd']);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM iss_persons WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $error = "This email is already taken. Please choose a different one.";
    } else {
        // Password hash + salt
        $pwd_salt = bin2hex(random_bytes(16));
        $pwd_hash = md5($pwd . $pwd_salt);

        // Insert as non-admin user
        $sql = "INSERT INTO iss_persons (fname, lname, mobile, email, pwd_hash, pwd_salt, admin)
                VALUES (:fname, :lname, :mobile, :email, :pwd_hash, :pwd_salt, 'N')";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':mobile' => $mobile,
            ':email' => $email,
            ':pwd_hash' => $pwd_hash,
            ':pwd_salt' => $pwd_salt
        ]);

        // Get new user ID
        $newUserId = $conn->lastInsertId();

        // Set session
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['admin'] = 'N';

        header("Location: issues_list.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles/signup.css"> <!-- Changed to signup.css -->
</head>
<body>
    <div class="container">
        <h2>Sign Up</h2>
        <?php if (!empty($error)) echo "<p class='error-message'>" . htmlspecialchars($error) . "</p>"; ?>
        <form action="signup.php" method="POST">
            <div class="input-group">
                <input type="text" name="fname" placeholder="First Name" required>
            </div>
            <div class="input-group">
                <input type="text" name="lname" placeholder="Last Name" required>
            </div>
            <div class="input-group">
                <input type="text" name="mobile" placeholder="Mobile" required>
            </div>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <input type="password" name="pwd" placeholder="Password" required>
            </div>
            <div class="input-group">
                <input type="submit" value="Sign Up" name="signup">
            </div>
        </form>
        <p class="signup-link">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>
