<?php
session_start();

$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';

try {
    $pdo = new PDO($dbn, $user, $pwd);
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit();
}

$email = isset($_SESSION['user_mail']) ? $_SESSION['user_mail'] : '';

if ($email) {
    $sql = 'SELECT * FROM user WHERE mail = :mail';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mail', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_name'] = $user['name'];
    }
}

// セッション変数からユーザーIDを取得
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// ユーザー情報をデータベースから取得
if ($email) {
    $sql = 'SELECT * FROM user WHERE mail = :mail';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mail', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_name'] = $user['name'];
        $userId = $user['id']; // ユーザーIDをセッションに設定
    }
}

// 18ポイントに達したカードを取得する処理
$sql = "
    SELECT rc.*, eh.exchange_date 
    FROM reward_card rc
    LEFT JOIN exchange_history eh ON rc.id = eh.card_id
    WHERE rc.user_id = :userId AND rc.total_point >= 18";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
$stmt->execute();
$completedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);


// // exchange_historyテーブルに新しいレコードを挿入
// foreach ($completedCards as $card) {
//     $cardId = $card['id'];
//     $sql = "SELECT COUNT(*) AS count FROM exchange_history WHERE card_id = :cardId";
//     $stmt = $pdo->prepare($sql);
//     $stmt->bindValue(':cardId', $cardId, PDO::PARAM_INT);
//     $stmt->execute();
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);

//     if ($result['count'] == 0) {
//         $created_at = date('Y-m-d H:i:s');
//         $deadline = date('Y-m-d H:i:s', strtotime($created_at . '+1 week'));
//         $exchange_date = date('Y-m-d H:i:s');

//         // デバッグ情報を出力
//         var_dump($cardId, $created_at, $deadline, $exchange_date);

//         $sql = "INSERT INTO exchange_history (card_id, created_at, deadline, exchange_date) 
//                 VALUES (:cardId, :created_at, :deadline, :exchange_date)";
//         $stmt = $pdo->prepare($sql);
//         $stmt->bindValue(':cardId', $cardId, PDO::PARAM_INT);
//         $stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);
//         $stmt->bindValue(':deadline', $deadline, PDO::PARAM_STR);
//         $stmt->bindValue(':exchange_date', $exchange_date, PDO::PARAM_STR);
//         $stmt->execute();
//     }
// }


// カードの交換処理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'exchange_card') {
    $cardId = $_POST['card_id'];
    $sql = "UPDATE exchange_history SET exchange_date = NOW() WHERE card_id = :cardId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':cardId', $cardId, PDO::PARAM_INT);
    $stmt->execute();
    header("Location: exchange.php");
    exit();
}



?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ポイントカード</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script>
  
function fetchCompletedCards(email) {
    fetch('fetch_exchange.php', {  // URLを新しいPHPファイルに変更
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=fetch_completed_cards&email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(cards => {
        var completedCardsList = document.getElementById('completed-cards-list');
        completedCardsList.innerHTML = '';
        cards.forEach(function(cardName) {
            var cardItem = document.createElement('li');
            var exchangeButton = document.createElement('button');
            exchangeButton.textContent = '交換完了';
            exchangeButton.onclick = function() { exchangeCard(cardName, email); };
            cardItem.textContent = cardName;
            cardItem.appendChild(exchangeButton);
            completedCardsList.appendChild(cardItem);
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
    </script>
</head>
<body>
     <header>
  <nav>
    <ul>
      <li><a href="mypage.php">マイページ</a></li>
      <li><a href="card_registration.php">カード登録</a></li>
      <li><a href="exchange.php">交換</a></li>
            <li><a href="affirmations.php">アファメーション</a></li>
      <li><a href="main2.php">ポイント管理</a></li>
      <li><a href="%20check.php">チェック</a></li>
      <li><a href="#">お問い合わせ</a></li>
      <div class="button_group">
              <input type="button" onclick="location.href='top.php'" value="ログアウト">
 </div>
  </nav>
</header>
<!-- 18ポイントに達したカードの表示 -->
<div class="card">
    <h2>18ポイント貯まったカード</h2>
    <ul id="completed-cards-list">
        <?php if (!empty($completedCards)): ?>
            <?php foreach ($completedCards as $card): ?>
            

                <li id="card-<?php echo htmlspecialchars($card['id']); ?>">
                    カード名: <?php echo htmlspecialchars($card['card_name']); ?>
                    <form action="exchange.php" method="post">
                        <input type="hidden" name="action" value="exchange_card">
                        <input type="hidden" name="card_id" value="<?php echo htmlspecialchars($card['id']); ?>">
                        <input type="submit" value="交換">
                    </form>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>18ポイントに達したカードはありません。</li>
        <?php endif; ?>
    </ul>
</div>

</body>
</html>