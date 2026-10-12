<?php

declare(strict_types=1);

namespace EmpireX\MarketplaceKit;

/**
 * Normalises login identifiers so a customer can authenticate with email OR national phone digits.
 */
final class PhoneIdentity
{
    public function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public function looksLikePhone(string $value): bool
    {
        $digits = $this->digits($value);

        return strlen($digits) >= 8 && strlen($digits) <= 15 && !str_contains($value, '@');
    }

    /**
     * @return array{column:string,value:string}
     */
    public function identifier(string $login): array
    {
        $login = trim($login);

        if ($this->looksLikePhone($login)) {
            return ['column' => 'telephone_digits', 'value' => $this->digits($login)];
        }

        return ['column' => 'email', 'value' => mb_strtolower($login)];
    }
}
