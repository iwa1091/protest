<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>取引完了のお知らせ</title>
    <style>
        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 20px;
            line-height: 1.8;
        }
        .container {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        h1 {
            color: #2c3e50;
            font-size: 20px;
            margin-bottom: 16px;
        }
        ul {
            background: #f6f8fa;
            padding: 16px;
            border-radius: 6px;
            list-style: none;
        }
        li {
            margin-bottom: 6px;
        }
        .footer {
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            margin-top: 24px;
            padding-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $sellerName }} 様</h1>

        <p>以下の商品について、購入者 <strong>{{ $buyerName }}</strong> 様より「取引完了」の報告がありました。</p>

        <ul>
            <li><strong>商品名：</strong>{{ $itemName }}</li>
            <li><strong>販売価格：</strong>¥{{ number_format($price) }}</li>
        </ul>

        <p>
            お取引ありがとうございました。<br>
            今後の対応（評価・確認など）をお願いいたします。
        </p>

        <div class="footer">
            <p>------------------------------<br>
            COACHTECH フリマ運営チーム<br>
            このメールは自動送信されています。<br>
            ------------------------------</p>
        </div>
    </div>
</body>
</html>
