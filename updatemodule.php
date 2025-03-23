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

    // Fetch existing modules and programmes for dropdowns
    $modulesQuery = $pdo->query("SELECT m.*, p.name AS programme_name, p.category FROM modules m JOIN programmes p ON m.programme_id = p.programme_id");
    $modules = $modulesQuery->fetchAll(PDO::FETCH_ASSOC);

    $programmesQuery = $pdo->query("SELECT programme_id, name, category FROM programmes");
    $programmes = $programmesQuery->fetchAll(PDO::FETCH_ASSOC);

    $selected_module = null;
    $error = '';

    // Handle module selection to pre-fill the form
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['module_id']) && !isset($_POST['update'])) {
        $module_id = $_POST['module_id'];
        $stmt = $pdo->prepare("SELECT m.*, p.category AS programme_category FROM modules m JOIN programmes p ON m.programme_id = p.programme_id WHERE m.module_id = :module_id");
        $stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
        $stmt->execute();
        $selected_module = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$selected_module) {
            $error = "Selected module not found.";
        }
    }

    // Handle form submission to update the module
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
        $module_id = $_POST['module_id'];
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
                // Verify the module_id exists
                $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_id = :module_id");
                $stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                $stmt->execute();
                if (!$stmt->fetch()) {
                    $error = "Invalid module ID.";
                } else {
                    // Update the module
                    $sql = "UPDATE modules SET programme_id = :programme_id, year = :year, block = :block, module_name = :module_name WHERE module_id = :module_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':programme_id', $programme_id, PDO::PARAM_INT);
                    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                    $stmt->bindParam(':block', $block, PDO::PARAM_STR);
                    $stmt->bindParam(':module_name', $module_name, PDO::PARAM_STR);
                    $stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $successMessage = "Module updated successfully! The change is reflected in the database and admin dashboard.";
                        echo "<script>
                                alert('$successMessage');
                                window.location.href = 'admindashboard.php?message=" . urlencode($successMessage) . "';
                              </script>";
                        exit();
                    } else {
                        $error = "Error: Unable to update module.";
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
    <title>Update Module</title>
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
    
    <!-- Go back link in the top right -->
    <a href="admindashboard.php" class="go-back-link">← Go Back</a>
    <main id="main-content">
        <div class="update-module-wrapper">
            <h2>Update Module</h2>

            <?php if (!empty($error)): ?>
                <p style="color: red;" role="alert" aria-live="polite"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="post">
                <label for="module_id">Select Module:</label><br>
                <select id="module_id" name="module_id" required aria-describedby="module-help" onchange="this.form.submit()">
                    <option value="" disabled <?php echo !$selected_module ? 'selected' : ''; ?>>Select a Module</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?php echo $module['module_id']; ?>" <?php echo $selected_module && $selected_module['module_id'] == $module['module_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($module['module_name'] . ' (Year ' . $module['year'] . ', Block ' . $module['block'] . ', ' . $module['programme_name'] . ' - ' . ($module['category'] === 'undergraduate' ? 'UG' : 'PG') . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="module-help">Choose a module to edit its details below.</p><br><br>

                <?php if ($selected_module): ?>
                    <label for="programme_id">Select Programme:</label><br>
                    <select id="programme_id" name="programme_id" required onchange="updateBlockOptions()">
                        <option value="" disabled>Select a Programme</option>
                        <?php foreach ($programmes as $programme): ?>
                            <option value="<?php echo $programme['programme_id']; ?>" <?php echo $selected_module['programme_id'] == $programme['programme_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($programme['name'] . ' - ' . ($programme['category'] === 'undergraduate' ? 'UG' : 'PG')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label for="year">Year:</label><br>
                    <input type="number" id="year" name="year" min="1" max="3" value="<?php echo htmlspecialchars($selected_module['year']); ?>" required aria-describedby="year-help">
                    <p id="year-help">Enter the year (1-3 for UG, 1 for PG).</p><br><br>

                    <label for="block">Block:</label><br>
                    <select id="block" name="block" required>
                        <option value="" disabled>Select a Block</option>
                        <?php
                        $blocks = $selected_module['programme_category'] === 'undergraduate' ? ['1', '2', '3', '4'] : ['1', '2', '3', '4', '5 & 6'];
                        foreach ($blocks as $b):
                        ?>
                            <option value="<?php echo $b; ?>" <?php echo $selected_module['block'] == $b ? 'selected' : ''; ?>>
                                Block <?php echo $b; ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label for="module_name">Module Name:</label><br>
                    <input type="text" id="module_name" name="module_name" value="<?php echo htmlspecialchars($selected_module['module_name']); ?>" required><br><br>

                    <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($selected_module['module_id']); ?>">
                    <button type="submit" name="update" aria-label="Update selected module">Update Module</button>
                <?php endif; ?>
            </form>
        </div>
    </main>

    <!-- Script to manage focus after form submission -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($selected_module): ?>
                document.getElementById('programme_id').focus(); // Refocus after module selection
            <?php endif; ?>
        });
    </script>
</body>
</html>