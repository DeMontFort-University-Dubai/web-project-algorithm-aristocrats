<?php
// DATABASE CONNECTION
$host = 'localhost';
$db = 'registrationform';
$user = 'root';
$pass = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if form submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get user inputs and sanitize them
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8');
        $gender = htmlspecialchars($_POST['gender'], ENT_QUOTES, 'UTF-8');

        // Insert with prepared statement
        $sql = "INSERT INTO `student data` (Name, Email, Phone, Gender) VALUES (:name, :email, :phone, :gender)";
        $stmt = $pdo->prepare($sql);

        // Bind the parameters
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':gender', $gender);

        // Execute the statement
        if ($stmt->execute()) {
            echo "<div class='message success'>
                    <h2>✅ Registration Successful!</h2>
                    <p>Your form has been submitted successfully. Click the button below to return to the homepage.</p>
                    <a href='index.html' class='button'>Go to Homepage</a>
                  </div>";
        } else {
            echo "<div class='message error'>❌ Failed to register.</div>";
        }
    } else {
        echo "<div class='message error'>❌ No form submission detected.</div>";
    }

} catch (PDOException $e) {
    echo "<div class='message error'>❌ Database error: " . $e->getMessage() . "</div>";
}
?>

<style>
/* General styles for the page */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Container for messages */
.message {
    background-color: white;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Success message styling */
.message.success {
    border-left: 5px solid #4CAF50;
    color: #4CAF50;
}

.message.error {
    border-left: 5px solid #f44336;
    color: #f44336;
}

/* Button styling */
.button {
    padding: 12px 20px;
    font-size: 16px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 20px;
    display: inline-block;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.button:hover {
    background-color: #45a049;
}

/* Responsive design adjustments */
@media (max-width: 600px) {
    .message {
        padding: 20px;
    }

    .button {
        width: 100%;
    }
}
</style>
