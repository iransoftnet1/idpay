<?php

namespace App\Lib\Pay;

use App\Models\ActivityRequest;
use GuzzleHttp\Client;

abstract class Base
{
    protected $END_POINT;
    protected $HEADER;
    protected $API_TOKEN;
    protected $CALLBACK;

    //for enum -> log
    protected $LOG_METHOD_REQUEST;
    private $tempLogModel;

    abstract public function SET_ENDPOINT();

    abstract public function SET_API_TOKEN();

    abstract public function SET_CALLBACK();

    abstract public function SET_HEADER();

    abstract public function createPaymentGateway($order_id, $amount, $phone, $name = null, $desc = null, $mail = null, $reseller = null): array;

    abstract public function verifyPayment($idPay, $order_id);

    abstract public static function GET_LOG_ID_CLASS(): int;

    public function __construct()
    {
        $this->registerLogProperty();
        $this->SET_ENDPOINT();
        $this->SET_API_TOKEN();
        $this->SET_CALLBACK();
        $this->SET_HEADER();
    }


    /**
     * @param $params
     * @param $header
     * exam url ---> payment/verify _ Without base and [/]
     * @param $url
     *  POST OR GET
     * @param string $method
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function requestHttp(array $params, array $header, string $url, $method = 'POST'): array
    {
        $this->logRequest($params, $header, $url, $method);
        $url = $this->END_POINT . $url;

        $client = new Client();
        $starttime = microtime(1);

        try {


            $response = $client->request($method, $url,
                [
                    'json' => $params,
                    'headers' => $header,
                    'http_errors' => false
                ]);

            $elapsed = microtime(1) - $starttime;
            $elapsed = number_format((float)$elapsed, 3, '.', '');
            $response->elapsed = $elapsed;

            $this->logResponse($response->getStatusCode(),$params,json_decode($response->getBody(), true),$response->elapsed);
            return [
                'http_code' => $response->getStatusCode(),
                'request' => $params,
                'response' => json_decode($response->getBody(), true),
                'request_time' => $response->elapsed
            ];


        } catch (\Exception $exception) {
            $this->logResponse($exception->getCode(),$params,$exception->getMessage(),0);
            return [
                'http_code' => $exception->getCode(),
                'request' => $params,
                'response' => env('APP_DEBUG') ? $exception->getMessage() : 'error'
            ];
        }
    }



    //-------------log Request and Response

    //just Log in db
    protected function registerLogProperty()
    {
        $this->LOG_METHOD_REQUEST = [
            'createPaymentGateway' => 1,
            'verifyPayment' => 2
        ];
    }


    protected function logRequest($params, $header, $url, $method)
    {
        unset($params['callback'], $params['reseller']);
        $this->tempLogModel = ActivityRequest::create([
            'order_id' => $params['order_id'],
            'method' => $this->LOG_METHOD_REQUEST[debug_backtrace()[2]['function']],
            'gateway' => call_user_func([get_called_class(), 'GET_LOG_ID_CLASS']),
            'request' => json_encode($params)
        ]);

    }

    protected function logResponse($httpCode, $param,$response,$request_time)
    {
        $this->tempLogModel->update([
            'http_code'=>$httpCode,
            'request_time'=>$request_time,
            'response'=>$response
        ]);
    }
}
