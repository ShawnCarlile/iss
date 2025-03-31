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

// Handle Add Person
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_person'])) {
    // Sanitize input data
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $pwd_hash = $_POST['pwd_hash'];  // Assuming you're hashing passwords before storing
    $admin = $_POST['admin'];

    // Hash the password before storing
    $pwd_salt = bin2hex(random_bytes(32)); // Generating a salt
    $pwd_hash = md5($pwd_salt . $pwd_hash); // Using a hash and salt for secure storage

    $sql = "INSERT INTO iss_persons (fname, lname, mobile, email, pwd_hash, pwd_salt, admin)
            VALUES (:fname, :lname, :mobile, :email, :pwd_hash, :pwd_salt, :admin)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':fname' => $fname,
        ':lname' => $lname,
        ':mobile' => $mobile,
        ':email' => $email,
        ':pwd_hash' => $pwd_hash,
        ':pwd_salt' => $pwd_salt,
        ':admin' => $admin
    ]);

    // Redirect to refresh the list
    header("Location: persons_list.php");
    exit();
}

// Handle Update Person
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_person'])) {
    // Sanitize input data
    $person_id = $_POST['person_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $admin = $_POST['admin'];

    // Prepare SQL to update the person details
    $sql = "UPDATE iss_persons
            SET fname = :fname, lname = :lname, mobile = :mobile, email = :email, admin = :admin
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id' => $person_id,
        ':fname' => $fname,
        ':lname' => $lname,
        ':mobile' => $mobile,
        ':email' => $email,
        ':admin' => $admin
    ]);

    // Redirect to refresh the list
    header("Location: persons_list.php");
    exit();
}

// Handle Delete Person
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_person'])) {
    // Get person ID from the form
    $person_id = $_POST['person_id'];

    // Prepare and execute the deletion SQL
    $sql = "DELETE FROM iss_persons WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $person_id]);

    // Redirect to refresh the list
    header("Location: persons_list.php");
    exit();
}

// Retrieve all persons from the database
$sql = "SELECT * FROM iss_persons";
$stmt = $conn->prepare($sql);
$stmt->execute();
$persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persons List</title>
    <link rel="stylesheet" href="styles/persons_list.css">
</head>
<body>
    <div class="container">
        <h1>Persons List</h1>
        <button type="button" class="btn" onclick="window.location.href='issues_list.php'">Go to Issues List</button>
        <button type="button" class="btn" onclick="openModal('addModal')">Add Person</button>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Admin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($persons as $person): ?>
                    <tr>
                        <td><?= htmlspecialchars($person['id']) ?></td>
                        <td><?= htmlspecialchars($person['fname']) ?></td>
                        <td><?= htmlspecialchars($person['lname']) ?></td>
                        <td><?= htmlspecialchars($person['mobile']) ?></td>
                        <td><?= htmlspecialchars($person['email']) ?></td>
                        <td><?= htmlspecialchars($person['admin']) ?></td>
                        <td>
                            <button type="button" onclick="openModal('readModal-<?= $person['id'] ?>')">Read</button>
                            <button type="button" onclick="openModal('updateModal-<?= $person['id'] ?>')">Update</button>
                            <button type="button" onclick="openModal('deleteModal-<?= $person['id'] ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for adding a person -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2>Add Person</h2>
            <form action="persons_list.php" method="POST">
                <input type="text" name="fname" placeholder="First Name" required>
                <input type="text" name="lname" placeholder="Last Name" required>
                <input type="text" name="mobile" placeholder="Mobile" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="pwd_hash" placeholder="Password Hash" required>
                <input type="text" name="admin" placeholder="Admin (Yes/No)" required>
                <button type="submit" name="add_person">Add Person</button>
            </form>
        </div>
    </div>

    <!-- Read Modal for each person -->
    <?php foreach ($persons as $person): ?>
        <div class="modal" id="readModal-<?= $person['id'] ?>">
            <div class="modal-content">
                <span class="close" onclick="closeModal('readModal-<?= $person['id'] ?>')">&times;</span>
                <h2>Person Details</h2>
                <p><strong>ID:</strong> <?= htmlspecialchars($person['id']) ?></p>
                <p><strong>First Name:</strong> <?= htmlspecialchars($person['fname']) ?></p>
                <p><strong>Last Name:</strong> <?= htmlspecialchars($person['lname']) ?></p>
                <p><strong>Mobile:</strong> <?= htmlspecialchars($person['mobile']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($person['email']) ?></p>
                <p><strong>Admin:</strong> <?= htmlspecialchars($person['admin']) ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Modal for updating a person -->
    <?php foreach ($persons as $person): ?>
        <div id="updateModal-<?= $person['id'] ?>" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('updateModal-<?= $person['id'] ?>')">&times;</span>
                <h2>Update Person</h2>
                <form action="persons_list.php" method="POST">
                    <input type="hidden" name="person_id" value="<?= $person['id'] ?>">
                    <input type="text" name="fname" value="<?= htmlspecialchars($person['fname']) ?>" required>
                    <input type="text" name="lname" value="<?= htmlspecialchars($person['lname']) ?>" required>
                    <input type="text" name="mobile" value="<?= htmlspecialchars($person['mobile']) ?>" required>
                    <input type="email" name="email" value="<?= htmlspecialchars($person['email']) ?>" required>
                    <input type="text" name="admin" value="<?= htmlspecialchars($person['admin']) ?>" required>
                    <button type="submit" name="update_person">Update Person</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Modal for deleting a person -->
    <?php foreach ($persons as $person): ?>
        <div id="deleteModal-<?= $person['id'] ?>" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('deleteModal-<?= $person['id'] ?>')">&times;</span>
                <h2>Delete Person</h2>
                <p>Are you sure you want to delete this person?</p>
                <form action="persons_list.php" method="POST">
                    <input type="hidden" name="person_id" value="<?= $person['id'] ?>">
                    <button type="submit" name="delete_person">Delete</button>
                    <button type="button" onclick="closeModal('deleteModal-<?= $person['id'] ?>')">Cancel</button>
                </form>
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

        // Close the modal if the user clicks outside of the modal content
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
