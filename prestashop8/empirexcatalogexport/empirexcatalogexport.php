<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/src/CatalogFeed.php';

class EmpirexCatalogExport extends Module
{
    public function __construct()
    {
        $this->name = 'empirexcatalogexport';
        $this->tab = 'export';
        $this->version = '1.0.0';
        $this->author = 'EMPIREX Tech';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('EMPIREX Catalog Export');
        $this->description = $this->l('Incremental product + combination XML feed for comparison engines.');
    }

    public function install(): bool
    {
        return parent::install()
            && Configuration::updateValue('EMPIREX_FEED_TOKEN', bin2hex(random_bytes(16)))
            && Configuration::updateValue('EMPIREX_FEED_SINCE', 0);
    }

    public function uninstall(): bool
    {
        return parent::uninstall()
            && Configuration::deleteByName('EMPIREX_FEED_TOKEN')
            && Configuration::deleteByName('EMPIREX_FEED_SINCE');
    }

    public function getContent(): string
    {
        if (Tools::isSubmit('submitEmpirexCatalogExport')) {
            $since = (int) Tools::getValue('EMPIREX_FEED_SINCE');
            Configuration::updateValue('EMPIREX_FEED_SINCE', $since);
        }

        $token = (string) Configuration::get('EMPIREX_FEED_TOKEN');
        $url = $this->context->link->getModuleLink($this->name, 'feed', ['token' => $token]);

        return $this->displayConfirmation($this->l('Feed URL (keep the token private):') . ' ' . Tools::safeOutput($url))
            . $this->renderForm();
    }

    private function renderForm(): string
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEmpirexCatalogExport';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value['EMPIREX_FEED_SINCE'] = Configuration::get('EMPIREX_FEED_SINCE');

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->l('Incremental export')],
                'input' => [[
                    'type' => 'text',
                    'label' => $this->l('Export products updated since (unix timestamp, 0 = all)'),
                    'name' => 'EMPIREX_FEED_SINCE',
                ]],
                'submit' => ['title' => $this->l('Save')],
            ],
        ]]);
    }
}
