<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\EasyStore as SDK;

use App\Shop;

class EasyStoreController extends Controller
{

    // APP client id and client secret from partners portal
    private $client_id;
    private $client_secret;

    // Common app_scopes for logistics app
    private $app_scopes = [
        'read_orders',
        'write_orders',
        'read_fulfillments',
        'write_fulfillments',
        'write_shipping',
        'read_customers',
        'write_locations'
    ];

    private $host_url;
    private $redirect_path = "/easystore/install";

    public function __construct(Request $request){

        if (env("APP_ENV") == "production") {
            $this->client_id = env('EASYSTORE_CLIENT_ID');
            $this->client_secret = env('EASYSTORE_CLIENT_SECRET');
        } else {
            $this->client_id = env('EASYSTORE_CLIENT_ID_DEV');
            $this->client_secret = env('EASYSTORE_CLIENT_SECRET_DEV');
        }

    }

    public function index(Request $request) {

        $host_url = $request->host_url;
        $shop_url = $request->shop;
        $timestamp = $request->timestamp;
        $hmac = $request->hmac;

        $this->host_url = $host_url;

        $shop = Shop::where('url', $shop_url)
                    ->where('is_deleted', false)
                    ->first();

        if (!$shop) {
            return $this->redirectToInstall();
        }

        var_dump('URL', $shop->url);
        var_dump('ACCESS_TOKEN', $shop->access_token);


        return view('index');

    }

    public function install(Request $request) {

        $code = $request->code;
        $host_url = $request->host_url;
        $timestamp = $request->timestamp;
        $shop_url = $request->shop;
        $hmac = $request->hmac;

        $this->host_url = $host_url;

        if (env("APP_ENV") == "production") {
            $hmac_correct = $this->verifyHmac($hmac, [ "code" => $code, "host_url" => $host_url, "shop" => $shop_url, "timestamp" => $timestamp ]);
        } else {
            $hmac_correct = $this->verifyHmac($hmac, [ "code" => $code, "shop" => $shop_url, "timestamp" => $timestamp ]);
        }

        if (!$hmac_correct) {
            return response()->json(['errors' => 'Hmac validate fail'], 400);
        }

        $data = [
            'shop_url' => $shop_url,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code
        ];

        $access_token = $this->getAccessToken($data);

        if (!$access_token) {
            return $this->redirectToInstall();
        }

        $shop = Shop::where('url', $shop_url)->first();

        if(empty($shop)){
            $shop = new Shop;
            $shop->url = $shop_url;
        }

        $shop->access_token = $access_token;
        $shop->is_deleted = false;
        $shop->save();

        $this->subscribeUninstallWebhook($shop);
        $this->registerCurl($shop);

        $host_url = $this->host_url ?? "https://admin.easystore.co";
        $client_id = $this->client_id;

        $setting_url = "$host_url/apps/installed/$client_id";

        return redirect()->away($setting_url);


    }

    public function uninstall(Request $request) {

        if ($request->header('Easystore-Topic') != 'app/uninstall') {
            return response()->json(['errors' => 'Topic invalid'], 400);
        }

        $data = file_get_contents('php://input');
        $this->slack_say("#cx", json_encode($data));
        $hmac = hash_hmac('sha256', $data, $this->client_secret);
        $shop_url = $request->header('Easystore-Shop-Domain');

        if ($hmac != $request->header('Easystore-Hmac-Sha256')) {
            return response()->json(['errors' => 'Hmac validate fail'], 400);
        }

        $shop = Shop::where('url', $shop_url)->first();

        if (!$shop) {
            return response()->json(['errors' => 'Shop not exists'], 200);
        }

        $shop->is_deleted = true;
        $shop->save();

        return response()->json(['success' => 'Shop deleted successfully.'], 200);


    }

    public function getRatesSF(Request $request) {

        $shipping_rate = [];
        $shop = str_replace("https://", NULL, $request->get('shop'));

        if(!$shop = Shop::where('url', $shop)->first()) return $this->redirectToInstall();

        if ($request->header('Easystore-Topic') != 'shipping/list/non_cod') {
            return response()->json(['errors' => 'Topic invalid'], 400);
        }

        $data = file_get_contents('php://input');
        $hmac = hash_hmac('sha256', $data, $this->client_secret);

        if ($hmac != $request->header('Easystore-Hmac-Sha256')) {
            return response()->json(['errors' => 'Hmac validate fail'], 400);
        }

        /* Format for shipping rate

        id               => unique ID for your shipping service
        name             => name for your shipping service
        remark           => remark in the order / fulfillment ('parcel', 'mail', etc)
        handling_fee     => handling fee for your shipping service
        shipping_charge  => shipping charge for your shipping service
        courier_name     => courier name for your shipping service
        courier_url      => image url for your shipping service

        */

        $sample_shipping1 = [
            "id" => "ep0001",
            "name" => "Skynet",
            "remark" => "",
            "handling_fee" => 10,
            "shipping_charge" => 6.00,
            "courier_name" => "Skynet",
            "courier_url" => "https://s3-ap-southeast-1.amazonaws.com/easyparcel-static/Public/img/couriers/Skynet.jpg",
        ];

        array_push($shipping_rate, $sample_shipping1);

        $sample_shipping2 = [
            "id" => "ep0002",
            "name" => "PosLaju",
            "remark" => "",
            "handling_fee" => 6.50,
            "shipping_charge" => 0.00,
            "courier_name" => "PosLaju",
            "courier_url" => "https://s3-ap-southeast-1.amazonaws.com/easyparcel-static/Public/img/couriers/Pos_Laju.jpg",
        ];

        array_push($shipping_rate, $sample_shipping2);


       return response()->json(['rate' => $shipping_rate], 200);

    }

    public function redirectToFulfillment(Request $request) {

        $input = $request->all();
        $shop = Shop::where('url', $input['shop'])->first();

        if(!$shop) {
            return response()->json(['errors' => 'Shop not found'], 400);
        }

        $store = [
            'url' => $shop['url'],
            'access_token' => $shop['access_token'],
            'order_id' => $input['order_id'],
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];

        $sdk = new SDK($this->client_id, $this->client_secret, $shop['url']);

        $test_sdk = $sdk->test_sdk($store);
        $sdk->set_access_token($shop['access_token']);
        $get_order = $sdk->get_order($input['order_id']);

        dd($get_order);

        return view('fulfillment', $input);

    }

    public function createFulfillment(Request $request) {

        $input = $request->all();

        dd($input);

    }


    private function getAccessToken($data) {

        $shop_url = $data["shop_url"];

        $url = 'https://'.$shop_url.'/api/1.0/oauth/access_token';

        $data = [
            'client_id' => $data["client_id"],
            'client_secret' => $data["client_secret"],
            'code' => $data["code"]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        $access_token = $result["access_token"] ?? null;

        return $access_token;

    }

    private function subscribeUninstallWebhook($shop) {

        $url = 'https://'.$shop->url.'/api/1.0/webhooks.json';

        $webhook_url = "https://" . $_SERVER['SERVER_NAME'] . '/easystore/uninstall';
        $access_token = $shop->access_token;

        $data = json_encode([
            'webhook' => [
                'topic' => 'app/uninstall',
                'url' => $webhook_url,
            ]
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["EasyStore-Access-Token: $access_token"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

    }

    private function registerCurl($shop){

        $url = 'https://'.$shop->url.'/api/1.0/curls.json';

        $curl_url = "https://" . $_SERVER['SERVER_NAME'] . '/easystore/storefront/rates';
        $access_token = $shop->access_token;

        $data = json_encode([
            'curl' => [
                'topic' => 'shipping/list/non_cod',
                'url' => $curl_url,
            ]
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["EasyStore-Access-Token: $access_token"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

    }

    private function redirectToInstall() {

        $redirect_uri = "https://" . $_SERVER['SERVER_NAME'] . $this->redirect_path;

        $host_url = $this->host_url ?? "https://admin.easystore.co";

        $url = "$host_url/oauth/authorize?app_id=". $this->client_id ."&scope=". implode(",", $this->app_scopes) ."&redirect_uri=" . $redirect_uri;

        return redirect()->away($url);

    }

    private function verifyHmac($hmac, $data) {

        ksort($data);

        $data = urldecode(http_build_query($data));

        $calculated = hash_hmac('sha256', $data, $this->client_secret);

        return $hmac === $calculated;
    }

    private function slack_say($channel, $text){
        $msg = "payload=".json_encode([
            'text' => $text,
            'channel' => $channel,
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://hooks.slack.com/services/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");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
        $reply = curl_exec($ch);
        curl_close($ch);
    }


}
