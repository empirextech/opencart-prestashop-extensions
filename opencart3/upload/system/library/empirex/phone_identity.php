<?php

$src = __DIR__ . '/src/PhoneIdentity.php';
if (!is_file($src)) {
    $src = dirname(__DIR__, 5) . '/src/PhoneIdentity.php';
}
require_once $src;

class EmpirexPhoneIdentityAdapter
{
    public function identifier(string $login): array
    {
        return (new \EmpireX\MarketplaceKit\PhoneIdentity())->identifier($login);
    }
}
