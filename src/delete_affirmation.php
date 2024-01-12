<?php
session_start();
$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';

$pdo = new PDO($dbn, $user, $pwd);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $affirmationId = $_POST['affirmation_id'];
    $userId = $_SESSION['user_id'];

    // ユーザーが投稿者かどうかを確認
    $stmt = $pdo->prepare("SELECT user_id FROM affirmations WHERE id = :id");
    $stmt->execute([':id' => $affirmationId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['user_id'] == $userId) {
        $deleteStmt = $pdo->prepare("DELETE FROM affirmations WHERE id = :id");
        $deleteStmt->execute([':id' => $affirmationId]);
    }

header('Location: affirmations.php?tab=my_posts');
exit;
}
?>
