<?php

require_once DIR_SYSTEM . 'library/empirex/phone_identity.php';

class ControllerExtensionModuleEmpirexPhoneLogin extends Controller
{
    /**
     * Event: catalog/controller/account/login/after — demonstration hook.
     * Stores a normalised digit form of the login attempt for diagnostics; it does not bypass password checks.
     */
    public function onLoginAttempt(&$route, &$args): void
    {
        if (!$this->config->get('module_empirex_phone_login_status')) {
            return;
        }

        $raw = (string) ($this->request->post['email'] ?? '');
        $identity = (new EmpirexPhoneIdentityAdapter())->identifier($raw);
        $this->session->data['empirex_login_identifier'] = $identity;
    }
}
