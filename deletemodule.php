<?php
// DATABASE CONNECTION
$host = 'localhost';
$db = 'registrationform';
$user = 'root';
$pass = '';

try {
    $dsn = "mysql:host=$host;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . htmlspecialchars($e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    // Get module_id to delete
    $module_id = $_POST['module_id'];

    // SQL query to delete the module
    $sql = "DELETE FROM modules WHERE module_id = :module_id";
    
    // Prepare and execute query
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $successMessage = "Module deleted successfully! The change is reflected in the database and admin dashboard.";
        echo "<script>
                alert('$successMessage');
                window.location.href = 'admindashboard.php?message=" . urlencode($successMessage) . "';
              </script>";
        exit();
    } else {
        $errorMessage = "Error: Unable to delete module.";
        echo "<script>
                alert('$errorMessage');
                window.location.href = 'admindashboard.php?message=" . urlencode($errorMessage) . "';
              </script>";
        exit();
    }
}

// Fetch existing modules for deletion
$modulesQuery = $pdo->query("SELECT m.*, p.name AS programme_name, p.category FROM modules m JOIN programmes p ON m.programme_id = p.programme_id");
$modules = $modulesQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Delete Module</title>
</head>
<body>
<!-- Go back link in the top right -->
<a href="admindashboard.php" class="go-back-link">← Go Back</a>
<div class="delete-module-wrapper">
    <h2>Delete Module</h2>

    <form method="post">
        <label for="module_id">Select Module to Delete:</label><br>
        <select id="module_id" name="module_id" required>
            <option value="" disabled selected>Select a Module</option>
            <?php foreach ($modules as $module): ?>
                <option value="<?= $module['module_id'] ?>">
                    <?= htmlspecialchars($module['module_name'] . ' (Year ' . $module['year'] . ', Block ' . $module['block'] . ', ' . $module['programme_name'] . ' - ' . ($module['category'] === 'undergraduate' ? 'UG' : 'PG') . ')') ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit" name="delete">Delete Module</button>
    </form>
</div>
</body>
</html>