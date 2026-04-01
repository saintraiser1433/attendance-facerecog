<?php

/**
 * Normalize Philippine mobile numbers to E.164 +639XXXXXXXXX (13 chars incl. +).
 */
function normalize_ph_mobile(?string $input): ?string
{
    if ($input === null || trim($input) === '') {
        return null;
    }
    $digits = preg_replace('/\D/', '', $input);
    if ($digits === '' || strlen($digits) < 10) {
        return null;
    }
    if (preg_match('/^639\d{9}$/', $digits)) {
        return '+' . $digits;
    }
    if (preg_match('/^63\d{10}$/', $digits) && $digits[2] === '9') {
        return '+' . $digits;
    }
    if (preg_match('/^09\d{9}$/', $digits)) {
        return '+63' . substr($digits, 1);
    }
    if (preg_match('/^9\d{9}$/', $digits)) {
        return '+63' . $digits;
    }
    return null;
}

function validate_ph_mobile_required(string $input): ?string
{
    $n = normalize_ph_mobile($input);
    if ($n === null || !preg_match('/^\+639\d{9}$/', $n)) {
        return null;
    }
    return $n;
}
