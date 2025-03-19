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

// Fetch all people into an associative array
$persons = [];
if ($stmt_persons->rowCount() > 0) {
    while ($row = $stmt_persons->fetch(PDO::FETCH_ASSOC)) {
        $persons[] = $row;
    }
}

// Query to fetch all issues sorted by project name
$sql = "SELECT * FROM iss_issues ORDER BY project ASC";
$stmt = $conn->query($sql);

// Check if there are any issues using rowCount() for PDO
$issues = [];
if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $issues[] = $row;
    }
}

// Handle Add Issue Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_issue'])) {
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = $_POST['open_date'];
    $close_date = "00-00-0000";  // Default close date
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $per_id = $_POST['per_id'];

    // Prepare SQL to insert the new issue into the database
    $sql = "INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id) 
            VALUES (:short_description, :long_description, :open_date, :close_date, :priority, :org, :project, :per_id)";
    $stmt = $conn->prepare($sql);

    // Bind parameters and execute the statement
    $stmt->bindParam(':short_description', $short_description);
    $stmt->bindParam(':long_description', $long_description);
    $stmt->bindParam(':open_date', $open_date);
    $stmt->bindParam(':close_date', $close_date);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':org', $org);
    $stmt->bindParam(':project', $project);
    $stmt->bindParam(':per_id', $per_id);

    // Execute the query
    if ($stmt->execute()) {
        header("Location: issues_list.php?modal=add");
        exit();
    } else {
        $error_message = "Failed to add issue. Please try again.";
    }
}

// Handle Update Issue Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_issue'])) {
    $issue_id = $_POST['issue_id'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $per_id = $_POST['per_id'];

    // Prepare SQL to update the issue in the database
    $sql = "UPDATE iss_issues 
            SET short_description = :short_description, long_description = :long_description, open_date = :open_date, 
                close_date = :close_date, priority = :priority, org = :org, project = :project, per_id = :per_id 
            WHERE id = :issue_id";
    $stmt = $conn->prepare($sql);

    // Bind parameters and execute the statement
    $stmt->bindParam(':short_description', $short_description);
    $stmt->bindParam(':long_description', $long_description);
    $stmt->bindParam(':open_date', $open_date);
    $stmt->bindParam(':close_date', $close_date);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':org', $org);
    $stmt->bindParam(':project', $project);
    $stmt->bindParam(':per_id', $per_id);
    $stmt->bindParam(':issue_id', $issue_id);

    // Execute the query
    if ($stmt->execute()) {
        header("Location: issues_list.php?modal=update&id=$issue_id");
        exit();
    } else {
        $error_message = "Failed to update issue. Please try again.";
    }
}

// Handle Delete Issue
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $sql = "DELETE FROM iss_issues WHERE id = :delete_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':delete_id', $delete_id);

    // Execute the delete query
    if ($stmt->execute()) {
        header("Location: issues_list.php?modal=delete&id=$delete_id");
        exit();
    } else {
        $error_message = "Failed to delete issue. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues List</title>
    <link rel="stylesheet" href="styles/issues_list.css">
    <script>
        function toggleModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal.style.display === "block") {
                modal.style.display = "none";
            } else {
                modal.style.display = "block";
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Issues List</h1>

        <!-- Add New Issue Button -->
        <button type="button" class="btn" onclick="toggleModal('addModal')">+</button>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Short Description</th>
                    <th>Open Date</th>
                    <th>Close Date</th>
                    <th>Priority</th>
                    <th>Organization</th>
                    <th>Project</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($issues) > 0): ?>
                    <?php foreach ($issues as $issue): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($issue['id']); ?></td>
                            <td><?php echo htmlspecialchars($issue['short_description']); ?></td>
                            <td><?php echo htmlspecialchars($issue['open_date']); ?></td>
                            <td><?php echo htmlspecialchars($issue['close_date']); ?></td>
                            <td><?php echo htmlspecialchars($issue['priority']); ?></td>
                            <td><?php echo htmlspecialchars($issue['org']); ?></td>
                            <td><?php echo htmlspecialchars($issue['project']); ?></td>
                            <td>
                                <!-- Form for Read, Update, Delete actions -->
                                <form action="issues_list.php" method="POST">
                                    <button type="submit" name="read_modal" value="<?php echo $issue['id']; ?>">Read</button>
                                    <button type="submit" name="update_modal" value="<?php echo $issue['id']; ?>">Update</button>
                                    <button type="submit" name="delete_modal" value="<?php echo $issue['id']; ?>">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No issues found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add New Issue Modal -->
    <div id="addModal" class="modal" style="display: none;">
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
                    <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['fname'] . ' ' . $person['lname']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add_issue">Save Issue</button>
            <a href="javascript:void(0)" onclick="toggleModal('addModal')">Cancel</a>
        </form>
    </div>
</body>
</html>
