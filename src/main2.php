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

// ユーザーカード一覧を取得（18ポイント未満のカードのみ）
if (isset($_POST['action']) && $_POST['action'] == 'fetch_cards') {
    $userId = $user['id'];
    $cardSql = 'SELECT * FROM reward_card WHERE user_id = :user_id AND total_point < 18';
    $cardStmt = $pdo->prepare($cardSql);
    $cardStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $cardStmt->execute();
    $cards = $cardStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cards);
    exit;
}

// カードの詳細情報を取得する処理
if (isset($_POST['action']) && $_POST['action'] == 'get_reward_card_details') {
    $cardId = $_POST['card_id'];

    $cardSql = 'SELECT * FROM reward_card WHERE id = :card_id';
    $cardStmt = $pdo->prepare($cardSql);
    $cardStmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
    $cardStmt->execute();
    $cardDetails = $cardStmt->fetch(PDO::FETCH_ASSOC);

    if ($cardDetails) {
        echo json_encode($cardDetails);
    } else {
        echo json_encode(['error' => 'Card not found']);
    }
    exit;
}
// ポイントを加算する処理
if (isset($_POST['action']) && $_POST['action'] == 'add_points') {
    $cardId = $_POST['card_id'];
    $pointsToAdd = $_POST['points'];

    // reward_card テーブルの total_point を更新
    $updateSql = 'UPDATE reward_card SET total_point = total_point + :points WHERE id = :card_id';
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindValue(':points', $pointsToAdd, PDO::PARAM_INT);
    $updateStmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        // point_history テーブルに記録を追加（もし使用している場合）
        $historySql = 'INSERT INTO point_history (card_id, point, updated_at) VALUES (:card_id, :point, NOW())';
        $historyStmt = $pdo->prepare($historySql);
        $historyStmt->bindValue(':card_id', $cardId, PDO::PARAM_INT);
        $historyStmt->bindValue(':point', $pointsToAdd, PDO::PARAM_INT);
        $historyStmt->execute();

        echo json_encode(['message' => 'Points and history added successfully']);
    } else {
        echo json_encode(['error' => 'Failed to add points']);
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


// exchange_historyテーブルに新しいレコードを挿入
foreach ($completedCards as $card) {
    $cardId = $card['id'];
    $sql = "SELECT COUNT(*) AS count FROM exchange_history WHERE card_id = :cardId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':cardId', $cardId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        $created_at = date('Y-m-d H:i:s');
        $deadline = date('Y-m-d H:i:s', strtotime($created_at . '+1 week'));
        $exchange_date = date('Y-m-d H:i:s');

        // デバッグ情報を出力
        var_dump($cardId, $created_at, $deadline, $exchange_date);

        $sql = "INSERT INTO exchange_history (card_id, created_at, deadline, exchange_date) 
                VALUES (:cardId, :created_at, :deadline, :exchange_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cardId', $cardId, PDO::PARAM_INT);
        $stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);
        $stmt->bindValue(':deadline', $deadline, PDO::PARAM_STR);
        $stmt->bindValue(':exchange_date', $exchange_date, PDO::PARAM_STR);
        $stmt->execute();
    }
}


if (isset($_POST['action']) && $_POST['action'] == 'fetch_reward_cards') {
    $userId = $user['id']; // ログインしているユーザーのID
    $rewardCardSql = 'SELECT * FROM reward_card WHERE user_id = :user_id';
    $rewardCardStmt = $pdo->prepare($rewardCardSql);
    $rewardCardStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $rewardCardStmt->execute();
    $rewardCards = $rewardCardStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rewardCards);
    exit;
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
document.addEventListener('DOMContentLoaded', function() {
    var email = '<?php echo $email; ?>';
    if (email) {
        fetchUserCards(email);
    }
});

function fetchUserCards(email) {
    fetch('main2.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=fetch_reward_cards&email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(rewardCards => {
        var userCardsList = document.getElementById('user-cards-list');
        userCardsList.innerHTML = '';
        rewardCards.forEach(function(card) {
            var cardItem = document.createElement('li');
            var cardLink = document.createElement('a');
            cardLink.href = 'javascript:void(0);';
            cardLink.className = 'check-card';
            cardLink.setAttribute('data-id', card.id);
            cardLink.textContent = card.card_name + ' (' + card.total_point + ' ポイント)';
            cardLink.addEventListener('click', function() {
                fetchCardDetails(card.id);
            });
            cardItem.appendChild(cardLink);
            userCardsList.appendChild(cardItem);
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function fetchCardDetails(cardId) {
     console.log("Fetching details for card ID:", cardId);
      var requestBody = 'action=get_reward_card_details&card_id=' + encodeURIComponent(cardId);
    console.log("Request body:", requestBody); // 追加するデバッグ行

    fetch('main2.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_reward_card_details&card_id=' + encodeURIComponent(cardId)
    })
    .then(response => response.json())
    .then(cardDetails => {
             console.log("Received card details:", cardDetails); 
        if (cardDetails.error) {
            console.error(cardDetails.error);
        } else {
            displayCardDetails(cardDetails);
            updateSlots(cardDetails); // cardDetailsをupdateSlotsに渡す
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateSlots(cardDetails) {
    var pointCardContainer = document.querySelector('#point-card-container');
    if (pointCardContainer) {
        var slots = pointCardContainer.querySelectorAll('.point-slot');
        slots.forEach(function(slot) {
            slot.addEventListener('click', function() {
                var currentPoints = pointCardContainer.querySelectorAll('.stamped').length;
                var slotNumber = parseInt(this.getAttribute('data-point-number'), 10);
                if (!this.classList.contains('stamped') && slotNumber === currentPoints + 1) {
                    addPoint(cardDetails.id, 1); // cardDetails.idをaddPointに渡す
                }
            });
        });
    }
}


        function displayCardDetails(cardDetails) {
                // カードの基本情報を表示
    var detailContainer = document.getElementById('card-detail-container');
    detailContainer.innerHTML = `
       <h3>カード詳細</h3>
        <p>カード名: ${cardDetails.card_name}</p>
        <p>ポイント数: ${cardDetails.total_point}</p>
        <p>期限: ${cardDetails.goal_date}</p>
        <p>ルール: ${cardDetails.rule}</p>
        <p>ご褒美: ${cardDetails.reward}</p>
    `;
            var pointCardHtml = '<div class="point-card">';
            pointCardHtml += '<div class="card-name">' + cardDetails.card_name + 'カード</div>';
            pointCardHtml += '<div class="point-grid">';
            for (var i = 1; i <= 18; i++) {
                pointCardHtml += '<div class="point-slot ' + (i <= cardDetails.total_point ? 'stamped' : '') + '" data-point-number="' + i + '" data-card-id="' + cardDetails.id + '">' + i + '</div>';
            }
            pointCardHtml += '</div></div>';

            var pointCardContainer = document.querySelector('#point-card-container');
            pointCardContainer.innerHTML = pointCardHtml;

    var slots = pointCardContainer.querySelectorAll('.point-slot');
    slots.forEach(function(slot) {
        slot.addEventListener('click', function() {
            if (!this.classList.contains('stamped')) {
                this.classList.add('stamped'); // スタンプの見た目を即時更新
                addPoint(cardDetails.id, 1); // 1ポイント加算のリクエスト
            }
        });
    });
}

   // 18ポイントに達した場合、モーダルを表示
    if (cardDetails.total_point >= 18) {
        showModal();
    }


// モーダルポップアップを表示する関数
function showModal() {
    var modal = document.getElementById('modal');
    modal.style.display = 'block';
}

// モーダルポップアップを閉じる関数
function closeModal() {
    var modal = document.getElementById('modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 画面外のクリックでモーダルを閉じる処理
window.onclick = function(event) {
    var modal = document.getElementById('modal');
    if (event.target == modal) {
        closeModal();
    }
}

function addPoint(cardId, pointsToAdd) {
    fetch('main2.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_points&card_id=' + encodeURIComponent(cardId) + '&points=' + pointsToAdd
    })
    .then(response => response.json())
      .then(data => {
        if (data.message === 'Points added successfully') {
            var pointCardContainer = document.querySelector('#point-card-container');
            var currentPoints = pointCardContainer.querySelectorAll('.stamped').length;
            var nextSlot = pointCardContainer.querySelector('.point-slot[data-point-number="' + (currentPoints + 1) + '"]');
            if (nextSlot) {
                nextSlot.classList.add('stamped');
                nextSlot.innerHTML = '<img src="img/point.png">'; // スタンプ画像のパスに注意
            }
        } else {
            console.error('Error: Failed to add points');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// スロットクリック時の処理を更新
var slots = pointCardContainer.querySelectorAll('.point-slot');
slots.forEach(function(slot) {
    slot.addEventListener('click', function() {
        var currentPoints = pointCardContainer.querySelectorAll('.stamped').length;
        var slotNumber = parseInt(this.getAttribute('data-point-number'), 10);
        if (!this.classList.contains('stamped') && slotNumber === currentPoints + 1) {
            addPoint(cardDetails.id, 1);
        }
    });
});



// スタンプの見た目を更新する関数
function updatePointSlotVisual(cardId, pointsToAdd) {
    var cardContainer = document.querySelector(`[data-card-id='${cardId}']`).parentNode;
    var slots = cardContainer.querySelectorAll('.point-slot:not(.stamped)');
    
    for (var i = 0; i < pointsToAdd && i < slots.length; i++) {
        slots[i].classList.add('stamped');
    }
}




        document.addEventListener('DOMContentLoaded', function() {
            var email = '<?php echo $email; ?>';
            fetchUserCards(email);
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
    <div class="card">
        <h2>ポイントカード一覧</h2>
        <ul id="user-cards-list">
            <!-- ここにユーザーカード一覧が動的に追加されます -->
        </ul>
    </div>
    <div id="point-card-container">
    <!-- ここに動的にポイントカードが表示されます -->
    </div>

    <!-- カード詳細情報表示エリア -->
<div class="card-details">
    <div id="card-detail-container">
        <!-- ここにカードの詳細情報が表示されます -->
    </div>
</div>

<!-- モーダルポップアップ -->
<div id="modal" class="modal" onclick="closeModal()">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <p>おめでとう！次のカードを作成してね。<br>
        （同じ名前のカードは作れないので、カード名の後に何枚目か番号をつけてね。）
        </p>
    </div>
</div>

</body>
</html>