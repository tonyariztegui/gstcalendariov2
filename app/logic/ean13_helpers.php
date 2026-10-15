<?php

/**
 * EAN-13 : validazione check digit GS1 (per barcode email premio).
 */

if (!function_exists('ean13_payload_checksum')) {
    function ean13_payload_checksum(string $payload12): ?int
    {
        if (!preg_match('/^\d{12}$/', $payload12)) {
            return null;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $payload12[$i];
            $sum += ($i % 2 === 0) ? $digit : ($digit * 3);
        }

        return (10 - ($sum % 10)) % 10;
    }
}

if (!function_exists('ean13_is_valid')) {
    function ean13_is_valid(string $code): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{13}$/', $code)) {
            return false;
        }
        $checksum = ean13_payload_checksum(substr($code, 0, 12));

        return $checksum !== null && $checksum === (int) $code[12];
    }
}
