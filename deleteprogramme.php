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
    die("Connection failed: " . htmlspecialchars($e->getMessage()));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $programme_id = $_POST['programme_id'];

    try {
        $sql = "DELETE FROM programmes WHERE programme_id = :programme_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':programme_id', $programme_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $successMessage = "Programme and related modules deleted successfully.";
            echo "<script>
                    alert('$successMessage');
                    window.location.href = 'admindashboard.php?message=" . urlencode($successMessage) . "';
                  </script>";
            exit();
        } else {
            throw new Exception("No rows affected. Programme might not exist.");
        }
    } catch (Exception $e) {
        $errorMessage = "Error deleting programme: " . htmlspecialchars($e->getMessage());
        echo "<script>
                alert('$errorMessage');
                window.location.href = 'admindashboard.php?message=" . urlencode($errorMessage) . "';
              </script>";
        exit();
    }
}

// Fetch existing programmes for deletion
$programmeQuery = $pdo->query("SELECT * FROM programmes");
$programmes = $programmeQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Delete Programme</title>
</head>
<body>
<a href="admindashboard.php" class="go-back-link">← Go Back</a>
<div class="delete-programme-wrapper">
    <h2>Delete Programme</h2>

    <form method="post">
        <label for="programme_id">Select Programme to Delete:</label><br>
        <select id="programme_id" name="programme_id" required>
            <option value="" disabled selected>Select a Programme</option>
            <?php foreach ($programmes as $programme): ?>
                <?php
                $categoryAbbr = ($programme['category'] === 'undergraduate') ? 'UG' : 'PG';
                $displayName = $programme['name'] . '-' . $categoryAbbr;
                ?>
                <option value="<?= $programme['programme_id'] ?>"><?= htmlspecialchars($displayName) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="submit" name="delete">Delete Programme</button>
    </form>
</div>
</body>
</html>