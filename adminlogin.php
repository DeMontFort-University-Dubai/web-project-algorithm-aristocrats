<?php
session_start(); // Start the session

// Database credentials
$host = 'localhost';
$db = 'registrationform';  // Your database name
$user = 'root';             // Your MySQL username
$pass = '';                 // Your MySQL password (if any)

// Sanitize and validate inputs to prevent XSS
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize user input
    $username = sanitize_input($_POST['frmusername']);
    $varpassword = sanitize_input($_POST['frmpassword']);

    // Basic validation (ensure both fields are not empty)
    if (empty($username) || empty($varpassword)) {
        echo "Username or password cannot be empty.";
        exit();
    }

    try {
        // Create PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch the hashed password from the 'adminlogin' table
        $stmt = $pdo->prepare("SELECT Username, Password FROM adminlogin WHERE Username = :username");
        
        // Bind parameter to avoid SQL injection
        $stmt->bindParam(':username', $username);

        // Execute query
        $stmt->execute();

        // Fetch result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Verify the password using password_verify()
            if (password_verify($varpassword, $result['Password'])) {
                // Password is correct, store session variable to indicate the user is logged in
                $_SESSION['username'] = $username;
                
                // Redirect to the admin dashboard
                header("Location: admindashboard.php");
                exit(); // Ensure no further code is executed after redirect
            } else {
                // Invalid password
                echo "Invalid password!";
            }
        } else {
            // User not found
            echo "User not found!";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
