<?php
/**
 * Normaliza los valores del método de verificación para ajustarlos
 * a los valores permitidos por la columna VERIFICATION_METHOD
 * (fingerprint, facial, traditional).
 */
if (!function_exists('normalizeVerificationMethod')) {
    function normalizeVerificationMethod(?string $method): string
    {
        if ($method === null) {
            return 'traditional';
        }

        $normalized = strtolower(trim($method));
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'u'],
            $normalized
        );

        switch ($normalized) {
            case 'fingerprint':
            case 'huella':
            case 'huella_dactilar':
            case 'huelladactilar':
                return 'fingerprint';

            case 'facial':
            case 'face':
            case 'rostro':
            case 'biometric_face':
            case 'biometrico_facial':
            case 'biometric_facial':
            case 'biometrico':
            case 'biometrica':
            case 'biometric':
                return 'facial';

            case 'traditional':
            case 'manual':
            case 'tradicional':
            case 'manual_traditional':
            case 'tradicional_manual':
            case 'rfid':
            case 'tarjeta':
            default:
                return 'traditional';
        }
    }
}
