<?php

class ControllerExtensionPaymentInstpay extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/instpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting('payment_inst', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['host'])) {
            $data['error_host'] = $this->error['host'];
        } else {
            $data['error_host'] = '';
        }

        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }

        if (isset($this->error['api_secret'])) {
            $data['error_api_secret'] = $this->error['api_secret'];
        } else {
            $data['error_api_secret'] = '';
        }

        if (isset($this->error['api_passphrase'])) {
            $data['error_api_passphrase'] = $this->error['api_passphrase'];
        } else {
            $data['error_api_passphrase'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/instpay', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/instpay', 'user_token=' . $this->session->data['user_token']);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

        if (isset($this->request->post['payment_inst_host'])) {
            $data['payment_inst_host'] = $this->request->post['payment_inst_host'];
        } else {
            $data['payment_inst_host'] = $this->config->get('payment_inst_host');
        }

        if (isset($this->request->post['payment_inst_api_key'])) {
            $data['payment_inst_api_key'] = $this->request->post['payment_inst_api_key'];
        } else {
            $data['payment_inst_api_key'] = $this->config->get('payment_inst_api_key');
        }

        if (isset($this->request->post['payment_inst_api_secret'])) {
            $data['payment_inst_api_secret'] = $this->request->post['payment_inst_api_secret'];
        } else {
            $data['payment_inst_api_secret'] = $this->config->get('payment_inst_api_secret');
        }

        if (isset($this->request->post['payment_inst_api_passphrase'])) {
            $data['payment_inst_api_passphrase'] = $this->request->post['payment_inst_api_passphrase'];
        } else {
            $data['payment_inst_api_passphrase'] = $this->config->get('payment_inst_api_passphrase');
        }

        if (isset($this->request->post['payment_instpay_webhooks_status'])) {
            $data['payment_instpay_webhooks_status'] = $this->request->post['payment_instpay_webhooks_status'];
        } else {
            $data['payment_instpay_webhooks_status'] = $this->config->get('payment_instpay_webhooks_status');
        }

        if (isset($this->request->post['payment_instpay_geo_zone_id'])) {
            $data['payment_instpay_geo_zone_id'] = $this->request->post['payment_instpay_geo_zone_id'];
        } else {
            $data['payment_instpay_geo_zone_id'] = $this->config->get('payment_instpay_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_instpay_sort_order'])) {
            $data['payment_instpay_sort_order'] = $this->request->post['payment_instpay_sort_order'];
        } else {
            $data['payment_instpay_sort_order'] = $this->config->get('payment_instpay_sort_order');
        }

        if (isset($this->request->post['payment_instpay_status'])) {
            $data['payment_instpay_status'] = $this->request->post['payment_instpay_status'];
        } else {
            $data['payment_instpay_status'] = $this->config->get('payment_instpay_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/instpay', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/instpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_inst_host']) {
            $this->error['host'] = $this->language->get('error_host');
        }

        if (!$this->request->post['payment_inst_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (!$this->request->post['payment_inst_api_secret']) {
            $this->error['api_secret'] = $this->language->get('error_api_secret');
        }

        if (!$this->request->post['payment_inst_api_passphrase']) {
            $this->error['api_passphrase'] = $this->language->get('error_api_passphrase');
        }

        return !$this->error;
    }
}
?>
