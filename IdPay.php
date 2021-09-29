<?php

namespace App\Lib\Pay;
class IdPay extends Base
{
    public function __construct()
    {
        parent::__construct();

    }

    public function createPaymentGateway($order_id, $amount, $phone, $name = null, $desc = null, $mail = null, $reseller = null): array
    {
        $params = [
            'order_id' => $order_id,
            'amount' => $amount,
            'name' => $name,
            'phone' => $phone,
            'mail' => $mail,
            'desc' => $desc,
            'callback' => $this->CALLBACK,
            'reseller' => $reseller,
        ];

        return $this->requestHttp($params, $this->HEADER, 'payment');
    }

    public function verifyPayment($idPay,$order_id)
    {
        $params = [
            'id'=>$idPay,
            'order_id'=>$order_id
        ];
        return $this->requestHttp($params, $this->HEADER, 'payment/verify');
    }


    //---Initialize property in setters

    public function SET_ENDPOINT()
    {
        $this->END_POINT = env('IDPAY_ENDPOINT');
    }

    public function SET_CALLBACK()
    {
        $this->CALLBACK = route('pay.online.callback');
    }

    public function SET_API_TOKEN()
    {
        $this->API_TOKEN = env('IDPAY_TOKEN');
    }

    public function SET_HEADER()
    {
        $this->HEADER = [
            'Content-Type' => 'application/json',
            "X-API-KEY" => $this->API_TOKEN,
            'X-SANDBOX' => env('IDPAY_DEV_MODE') ? 1 : 0
        ];
    }

    public static function GET_LOG_ID_CLASS(): int
    {
        return 1;
    }


}

