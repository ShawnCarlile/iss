<?php
// Start session to verify if the user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require '../database/db_connection.php';

// Enable error reporting to catch any issues during execution
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the issue ID is passed through the URL
if (isset($_GET['issue_id'])) {
    $issue_id = $_GET['issue_id'];
} else {
    echo "Issue ID is missing!";
    exit();
}
// Fetch person details to display first and last name
$sql = "SELECT * FROM iss_persons";
$stmt = $conn->prepare($sql);
$stmt->execute();
$persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch the issue details based on issue_id
$sql = "SELECT * FROM iss_issues WHERE id = :issue_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':issue_id' => $issue_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the comments related to the issue
$sql = "SELECT iss_comments.*, iss_persons.fname, iss_persons.lname 
        FROM iss_comments 
        LEFT JOIN iss_persons ON iss_comments.per_id = iss_persons.id 
        WHERE iss_comments.iss_id = :issue_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':issue_id' => $issue_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add new comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];
    $posted_date = $_POST['posted_date'];
    $per_id = $_SESSION['user_id'];  // Assuming the logged-in user's ID is stored in the session

    // Insert the new comment
    $sql = "INSERT INTO iss_comments (short_comment, long_comment, posted_date, per_id, iss_id) 
            VALUES (:short_comment, :long_comment, :posted_date, :per_id, :iss_id)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':short_comment' => $short_comment,
        ':long_comment' => $long_comment,
        ':posted_date' => $posted_date,
        ':per_id' => $per_id,
        ':iss_id' => $issue_id
    ]);

    // Refresh the comments list after adding a new comment
    header("Location: comments_list.php?issue_id=" . $issue_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments for Issue #<?= htmlspecialchars($issue['id']) ?></title>
    <link rel="stylesheet" href="styles/comments_list.css">
</head>
<body>
    <div class="container">
        <h1>Issue Details and Comments</h1>
        
        <!-- Display the Issue Details -->
        <h2>Issue Details</h2>
        <p><strong>ID:</strong> <?= htmlspecialchars($issue['id']) ?></p>
        <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']) ?></p>
        <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']) ?></p>
        <p><strong>Open Date:</strong> <?= htmlspecialchars($issue['open_date']) ?></p>
        <p><strong>Close Date:</strong> <?= htmlspecialchars($issue['close_date']) ?></p>
        <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']) ?></p>
        <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']) ?></p>
        <?php

        $status = "Open";

        if(htmlspecialchars($issue['close_date']) != '0000-00-00'){
            $status = "Closed";
        }

        echo "<p><strong>Status: </strong>$status</p>";

        ?>
        <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']) ?></p>
        <p><strong>Assigned Person:</strong> <?= htmlspecialchars($persons[$issue['per_id'] - 1]['fname']) . ' ' . htmlspecialchars($persons[$issue['per_id'] - 1]['lname']) ?></p>
        <button type="button" class="btn" onclick="window.location.href='issues_list.php'">Back to Issues List</button>

        <!-- Display the Comments Table -->
        <h2>Comments</h2>
        <table>
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Short Comment</th>
                    <th>Long Comment</th>
                    <th>Posted Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><?= htmlspecialchars($comment['fname']) ?> <?= htmlspecialchars($comment['lname']) ?></td>
                        <td><?= htmlspecialchars($comment['short_comment']) ?></td>
                        <td><?= htmlspecialchars($comment['long_comment']) ?></td>
                        <td><?= htmlspecialchars($comment['posted_date']) ?></td>
                        <td>
                            <button type="button" onclick="openModal('readModal-<?= $comment['id'] ?>')">Read</button>
                            <button type="button" onclick="openModal('updateModal-<?= $comment['id'] ?>')">Update</button>
                            <button type="button" onclick="openModal('deleteModal-<?= $comment['id'] ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Form to Add a New Comment -->
        <?php if($status != 'Closed') { ?>
        <h3>Add a New Comment</h3>
        <form action="comments_list.php?issue_id=<?= htmlspecialchars($issue_id) ?>" method="POST">
            <input type="text" name="short_comment" placeholder="Short Comment" required class="input-field">
            <textarea name="long_comment" placeholder="Long Comment" required class="textarea-field"></textarea>
            <input type="date" name="posted_date" required class="input-field">
            <button type="submit" name="add_comment" class="btn">Add Comment</button>
        </form>
    </div>

        <?php } ?>

    <!-- Read Modal for each issue
    <?php foreach ($issues as $issue): ?>
    <div class="modal" id="readModal-<?= $issue['id'] ?>">
        <div class="modal-content">
            <span class="close" onclick="closeModal('readModal-<?= $issue['id'] ?>')">&times;</span>
            <h2>Issue Details</h2>
            <p><strong>ID:</strong> <?= htmlspecialchars($issue['id']) ?></p>
            <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']) ?></p>
            <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']) ?></p>
            <p><strong>Open Date:</strong> <?= htmlspecialchars($issue['open_date']) ?></p>
            <p><strong>Close Date:</strong> <?= htmlspecialchars($issue['close_date']) ?></p>
            <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']) ?></p>
            <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']) ?></p>
            <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']) ?></p>
            <p><strong>Person:</strong> <?= htmlspecialchars($persons[$issue['per_id'] - 1]['fname']) . ' ' . htmlspecialchars($persons[$issue['per_id'] - 1]['lname']) ?></p>

        </div>
    </div> -->

    <?php endforeach; ?>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add("active");
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove("active");
        }
        window.onclick = function(event) {
            document.querySelectorAll(".modal").forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove("active");
                }
            });
        }
    </script>
</body>
</html>