<?php

class ModelExtensionShippingEmpirexRules extends Model
{
    public function getQuote($address)
    {
        $this->load->language('extension/shipping/empirex_rules');

        if (!$this->config->get('shipping_empirex_rules_status')) {
            return [];
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('shipping_empirex_rules_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('shipping_empirex_rules_geo_zone_id') && !$query->num_rows) {
            return [];
        }

        $this->load->model('localisation/country');
        $country = $this->model_localisation_country->getCountry((int) $address['country_id']);

        $config = [
            'status' => true,
            'title' => $this->language->get('text_title'),
            'blocked_postcode_prefixes' => (string) $this->config->get('shipping_empirex_rules_blocked_prefixes'),
            'free_from' => (float) $this->config->get('shipping_empirex_rules_free_from'),
            'city_rates' => (string) $this->config->get('shipping_empirex_rules_city_rates'),
            'country_rates' => (string) $this->config->get('shipping_empirex_rules_country_rates'),
            'default_cost' => (float) $this->config->get('shipping_empirex_rules_default_cost'),
            'heavy_weight_kg' => (float) $this->config->get('shipping_empirex_rules_heavy_weight'),
            'heavy_surcharge' => (float) $this->config->get('shipping_empirex_rules_heavy_surcharge'),
        ];

        $address['iso_code_2'] = $country['iso_code_2'] ?? '';
        $enginePath = DIR_SYSTEM . 'library/empirex/shipping_engine.php';
        if (!is_file($enginePath)) {
            return [];
        }

        require_once $enginePath;
        $engine = new EmpirexShippingEngine();
        $quote = $engine->quote(
            $config,
            $address,
            (float) $this->cart->getSubTotal(),
            (float) $this->cart->getWeight()
        );

        return $quote ?? [];
    }
}
