<?php
/**
 * paymob_helpers.php
 * Helper functions for HMAC verification and logging in the Paymob Webhook system.
 */
/**
 * Verifies the validity of the Paymob HMAC signature sent in a webhook.
 * Computes the SHA-512 HMAC hash of concatenated payload fields using the configured secret
 * and does a constant-time comparison against the received signature.
 *
 * @param array  $payload      The JSON-decoded webhook request body.
 * @param string $receivedHmac  The HMAC signature sent in the request query string.
 * @param string $hmacSecret    The merchant's HMAC Secret from the Paymob Dashboard.
 * @return bool                 True if signature is valid, false otherwise.
 */
function verify_paymob_webhook_hmac(array $payload, string $receivedHmac, string $hmacSecret): bool {
    // Webhook callbacks put transaction variables inside the 'obj' block
    if (!isset($payload['obj']) || !is_array($payload['obj'])) {
        return false;
    }
    
    $obj = $payload['obj'];
    
    /**
     * Helper to cast values to strings as required by Paymob signature rules.
     * Boolean values MUST be converted to lowercase literal 'true' or 'false'.
     * Null or undefined values should be empty strings.
     */
    $format = function($val) {
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }
        if (is_null($val)) {
            return '';
        }
        return (string)$val;
    };
    
    // Extract variables following Paymob's lexicographically sorted key structure:
    // amount_cents, created_at, currency, error_occured, has_parent_transaction, id, integration_id, is_3d_secure, is_auth, is_capture, is_refunded, is_standalone_payment, is_voided, order, owner, pending, source_data_pan, source_data_sub_type, source_data_type, success
    $amount_cents            = $format($obj['amount_cents'] ?? '');
    $created_at              = $format($obj['created_at'] ?? '');
    $currency                = $format($obj['currency'] ?? '');
    $error_occured           = $format($obj['error_occured'] ?? '');
    $has_parent_transaction  = $format($obj['has_parent_transaction'] ?? '');
    $id                      = $format($obj['id'] ?? '');
    $integration_id          = $format($obj['integration_id'] ?? '');
    $is_3d_secure            = $format($obj['is_3d_secure'] ?? '');
    $is_auth                 = $format($obj['is_auth'] ?? '');
    $is_capture              = $format($obj['is_capture'] ?? '');
    $is_refunded             = $format($obj['is_refunded'] ?? '');
    $is_standalone_payment   = $format($obj['is_standalone_payment'] ?? '');
    $is_voided               = $format($obj['is_voided'] ?? '');
    
    // 'order' in webhook payloads is nested (i.e. ['order']['id'])
    $order = '';
    if (isset($obj['order'])) {
        if (is_array($obj['order'])) {
            $order = $format($obj['order']['id'] ?? '');
        } else {
            $order = $format($obj['order']);
        }
    }
    
    $owner   = $format($obj['owner'] ?? '');
    $pending = $format($obj['pending'] ?? '');
    
    // 'source_data' in webhook payloads contains nested card/wallet source values
    $source_data_pan      = '';
    $source_data_sub_type = '';
    $source_data_type     = '';
    if (isset($obj['source_data']) && is_array($obj['source_data'])) {
        $source_data_pan      = $format($obj['source_data']['pan'] ?? '');
        $source_data_sub_type = $format($obj['source_data']['sub_type'] ?? '');
        $source_data_type     = $format($obj['source_data']['type'] ?? '');
    }
    
    $success = $format($obj['success'] ?? '');
    
    // Concatenate all values in the EXACT alphabetical order of their keys:
    // amount_cents -> created_at -> currency -> error_occured -> has_parent_transaction -> id -> integration_id -> is_3d_secure -> is_auth -> is_capture -> is_refunded -> is_standalone_payment -> is_voided -> order -> owner -> pending -> source_data_pan -> source_data_sub_type -> source_data_type -> success
    $concatenatedString = 
        $amount_cents .
        $created_at .
        $currency .
        $error_occured .
        $has_parent_transaction .
        $id .
        $integration_id .
        $is_3d_secure .
        $is_auth .
        $is_capture .
        $is_refunded .
        $is_standalone_payment .
        $is_voided .
        $order .
        $owner .
        $pending .
        $source_data_pan .
        $source_data_sub_type .
        $source_data_type .
        $success;
    
    // Hash using HMAC-SHA512
    $computedHmac = hash_hmac('sha512', $concatenatedString, $hmacSecret);
    
    // Use hash_equals for secure constant-time comparison to prevent timing attacks
    return hash_equals($computedHmac, $receivedHmac);
}
/**
 * Appends diagnostic information, payload metadata, or processing statuses to a log file.
 *
 * @param string     $message  Brief label of the event being logged.
 * @param array|null $data     Optional context array containing payloads, hashes, or errors.
 */
function log_paymob_webhook(string $message, ?array $data = null): void {
    // Save inside graduation project directory under a protected logs folder
    $logDir = __DIR__ . '/../logs';
    
    // Ensure log directory exists
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile   = $logDir . '/paymob_webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    
    // Construct log line
    $logEntry = "[{$timestamp}] {$message}";
    if ($data !== null) {
        // Redact any sensitive credit card data (like PAN) if logged
        if (isset($data['payload']['obj']['source_data']['pan'])) {
            $data['payload']['obj']['source_data']['pan'] = 'XXXX-XXXX-XXXX-' . substr($data['payload']['obj']['source_data']['pan'], -4);
        }
        $logEntry .= " | Data: " . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    $logEntry .= "\n";
    
    // Write atomically
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>