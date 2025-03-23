<?php
$host = 'localhost';
$db = 'registrationform';
$user = 'root';
$pass = '';

try {
    $dsn = "mysql:host=$host;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $category = isset($_GET['category']) ? $_GET['category'] : '';

    if ($category) {
        $stmt = $pdo->prepare("SELECT * FROM programmes WHERE category = :category");
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->execute();

        $programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($programmes)) {
            echo json_encode(['message' => 'No programmes available for this category.']);
        } else {
            echo json_encode($programmes);
        }
    } else {
        echo json_encode(['message' => 'Category not specified.']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Unable to fetch data: ' . $e->getMessage()]);
}
?>