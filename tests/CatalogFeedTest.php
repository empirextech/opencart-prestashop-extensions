<?php

declare(strict_types=1);

namespace EmpireX\MarketplaceKit\Tests;

use EmpireX\MarketplaceKit\CatalogFeed;
use EmpireX\MarketplaceKit\PhoneIdentity;
use PHPUnit\Framework\TestCase;

final class CatalogFeedTest extends TestCase
{
    public function testFlattensCombinationsAndWritesXml(): void
    {
        $feed = new CatalogFeed();
        $rows = $feed->flatten(
            ['id_product' => 12, 'reference' => 'PIPE', 'name' => 'Copper pipe', 'price' => 10, 'quantity' => 0, 'link_rewrite' => 'copper-pipe', 'ean13' => ''],
            [
                ['id_product_attribute' => 4, 'reference' => 'PIPE-15', 'label' => '15mm', 'price' => 11.5, 'quantity' => 3, 'ean13' => '5901234123457'],
            ]
        );

        self::assertSame('12-4', $rows[0]['id']);
        $xml = $feed->toXml($rows, 'https://shop.example');
        self::assertStringContainsString('<gtin>5901234123457</gtin>', $xml);
        self::assertStringContainsString('<combination>15mm</combination>', $xml);
    }

    public function testIncrementalFilter(): void
    {
        $feed = new CatalogFeed();
        self::assertTrue($feed->changedSince('2026-10-01 12:00:00', strtotime('2026-09-01')));
        self::assertFalse($feed->changedSince('2026-08-01 12:00:00', strtotime('2026-09-01')));
    }

    public function testPhoneLoginIdentifier(): void
    {
        $identity = new PhoneIdentity();
        self::assertSame(['column' => 'telephone_digits', 'value' => '40745111222'], $identity->identifier('+40 745 111 222'));
        self::assertSame(['column' => 'email', 'value' => 'ada@example.com'], $identity->identifier('Ada@example.com'));
    }
}
