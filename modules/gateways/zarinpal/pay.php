<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';


$gateway = getGatewayVariables("zarinpal");
if (!$gateway['type']) {
    die("Module Not Activated");
}

$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
if ($amount <= 0) {
    die("Wrong amount");
}
$invoice = isset($_POST['invoice']) ? $_POST['invoice'] : "";
if (!$invoice) {
    die("missing invoice id");
}
$email = isset($_POST['email']) ? $_POST['email'] : "";

$response = zarinpal_payment_request($gateway['merchant_id'], $invoice, $amount, $email);
if ($response['code'] == 100) {
    header('Location: https://www.zarinpal.com/pg/StartPay/' . $response['authority']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="utf-8">
    <title>خطا در ارسال به بانک</title>
    <style>
        body {
            font-family: tahoma;
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            direction: rtl;
        }

        .container {
            border: 1px dotted #c3c3c3;
            width: 60%;
            margin: 50px auto 0px auto;
            line-height: 25px;
            padding: 15px 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <p style="color:#ff0000;font-weight: bold;">خطا در ارسال به بانک</p>
        <p style="text-align:right;margin-right:8px;">در حال حاضر امکان اتصال به درگاه بانک وجود ندارد، لطفا دقایق دیگری مجددا تلاش کنید یا از سایر درگاه ها استفاده بفرمایید. (کد خطا: <strong><?php echo $response['code']; ?></strong>)</p>
        <a href="/viewinvoice.php?id=<?php echo $invoice; ?>">بازگشت »»</a>
    </div>
</body>
</html>
