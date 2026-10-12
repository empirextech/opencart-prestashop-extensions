<?php

declare(strict_types=1);

namespace EmpireX\MarketplaceKit;

/**
 * Memory-safe product feed writer for PrestaShop-style combination catalogs.
 */
final class CatalogFeed
{
    /**
     * @param iterable<array<string, mixed>> $rows
     */
    public function toXml(iterable $rows, string $shopUrl): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('products');
        $xml->writeAttribute('generated_at', gmdate('c'));

        foreach ($rows as $row) {
            $xml->startElement('product');
            $xml->writeElement('id', (string) ($row['id'] ?? ''));
            $xml->writeElement('reference', (string) ($row['reference'] ?? ''));
            $xml->writeElement('title', $this->clean((string) ($row['title'] ?? '')));
            $xml->writeElement('price', number_format((float) ($row['price'] ?? 0), 2, '.', ''));
            $xml->writeElement('quantity', (string) ((int) ($row['quantity'] ?? 0)));
            $xml->writeElement('url', rtrim($shopUrl, '/') . '/' . ltrim((string) ($row['link_rewrite'] ?? ''), '/'));
            if (!empty($row['ean13'])) {
                $xml->writeElement('gtin', preg_replace('/\D+/', '', (string) $row['ean13']) ?? '');
            }
            if (!empty($row['combination'])) {
                $xml->writeElement('combination', $this->clean((string) $row['combination']));
            }
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Flattens a parent product plus combination rows into feed records.
     *
     * @param array<string, mixed> $product
     * @param list<array<string, mixed>> $combinations
     * @return list<array<string, mixed>>
     */
    public function flatten(array $product, array $combinations): array
    {
        if ($combinations === []) {
            return [[
                'id' => $product['id_product'] ?? $product['id'] ?? '',
                'reference' => $product['reference'] ?? '',
                'title' => $product['name'] ?? '',
                'price' => $product['price'] ?? 0,
                'quantity' => $product['quantity'] ?? 0,
                'link_rewrite' => $product['link_rewrite'] ?? '',
                'ean13' => $product['ean13'] ?? '',
            ]];
        }

        $rows = [];
        foreach ($combinations as $combo) {
            $rows[] = [
                'id' => ($product['id_product'] ?? $product['id'] ?? '') . '-' . ($combo['id_product_attribute'] ?? $combo['id'] ?? ''),
                'reference' => $combo['reference'] ?? $product['reference'] ?? '',
                'title' => trim(($product['name'] ?? '') . ' ' . ($combo['label'] ?? '')),
                'price' => isset($combo['price']) ? $combo['price'] : $product['price'],
                'quantity' => $combo['quantity'] ?? 0,
                'link_rewrite' => $product['link_rewrite'] ?? '',
                'ean13' => $combo['ean13'] ?? $product['ean13'] ?? '',
                'combination' => $combo['label'] ?? '',
            ];
        }

        return $rows;
    }

    public function changedSince(?string $updatedAt, int $sinceTimestamp): bool
    {
        if ($updatedAt === null || $updatedAt === '') {
            return true;
        }

        $stamp = strtotime($updatedAt);

        return $stamp === false || $stamp >= $sinceTimestamp;
    }

    private function clean(string $value): string
    {
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
