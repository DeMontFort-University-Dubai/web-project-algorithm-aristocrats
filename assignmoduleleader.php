<?php
// Include database connection
$host = 'localhost';
$db = 'registrationform';
$user = 'root';
$pass = '';

try {
    $dsn = "mysql:host=$host;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . htmlspecialchars($e->getMessage()));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $module_id = $_POST['module_id'];
    $faculty_id = $_POST['faculty_id'];

    // Debugging: Log the received values
    error_log("Received module_id: $module_id, faculty_id: $faculty_id");

    // SQL query to update module leader
    $sql = "UPDATE modules SET faculty_id = :faculty_id WHERE module_id = :module_id";
    
    // Prepare statement
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
    $stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
    
    // Execute the query
    if ($stmt->execute()) {
        error_log("Module leader assigned successfully for module_id: $module_id");
        $successMessage = "Module leader assigned successfully! The change is reflected in the database and admin dashboard.";
        echo "<script>
                alert('$successMessage');
                window.location.href = 'admindashboard.php?message=" . urlencode($successMessage) . "';
              </script>";
        exit();
    } else {
        error_log("Failed to assign module leader for module_id: $module_id");
        $errorMessage = "Unable to assign module leader.";
        echo "<script>
                alert('$errorMessage');
                window.location.href = 'admindashboard.php?message=" . urlencode($errorMessage) . "';
              </script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Assign/Update Module Leader</title>
</head>
<body>
<!-- Go back link in the top right -->
<a href="admindashboard.php" class="go-back-link">← Go Back</a>
<div class="assign-module-wrapper">
    <h2>Assign/Update Module Leader</h2>
    <form method="post" onsubmit="return confirm('Are you sure you want to assign this module leader?');">
        <label for="module_id">Select Module:</label><br>
        <select id="module_id" name="module_id" required>
            <option value="" disabled selected>Select a Module</option>
            <?php
            // Fetching modules from the database to populate the dropdown
            $stmt = $pdo->query("SELECT m.*, p.name AS programme_name, p.category FROM modules m JOIN programmes p ON m.programme_id = p.programme_id");
            if ($stmt->rowCount() == 0) {
                echo "<option value='' disabled>No modules found</option>";
            }
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='" . $row['module_id'] . "'>" . htmlspecialchars($row['module_name'] . ' (Year ' . $row['year'] . ', Block ' . $row['block'] . ', ' . $row['programme_name'] . ' - ' . ($row['category'] === 'undergraduate' ? 'UG' : 'PG') . ')') . "</option>";
            }
            ?>
        </select><br><br>

        <label for="faculty_id">Select Faculty Member:</label><br>
        <select id="faculty_id" name="faculty_id" required>
            <option value="" disabled selected>Select Faculty</option>
            <?php
            // Fetching faculty members from the database to populate the dropdown
            $stmt = $pdo->query("SELECT faculty_id, name FROM faculty");
            if ($stmt->rowCount() == 0) {
                echo "<option value='' disabled>No faculty found</option>";
            }
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='" . $row['faculty_id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
            }
            ?>
        </select><br><br>

        <button type="submit">Assign Module Leader</button>
    </form>
</div>
</body>
</html>