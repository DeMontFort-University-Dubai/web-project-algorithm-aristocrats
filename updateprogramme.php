<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: adminlogin.html');
    exit();
}

// Database connection
$host = 'localhost';
$db = 'registrationform';
$user = 'root';
$pass = '';

try {
    $dsn = "mysql:host=$host;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch existing programmes for dropdown
    $programmeQuery = $pdo->query("SELECT * FROM programmes");
    $programmes = $programmeQuery->fetchAll(PDO::FETCH_ASSOC);

    $selected_programme = null;
    $error = '';

    // Handle programme selection to pre-fill the form
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['programme_id']) && !isset($_POST['update'])) {
        $programme_id = $_POST['programme_id'];
        $stmt = $pdo->prepare("SELECT * FROM programmes WHERE programme_id = :programme_id");
        $stmt->bindParam(':programme_id', $programme_id, PDO::PARAM_INT);
        $stmt->execute();
        $selected_programme = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$selected_programme) {
            $error = "Selected programme not found.";
        }
    }

    // Handle form submission to update the programme
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
        $programme_id = $_POST['programme_id'];
        $programme_name = trim($_POST['name']);
        $programme_description = trim($_POST['description']);
        $category = $_POST['category'];

        // Validate inputs
        if (empty($programme_name) || empty($programme_description) || !in_array($category, ['undergraduate', 'postgraduate'])) {
            $error = "Invalid input. Please fill all fields correctly.";
        } else {
            // Verify the programme_id exists
            $stmt = $pdo->prepare("SELECT * FROM programmes WHERE programme_id = :programme_id");
            $stmt->bindParam(':programme_id', $programme_id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                $error = "Invalid programme ID.";
            } else {
                // Handle image upload
                $programme_image = null;
                $target = null;
                if (!empty($_FILES['image']['name'])) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    $file_type = $_FILES['image']['type'];
                    $file_size = $_FILES['image']['size'];

                    if (!in_array($file_type, $allowed_types)) {
                        $error = "Invalid image type. Only JPEG, PNG, and GIF are allowed.";
                    } elseif ($file_size > $max_size) {
                        $error = "Image size exceeds 5MB limit.";
                    } else {
                        $programme_image = $_FILES['image']['name'];
                        $target = "images/" . basename($programme_image);
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                            $error = "Failed to upload image.";
                            $programme_image = null;
                        }
                    }
                }

                if (empty($error)) {
                    // SQL query to update the programme
                    $sql = "UPDATE programmes SET name = :name, description = :description, category = :category" . ($programme_image ? ", image = :image" : "") . " WHERE programme_id = :programme_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':name', $programme_name, PDO::PARAM_STR);
                    $stmt->bindParam(':description', $programme_description, PDO::PARAM_STR);
                    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
                    if ($programme_image) {
                        $stmt->bindParam(':image', $programme_image, PDO::PARAM_STR);
                    }
                    $stmt->bindParam(':programme_id', $programme_id, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $successMessage = "Programme updated successfully! Check the frontend page and admin dashboard to see the changes.";
                        echo "<script>
                                alert('$successMessage');
                                window.location.href = 'admindashboard.php?message=" . urlencode($successMessage) . "';
                              </script>";
                        exit();
                    } else {
                        $error = "Error: Unable to update programme.";
                    }
                }
            }
        }
    }

} catch (PDOException $e) {
    $error = "Connection failed: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Update Programme</title>
</head>
<body>
<!-- Go back link in the top right -->
<a href="admindashboard.php" class="go-back-link">← Go Back</a>
<div class="update-programme-wrapper">
    <h2>Update Programme</h2>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label for="programme_id">Select Programme:</label><br>
        <select id="programme_id" name="programme_id" required onchange="this.form.submit()">
            <option value="" disabled <?php echo !$selected_programme ? 'selected' : ''; ?>>Select a Programme</option>
            <?php foreach ($programmes as $programme): ?>
                <option value="<?php echo $programme['programme_id']; ?>" <?php echo $selected_programme && $selected_programme['programme_id'] == $programme['programme_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($programme['name'] . ' - ' . ($programme['category'] === 'undergraduate' ? 'UG' : 'PG')); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <?php if ($selected_programme): ?>
            <label for="name">Programme Name:</label><br>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($selected_programme['name']); ?>" required><br><br>

            <label for="description">Programme Description:</label><br>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($selected_programme['description']); ?></textarea><br><br>

            <label for="category">Category:</label><br>
            <select id="category" name="category" required>
                <option value="undergraduate" <?php echo $selected_programme['category'] === 'undergraduate' ? 'selected' : ''; ?>>Undergraduate (UG)</option>
                <option value="postgraduate" <?php echo $selected_programme['category'] === 'postgraduate' ? 'selected' : ''; ?>>Postgraduate (PG)</option>
            </select><br><br>

            <label for="current_image">Current Image:</label><br>
            <?php if (!empty($selected_programme['image'])): ?>
                <img src="images/<?php echo htmlspecialchars($selected_programme['image']); ?>" alt="Current Image" style="max-width: 200px;"><br><br>
            <?php else: ?>
                <p>No image uploaded.</p><br>
            <?php endif; ?>

            <label for="image">Upload New Image (optional):</label><br>
            <input type="file" id="image" name="image"><br><br>

            <input type="hidden" name="programme_id" value="<?php echo htmlspecialchars($selected_programme['programme_id']); ?>">
            <button type="submit" name="update">Update Programme</button>
        <?php endif; ?>
    </form>
</div>
</body>
</html>