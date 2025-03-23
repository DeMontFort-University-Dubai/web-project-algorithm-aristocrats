<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: adminlogin.php');
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

    // Fetch programmes for the dropdown
    $stmt = $pdo->query("SELECT programme_id, name, category FROM programmes");
    $programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $error = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $programme_id = $_POST['programme_id'];
        $year = $_POST['year'];
        $block = $_POST['block'];
        $module_name = trim($_POST['module_name']);

        // Fetch the selected programme's category
        $stmt = $pdo->prepare("SELECT category FROM programmes WHERE programme_id = :programme_id");
        $stmt->bindParam(':programme_id', $programme_id, PDO::PARAM_INT);
        $stmt->execute();
        $programme = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$programme) {
            $error = "Invalid programme selected.";
        } else {
            $category = $programme['category'];

            // Validate inputs
            if (empty($module_name)) {
                $error = "Module name is required.";
            } elseif ($category === 'undergraduate' && ($year < 1 || $year > 3)) {
                $error = "Year must be between 1 and 3 for undergraduate programmes.";
            } elseif ($category === 'postgraduate' && $year != 1) {
                $error = "Year must be 1 for postgraduate programmes.";
            } elseif ($category === 'undergraduate' && $block === '5 & 6') {
                $error = "Block 5 & 6 is only available for postgraduate programmes.";
            } elseif ($category === 'postgraduate' && !in_array($block, ['1', '2', '3', '4', '5 & 6'])) {
                $error = "Invalid block for postgraduate programme.";
            } elseif ($category === 'undergraduate' && !in_array($block, ['1', '2', '3', '4'])) {
                $error = "Invalid block for undergraduate programme.";
            } else {
                // Insert the module
                $sql = "INSERT INTO modules (programme_id, year, block, module_name) VALUES (:programme_id, :year, :block, :module_name)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':programme_id', $programme_id, PDO::PARAM_INT);
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                $stmt->bindParam(':block', $block, PDO::PARAM_STR);
                $stmt->bindParam(':module_name', $module_name, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    // Show pop-up and redirect
                    $successMessage = "Module added successfully! The change is reflected in the database and admin dashboard.";
                    echo "<script>
                            alert('$successMessage');
                            window.location.href = 'admindashboard.php?message=" . urlencode($successMessage) . "';
                          </script>";
                    exit();
                } else {
                    $error = "Error: Unable to add module.";
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
    <title>Add Module</title>
    <script>
        function updateBlockOptions() {
            const programmeSelect = document.getElementById('programme_id');
            const blockSelect = document.getElementById('block');
            const selectedProgrammeId = programmeSelect.value;

            // Fetch the category of the selected programme
            const programmes = <?php echo json_encode($programmes); ?>;
            const selectedProgramme = programmes.find(p => p.programme_id == selectedProgrammeId);
            const category = selectedProgramme ? selectedProgramme.category : '';

            // Update block options based on category
            blockSelect.innerHTML = '<option value="" disabled selected>Select a Block</option>';
            const blocks = category === 'undergraduate' ? ['1', '2', '3', '4'] : ['1', '2', '3', '4', '5 & 6'];
            blocks.forEach(block => {
                const option = document.createElement('option');
                option.value = block;
                option.text = 'Block ' + block;
                blockSelect.appendChild(option);
            });
        }
    </script>
</head>
<body>
    <a href="admindashboard.php" class="go-back-link">← Go Back</a>

    <div class="form-wrapper">
        <h2>Add Module</h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="post">
            <label for="programme_id">Select Programme:</label><br>
            <select id="programme_id" name="programme_id" required onchange="updateBlockOptions()">
                <option value="" disabled selected>Select a Programme</option>
                <?php foreach ($programmes as $row): ?>
                    <option value="<?php echo $row['programme_id']; ?>">
                        <?php echo htmlspecialchars($row['name'] . ' - ' . ($row['category'] === 'undergraduate' ? 'UG' : 'PG')); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="year">Year:</label><br>
            <input type="number" id="year" name="year" min="1" max="3" value="<?php echo isset($_POST['year']) ? htmlspecialchars($_POST['year']) : ''; ?>" required><br><br>

            <label for="block">Block:</label><br>
            <select id="block" name="block" required>
                <option value="" disabled selected>Select a Block</option>
            </select><br><br>

            <label for="module_name">Module Name:</label><br>
            <input type="text" id="module_name" name="module_name" value="<?php echo isset($_POST['module_name']) ? htmlspecialchars($_POST['module_name']) : ''; ?>" required><br><br>

            <button type="submit">Add Module</button>
        </form>
    </div>
</body>
</html>