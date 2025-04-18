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

// Check if the issue ID is passed
if (isset($_GET['issue_id'])) {
    $_SESSION['current_issue_id'] = $_GET['issue_id'];
}

if (isset($_SESSION['current_issue_id'])) {
    $issue_id = $_SESSION['current_issue_id'];
} else {
    echo "Issue ID is missing!";
    exit();
}

// Fetch person details to display first and last name
$sql = "SELECT * FROM iss_persons";
$stmt = $conn->prepare($sql);
$stmt->execute();
$persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$personMap = [];
foreach ($persons as $p) {
    $personMap[$p['id']] = $p['fname'] . ' ' . $p['lname'];
}

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
    $per_id = $_SESSION['user_id'];

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

    header("Location: comments_list.php");
    exit();
}

// Update comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_comment']) 
    && !(isset($_POST['reopen']) && $_POST['reopen'] === 'yes')
    && ($_SESSION['admin'] == 'Y' || $_SESSION['user_id'] == $_POST['per_id'])) {


    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];
    $posted_date = $_POST['posted_date'];
    $comm_id = $_POST['comm_id'];

    $sql = "UPDATE iss_comments 
            SET short_comment = :short_comment, long_comment = :long_comment, posted_date = :posted_date 
            WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':short_comment' => $short_comment,
        ':long_comment' => $long_comment,
        ':posted_date' => $posted_date,
        ':id' => $comm_id
    ]);

    header("Location: comments_list.php");
    exit();
}

// Delete comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_comment']) 
&& ($_SESSION['admin'] == 'Y' || $_SESSION['user_id'] == $_POST['per_id'])) {

    $comment_id = $_POST['comment_id'];

    $sql = "DELETE FROM iss_comments WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $comment_id]);

    header("Location: comments_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comments for Issue #<?= htmlspecialchars($issue['id']) ?></title>
    <link rel="stylesheet" href="styles/comments_list.css">
</head>
<body>
    <div class="container">
        <h1>Issue Details and Comments</h1>

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
        if (htmlspecialchars($issue['close_date']) != '0000-00-00') {
            $status = "Closed";
        }
        echo "<p><strong>Status: </strong>$status</p>";
        ?>

        <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']) ?></p>
        <p><strong>Assigned Person:</strong> <?= htmlspecialchars($personMap[$issue['per_id']]) ?></p>

        <button type="button" class="btn" onclick="window.location.href='issues_list.php'">Back to Issues List</button>

        <h2>Comments</h2>
        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Short Comment</th>
                    <th class="long-comment-column">Long Comment</th>
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
                            <button onclick="openModal('readModal-<?= $comment['id'] ?>')">Read</button>
                            <?php if ($_SESSION['user_id'] == $comment['per_id'] || $_SESSION['admin'] == 'Y') { ?>
                                <button type="button" onclick="openModal('updateModal-<?= $comment['id'] ?>')">Update</button>
                                <button type="button" onclick="openModal('deleteModal-<?= $comment['id'] ?>')">Delete</button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if ($status !== 'Closed') { ?>
        <h3>Add a New Comment</h3>
        <form method="POST">
            <input type="text" name="short_comment" placeholder="Short Comment" required class="input-field">
            <textarea name="long_comment" placeholder="Long Comment" required class="textarea-field"></textarea>
            <input type="date" name="posted_date" required class="input-field">
            <button type="submit" name="add_comment" class="btn">Add Comment</button>
        </form>
        <?php } ?>
    </div>

    <?php foreach ($comments as $comment): ?>
    <!-- Read Modal -->
    <div class="modal" id="readModal-<?= $comment['id'] ?>">
        <div class="modal-content">
            <span class="close" onclick="closeModal('readModal-<?= $comment['id'] ?>')">&times;</span>
            <h2>Comment Details</h2>
            <p><strong>Short Comment:</strong> <?= htmlspecialchars($comment['short_comment']) ?></p>
            <p class="long-comment-text"><strong>Long Comment: </strong><?= htmlspecialchars($comment['long_comment']) ?></p>
            <p><strong>Posted Date:</strong> <?= htmlspecialchars($comment['posted_date']) ?></p>
            <p><strong>Person:</strong> <?= htmlspecialchars($personMap[$comment['per_id']]) ?></p>
        </div>
    </div>

    <!-- Update Modal -->
    <?php if($_SESSION['user_id'] == $comment['per_id'] || $_SESSION['admin'] == "Y"): ?>
    <div class="modal" id="updateModal-<?= $comment['id'] ?>">
        <div class="modal-content">
            <span class="close" onclick="closeModal('updateModal-<?= $comment['id'] ?>')">&times;</span>
            <h2>Update Comment</h2>
            <form method="POST">
                <input type="hidden" name="comm_id" value="<?= $comment['id'] ?>">
                <input type="text" name="short_comment" value="<?= htmlspecialchars($comment['short_comment']) ?>" required class="input-field">
                <textarea name="long_comment" required class="textarea-field"><?= htmlspecialchars($comment['long_comment']) ?></textarea>
                <input type="date" name="posted_date" value="<?= htmlspecialchars($comment['posted_date']) ?>" required class="input-field">
                <input type="hidden" name="per_id" value="<?= $comment['per_id'] ?>">
                <button type="submit" name="update_comment" class="btn">Update</button>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal" id="deleteModal-<?= $comment['id'] ?>">
        <div class="modal-content">
            <h2>Comment Details</h2>
            <p><strong>Short Comment:</strong> <?= htmlspecialchars($comment['short_comment']) ?></p>
            <p class="long-comment-text"><strong>Long Comment: </strong><?= htmlspecialchars($comment['long_comment']) ?></p>
            <p><strong>Posted Date:</strong> <?= htmlspecialchars($comment['posted_date']) ?></p>
            <p><strong>Person:</strong> <?= htmlspecialchars($personMap[$comment['per_id']]) ?></p>
            <span class="close" onclick="closeModal('deleteModal-<?= $comment['id'] ?>')">&times;</span>
            <h2>Delete Comment</h2>
            <p>Are you sure you want to delete this comment?</p>
            <?php if ($_SESSION['user_id'] == $comment['per_id'] || $_SESSION['admin'] == "Y") { ?>
            <form method="POST">
                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                <input type="hidden" name="per_id" value="<?= $comment['per_id'] ?>">
                <button type="submit" name="delete_comment">Delete</button>
                <button type="button" onclick="closeModal('deleteModal-<?= $comment['id'] ?>')">Cancel</button>
            </form>
            <?php } else { ?>
            <form action="logout.php">
                <button type="submit">Logout</button>
            </form>
            <?php } ?>
        </div>
    </div>
    <?php endif; ?>
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
