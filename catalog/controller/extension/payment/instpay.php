<?php
class ControllerExtensionPaymentInstpay extends Controller {
    public function index() {
        return $this->load->view('extension/payment/instpay');
    }

    public function confirm() {

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $payment_info = $this->session->data; // todo test

        $timestamp = $this->getMillisecond();
        $method = 'POST';
        $requestPath = '/api/v1/payment';

        $url = $this->config->get('payment_inst_host') . $requestPath;
        $key = $this->config->get('payment_inst_api_key') . '';
        $secret = $this->config->get('payment_inst_api_secret') . '';
        $passphrase = $this->config->get('payment_inst_api_passphrase') . '';

        $customer = array(
//            'email' => $payment_info['payment_address']['email'],
            'phone' => '+' . $order_info['payment_calling_code'] . ' ' . $order_info['payment_telephone'],
            'first_name' => $order_info['payment_firstname'],
            'last_name' => $order_info['payment_lastname'],
//            'country' => $order_info['payment_country'],
//            'state' => $order_info['payment_zone'],
//            'city' => $order_info['payment_city'],
//            'address' => $order_info['payment_address_format'],
//            'zipcode' => '',
        );

//        $product_info = $payment_info['cart_paypal'];
        $product_info = array(
            'name' => 'test'
        );

        $shipping_info = array(
            'address' => 'zh-cn'
//            'shipping_firstname' => $order_info['shipping_firstname'],
//            'shipping_lastname' => $order_info['shipping_lastname'],
//            'shipping_calling_code' => $order_info['shipping_calling_code'],
//            'shipping_telephone' => $order_info['shipping_telephone'],
//            'shipping_company' => $order_info['shipping_company'],
//            'shipping_address_1' => $order_info['shipping_address_1'],
//            'shipping_address_2' => $order_info['shipping_address_2'],
//            'shipping_postcode' => $order_info['shipping_postcode'],
//            'shipping_city' => $order_info['shipping_city'],
//            'shipping_city_id' => $order_info['shipping_city_id'],
//            'shipping_zone_id' => $order_info['shipping_zone_id'],
//            'shipping_zone' => $order_info['shipping_zone'],
//            'shipping_zone_code' => $order_info['shipping_zone_code'],
//            'shipping_country_id' => $order_info['shipping_country_id'],
//            'shipping_country' => $order_info['shipping_country'],
//            'shipping_county_id' => $order_info['shipping_county_id'],
//            'shipping_county' => $order_info['shipping_county'],
//            'shipping_iso_code_2' => $order_info['shipping_iso_code_2'],
//            'shipping_iso_code_3' => $order_info['shipping_iso_code_3'],
//            'shipping_address_format' => $order_info['shipping_address_format'],
//            'shipping_custom_field' => $order_info['shipping_custom_field'],
//            'shipping_method' => $order_info['shipping_method'],
//            'shipping_code' => $order_info['shipping_code'],
        );

        $post_data = $this->formatArray(array(
            'currency' => $order_info['currency_code'],
            'amount' => number_format($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false), 2),
            'cust_order_id' => 'OpenCart_' . $key . '_' .$order_info['order_id'],
            'customer' => $customer,
            'product_info' => $product_info,
            'shipping_info' => $shipping_info,
        ));

        $sign = $this->sign($timestamp, $method, $requestPath, '', $key, $secret, $post_data);
        $authorization = 'Inst:' . $key . ':' . $timestamp . ':' . $sign;
        $result = $this->send_post($url, json_encode($post_data), $authorization, $passphrase);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput($result);

//        // for test
//        $preHash = $this->preHash($timestamp, $method, $requestPath, '', $key, $post_data);
//        $this->response->setOutput(json_encode($preHash));

    }

    private function sign($timestamp, $method, $requestPath, $queryString, $apiKey, $apiSecret, $body) {
        $preHash = $this->preHash($timestamp, $method, $requestPath, $queryString, $apiKey, $body);
        $sign = hash_hmac('sha256', utf8_encode($preHash) , utf8_encode($apiSecret), true);
        return base64_encode($sign);
    }

    private function preHash($timestamp, $method, $requestPath, $queryString, $apiKey, $body) {
        $preHash = $timestamp . $method . $apiKey . $requestPath;
        if (!empty($queryString)) {
            $preHash = $preHash . '?' . urldecode($queryString);
        }

        $postStr = '';
        if (!empty($body)){
            foreach ($body as $key => $value) {
                if (is_array($value)) {
                    $postStr .= $key.'=' .json_encode($value).'&';
                } else {
                    $postStr .= $key.'=' .$value.'&';
                }
            }
            $postStr = substr($postStr ,0, -1);
        }
        return $preHash . $postStr;
    }

//    private function appendBody($body) {
//        foreach ($arr as $key => $value) {
//            $arr[$key] = $value . '_i';
//        }
//    }

    private function send_post( $url , $post_data , $authorization, $access_Passphrase) {

        $curl = curl_init($url);

        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($curl, CURLOPT_POST, true);
        curl_setopt ($curl, CURLOPT_POSTFIELDS, ($post_data) );
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
            "Content-Type: application/json; charset=utf-8",
            "Accept: application/json",
            "Authorization:" . $authorization,
            "Access-Passphrase:" . $access_Passphrase,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $responseText = curl_exec($curl);
        if (!$responseText) {
            $this->log->write('INSTPAY NOTIFY CURL_ERROR: ' . var_export(curl_error($curl), true));
        }
        curl_close($curl);

        return $responseText;
    }


    private function getMillisecond() {
        list($s1,$s2)=explode(' ',microtime());
        return (float)sprintf('%.0f',(floatval($s1)+floatval($s2))*1000);
    }

    public function formatArray($array) {
        if (is_array($array)) {
            ksort($array);
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->formatArray($value);
                }
            }
        }
        return $array;
    }

    public function callback() {
        $this->response->addHeader('Content-Type: application/json');

        $response = array(
            'code' => 0,
            'msg' => 'SUCCESS',
        );

        // 判断是否开启推送功能
        $status = $this->config->get('payment_instpay_webhooks_status');
        if (!$status) {
            $response['code'] = 1;
            $response['msg'] = 'REFUSE';
            $this->response->setOutput(json_encode($response));
            return;
        }

        $this->log->write('inst pay notify:');

//        // todo 验签
//        $result = $this->check();

        $result = true;

        if ($result) { //check succeed
            $this->log->write('Instpay check succeed');
            $tmpData = strval(file_get_contents("php://input"));
            $dataArray = json_decode($tmpData, true);

            if (strcmp($dataArray['action'], 'order_result') == 0) {
                foreach ($dataArray['events'] as $val) {
                    $value = json_decode($val, true);
                    $order_id = substr($value['params']['cust_order_id'], 42);
                    $this->load->model('checkout/order');
                    $order_info = $this->model_checkout_order->getOrder($order_id);
                    if ($order_info == false) {
                        $response['code'] = 2;
                        $response['msg'] = 'ORDER NOT EXIST';
                        $this->response->setOutput(json_encode($response));
                        return;
                    }

                    if ($value['params']['status'] == 1) {
                        $this->load->model('checkout/order');
                        $this->model_checkout_order->addOrderHistory($order_id, 1);
                    } // todo 其他订单状态可自行添加
                }

            } // todo 是否需要接收其他推送action？
            $this->response->setOutput(json_encode($response));
        } else {
            $this->log->write('Instpay check failed');
            $response['code'] = 3;
            $response['msg'] = 'FAIL';
            $this->response->setOutput(json_encode($response));
        }
    }
}
