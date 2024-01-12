<?php

$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';

$pdo = new PDO($dbn, $user, $pwd);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $affirmationId = $_POST['affirmation_id'];
    $stmt = $pdo->prepare("UPDATE affirmations SET likes = likes + 1 WHERE id = :id");
    $stmt->execute([':id' => $affirmationId]);

    header('Location: affirmations.php');
    exit;
}
?>
