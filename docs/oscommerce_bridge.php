<?php
/**
 * osCommerce v2.2 RC2a Bridge Adapter
 *
 * Legacy helper script for osCommerce running on PHP 5.3.29 with CP1250 encoding.
 * Converts all product names and strings from CP1250 to UTF-8 before sending JSON
 * POST requests to the Customer Display API endpoint.
 *
 * USAGE: Include this file in osCommerce's checkout or admin code, then call
 * the appropriate function when cart/payment events occur.
 *
 * Compatibility: PHP 5.3+ (no namespaces, no short closures)
 * Encoding: Input CP1250 → UTF-8 output
 */

// Customer Display API base URL – update to your deployed domain
define('DISPLAY_API_BASE', 'https://YOUR_DOMAIN');
define('DISPLAY_API_URL', DISPLAY_API_BASE . '/api/display/update.php');
define('DISPLAY_API_KEY', 'CHANGE_ME_DISPLAY_API_KEY'); // Must match DISPLAY_API_KEY in the server config

/**
 * Convert a string from CP1250 to UTF-8 with transliteration fallback.
 *
 * @param string $str Input string in CP1250
 * @return string UTF-8 encoded string
 */
function atac_to_utf8($str) {
    $result = @iconv('CP1250', 'UTF-8//TRANSLIT', $str);
    if ($result === false) {
        $result = mb_convert_encoding($str, 'UTF-8', 'CP1250');
    }
    return $result;
}

/**
 * Recursively convert all string values in an array from CP1250 to UTF-8.
 *
 * @param mixed $data
 * @return mixed
 */
function atac_array_to_utf8($data) {
    if (is_array($data)) {
        $out = array();
        foreach ($data as $k => $v) {
            $out[$k] = atac_array_to_utf8($v);
        }
        return $out;
    }
    if (is_string($data)) {
        return atac_to_utf8($data);
    }
    return $data;
}

/**
 * Send an HTTP POST request to the Customer Display API.
 *
 * @param array $payload  Associative array to JSON-encode and POST
 * @return array|false    Decoded JSON response or false on failure
 */
function atac_post_to_display($payload) {
    $json = json_encode($payload);

    $ch = curl_init(DISPLAY_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $json);
    curl_setopt($ch, CURLOPT_TIMEOUT,        5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     array(
        'Content-Type: application/json; charset=utf-8',
        'Content-Length: ' . strlen($json),
        'X-API-Key: ' . DISPLAY_API_KEY,
    ));
    // Allow self-signed certificates for local dev; set to true in production
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return false;
    }

    return json_decode($response, true);
}

/**
 * Call any Customer Display API endpoint.
 *
 * @param string     $path    Path relative to the application root, e.g. '/api/payment/status.php'
 * @param array|null $payload JSON body (null = GET request)
 * @return array Decoded response (always an array, with 'error' key on failure)
 */
function atac_call_api($path, $payload = null) {
    $url = DISPLAY_API_BASE . $path;

    $headers = array('X-API-Key: ' . DISPLAY_API_KEY);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($payload !== null) {
        $json = json_encode($payload);
        curl_setopt($ch, CURLOPT_POST,       true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Type: application/json; charset=utf-8';
        $headers[] = 'Content-Length: ' . strlen($json);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        // Network error – the POS must stay usable and may retry.
        return array('error' => 'network', 'detail' => $curlErr, 'http_code' => 0);
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return array('error' => 'invalid_response', 'http_code' => $httpCode);
    }

    $decoded['http_code'] = $httpCode;

    return $decoded;
}

/**
 * Convert item names of a cart array from CP1250 to UTF-8.
 *
 * @param array $items
 * @return array
 */
function atac_items_to_utf8($items) {
    $utf8Items = array();
    foreach ($items as $item) {
        $utf8Item = $item;
        if (isset($utf8Item['name'])) {
            $utf8Item['name'] = atac_to_utf8($utf8Item['name']);
        }
        if (isset($utf8Item['sku'])) {
            $utf8Item['sku'] = atac_to_utf8($utf8Item['sku']);
        }
        if (isset($utf8Item['no_discount_reason'])) {
            $utf8Item['no_discount_reason'] = atac_to_utf8($utf8Item['no_discount_reason']);
        }
        $utf8Items[] = $utf8Item;
    }
    return $utf8Items;
}

/**
 * Set the display to IDLE mode.
 */
function atac_display_idle() {
    return atac_post_to_display(array('mode' => 'idle'));
}

/**
 * Show the THANK YOU screen (fullscreen thank_you.jpg on white background).
 *
 * The display returns to IDLE by itself after a server-configured timeout
 * (THANK_YOU_DURATION_SECONDS); the POS does not wait for anything and can
 * send a new state at any time.
 *
 * @param string $orderId Optional order reference (for logging on the server)
 */
function atac_display_thank_you($orderId = '') {
    $payload = array();
    if ($orderId !== '' && $orderId !== null) {
        $payload['order_id'] = (string)$orderId;
    }
    return atac_call_api('/api/display/thank_you.php', $payload);
}

/**
 * Set the display to SHOPPING mode with a cart.
 *
 * @param string $orderId   Order / cart reference ID
 * @param array  $items     Array of cart items:
 *                           [['name'=>'', 'qty'=>1, 'unit_price'=>0.00, 'total'=>0.00], ...]
 *                           Item names must be in CP1250 – will be converted automatically.
 * @param float  $total     Total gross amount
 * @param float  $discount  Discount amount (optional)
 * @param float  $vat       VAT amount (optional)
 */
function atac_display_shopping($orderId, $items, $total, $discount = 0.0, $vat = 0.0) {
    // Convert all item names from CP1250 to UTF-8
    $utf8Items = array();
    foreach ($items as $item) {
        $utf8Item = $item;
        if (isset($utf8Item['name'])) {
            $utf8Item['name'] = atac_to_utf8($utf8Item['name']);
        }
        if (isset($utf8Item['sku'])) {
            $utf8Item['sku'] = atac_to_utf8($utf8Item['sku']);
        }
        $utf8Items[] = $utf8Item;
    }

    $total    = (float)$total;
    $discount = (float)$discount;
    // A discount is only rendered in the `discounted` phase – the `preview`
    // phase always shows original prices, so the server resets the discount
    // (and with it the "Medzisúčet" row) to zero.
    $phase  = ($discount > 0) ? 'discounted' : 'preview';
    $before = round($total + $discount, 2);

    $payload = array(
        'mode'            => 'shopping',
        'order_id'        => atac_to_utf8((string)$orderId),
        'amount'          => $total,
        'discount_amount' => $discount,
        'vat_amount'      => (float)$vat,
        'payload'         => array(
            'phase'                  => $phase,
            'items'                  => $utf8Items,
            'amount_before_discount' => $before,
            'amount'                 => $total,
            'discount_total'         => $discount,
            'discount_amount'        => $discount,
            'vat_amount'             => (float)$vat,
        ),
    );

    return atac_post_to_display($payload);
}

/**
 * Set the display to QR_PAYMENT mode.
 * The server will generate the Pay by Square QR code automatically.
 *
 * @param string $orderId   Order ID (used as Variable Symbol – max 10 digits)
 * @param array  $items     Cart items (CP1250 encoded names)
 * @param float  $total     Total amount to be paid
 * @param float  $discount  Discount amount
 * @param float  $vat       VAT amount
 */
function atac_display_qr_payment($orderId, $items, $total, $discount = 0.0, $vat = 0.0) {
    $utf8Items = array();
    foreach ($items as $item) {
        $utf8Item = $item;
        if (isset($utf8Item['name'])) {
            $utf8Item['name'] = atac_to_utf8($utf8Item['name']);
        }
        if (isset($utf8Item['sku'])) {
            $utf8Item['sku'] = atac_to_utf8($utf8Item['sku']);
        }
        $utf8Items[] = $utf8Item;
    }

    $total    = (float)$total;
    $discount = (float)$discount;
    $phase    = ($discount > 0) ? 'discounted' : 'preview';
    $before   = round($total + $discount, 2);

    $payload = array(
        'mode'            => 'qr_payment',
        'order_id'        => atac_to_utf8((string)$orderId),
        'amount'          => $total,
        'discount_amount' => $discount,
        'vat_amount'      => (float)$vat,
        'payload'         => array(
            'phase'                  => $phase,
            'items'                  => $utf8Items,
            'amount_before_discount' => $before,
            'amount'                 => $total,
            'discount_total'         => $discount,
            'discount_amount'        => $discount,
            'vat_amount'             => (float)$vat,
        ),
    );

    return atac_post_to_display($payload);
}

/**
 * Set the display to CANCEL mode.
 *
 * @param string $orderId  Order ID (CP1250)
 */
function atac_display_cancel($orderId) {
    return atac_post_to_display(array(
        'mode'     => 'cancel',
        'order_id' => atac_to_utf8((string)$orderId),
    ));
}

/**
 * Send the FINAL CART PREVIEW – all items at their original price, no discount.
 *
 * @param string $orderId
 * @param array  $items  [['name'=>'', 'qty'=>1, 'unit_price'=>0.00, 'total'=>0.00], ...] (CP1250 names)
 * @param float  $total
 * @param float  $vat
 */
function atac_display_cart_preview($orderId, $items, $total, $vat = 0.0) {
    return atac_post_to_display(array(
        'mode'     => 'shopping',
        'order_id' => atac_to_utf8((string)$orderId),
        'amount'   => (float)$total,
        'payload'  => array(
            'phase'                  => 'preview',
            'items'                  => atac_items_to_utf8($items),
            'amount_before_discount' => (float)$total,
            'discount_total'         => 0.0,
            'amount'                 => (float)$total,
            'vat_amount'             => (float)$vat,
        ),
    ));
}

/**
 * Send the cart AFTER a discount was entered.
 *
 * Items may carry 'discount_percent', 'discount_amount' and 'total_after_discount'.
 * Non-discountable items (e.g. KC) must be flagged with 'no_discount' => true
 * (optionally 'no_discount_reason').
 */
function atac_display_cart_discounted($orderId, $items, $amountBeforeDiscount, $discountTotal, $amountToPay, $vat = 0.0) {
    return atac_post_to_display(array(
        'mode'     => 'shopping',
        'order_id' => atac_to_utf8((string)$orderId),
        'amount'   => (float)$amountToPay,
        'payload'  => array(
            'phase'                  => 'discounted',
            'items'                  => atac_items_to_utf8($items),
            'amount_before_discount' => (float)$amountBeforeDiscount,
            'discount_total'         => (float)$discountTotal,
            'amount'                 => (float)$amountToPay,
            'vat_amount'             => (float)$vat,
        ),
    ));
}

/**
 * Start a QR payment for the final (already discounted) amount.
 * $orderId must be a unique document number of 1–10 digits (used as Variable Symbol).
 *
 * @param string $orderId             Order ID (Variable Symbol, max 10 digits)
 * @param float  $amountToPay         Final amount the customer must pay
 * @param float  $amountBeforeDiscount Original total before discount (0 = same as $amountToPay)
 * @param float  $discountTotal        Discount amount (0 = no discount)
 * @param float  $vat                  VAT amount (optional)
 */
function atac_start_qr_payment($orderId, $amountToPay, $amountBeforeDiscount = 0.0, $discountTotal = 0.0, $vat = 0.0) {
    $before = ($amountBeforeDiscount > 0) ? (float)$amountBeforeDiscount : (float)$amountToPay;

    return atac_post_to_display(array(
        'mode'     => 'qr_payment',
        'order_id' => atac_to_utf8((string)$orderId),
        'amount'   => (float)$amountToPay,
        'payload'  => array(
            'phase'                  => ($discountTotal > 0) ? 'discounted' : 'preview',
            'amount_before_discount' => $before,
            'discount_total'         => (float)$discountTotal,
            'amount'                 => (float)$amountToPay,
            'vat_amount'             => (float)$vat,
        ),
    ));
}

/**
 * Read the current payment status (cheap, no bank call).
 */
function atac_payment_status($orderId) {
    return atac_call_api('/api/payment/status.php?order_id=' . rawurlencode((string)$orderId));
}

/**
 * Ask the server to query Fio Banka (respects the mandatory 30 s rate-limit).
 */
function atac_payment_check($orderId, $amount) {
    return atac_call_api(
        '/api/payment/check.php?order_id=' . rawurlencode((string)$orderId)
        . '&amount=' . rawurlencode((string)$amount)
    );
}

/**
 * Cancel a pending QR payment (e.g. the customer pays cash instead).
 */
function atac_cancel_qr_payment($orderId, $reason = '', $displayMode = 'cancel') {
    return atac_call_api('/api/payment/cancel.php', array(
        'order_id'     => atac_to_utf8((string)$orderId),
        'reason'       => atac_to_utf8((string)$reason),
        'display_mode' => $displayMode,
    ));
}

/**
 * Manually confirm a payment (bank verification too slow, customer proved the transfer).
 */
function atac_manual_confirm_payment($orderId, $confirmedBy, $reason = '') {
    return atac_call_api('/api/payment/manual_confirm.php', array(
        'order_id'     => atac_to_utf8((string)$orderId),
        'confirmed_by' => atac_to_utf8((string)$confirmedBy),
        'reason'       => atac_to_utf8((string)$reason),
    ));
}

/**
 * Print the receipt. The endpoint is idempotent – repeated calls never print twice.
 */
function atac_print_receipt($orderId) {
    return atac_call_api('/api/payment/receipt.php', array(
        'order_id' => atac_to_utf8((string)$orderId),
    ));
}

/**
 * Wait for the QR payment to be settled and print the receipt exactly once.
 *
 * @return array array('status' => ..., 'receipt' => ..., 'payment' => ...)
 */
function atac_wait_for_payment($orderId, $amount, $timeoutSeconds = 300, $intervalSeconds = 3) {
    $deadline      = time() + $timeoutSeconds;
    $lastBankCheck = 0;
    $finalStates   = array('EXPIRED', 'FAILED', 'CANCELLED', 'NOT_FOUND');

    while (time() < $deadline) {
        $status = atac_payment_status($orderId);
        $state  = (is_array($status) && isset($status['status'])) ? $status['status'] : 'UNKNOWN';

        if ($state === 'PAID') {
            $receipt = atac_print_receipt($orderId);
            atac_display_idle();

            return array('status' => 'PAID', 'receipt' => $receipt, 'payment' => $status);
        }

        if (in_array($state, $finalStates)) {
            return array('status' => $state, 'receipt' => null, 'payment' => $status);
        }

        // PENDING / UNKNOWN (e.g. temporary network error) – keep waiting.
        if (time() - $lastBankCheck >= 30) {
            atac_payment_check($orderId, $amount);
            $lastBankCheck = time();
        }

        sleep(max(1, $intervalSeconds));
    }

    return array('status' => 'TIMEOUT', 'receipt' => null, 'payment' => null);
}

// ============================================================
// EXAMPLE USAGE (remove or comment out in production):
// ============================================================
//
// $items = array(
//     array('name' => 'Produkt 1',  'qty' => 2, 'unit_price' => 5.00,  'total' => 10.00),
//     array('name' => 'Produkt 2',  'qty' => 1, 'unit_price' => 12.50, 'total' => 12.50),
// );
//
// atac_display_shopping('ORD-1234', $items, 22.50, 0.00, 3.75);
// atac_display_qr_payment('1234', $items, 22.50, 0.00, 3.75);
// atac_display_cancel('1234');
// atac_display_idle();
//
// --- Full flow: preview → discount → QR payment → receipt -------------------
//
// atac_display_cart_preview('20240001', $items, 22.50, 3.75);
//
// $discounted = array(
//     array('name' => 'Produkt 1', 'qty' => 2, 'unit_price' => 5.00, 'total' => 10.00,
//           'discount_percent' => 10, 'discount_amount' => 1.00, 'total_after_discount' => 9.00),
//     array('name' => 'KC poukazka', 'qty' => 1, 'unit_price' => 12.50, 'total' => 12.50,
//           'no_discount' => true, 'no_discount_reason' => 'KC polozka'),
// );
// atac_display_cart_discounted('20240001', $discounted, 22.50, 1.00, 21.50, 3.58);
// atac_start_qr_payment('20240001', 21.50, 22.50, 1.00, 3.58);
// $result = atac_wait_for_payment('20240001', 21.50); // prints the receipt once PAID
