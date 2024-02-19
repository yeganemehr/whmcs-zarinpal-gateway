<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$gateway = getGatewayVariables("zarinpal");
if (!$gateway['type']) {
    die("Module Not Activated");
}

$error = array(
    'message' => '',
);

$amount = isset($_GET['amount']) ? $_GET['amount'] : '';
$invoice = isset($_GET['invoice']) ? $_GET['invoice'] : '';
$hash = isset($_GET['hash']) ? $_GET['hash'] : '';
$verifyHash = hash("sha256", "{$amount}-{$invoice}-{$gateway['merchant_id']}");
$authority = isset($_GET['Authority']) ? $_GET['Authority'] : '';
$status = isset($_GET['Status']) ? $_GET['Status'] : 'NOK';
if ($authority and in_array($status, ['OK', 'NOK']) or $verifyHash != $hash) {
    if ($status == 'OK') {
        $invoice = checkCbInvoiceID($invoice, 'zarinpal');
        $response = zarinpal_payment_verify($gateway['merchant_id'], $authority, $amount);
        if ($response['code'] == 100) {
            $fee = (isset($response['fee_type'], $response['fee']) and $response['fee_type'] == 'Merchant') ? $response['fee'] : 0;
            logTransaction("zarinpal", array(
                'invoice' => $invoice,
                'authority' => $authority,
                'amount' => $amount,
                'hash' => $hash,
                'card_hash' => isset($response['card_hash']) ? $response['card_hash'] : null,
                'card_pan' => isset($response['card_pan']) ? $response['card_pan'] : null,
                'ref_id' => isset($response['ref_id']) ? $response['ref_id'] : null,
            ), "موفق");
            addInvoicePayment($invoice, $authority, $amount, $fee, "zarinpal");
            header('Location: /viewinvoice.php?id=' . $invoice);
            exit;
        } else if ($response['code'] == 101) {
            $error = array(
                'message' => 'این تراکنش قبلا در سیستم اعمال شده. درصورتی که هنوز فاکتور شده بصورت پرداخت نشده باقی‌مانده لطفا با پشتیبانی در تماس باشید'
            );
        }
    } else {
        $error = array(
            'message' => 'تراكنش ناموفق بوده یا توسط شما لغو شده است.'
        );
    }
} else {
    $error = array(
        'message' => 'درخواست شما معتبر نمی‌باشد'
    );
}

?>
<!DOCTYPE html>
<html lang="fa">

<head>
	<meta charset="utf-8">
	<title>خطا در روند پرداخت آنلاین</title>
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
		<p style="color:#ff0000;font-weight: bold;"><b>خطا در روند پرداخت آنلاین</b></p>
		<p style="text-align:right;margin-right:8px;"><?php echo $error['message']; ?></p>
		<p style="text-align:right;margin-right:8px;">درصورتی که مبلغی از حساب شما برداشت شده و تا ۲۴ ساعت آینده به حساب شما مرجوع نگردید، لطفا با پشتیبانی در ارتباط باشید.</p>
		<a href="/viewinvoice.php?id=<?php echo $invoice; ?>">بازگشت »»</a>
	</div>
</body>
</html>