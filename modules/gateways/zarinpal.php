<?php

use WHMCS\Config\Setting;

function zarinpal_MetaData(): array
{
    return array(
        'DisplayName' => 'Zarinpal',
        'APIVersion' => '1.1',
    );
}

function zarinpal_config(): array
{
    return array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => "Zarinpal Payment"
        ),
        "merchant_id" => array(
            "FriendlyName" => "Merchant ID",
            "Type" => "text",
        ),
        "currency" => array(
            "FriendlyName" => "Curreny",
            "Type" => "dropdown",
            "Options" => "Rial,Toman",
            "Description" => "Please choose your system's currency"
        ),
    );
}

/**
 * @param array{amount:string,invoiceid:int,clientdetails:array{email:string}}
 */
function zarinpal_link(array $params): string
{
    $params['amount'] = explode('.', $params['amount']);
    $params['amount'] = intval($params['amount'][0]);

    if (strtolower($params['currency']) == 'rial') {
        $params['amount'] = ceil($params['amount'] / 10);
    }
    if ($params['amount'] < 1000) {
        return "<p style=\"color:red\">امکان پرداخت فاکتور هایی با مبلغ کمتر از 10,000 ریال از طریق این درگاه وجود ندارد.</p>";
    }

    return "<form method=\"POST\" action=\"modules/gateways/zarinpal/pay.php\">
        <input type=\"hidden\" name=\"invoice\" value=\"{$params['invoiceid']}\" />
        <input type=\"hidden\" name=\"amount\" value=\"{$params['amount']}\" />
		<input type=\"hidden\" name=\"email\" value=\"{$params['clientdetails']['email']}\" />
        <input type=\"submit\" name=\"pay\" value=\" پرداخت \" />
    </form>";
}

/**
 * @return array{code:int,message?:string,authority?:string}
 */
function zarinpal_payment_request(string $merchantId, int $invoice, string $amount, ?string $email = null): array
{

    $data = array(
        "merchant_id" => $merchantId,
        "amount" => $amount,
        "callback_url" => Setting::getValue('SystemURL') . "/modules/gateways/zarinpal/callback.php?" . http_build_query(array(
            'amount' => $amount,
            'invoice' => $invoice,
            'hash' => hash("sha256", "{$amount}-{$invoice}-{$merchantId}")
        )),
        "description" => "صورتحساب #{$invoice}",
        "metadata" => [],
    );
    if ($email) {
        $data['metadata']['email'] = $email;
    }
    return zarinpal_api_request("payment/request.json", $data);
}

/**
 * @return array{code:int}
 */
function zarinpal_payment_verify(string $merchantId, string $authority, string $amount): array
{
    return zarinpal_api_request("payment/verify.json", array(
        "merchant_id" => $merchantId,
        "amount" => $amount,
        "authority" => $authority,
    ));
}

/**
 * @return array{code:int}
 */
function zarinpal_api_request(string $uri, array $data): array
{

    $data = json_encode($data);
    $ch = curl_init('https://api.zarinpal.com/pg/v4/' . $uri);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));

    $result = curl_exec($ch);
    if ($result === false) {
        return array(
            'code' => 0,
        );
    }
    $result = json_decode($result, true);
    curl_close($ch);

    if (!isset($result['data'])) {
        return array(
            'code' => -10000,
        );
    } elseif (!$result['data'] and isset($result['errors']) and $result['errors']) {
        return $result['errors'];
    }


    return $result['data'];
}
