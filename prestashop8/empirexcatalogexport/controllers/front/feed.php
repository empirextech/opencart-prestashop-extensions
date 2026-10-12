<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class EmpirexCatalogExportFeedModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent(): void
    {
        parent::initContent();

        $token = (string) Tools::getValue('token');
        $expected = (string) Configuration::get('EMPIREX_FEED_TOKEN');
        if ($expected === '' || !hash_equals($expected, $token)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Forbidden');
        }

        $since = (int) Configuration::get('EMPIREX_FEED_SINCE');
        $feed = new \EmpireX\MarketplaceKit\CatalogFeed();
        $shopUrl = Tools::getShopDomainSsl(true);

        $sql = 'SELECT p.id_product, p.reference, p.ean13, p.price, sa.quantity, pl.name, pl.link_rewrite, p.date_upd
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (pl.id_product = p.id_product AND pl.id_lang = ' . (int) $this->context->language->id . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (sa.id_product = p.id_product AND sa.id_product_attribute = 0)
                WHERE p.active = 1';

        $products = Db::getInstance()->executeS($sql) ?: [];
        $rows = [];

        foreach ($products as $product) {
            if (!$feed->changedSince($product['date_upd'] ?? null, $since)) {
                continue;
            }

            $comboSql = 'SELECT pa.id_product_attribute, pa.reference, pa.ean13, pa.price, sa.quantity
                         FROM ' . _DB_PREFIX_ . 'product_attribute pa
                         LEFT JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (sa.id_product_attribute = pa.id_product_attribute)
                         WHERE pa.id_product = ' . (int) $product['id_product'];
            $combos = Db::getInstance()->executeS($comboSql) ?: [];
            $labelled = [];
            foreach ($combos as $combo) {
                $combo['label'] = $this->combinationLabel((int) $combo['id_product_attribute']);
                $labelled[] = $combo;
            }

            foreach ($feed->flatten($product, $labelled) as $row) {
                $rows[] = $row;
            }
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo $feed->toXml($rows, $shopUrl);
        exit;
    }

    private function combinationLabel(int $idProductAttribute): string
    {
        $sql = 'SELECT agl.name AS group_name, al.name AS attr_name
                FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute
                INNER JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON (al.id_attribute = a.id_attribute AND al.id_lang = ' . (int) $this->context->language->id . ')
                INNER JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl ON (agl.id_attribute_group = a.id_attribute_group AND agl.id_lang = ' . (int) $this->context->language->id . ')
                WHERE pac.id_product_attribute = ' . (int) $idProductAttribute;

        $parts = [];
        foreach (Db::getInstance()->executeS($sql) ?: [] as $row) {
            $parts[] = $row['group_name'] . ': ' . $row['attr_name'];
        }

        return implode(', ', $parts);
    }
}
