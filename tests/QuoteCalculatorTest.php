<?php

declare(strict_types=1);

namespace EmpireX\MarketplaceKit\Tests;

use EmpireX\MarketplaceKit\QuoteCalculator;
use PHPUnit\Framework\TestCase;

final class QuoteCalculatorTest extends TestCase
{
    public function testCityRate(): void
    {
        $quote = $this->calculator()->quote(
            $this->config(),
            ['iso_code_2' => 'RO', 'city' => 'Cluj-Napoca', 'postcode' => '400001'],
            80.0,
            2.0
        );

        self::assertNotNull($quote);
        self::assertSame(14.9, $quote['quote']['empirex_rules_city']['cost']);
    }

    public function testBlockedPostcodeReturnsNothing(): void
    {
        $quote = $this->calculator()->quote(
            $this->config(),
            ['iso_code_2' => 'RO', 'city' => 'Tulcea', 'postcode' => '07001'],
            40.0,
            1.0
        );

        self::assertNull($quote);
    }

    public function testHeavySurchargeOnCountryRate(): void
    {
        $quote = $this->calculator()->quote(
            $this->config(),
            ['iso_code_2' => 'RO', 'city' => 'Iasi', 'postcode' => '700001'],
            50.0,
            12.0
        );

        self::assertNotNull($quote);
        self::assertSame(29.9, $quote['quote']['empirex_rules_geo']['cost']);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'status' => true,
            'title' => 'Courier',
            'blocked_postcode_prefixes' => '07,98',
            'free_from' => 200,
            'city_rates' => "Cluj-Napoca=14.90\nBucharest=9.90",
            'country_rates' => 'RO=19.90',
            'default_cost' => 25,
            'heavy_weight_kg' => 10,
            'heavy_surcharge' => 10,
        ];
    }

    private function calculator(): QuoteCalculator
    {
        return new QuoteCalculator();
    }
}
