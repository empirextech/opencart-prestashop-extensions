<?php

class ControllerExtensionShippingEmpirexRules extends Controller
{
    private array $error = [];

    public function index(): void
    {
        $this->load->language('extension/shipping/empirex_rules');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('shipping_empirex_rules', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        }

        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['breadcrumbs'] = [];
        $data['action'] = $this->url->link('extension/shipping/empirex_rules', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

        $fields = [
            'shipping_empirex_rules_status',
            'shipping_empirex_rules_geo_zone_id',
            'shipping_empirex_rules_sort_order',
            'shipping_empirex_rules_default_cost',
            'shipping_empirex_rules_free_from',
            'shipping_empirex_rules_city_rates',
            'shipping_empirex_rules_country_rates',
            'shipping_empirex_rules_blocked_prefixes',
            'shipping_empirex_rules_heavy_weight',
            'shipping_empirex_rules_heavy_surcharge',
            'shipping_empirex_rules_tax_class_id',
        ];

        foreach ($fields as $field) {
            $data[$field] = $this->request->post[$field] ?? $this->config->get($field);
        }

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/empirex_rules', $data));
    }

    protected function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/shipping/empirex_rules')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
