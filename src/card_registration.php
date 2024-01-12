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


// 新しいカードの登録処理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_card') {
    $cardName = $_POST['card_name'];
    $goalDate = $_POST['goal_date'];
    $rule = $_POST['rule'];
    $reward = $_POST['reward'];
    $userId = $user['id'];

    $checkCardSql = 'SELECT * FROM reward_card WHERE user_id = :user_id AND card_name = :card_name';
    $checkCardStmt = $pdo->prepare($checkCardSql);
    $checkCardStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkCardStmt->bindValue(':card_name', $cardName, PDO::PARAM_STR);
    $checkCardStmt->execute();

    if (!$checkCardStmt->fetch()) {
        $insertSql = 'INSERT INTO reward_card (user_id, card_name, total_point, goal_date, rule, reward, created_at) VALUES (:user_id, :card_name, 0, :goal_date, :rule, :reward, NOW())';
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindValue(':card_name', $cardName, PDO::PARAM_STR);
        $insertStmt->bindValue(':goal_date', $goalDate, PDO::PARAM_STR);
        $insertStmt->bindValue(':rule', $rule, PDO::PARAM_STR);
        $insertStmt->bindValue(':reward', $reward, PDO::PARAM_STR);

        if ($insertStmt->execute()) {
            $_SESSION['message'] = 'カードが作成されました。';
        } else {
            $_SESSION['message'] = 'カードの作成に失敗しました。';
        }
    } else {
        $_SESSION['message'] = '同じ名前のカードは既に存在します。';
    }

    header('Location: card_registration.php');
    exit();
}

// HTMLの表示部分でメッセージを表示
if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
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

<!-- カード作成フォーム -->
<div class="card">
    <h2>ポイントカード登録</h2>
    <form action="card_registration.php" method="POST">
        <input type="hidden" name="action" value="create_card">
        カードの名前: <input type="text" name="card_name" required><br>
        目標期日: <input type="date" name="goal_date" required><br>
        ルール: <input type="text" name="rule" required><br>
        報酬: <input type="text" name="reward" required><br>
        <input type="submit" value="カードを作成">
    </form>
</div>



</body>
</html>