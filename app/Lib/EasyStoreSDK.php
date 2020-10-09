<?php

namespace App\Lib;

class EasyStore {

    public $client_id;
    public $client_secret;
    public $shop;

    protected $header = ['Content-Type: application/json'];

    public function __construct($client_id, $client_secret, $shop){

        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->shop = $shop;

    }

    public function generate_hmac($string){

        return hash_hmac( "sha256", $string, $this->client_secret);


    }

    public function verify_hmac($hmac1, $hmac2){

        return hash_equals($hmac1, $hmac2);


    }

    public function get_access_token($code){

        $response = $this->call(
            "https://".$this->shop."/api/1.0/oauth/access_token",
            "POST",
            [
                "client_id" => $this->client_id,
                "client_secret" => $this->client_secret,
                "code" => $code
            ]
        );

        if(isset($response["access_token"])){

            return $response["access_token"];

        }else{

            throw new Exception("invalid grant");

        }

    }

    public function set_access_token($access_token){

        $this->header = array_merge($this->header, [ 'easystore-access-token: '.$access_token ]);

    }

    public function get_store_detail(){

        $response = $this->call(
            "https://".$this->shop."/api/1.0/store.json",
            "GET"
        );

        if(isset($response["store"])){

            return $response;

        }else{

            throw new Exception("get store detail failed");

        }

    }

    public function get_order($order_id){

        $response = $this->call(
            "https://".$this->shop."/api/3.0/orders/".$order_id.".json",
            "GET"
        );

        if(isset($response["order"])){

            return $response;

        }else{

            throw new Exception("get order detail failed");

        }

    }

    public function get_customer($customer_id){

        $response = $this->call(
            "https://".$this->shop."/api/3.0/customers/".$customer_id.".json",
            "GET"
        );

        if(isset($response["customer"])){

            return $response;

        }else{

            throw new Exception("get customer details failed");
        }
    }

    public function create_fulfillment($order_id, $fulfillment_params){

        $response = $this->call(
            "https://".$this->shop."/api/3.0/orders/".$order_id."/fulfillments.json",
            "POST",
            $fulfillment_params
        );

        if(isset($response["fulfillment"])){

            return $response;

        }else{

            throw new Exception("get fulfillment detail failed");

        }

    }

    public function subscribe_webhook($params){

        $response = $this->call(
            "https://".$this->shop."/api/1.0/webhooks.json",
            "POST",
            [
                'webhook' => $params
            ]
        );

        if(isset($response["url"])){

            return $response;

        }else{

            throw new Exception("subscribe webhook failed");

        }

    }

    public function register_curl($params){

        $response = $this->call(
            "https://".$this->shop."/api/1.0/curls.json",
            "POST",
            [
                'curl' => $params
            ]
        );

        if(isset($response["url"])){

            return $response;

        }else{

            throw new Exception("subscribe curls failed");

        }

    }

    public function test_sdk($params) {
        return 123;
    }

    private function call($url, $method, $payload = null){



        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_MAXREDIRS => 300,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $this->header
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);
        curl_close($curl);

        return $response;

    }

}
