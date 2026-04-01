<?php
// Basic biometric data encryption utilities. Uses AES-256-CBC when a key is available.
// Fallbacks to base64 (weak) if key/openssl is not available to avoid hard failures during setup.

if (!function_exists('getBiometricKey')) {
	function getBiometricKey(): string {
		// In production, store this in a secure location (env/secret manager)
		$key = getenv('BIOMETRIC_SECRET_KEY');
		if ($key && strlen($key) >= 32) {
			return substr(hash('sha256', $key, true), 0, 32);
		}
		// Dev fallback deterministic key (DO NOT USE IN PRODUCTION)
		return substr(hash('sha256', __FILE__), 0, 32);
	}
}

if (!function_exists('encryptBiometric')) {
	function encryptBiometric(string $plaintext): string {
		if (!function_exists('openssl_encrypt')) {
			return base64_encode($plaintext);
		}
		$key = getBiometricKey();
		$iv = random_bytes(16);
		$ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
		if ($ciphertext === false) {
			return base64_encode($plaintext);
		}
		return base64_encode($iv . $ciphertext);
	}
}

if (!function_exists('decryptBiometric')) {
	function decryptBiometric(string $encoded): string {
		if (!function_exists('openssl_decrypt')) {
			$dec = base64_decode($encoded, true);
			return $dec !== false ? $dec : '';
		}
		$data = base64_decode($encoded, true);
		if ($data === false || strlen($data) < 17) {
			return '';
		}
		$iv = substr($data, 0, 16);
		$ciphertext = substr($data, 16);
		$key = getBiometricKey();
		$plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
		return $plaintext !== false ? $plaintext : '';
	}
}
