<?php

/**
 * Thin OpenCart adapter around the shared quote calculator.
 * Copied into system/library so the store does not need Composer on the server.
 */

$src = __DIR__ . '/src/QuoteCalculator.php';
if (!is_file($src)) {
    $src = dirname(__DIR__, 5) . '/src/QuoteCalculator.php';
}
require_once $src;

class EmpirexShippingEngine
{
    private \EmpireX\MarketplaceKit\QuoteCalculator $calculator;

    public function __construct()
    {
        $this->calculator = new \EmpireX\MarketplaceKit\QuoteCalculator();
    }

    public function quote(array $config, array $address, float $subtotal, float $weightKg): ?array
    {
        return $this->calculator->quote($config, $address, $subtotal, $weightKg);
    }
}
