<?php
session_start(); // セッションを開始

// データベース接続
$dbn ='mysql:dbname=thanks_card;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';



try {
    $pdo = new PDO($dbn, $user, $pwd);

    // POSTデータを受け取る
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // セッションからユーザーIDを取得する際のエラーチェック
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("ユーザーIDがセットされていません。");
        }
        $userId = $_SESSION['user_id'];

        // POSTデータの存在チェック
        $requiredFields = ['total_score', 'jisonkanjo_score', 'jikojuyoukan_score', 'jikokouryokukan_score', 'jikoshinraikan_score', 'jikoketteikan_score', 'jikoyuuyoukan_score'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field])) {
                throw new Exception("必要なデータがPOSTされていません: " . $field);
            }
        }

        $totalScore = $_POST['total_score'];
        $jisonkanjoScore = $_POST['jisonkanjo_score'];
        $jikojuyoukanScore = $_POST['jikojuyoukan_score'];
        $jikokouryokukanScore = $_POST['jikokouryokukan_score'];
        $jikoshinraikanScore = $_POST['jikoshinraikan_score'];
        $jikoketteikanScore = $_POST['jikoketteikan_score'];
        $jikoyuuyoukanScore = $_POST['jikoyuuyoukan_score'];

       // 現在の日時を取得
        $currentTime = date('Y-m-d H:i:s');

        // SQLクエリを準備（created_atを含む）
        $stmt = $pdo->prepare('INSERT INTO affirmation_check (user_id, total_score, jisonkanjo_score, jikojuyoukan_score, jikokouryokukan_score, jikoshinraikan_score, jikoketteikan_score, jikoyuuyoukan_score, created_at) VALUES (:user_id, :total_score, :jisonkanjo_score, :jikojuyoukan_score, :jikokouryokukan_score, :jikoshinraikan_score, :jikoketteikan_score, :jikoyuuyoukan_score, :created_at)');

        // パラメータをバインド
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':total_score', $totalScore, PDO::PARAM_INT);
        $stmt->bindParam(':jisonkanjo_score', $jisonkanjoScore, PDO::PARAM_INT);
        $stmt->bindParam(':jikojuyoukan_score', $jikojuyoukanScore, PDO::PARAM_INT);
        $stmt->bindParam(':jikokouryokukan_score', $jikokouryokukanScore, PDO::PARAM_INT);
        $stmt->bindParam(':jikoshinraikan_score', $jikoshinraikanScore, PDO::PARAM_INT);
        $stmt->bindParam(':jikoketteikan_score', $jikoketteikanScore, PDO::PARAM_INT);
        $stmt->bindParam(':jikoyuuyoukan_score', $jikoyuuyoukanScore, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $currentTime, PDO::PARAM_STR);


        // クエリを実行
        $stmt->execute();
    }
} catch (PDOException $e) {
    // データベースエラー処理
    echo "データベースエラー: " . $e->getMessage();
    exit();
} catch (Exception $e) {
    // その他のエラー処理
    echo "エラー: " . $e->getMessage();
    exit();
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>自己肯定感チェック診断</title>
     <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('selfEsteemForm');
        const submitButton = document.getElementById('submitButton');
        form.addEventListener('change', function() {
            // すべての質問が回答されているか確認
            const allAnswered = [...form.querySelectorAll('.question')].every(question => {
                return question.querySelector('input[type="radio"]:checked');
            });
            submitButton.disabled = !allAnswered; // すべて回答されていなければボタンを無効化
        });
    });

    let dataSent = false;

function calculateScoresAndSendData() {
    if (dataSent) {
        return; // 既にデータを送信した場合は何もしない
    }
    const form = document.getElementById('selfEsteemForm');
    let formData = new FormData(form);
    let scores = [0, 0, 0, 0, 0, 0];
    let totalScore = 0;

        for (let i = 1; i <= 18; i++) {
            let questionInput = form.querySelector(`input[name="q${i}"]:checked`);
            if (questionInput) {
                scores[Math.floor((i - 1) / 3)] += parseInt(questionInput.value);
            }
        }

        scores.forEach(score => totalScore += score);
        // 結果コメントの表示
        let resultText = "あなたの自己肯定感の結果：\n";
        resultText += "自尊感情: " + scores[0] + "\n";
        resultText += "自己受容感: " + scores[1] + "\n";
        resultText += "自己効力感: " + scores[2] + "\n";
        resultText += "自己信頼感: " + scores[3] + "\n";
        resultText += "自己決定感: " + scores[4] + "\n";
        resultText += "自己有用感: " + scores[5] + "\n";
        resultText += "合計スコア: " + totalScore + "\n";

        document.getElementById('result').innerText = resultText;

        // レーダーチャートの描画
        drawChart(scores);

// 各スコアをFormDataに追加
    formData.append('total_score', totalScore);
    formData.append('jisonkanjo_score', scores[0]);
    formData.append('jikojuyoukan_score', scores[1]);
    formData.append('jikokouryokukan_score', scores[2]);
    formData.append('jikoshinraikan_score', scores[3]);
    formData.append('jikoketteikan_score', scores[4]);
    formData.append('jikoyuuyoukan_score', scores[5]);
    
   sendData(formData);
    }

      function sendData(formData) {
        fetch('%20check.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // レスポンスの内容をコンソールに表示
        })
        .catch(error => console.error('Error:', error));
    }

function addHiddenInput(form, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }
// グローバルスコープでチャートのインスタンスを保持する変数を宣言
let myRadarChart = null;

function drawChart(scores) {
    const ctx = document.getElementById('radarChart').getContext('2d');

    // 既存のチャートが存在する場合は破棄する
    if (myRadarChart) {
        myRadarChart.destroy();
    }

    // 新しいチャートを作成
    myRadarChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['自尊感情', '自己受容感', '自己効力感', '自己信頼感', '自己決定感', '自己有用感'],
            datasets: [{
                label: '自己肯定感',
                data: scores,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scale: {
                ticks: {
                    suggestedMin: -3,
                    suggestedMax: 3
                }
            }
        }
    });
    
    // フォームの入力を無効化
    disableForm();

    // 「もう一度診断する」ボタンがまだ存在しない場合にのみ表示
    if (!document.getElementById('resetButton')) {
        showResetButton();
    }
}
function disableForm() {
    const form = document.getElementById('selfEsteemForm');
    const inputs = form.getElementsByTagName('input');
    for (let i = 0; i < inputs.length; i++) {
        inputs[i].disabled = true;
    }
}

function showResetButton() {
    const resetButton = document.createElement('button');
    resetButton.id = 'resetButton';
    resetButton.textContent = 'もう一度診断する';
    resetButton.onclick = resetForm;
    document.body.appendChild(resetButton);
}

function resetForm() {
    const form = document.getElementById('selfEsteemForm');
    
    // ラジオボタンの選択をクリア
    const inputs = form.querySelectorAll('input[type=radio]');
    inputs.forEach(input => {
        input.checked = false;
    });

    // フォームの入力を有効化
    enableForm();

    // 結果表示をクリア
    document.getElementById('result').innerText = '';

    // レーダーチャートをクリア（必要に応じて）
    if (myRadarChart) {
        myRadarChart.destroy();
    }

    // 「もう一度診断する」ボタンを削除
    this.remove();

    // フォームの状態をチェックし、「結果を見る」ボタンの状態を更新
    checkAllAnswered();
}

function checkAllAnswered() {
    const form = document.getElementById('selfEsteemForm');
    const submitButton = document.getElementById('submitButton');
    const allAnswered = [...form.querySelectorAll('.question')].every(question => {
        return question.querySelector('input[type="radio"]:checked');
    });
    submitButton.disabled = !allAnswered; // すべて回答されていなければボタンを無効化
}
function enableForm() {
    const form = document.getElementById('selfEsteemForm');
    const inputs = form.querySelectorAll('input');
    for (let i = 0; i < inputs.length; i++) {
        inputs[i].disabled = false;
    }
}


</script>
</head>
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
<body>
<form id="selfEsteemForm" method="POST" action="%20check.php">
        <!-- 自尊感情の質問 -->
        <div class="question">
            <p>質問1: 私は自分のことを価値ある人間だと感じる。</p>
<input type="radio" name="q1" value="1"> はい
<input type="radio" name="q1" value="0"> 中立
<input type="radio" name="q1" value="-1"> いいえ

        </div>
        <!-- 自尊感情の質問2 -->
        <div class="question">
            <p>質問2: 私は自分自身の成功を誇りに思う。</p>
<input type="radio" name="q2" value="1"> はい
<input type="radio" name="q2" value="0"> 中立
<input type="radio" name="q2" value="-1"> いいえ

        </div>
        <!-- 自尊感情の質問3 -->
        <div class="question">
            <p>質問3: 私は自分の長所を認識している。</p>
<input type="radio" name="q3" value="1"> はい
<input type="radio" name="q3" value="0"> 中立
<input type="radio" name="q3" value="-1"> いいえ
        </div>
        <!-- 自己受容感の質問4 -->
        <div class="question">
            <p>質問4: 私は自分の欠点を受け入れている。</p>
<input type="radio" name="q4" value="1"> はい
<input type="radio" name="q4" value="0"> 中立
<input type="radio" name="q4" value="-1"> いいえ
        </div>
        <!-- 自己受容感の質問5 -->
        <div class="question">
            <p>質問5: 私は自分の過去の選択を後悔していない。</p>
<input type="radio" name="q5" value="1"> はい
<input type="radio" name="q5" value="0"> 中立
<input type="radio" name="q5" value="-1"> いいえ
        </div>
        <!-- 自己受容感の質問6 -->
        <div class="question">
            <p>質問6: 私は自分自身を他人と比べない。</p>
<input type="radio" name="q6" value="1"> はい
<input type="radio" name="q6" value="0"> 中立
<input type="radio" name="q6" value="-1"> いいえ
        </div>
        <!-- 自己効力感の質問7 -->
        <div class="question">
            <p>質問7: 私は新しいことに挑戦する自信がある。</p>
<input type="radio" name="q7" value="1"> はい
<input type="radio" name="q7" value="0"> 中立
<input type="radio" name="q7" value="-1"> いいえ
        </div>
        <!-- 自己効力感の質問8 -->
        <div class="question">
            <p>質問8: 私は困難な状況でも自分を信じる。</p>
<input type="radio" name="q8" value="1"> はい
<input type="radio" name="q8" value="0"> 中立
<input type="radio" name="q8" value="-1"> いいえ
        </div>
        <!-- 自己効力感の質問9 -->
        <div class="question">
            <p>質問9: 私は自分の目標を達成する能力があると感じる。</p>
<input type="radio" name="q9" value="1"> はい
<input type="radio" name="q9" value="0"> 中立
<input type="radio" name="q9" value="-1"> いいえ
        </div>
              <!-- 自己信頼感の質問10 -->
        <div class="question">
            <p>質問10: 私は自分の決定に自信を持っている。</p>
<input type="radio" name="q10" value="1"> はい
<input type="radio" name="q10" value="0"> 中立
<input type="radio" name="q10" value="-1"> いいえ
        </div>
        <!-- 自己信頼感の質問11 -->
        <div class="question">
            <p>質問11: 私は自分自身を信頼している。</p>
<input type="radio" name="q11" value="1"> はい
<input type="radio" name="q11" value="0"> 中立
<input type="radio" name="q11" value="-1"> いいえ
        </div>
        <!-- 自己信頼感の質問12 -->
        <div class="question">
            <p>質問12: 私は自分の直感に従うことができる。</p>
<input type="radio" name="q12" value="1"> はい
<input type="radio" name="q12" value="0"> 中立
<input type="radio" name="q12" value="-1"> いいえ
        </div>
        <!-- 自己決定感の質問13 -->
        <div class="question">
            <p>質問13: 私は自分の人生の方向を決定することができる。</p>
<input type="radio" name="q13" value="1"> はい
<input type="radio" name="q13" value="0"> 中立
<input type="radio" name="q13" value="-1"> いいえ
        </div>
        <!-- 自己決定感の質問14 -->
        <div class="question">
            <p>質問14: 私は自分の人生において重要な選択を自分で行っている。</p>
<input type="radio" name="q14" value="1"> はい
<input type="radio" name="q14" value="0"> 中立
<input type="radio" name="q14" value="-1"> いいえ
        </div>
        <!-- 自己決定感の質問15 -->
        <div class="question">
            <p>質問15: 私は自分の行動と決定に責任を持つ。</p>
<input type="radio" name="q15" value="1"> はい
<input type="radio" name="q15" value="0"> 中立
<input type="radio" name="q15" value="-1"> いいえ
        </div>
        <!-- 自己有用感の質問16 -->
        <div class="question">
            <p>質問16: 私は他人にとって価値ある存在だと感じる。</p>
<input type="radio" name="q16" value="1"> はい
<input type="radio" name="q16" value="0"> 中立
<input type="radio" name="q16" value="-1"> いいえ
        </div>
        <!-- 自己有用感の質問17 -->
        <div class="question">
            <p>質問17: 私は自分の社会的役割に満足している。</p>
<input type="radio" name="q17" value="1"> はい
<input type="radio" name="q17" value="0"> 中立
<input type="radio" name="q17" value="-1"> いいえ
        </div>
        <!-- 自己有用感の質問18 -->
        <div class="question">
            <p>質問18: 私は自分が他人の生活に良い影響を与えていると感じる。</p>
<input type="radio" name="q18" value="1"> はい
<input type="radio" name="q18" value="0"> 中立
<input type="radio" name="q18" value="-1"> いいえ
        </div>
        <button type="button" id="submitButton" onclick="calculateScoresAndSendData()" disabled>結果を見る</button>
    </form>
    <div id="resetButtonContainer"></div> <!-- ここに新しいボタンが表示される -->
    <div id="result"></div>
    <canvas id="radarChart" width="400" height="400"></canvas>
</body>
</html>