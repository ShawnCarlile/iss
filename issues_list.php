<?php
// Start session to verify if the user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Include database connection
require '../database/db_connection.php';

// Enable error reporting to catch any issues during execution
error_reporting(E_ALL);
ini_set('display_errors', 1);

$sql = "SELECT * FROM iss_persons";
$stmt = $conn->prepare($sql);
$stmt->execute();
$persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT * FROM iss_issues";
$stmt = $conn->prepare($sql);
$stmt->execute();
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add new issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_issue'])) {
    //echo "this a test of file uploads"; print_r($_FILES); exit(); //checkpoint
    
    if(isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] === UPLOAD_ERR_OK){
        echo "True";
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileSize = $_FILES['pdf_attachment']['size'];
        $fileType = $_FILES['pdf_attachment']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        if($fileExtension !== 'pdf') {
            die("Only PDF files are allowed!"); //die kills whole program
        }
        if($fileSize > 2 * 1024 * 1024) {
            die("File size exceeds 2MB limit.");
        }

        $newFileName = md5(time() . $fileName) . ',' . $fileExtension;
        $uploadFileDir = './uploads/'; //creates an upload file folder where the program is
        $dest_path = $uploadFileDir . $newFileName;

        // if uploads directory does not exist, create it
        if(!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        if(move_uploaded_file($fileTmpPath, $dest_path)) {
            $attachmentPath = $dest_path;
        } else{
            die("Error moving the uploaded file.");
        }
    } //end pdf attachment
    
    else { 
        $attachmentPath = null;
    }


    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = $_POST['open_date'];
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $per_id = $_POST['per_id'];
    $close_date = '0000-00-00'; // Default until updated
    // $newFileName is PDF attachment
    // $attachmentPath is the entire path

    $sql = "INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id, pdf_attachment) 
        VALUES (:short_description, :long_description, :open_date, :close_date, :priority, :org, :project, :per_id, :pdf_attachment)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':short_description' => $short_description,
        ':long_description' => $long_description,
        ':open_date' => $open_date,
        ':close_date' => $close_date,
        ':priority' => $priority,
        ':org' => $org,
        ':project' => $project,
        ':per_id' => $per_id,
        ':pdf_attachment' => $newFileName
    ]);

    // Refresh the issues list after inserting new issue
    $sql = "SELECT iss_issues.*, iss_persons.fname, iss_persons.lname 
    FROM iss_issues 
    LEFT JOIN iss_persons ON iss_issues.per_id = iss_persons.id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header("Location: issues_list.php");
    exit();
}

// Update issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_issue']) && !(isset($_POST['reopen']) && $_POST['reopen'] === 'yes')) {
    $id = $_POST['issue_id'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'] ?: '0000-00-00'; // Default to '0000-00-00' if empty
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $per_id = $_POST['per_id'];

    try {
        $sql = "UPDATE iss_issues 
                SET short_description = :short_description, long_description = :long_description, open_date = :open_date, 
                    close_date = :close_date, priority = :priority, org = :org, project = :project, per_id = :per_id 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':short_description' => $short_description,
            ':long_description' => $long_description,
            ':open_date' => $open_date,
            ':close_date' => $close_date,
            ':priority' => $priority,
            ':org' => $org,
            ':project' => $project,
            ':per_id' => $per_id
        ]);

        // Refresh the issues list after updating issue
        header("Location: issues_list.php");
        exit();
    } catch (PDOException $e) {
        echo "Error updating issue: " . $e->getMessage();
    }
}
else if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_issue']) && isset($_POST['reopen']) && $_POST['reopen'] === 'yes'){
    $id = $_POST['issue_id'];
    $close_date = '0000-00-00';
    
    try {
        $sql = "UPDATE iss_issues 
                SET close_date = :close_date 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':close_date' => $close_date,
        ]);

        // Refresh the issues list after updating issue
        header("Location: issues_list.php");
        exit();
    } catch (PDOException $e) {
        echo "Error updating issue: " . $e->getMessage();
    }
}

// Delete issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_issue'])) {
    $id = $_POST['issue_id'];

    $sql = "DELETE FROM iss_issues WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);

    header("Location: issues_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues List</title>
    <link rel="stylesheet" href="styles/issues_list.css">
</head>
<body>
    <div class="container">
        <h1>Issues List</h1>
        <button type="button" class="btn" onclick="window.location.href='persons_list.php'">Go to Persons List</button>
        <button type="button" class="btn" onclick="openModal('addModal')">+</button>
        <button type="button" class="btnLogout" onclick="window.location.href='logout.php'">Logout</button>
        <table>
            <thead>
                <tr>
                    <th>Short Description</th>
                    <th>Open Date</th>
                    <th>Priority</th>
                    <th>Organization</th>
                    <th>Status</th>
                    <th>Project</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><?= htmlspecialchars($issue['short_description']) ?></td>
                        <td><?= htmlspecialchars($issue['open_date']) ?></td>
                        <td><?= htmlspecialchars($issue['priority']) ?></td>
                        <td><?= htmlspecialchars($issue['org']) ?></td>
                        <?php 
                            $status = "Open";

                            if(htmlspecialchars($issue['close_date']) != '0000-00-00'){
                                $status = "Closed";
                            }
                            
                            echo "<td>$status</td>";
                        ?>
                        <td><?= htmlspecialchars($issue['project']) ?></td>
                        <td>
                            <button type="button" onclick="openModal('readModal-<?= $issue['id'] ?>')">Read</button>
                            <?php if($_SESSION['user_id'] == $issue['per_id'] || $_SESSION['admin'] == "Y") { ?>
                            
                                <!-- displays read and delete buttons if user wrote issue or is an admin -->

                            <button type="button" onclick="openModal('updateModal-<?= $issue['id'] ?>')">Update</button>
                            <button type="button" onclick="openModal('deleteModal-<?= $issue['id'] ?>')">Delete</button>

                            <?php } ?>

                            <button type="button" onclick="window.location.href='comments_list.php?issue_id=<?= $issue['id'] ?>'">Comments</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for adding an issue -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2>Add Issue</h2>
            <form action="issues_list.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="short_description" placeholder="Short Description" required>
                <textarea name="long_description" placeholder="Long Description"></textarea>
                <input type="date" name="open_date" required>
                <input type="text" name="priority" placeholder="Priority" required>
                <input type="text" name="org" placeholder="Organization" required>
                <input type="text" name="project" placeholder="Project" required>
                <select name="per_id" required>
                    <?php foreach ($persons as $person): ?>
                        <option value="<?= htmlspecialchars($person['id']) ?>"><?= htmlspecialchars($person['fname'] . ' ' . $person['lname']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label for="pdf_attachment">PDF<label>
                <input type="file" name="pdf_attachment" accept="application/pdf">
                
                <button type="submit" name="add_issue">Add Issue</button>
            </form>
        </div>
    </div>

    <!-- Read Modal for each issue -->
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
    </div>

    <div id="updateModal-<?= $issue['id'] ?>" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('updateModal-<?= $issue['id'] ?>')">&times;</span>

            <h2>Update Issue</h2>
            
            <!-- Display the issue details that are going to be updated -->
            <div class="update-description">
                <h3>Current Details</h3>
                <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']) ?></p>
                <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']) ?></p>
                <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']) ?></p>
                <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']) ?></p>
                <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']) ?></p>
                <p><strong>Assigned Person:</strong> <?= htmlspecialchars($persons[$issue['per_id'] - 1]['fname']) . ' ' . htmlspecialchars($persons[$issue['per_id'] - 1]['lname']) ?></p>
                <p><strong>Open Date:</strong> <?= $issue['open_date'] ?></p>
                <?php 
            
                if($status == 'Closed'){
                    echo "<p><strong>Close Date:</strong>" . htmlspecialchars($issue['close_date']) . "</p>";
                }
                else{
                    echo "<p><strong>Status: </strong>Open</p>";
                }

                ?>
            </div>

            <?php
            $status = ($issue['close_date'] == '0000-00-00') ? 'Open' : 'Closed';
            ?>

            <form action="issues_list.php" method="POST" enctype="multipart/form-data">

                <?php if($_SESSION['user_id'] == $issue['per_id'] || $_SESSION['admin'] == "Y") { ?> 

                <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                
                <?php if($status == 'Open') { 
                    //will display edit issue boxes if status is open
                    //will not display if status is closed to prevent user from editingclosed issues
                ?>
                
                <label for="short_description">Short Description:</label>
                <input type="text" name="short_description" value="<?= htmlspecialchars($issue['short_description']) ?>" required>
                
                <label for="long_description">Long Description:</label>
                <textarea name="long_description"><?= htmlspecialchars($issue['long_description']) ?></textarea>
                
                <label for="open_date">Open Date:</label>
                <input type="date" name="open_date" value="<?= $issue['open_date'] ?>" required>

                <label for="close_date">Close Date:</label>
                <input type="date" name="close_date" value="<?= $issue['close_date'] ?>">

                <label for="priority">Priority:</label>
                <input type="text" name="priority" value="<?= htmlspecialchars($issue['priority']) ?>" required>
                
                <label for="org">Organization:</label>
                <input type="text" name="org" value="<?= htmlspecialchars($issue['org']) ?>" required>
                
                <label for="project">Project:</label>
                <input type="text" name="project" value="<?= htmlspecialchars($issue['project']) ?>" required>
                
                <label for="per_id">Assigned Person:</label>
                <select name="per_id" required>
                    <?php foreach ($persons as $person): ?>
                        <option value="<?= htmlspecialchars($person['id']) ?>" <?= $person['id'] == $issue['per_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($person['fname'] . ' ' . $person['lname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="pdf_attachment">PDF<label>
                <input type="file" name="pdf_attachment" accept="application/pdf">
                
                

                <?php } else { //displayed if issue is closed upon clicked update ?>
                    
                    <label>Reopen this issue?</label>
                    <select name="reopen">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>

                <?php } ?>

                <button type="submit" name="update_issue">Update Issue</button>

                <?php } else{ ?>
                    <button name="update_issue" onclick="window.location.href='logout.php'">Update Issue</button>
                <?php } ?>
            </form>
        </div>
    </div>


        <!-- Modal for deleting an issue -->
        <div id="deleteModal-<?= $issue['id'] ?>" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('deleteModal-<?= $issue['id'] ?>')">&times;</span>

                

                <h2>Delete Issue</h2>
                <p>Are you sure you want to delete this issue?</p>
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
                <p><strong>Assigned Person:</strong> <?= htmlspecialchars($persons[$issue['per_id'] - 1]['fname']) . ' ' . htmlspecialchars($persons[$issue['per_id'] - 1]['lname']) ?></p>

                <?php if($_SESSION['user_id'] == $issue['per_id'] || $_SESSION['admin'] == "Y") { ?> 

                <form action="issues_list.php" method="POST">
                    <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                    <button type="submit" name="delete_issue">Delete</button>
                    <button type="button" onclick="closeModal('deleteModal-<?= $issue['id'] ?>')">Cancel</button>
                </form>

                <?php } else{ ?>
                <form action="logout.php">
                    <button type="submit" name="delete_issue">Delete</button>
                    <button type="button" onclick="window.location.href='logout.php'">Cancel</button>
                </form>
                <?php } ?>
            </div>
        </div>
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
