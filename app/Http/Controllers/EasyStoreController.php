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
    public $cp_url;  // admin URL

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
    public $shop;

    public function __construct(Request $request){

        if (env("APP_ENV") == "production") {
            $this->client_id = env('EASYSTORE_CLIENT_ID');
            $this->client_secret = env('EASYSTORE_CLIENT_SECRET');

        } else {
            $this->client_id = env('EASYSTORE_CLIENT_ID_DEV');
            $this->client_secret = env('EASYSTORE_CLIENT_SECRET_DEV');
        }

        $this->cp_url = 'https://admin.easystore.co';
        $this->shop = str_replace(['https://', 'http://'], '', $request->shop);

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

        return view('index');

    }

    public function install(Request $request) {

        $code = $request->code;
        $host_url = $request->host_url;
        $timestamp = $request->timestamp;
        $shop_url = $request->shop;
        $hmac = $request->hmac;

        $this->host_url = $host_url;

        $hmac_correct = $this->verifyHmac($hmac, [ "code" => $code, "host_url" => $host_url, "shop" => $shop_url, "timestamp" => $timestamp ]);

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
        $this->registerShippingCurl($shop);
        $this->registerVerifyPickupCurl($shop);
        $this->registerListPickupCurl($shop);

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

        $this->slack_say(123);
        $input = $request->all();

        $shop_url = $_SERVER["HTTP_EASYSTORE_SHOP_DOMAIN"];
        // $shop_url = $input['shop'];

        if(!$shop_url)
            return response()->json(["errors" => "Shop not found"], 400);

        if(!$shop = Shop::where('url', $shop_url)->first()) return $this->redirectToInstall();

        $this->slack_say(json_encode($shop));

        $topic = $_SERVER["HTTP_EASYSTORE_TOPIC"];
        // $topic = $request->header('Easystore-Topic');

        if(!in_array($topic, ['shipping/list/non_cod'])) return response()->json(["errors" => "Topic invalid"], 400);

        $data = file_get_contents('php://input');
        $hmac = hash_hmac('sha256', $data, $this->client_secret);

        if ($hmac != $_SERVER["HTTP_EASYSTORE_HMAC_SHA256"]) {
        // if ($hmac != $request->header('Easystore-Hmac-Sha256')) {
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

        $shipping_rate = [

            [
                "id"                => "ep0001",
                "name"              => "Skynet - ".$shop['url'],
                "remark"            => "",
                "handling_fee"      => 10.00,
                "shipping_charge"   => 6.00,
                "courier_name"      => "Skynet",
                "courier_url"       => "https://s3-ap-southeast-1.amazonaws.com/easyparcel-static/Public/img/couriers/Skynet.jpg",
            ],
            [
                "id"                => "ep0002",
                "name"              => "PosLaju - ".$shop['access_token'],
                "remark"            => "",
                "handling_fee"      => 6.50,
                "shipping_charge"   => 5.00,
                "courier_name"      => "PosLaju",
                "courier_url"       => "https://s3-ap-southeast-1.amazonaws.com/easyparcel-static/Public/img/couriers/Pos_Laju.jpg",

            ]

        ];

       return response()->json(['rate' => $shipping_rate], 200);

    }

    public function listPickupMethods(Request $request)
    {
        $shop_url = $_SERVER["HTTP_EASYSTORE_SHOP_DOMAIN"];

        if ($_SERVER["HTTP_EASYSTORE_TOPIC"] != 'pickup/methods/list') {
            return response()->json(['errors' => 'Topic invalid'], 400);
        }

        $data = file_get_contents('php://input');
        $hmac = hash_hmac('sha256', $data, $this->client_secret);

        if ($hmac != $_SERVER["HTTP_EASYSTORE_HMAC_SHA256"]) {
            return response()->json(['errors' => 'Hmac validate fail'], 400);
        }

        $pickup_methods = [];

        $non_cod = [
            'id'                 => "noncod",
            'name'               => $shop_url,
            'cod_type'           => 0,
            'pickup_methods_url' => 'https://'.$shop_url.'/apps/easystore/non-cod',
            'verify_rate_url'    => 'https://testapp-easystore.herokuapp.com/easystore/pickup_verify_rate',
        ];

        array_push($pickup_methods, $non_cod);

        // if your logistic service provides COD
        $cod = [
            'id'                 => "cod",
            'name'               => "Test App CPD",
            'cod_type'           => 1,
            'pickup_methods_url' => '/apps/easystore/cod',
            'verify_rate_url'    => 'https://testapp-easystore.herokuapp.com/easystore/pickup_verify_rate',
        ];

        array_push($pickup_methods, $cod);

        return response()->json(['methods' => $pickup_methods], 200);

    }

    public function pickupIFrame(Request $request)
    {
        // $shop_url = $_SERVER["HTTP_EASYSTORE_SHOP_DOMAIN"];
        // $cart_token = $request->header('x-easystore-cart-token');
        // $order_token = $request->header('x-easystore-order-token');

        // if(!$shop_url)
        //     return response()->json(["errors" => "Shop not found"], 400);

        // if(!$shop = Shop::where('url', $shop_url)->first()) return $this->redirectToInstall();

        // Configure your pickup restrictions


        // Sample data
        $data = [
            // 'url' => $shop_url
        ];

        return view('non_cod_location', $data);

    }

    public function pickupVerifyRate(Request $request)
    {

        $data = file_get_contents('php://input');
        $hmac = hash_hmac('sha256', $data, $this->app_secret);

        $data = [
            'hmac' => $hmac,
            'other_hmac' => $request->header('Easystore-Hmac-Sha256')
        ];


        if ($request->header('Easystore-Topic') != 'pickup/verify') {


            return response()->json($data, 200);

            return response()->json(['errors' => 'Topic invalid'], 400);
        }


        // if ($hmac != $_SERVER["HTTP_EASYSTORE_HMAC_SHA256"]) {
        //     return response()->json(['errors' => 'Hmac validate fail'], 400);
        // }


        // Sample Data
        $pickup_location['location'] = [
            'name'          => 'Test Point Name',
            'address1'      => 'Test Address 1',
            'address2'      => 'Test Address 2',
            'city'          => 'Test City',
            'province'      => 'MY-10',
            'zip'           => '43000',
            'country_code'  => 'MY',
            'pickup_charge' => '100.00',
            'request'       => "",
            'topic'         => $hmac,
            'hmac'          => $request->header('Easystore-Hmac-Sha256')
        ];

        return response()->json($pickup_location, 200);

    }

    public function pickupIFrameRate(Request $request)
    {
        // get pickup rate by calling your logistics API and store in DB

        // sample pickup rate
        $pickup_rate = "100.00";

        $pickup_params = [
            'status'  => 'success',
            'name'    => 'Test Point Name',
            'address' => 'Test Address 1, Test Address 2',
            'price'   => $pickup_rate,
            'json'    => '',
          ];

        $cookie_params = json_encode($pickup_params);
        $cookie_params = base64_encode($cookie_params);

        return response()->json([
            'rate'          => $pickup_rate,
            'cookie_params' => $cookie_params
        ]);
    }

    public function redirectToFulfillment(Request $request) {

        $input = $request->all();
        $shop = Shop::where('url', $input['shop'])->first();

        if(!$shop) {
            return response()->json(['errors' => 'Shop not found'], 400);
        }

        // Initialize SDK
        $sdk = new SDK($this->client_id, $this->client_secret, $shop['url']);

        // Set EasyStore access token
        $sdk->set_access_token($shop['access_token']);

        // Use SDK to call EasyStore API
        $get_order = $sdk->get_order($input['order_id']);
        $get_customer = $sdk->get_customer($get_order['order']['customer_id']);

        $order_number = $get_order['order']['order_number']; // EasyStore Order Number
        $total_amount = $get_order['order']['total_amount_include_transaction'];
        $order_items  = $get_order['order']['line_items'];

        // Get address based on order (may include variable type to determine if order type is pickup / shipping)
        if(!empty($get_order['order']['pickup_address'])){
            $address = $get_order['order']['pickup_address']; // customer will pickup the order
        } elseif(!empty($get_order['order']['shipping_address'])){
            $address = $get_order['order']['shipping_address']; // merchant will ship the order
        } elseif (!empty($get_order['order']['billing_address'])) {
            $address = $get_order['order']['billing_address'];
        }

        // Common data required for fulfillment
        $data = [
            "shop"              => $shop['url'],
            "address"           => $address,
            "customer"          => $get_customer['customer'],
            "order_id"          => $input['order_id'],
            "order_number"      => $order_number,
            "total_amount"      => $total_amount,
            "order_item"        => $order_items,
            'billing_address'   => $get_order['order']['billing_address'], // May pass in additional billing address data to obtain receiver details
        ];

        return view('create_fulfillment', $data);

    }

    public function createFulfillment(Request $request) {

        // Get inputs from create_fulfillment blade file
        $input = $request->all();
        $order_id = $input['order_id'];

        $shop = Shop::where('url', $input['shop'])->first();

        $sdk = new SDK($this->client_id, $this->client_secret, $shop['url']);
        $sdk->set_access_token($shop['access_token']);

        $get_order = $sdk->get_order($order_id);

        $fulfill_items = [];

        foreach ($get_order['order']['line_items'] as $key => $value) {
            array_push($fulfill_items, [
                "id" => $value['id'],
                "quantity" => $value['quantity']
            ]);
        }

        /* format for fullfillment params

        tracking_number  => tracking ID
        tracking_company => courier name
        tracking_url     => URL for buyer / seller to check the parcel status
        status           => current status for this fulfillment
        is_mail          => indicator to send email notification to buyer. send = 1, not to send = 0
        message          => a short message to display in this order
        line_items       => list of items (id and quantity only)

        */

        $fulfillment_params = [
            "tracking_number"     => "EP00011323187924MY",
            "tracking_company"    => "PosLaju",
            "tracking_url"        => "https://your-app-tracking-urls.com",
            "is_mail"             => 0,
            "message"             => "Download your airway bill <a href='https://airwaybills.com'>here</a>",
            "line_items"          => json_encode($fulfill_items),
            "service"             => "Sample Service",
            "app_handle"          => env('APP_HANDLE'),
            "note"                => "",
        ];

        $create_fulfillment = $sdk->create_fulfillment($order_id, $fulfillment_params);

        // sample data, maybe arrange based on your needs
        $data = [
            'order_number' => $get_order['order']['order_number'],
            'tracking_number' => $fulfillment_params['tracking_number'],
            'tracking_url'  => $fulfillment_params['tracking_url'],
            'back_to_order' => $this->cp_url.'/orders/'.$order_id,
        ];

        return view('fulfillment_success', $data);

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

    private function registerShippingCurl($shop){

        $url = 'https://'.$shop->url.'/api/1.0/curls.json';

        $curl_url = "https://".$_SERVER['SERVER_NAME'].'/easystore/storefront/rates';
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

    private function registerListPickupCurl($shop){

        $url = 'https://'.$shop->url.'/api/1.0/curls.json';

        $pickup_method_url = "https://".$_SERVER['SERVER_NAME']."/easystore/pickup_methods";

        $access_token = $shop->access_token;

        $data = json_encode([
            'curl' => [
                'topic' => 'pickup/methods/list',
                'url'   => $pickup_method_url,
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

    private function registerVerifyPickupCurl($shop){

        $url = 'https://'.$shop->url.'/api/1.0/curls.json';

        $pickup_verify_url = "https://".$_SERVER['SERVER_NAME']."/easystore/pickup_verify_rate";

        $access_token = $shop->access_token;

        $data = json_encode([
            'curl' => [
                'topic' => 'pickup/verify',
                'url'   => $pickup_verify_url,
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

    public function slack_say($text){
        $msg = "payload=".json_encode([
            'text' => $text,
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ENV('SLACK_URL'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
        $reply = curl_exec($ch);
        curl_close($ch);
    }


}
