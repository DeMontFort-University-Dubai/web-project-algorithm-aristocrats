<?php
$host = 'localhost';
$db = 'registrationform';
$user = 'root';
$pass = '';

try {
    $dsn = "mysql:host=$host;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];

    $sql = "INSERT INTO programmes (name, description, category) VALUES (:name, :description, :category)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':category', $category);

    if ($stmt->execute()) {
        // Show pop-up and redirect to admindashboard.php
        $successMessage = "Programme added successfully! Check the frontend page and admin dashboard to see the changes.";
        echo "<script>
                alert('$successMessage');
                window.location.href = 'admindashboard.php?message=" . urlencode($successMessage) . "';
              </script>";
        exit();
    } else {
        echo "Error: Unable to add programme.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Add Programme</title>
</head>
<body>
    <a href="admindashboard.php" class="go-back-link">← Go Back</a>

    <div class="add-programme-container">
        <h2>Add Programme</h2>
        <form method="post">
            <div class="form-group">
                <label for="name">Programme Name:</label><br>
                <input type="text" id="name" name="name" required><br><br>
            </div>

            <div class="form-group">
                <label for="description">Description:</label><br>
                <textarea id="description" name="description" required></textarea><br><br>
            </div>

            <div class="form-group">
                <label for="category">Category:</label><br>
                <select id="category" name="category" required>
                    <option value="undergraduate">Undergraduate</option>
                    <option value="postgraduate">Postgraduate</option>
                </select><br><br>
            </div>

            <button type="submit">Add Programme</button>
        </form>
    </div>
</body>
</html>