<?php
class ControllerExtensionPaymentInstpay extends Controller {
	public function index() {
		return $this->load->view('extension/payment/instpay');
	}

	public function confirm() {

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $timestamp = $this->getMillisecond();
        $method = 'POST';
        $requestPath = '/api/v1/payment';

        $url = $this->config->get('payment_inst_host') . $requestPath;
        $key = $this->config->get('payment_inst_api_key') . '';
        $secret = $this->config->get('payment_inst_api_secret') . '';
        $passphrase = $this->config->get('payment_inst_api_passphrase') . '';

        $post_data = array(
            'currency' => $order_info['currency_code'],
            'amount' => number_format($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false), 2),
            'cust_order_id' => 'OpenCart_' . $key . '_' .$order_info['order_id']
        );

        $sign = $this->sign($timestamp, $method, $requestPath, '', $key, $secret, $post_data);
        $authorization = 'Inst:' . $key . ':' . $timestamp . ':' . $sign;
        $result = $this->send_post($url, json_encode($post_data), $authorization, $passphrase);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput($result);
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
        if (!empty($body)) {
            // ksort()对数组按照键名进行升序排序
            ksort($body);
            // reset()内部指针指向数组中的第一个元素
            reset($body);
            $preHash = $preHash . http_build_query($body);
        }
        return $preHash;
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
}
