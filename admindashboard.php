<?php
session_start();

// Session timeout settings
$timeout_duration = 900; // 15 minutes in seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    header('Location: adminlogin.html');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['username'])) {
    header('Location: adminlogin.html');
    exit();
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

// Database connection
$host = 'localhost';
$db = 'registrationform';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle delete request for student data
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_email'])) {
        $delete_email = $_POST['delete_email'];
        $stmt_delete = $pdo->prepare("DELETE FROM `student data` WHERE Email = :email LIMIT 1");
        $stmt_delete->bindParam(':email', $delete_email, PDO::PARAM_STR);
        $stmt_delete->execute();
        header("Location: admindashboard.php"); // Refresh page after deletion
        exit();
    }

    // Handle email sending for a single user
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
        $recipient_email = $_POST['recipient_email'];
        $recipient_name = $_POST['recipient_name'];
        $subject = $_POST['email_subject'];
        $message = $_POST['email_message'];

        $mail = new PHPMailer(true);
        try {
            // Server settings (Gmail SMTP)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ayeshnaz2006@gmail.com';
            $mail->Password = 'hquy hdlf euze hsmv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Sender and recipient
            $mail->setFrom('ayeshnaz2006@gmail.com', 'Admin Dashboard');
            $mail->addAddress($recipient_email, $recipient_name);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = nl2br(htmlspecialchars($message));
            $mail->AltBody = strip_tags($message);

            $mail->send();
            $email_status = "Message sent successfully";
        } catch (Exception $e) {
            $email_status = "Failed to send email: " . $mail->ErrorInfo;
        }
    }

    // Pagination setup for student data
    $records_per_page = 5;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $records_per_page;

    // Fetch student data with pagination
    $stmt = $pdo->prepare("SELECT * FROM `student data` LIMIT :offset, :records_per_page");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of student records for pagination
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM `student data`");
    $stmt_count->execute();
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Detect duplicate student entries
    $stmt_duplicates = $pdo->prepare("SELECT Name, Email, COUNT(*) as count FROM `student data` GROUP BY Name, Email HAVING count > 1");
    $stmt_duplicates->execute();
    $duplicates = $stmt_duplicates->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all programmes
    $stmt_programmes = $pdo->query("SELECT * FROM programmes");
    $programmes = $stmt_programmes->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all modules with programme names and categories
    $stmt_modules = $pdo->query("SELECT m.*, p.name AS programme_name, p.category AS programme_category 
                                 FROM modules m 
                                 LEFT JOIN programmes p ON m.programme_id = p.programme_id 
                                 ORDER BY p.name, p.category, m.year, m.block");
    $modules = $stmt_modules->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
    $students = [];
    $programmes = [];
    $modules = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css?v=1.3">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="script.js" defer></script>
</head>
<body>

<!-- Display success message if present -->
<?php if (isset($_GET['message'])): ?>
    <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border: 1px solid #c3e6cb; border-radius: 5px;">
        <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
    </div>
<?php endif; ?>

<div class="admin-dashboard">
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
        </div>
        <a href="index.html">Logout</a>
        <a href="addprogramme.php">Add Programme</a>
        <a href="addmodule.php">Add Module</a>
        <a href="updatemodule.php">Update Module</a>
        <a href="updateprogramme.php">Update Programme</a>
        <a href="deletemodule.php">Delete Module</a>
        <a href="deleteprogramme.php">Delete Programme</a>
        <a href="assignmoduleleader.php">Assign Module Leader</a>
    </div>

    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

    <?php
    if (isset($_GET['status']) && isset($_GET['message'])) {
        $status = $_GET['status'];
        $message = urldecode($_GET['message']);
        $color = $status === 'success' ? 'green' : 'red';
        echo "<p style='color: $color; text-align: center; margin-bottom: 20px;'>$message</p>";
    }
    ?>

    <div id="timer" data-timeout="<?php echo $timeout_duration; ?>"></div>

    <!-- Combined Mailing List/Student Data Section -->
    <h2>Mailing List/Student Data</h2>
    <?php if (!empty($duplicates)): ?>
        <p id="duplicate-warning">Duplicate entries detected! You can delete them.</p>
    <?php endif; ?>

    <?php if (isset($email_status)): ?>
        <p style="color: <?php echo strpos($email_status, 'success') !== false ? 'green' : 'red'; ?>;"><?php echo htmlspecialchars($email_status); ?></p>
    <?php endif; ?>

    <?php if (empty($students)): ?>
        <p>No students found in the database.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Delete</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['Name']); ?></td>
                    <td><?php echo htmlspecialchars($student['Email']); ?></td>
                    <td><?php echo htmlspecialchars($student['Phone']); ?></td>
                    <td><?php echo htmlspecialchars($student['Gender']); ?></td>
                    <td>
                        <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this student?');">
                            <input type="hidden" name="delete_email" value="<?php echo htmlspecialchars($student['Email']); ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </td>
                    <td>
                        <button type="button" class="send-mail-btn" 
                                data-email="<?php echo htmlspecialchars($student['Email']); ?>" 
                                data-name="<?php echo htmlspecialchars($student['Name']); ?>">
                            Send Mail
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="admindashboard.php?page=<?php echo $current_page - 1; ?>">Previous</a>
            <?php endif; ?>
            <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
            <?php if ($current_page < $total_pages): ?>
                <a href="admindashboard.php?page=<?php echo $current_page + 1; ?>">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Modal for Sending Email -->
    <div id="emailModal" class="modal">
        <div class="modal-content">
            <h3>Send Email</h3>
            <form method="POST" id="emailForm">
                <input type="hidden" name="recipient_email" id="modal_recipient_email">
                <input type="hidden" name="recipient_name" id="modal_recipient_name">
                <input type="text" name="email_subject" id="modal_email_subject" placeholder="Subject" required>
                <textarea name="email_message" id="modal_email_message" placeholder="Message" required></textarea>
                <div class="btn-container">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="send_email" class="send-btn">Send</button>
                </div>
            </form>
        </div>
    </div>

    <h2>View Programmes</h2>
    <?php if (empty($programmes)): ?>
        <p>No programmes found in the database.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <?php if (isset($programmes[0]['image'])): ?>
                        <th>Image</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programmes as $programme): ?>
                <tr>
                    <td><?php echo htmlspecialchars($programme['programme_id']); ?></td>
                    <td><?php echo htmlspecialchars($programme['name']); ?></td>
                    <td><?php echo htmlspecialchars($programme['description']); ?></td>
                    <td><?php echo htmlspecialchars($programme['category']); ?></td>
                    <td>
                        <?php if (isset($programme['image'])): ?>
                            <?php if ($programme['image']): ?>
                                <img src="images/<?php echo htmlspecialchars($programme['image']); ?>" alt="<?php echo htmlspecialchars($programme['name']); ?>" style="max-width: 100px;">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>View Modules</h2>
    <?php if (empty($modules)): ?>
        <p>No modules found in the database.</p>
    <?php else: ?>
        <?php
        $grouped_modules = [
            'Computer Science UG' => [],
            'Cyber Security UG' => [],
            'Computer Science PG' => [],
            'Cyber Security PG' => []
        ];

        foreach ($modules as $module) {
            $programme_name = $module['programme_name'];
            $category = $module['programme_category'];
            $key = "$programme_name " . ($category === 'undergraduate' ? 'UG' : 'PG');

            if (isset($grouped_modules[$key])) {
                $year = (int)$module['year'];
                $block = $module['block']; // Keep block as a string
                $grouped_modules[$key][$year][$block] = $module;
            }
        }

        function displayModuleTable($title, $modules, $is_ug = true) {
            $years = $is_ug ? [1, 2, 3] : [1];
            $blocks = $is_ug ? ['1', '2', '3', '4'] : ['1', '2', '3', '4', '5 & 6'];
            ?>
            <h3><?php echo htmlspecialchars($title); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Block</th>
                        <th>Module Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($years as $year): ?>
                        <?php foreach ($blocks as $block): ?>
                            <tr>
                                <td><?php echo $year; ?></td>
                                <td><?php echo htmlspecialchars($block); ?></td>
                                <td>
                                    <?php
                                    if (isset($modules[$year][$block])) {
                                        echo htmlspecialchars($modules[$year][$block]['module_name']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        foreach ($grouped_modules as $title => $modules) {
            if (!empty($modules)) {
                $is_ug = strpos($title, 'UG') !== false;
                displayModuleTable($title, $modules, $is_ug);
            }
        }
        ?>
    <?php endif; ?>
</div>

</body>
</html>