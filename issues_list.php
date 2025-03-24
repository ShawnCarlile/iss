<?php
// Start session to verify if the user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require '../database/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Query to fetch all people from the iss_persons table
$sql_persons = "SELECT id, fname, lname FROM iss_persons ORDER BY fname, lname ASC";
$stmt_persons = $conn->query($sql_persons);
$persons = $stmt_persons->fetchAll(PDO::FETCH_ASSOC);

// Query to fetch all issues sorted by project name
$sql = "SELECT * FROM iss_issues ORDER BY project ASC";
$stmt = $conn->query($sql);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues List</title>
    <link rel="stylesheet" href="styles/issues_list.css">
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
</head>
<body>
    <div class="container">
        <h1>Issues List</h1>
        <button type="button" class="btn" onclick="openModal('addModal')">+</button>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Short Description</th>
                    <th>Open Date</th>
                    <th>Priority</th>
                    <th>Organization</th>
                    <th>Project</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><?= htmlspecialchars($issue['id']) ?></td>
                        <td><?= htmlspecialchars($issue['short_description']) ?></td>
                        <td><?= htmlspecialchars($issue['open_date']) ?></td>
                        <td><?= htmlspecialchars($issue['priority']) ?></td>
                        <td><?= htmlspecialchars($issue['org']) ?></td>
                        <td><?= htmlspecialchars($issue['project']) ?></td>
                        <td>
                            <button type="button" onclick="openModal('readModal-<?= $issue['id'] ?>')">Read</button>
                            <button type="button" onclick="openModal('updateModal-<?= $issue['id'] ?>')">Update</button>
                            <button type="button" onclick="openModal('deleteModal-<?= $issue['id'] ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Issue</h2>
            <form action="issues_list.php" method="POST">
                <input type="text" name="short_description" placeholder="Short Description" required>
                <textarea name="long_description" placeholder="Long Description" required></textarea>
                <input type="date" name="open_date" required>
                <input type="text" name="priority" placeholder="Priority" required>
                <input type="text" name="org" placeholder="Organization" required>
                <input type="text" name="project" placeholder="Project" required>
                <select name="per_id" required>
                    <option value="">Select Person</option>
                    <?php foreach ($persons as $person): ?>
                        <option value="<?= $person['id'] ?>"><?= htmlspecialchars($person['fname'] . ' ' . $person['lname']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_issue">Save Issue</button>
                <button type="button" onclick="closeModal('addModal')">Cancel</button>
            </form>
        </div>
    </div>
    <?php foreach ($issues as $issue): ?>
        <div id="readModal-<?= $issue['id'] ?>" class="modal">
            <div class="modal-content">
                <h2>Issue Details</h2>
                <p><strong>Posted By:</strong> <?= htmlspecialchars($issue['per_id']) ?></p>
                <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']) ?></p>
                <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']) ?></p>
                <p><strong>Open Date:</strong> <?= htmlspecialchars($issue['open_date']) ?></p>
                <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']) ?></p>
                <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']) ?></p>
                <button type="button" onclick="closeModal('readModal-<?= $issue['id'] ?>')">Close</button>
            </div>
        </div>
        <div id="updateModal-<?= $issue['id'] ?>" class="modal">
            <div class="modal-content">
                <h2>Update Issue</h2>
                <form action="issues_list.php" method="POST">
                    <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                    <input type="text" name="short_description" value="<?= htmlspecialchars($issue['short_description']) ?>" required>
                    <textarea name="long_description" required><?= htmlspecialchars($issue['long_description']) ?></textarea>
                    <input type="date" name="open_date" value="<?= $issue['open_date'] ?>" required>
                    <input type="text" name="priority" value="<?= htmlspecialchars($issue['priority']) ?>" required>
                    <input type="text" name="org" value="<?= htmlspecialchars($issue['org']) ?>" required>
                    <input type="text" name="project" value="<?= htmlspecialchars($issue['project']) ?>" required>
                    <button type="submit" name="update_issue">Update Issue</button>
                    <button type="button" onclick="closeModal('updateModal-<?= $issue['id'] ?>')">Cancel</button>
                </form>
            </div>
        </div>
        <div id="deleteModal-<?= $issue['id'] ?>" class="modal">
            <div class="modal-content">
                <h2>Confirm Deletion</h2>
                <p>Are you sure you want to delete this issue?</p>
                <form action="issues_list.php" method="POST">
                    <input type="hidden" name="delete_id" value="<?= $issue['id'] ?>">
                    <button type="submit">Delete</button>
                    <button type="button" onclick="closeModal('deleteModal-<?= $issue['id'] ?>')">Cancel</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>
