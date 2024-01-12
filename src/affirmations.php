<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';

try {
    $pdo = new PDO($dbn, $user, $pwd);
} catch (PDOException $e) {
    exit("データベース接続エラー: " . $e->getMessage());
}

// アファメーションの投稿
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = $_POST['message'];
    $userId = $_SESSION['user_id']; // セッションからユーザーIDを取得

    $stmt = $pdo->prepare("INSERT INTO affirmations (user_id, message) VALUES (:user_id, :message)");
    $stmt->execute([':user_id' => $userId, ':message' => $message]);

  header('Location: affirmations.php?tab=my_posts');
    exit;
}


// アファメーションをデータベースから取得
$stmt = $pdo->query("SELECT * FROM affirmations ORDER BY created_at DESC");
$affirmations = $stmt->fetchAll(PDO::FETCH_ASSOC);


// アファメーションとユーザー情報をデータベースから取得
$stmt = $pdo->query("
    SELECT affirmations.*, user.name AS user_name
    FROM affirmations
    JOIN user ON affirmations.user_id = user.id
    ORDER BY created_at DESC
");
$affirmations = $stmt->fetchAll(PDO::FETCH_ASSOC);





// 自分の投稿を取得
$myStmt = $pdo->prepare("
    SELECT affirmations.*, user.name AS user_name
    FROM affirmations
    JOIN user ON affirmations.user_id = user.id
    WHERE affirmations.user_id = :user_id
    ORDER BY created_at DESC
");
$myStmt->execute([':user_id' => $_SESSION['user_id']]);
$myAffirmations = $myStmt->fetchAll(PDO::FETCH_ASSOC);

// みんなの投稿を取得
$allStmt = $pdo->query("
    SELECT affirmations.*, user.name AS user_name
    FROM affirmations
    JOIN user ON affirmations.user_id = user.id
    ORDER BY created_at DESC
");
$allAffirmations = $allStmt->fetchAll(PDO::FETCH_ASSOC);




?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>アファメーションカード</title>
      <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<script>
function showTab(tabName) {
    var i, tabcontent, tabbuttons;
    tabcontent = document.getElementsByClassName("tabcontent");
    tabbuttons = document.getElementsByClassName("tab-button");

    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    for (i = 0; i < tabbuttons.length; i++) {
        tabbuttons[i].classList.remove("active");
    }

    document.getElementById(tabName).style.display = "block";
    document.getElementById(tabName + "Button").classList.add("active");
}

document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    var tab = urlParams.get('tab');
    if (tab === 'my_posts') {
        showTab('MyPosts');
    } else {
        showTab('AllPosts');
    }
});
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
    <div class="tab-button">
  <button id="MyPostsButton" class="tab-button" onclick="showTab('MyPosts')">自分の投稿</button>
<button id="AllPostsButton" class="tab-button" onclick="showTab('AllPosts')">みんなの投稿</button>
</div>

<div id="MyPosts" class="tabcontent" style="display:none;">

        <form action="affirmations.php" method="post">
            <label for="message">アファメーション:</label>
            <textarea id="message" name="message" required></textarea><br>
            <input type="submit" value="投稿">
        </form>
    <?php foreach ($myAffirmations as $affirmation) { ?>
        <div>
            <p><?php echo htmlspecialchars($affirmation['message'], ENT_QUOTES); ?></p>
            <p>投稿者: <?php echo htmlspecialchars($affirmation['user_name'], ENT_QUOTES); ?></p>
            <p>投稿日時: <?php echo $affirmation['created_at']; ?></p>
            <form action='delete_affirmation.php' method='post'>
                <input type='hidden' name='affirmation_id' value='<?php echo $affirmation['id']; ?>'>
                <input type='submit' value='削除'>
            </form>
        </div>
    <?php } ?>
</div>

<div id="AllPosts" class="tabcontent" style="display:none;">
    <?php foreach ($allAffirmations as $affirmation) { ?>
        <div>
            <p><?php echo htmlspecialchars($affirmation['message'], ENT_QUOTES); ?></p>
            <p>投稿者: <?php echo htmlspecialchars($affirmation['user_name'], ENT_QUOTES); ?></p>
            <p>投稿日時: <?php echo $affirmation['created_at']; ?></p>
            <form action='like_affirmation.php' method='post'>
                <input type='hidden' name='affirmation_id' value='<?php echo $affirmation['id']; ?>'>
                <input type='submit' value='いいね'>
            </form>
        </div>
    <?php } ?>
</div>



</body>
</html>