<?php
//  ****************************************************************
//  ******* TESTOVACÍ STUB pre ATaC zákaznícky displej *************
//  ****************************************************************
//  ****** verzia 1.00 *********************************************
//  ****************************************************************
//  Tento súbor slúži VÝHRADNE NA TESTOVANIE QR platobného toku
//  bez reálneho ATaC displeja.
//
//  INŠTALÁCIA PRE TESTOVANIE:
//    cp portos/ekasa_bridge_test_stub.php admin/oscommerce_bridge.php
//  (nahradí reálny bridge – na produkciu NEVHODNÉ)
//
//  Funkcionalita:
//    - atac_start_qr_payment  → vráti testovacie payment_id
//    - atac_payment_status    → po QR_TEST_PAID_AFTER_SECONDS sek. vráti paid=true
//    - atac_payment_check     → alias pre atac_payment_status
//    - atac_cancel_qr_payment → vráti true
//    - atac_display_thank_you → vráti true
//    - atac_display_idle      → vráti true
//    - atac_display_send_order   → vráti true
//    - atac_pos_preview_order    → vráti true
//    - atac_pos_discount_order   → vráti true
//
//  Nastavenia simulácie:
//    QR_TEST_PAID_AFTER_SECONDS   - po koľkých sekundách od štartu platby stub vráti paid=true (default 15)
//    QR_TEST_FAIL_START           - ak true, atac_start_qr_payment vráti false (test chybového stavu)
//    QR_TEST_FAIL_STATUS          - ak true, atac_payment_status vráti false (test chybového stavu)

       if (!defined('QR_TEST_PAID_AFTER_SECONDS'))  { define('QR_TEST_PAID_AFTER_SECONDS',  15); }
       if (!defined('QR_TEST_FAIL_START'))           { define('QR_TEST_FAIL_START',          false); }
       if (!defined('QR_TEST_FAIL_STATUS'))          { define('QR_TEST_FAIL_STATUS',         false); }

       // pomocný súbor pre uloženie času štartu platby medzi požiadavkami
       if (!defined('QR_TEST_STATE_FILE')) {
               define('QR_TEST_STATE_FILE', sys_get_temp_dir() . '/ekasa_qr_test_state.json');
       }

       // načítanie stavu testovacej platby
       function _qr_test_nacitaj_stav() {
               if (!file_exists(QR_TEST_STATE_FILE)) { return array(); }
               $data = @json_decode(file_get_contents(QR_TEST_STATE_FILE), true);
               return is_array($data) ? $data : array();
       }

       // zápis stavu testovacej platby
       function _qr_test_zapis_stav($stav) {
               $result = file_put_contents(QR_TEST_STATE_FILE, json_encode($stav));
               if ($result === false) {
                       error_log('[QR TEST STUB] _qr_test_zapis_stav: nepodarilo sa zapísať stav do ' . QR_TEST_STATE_FILE);
               }
       }

       // ---------------------------------------------------------------
       //  ŠTART QR PLATBY
       //  Parametre: $oID (int), $suma (float), $amountBeforeDiscount (float), $discountTotal (float), $vat (float)
       //  Návratová hodnota: array('payment_id' => ..., 'amount' => ..., ...)
       //                 alebo false pri chybe
       // ---------------------------------------------------------------
       if (!function_exists('atac_start_qr_payment')) {
       function atac_start_qr_payment ($oID, $suma, $amountBeforeDiscount = 0.0, $discountTotal = 0.0, $vat = 0.0) {
               if (QR_TEST_FAIL_START) {
                       error_log('[QR TEST STUB] atac_start_qr_payment: simulovaná chyba štartu');
                       return false;
               }

               $payment_id = 'TEST-' . $oID . '-' . time();
               $stav = array(
                       'payment_id'  => $payment_id,
                       'oID'         => $oID,
                       'amount'      => $suma,
                       'started_at'  => time(),
               );
               _qr_test_zapis_stav($stav);

               error_log('[QR TEST STUB] atac_start_qr_payment: oID=' . $oID . ' suma=' . $suma . ' payment_id=' . $payment_id);

               return array(
                       'payment_id'   => $payment_id,
                       'amount'       => $suma,
                       'currency'     => 'EUR',
                       'status'       => 'pending',
                       'qr_code_url'  => 'https://example.com/qr/test/' . $payment_id,
                       'message'      => 'QR test platba spustená (STUB)',
               );
       }
       }

       // ---------------------------------------------------------------
       //  STAV QR PLATBY
       //  Parametre: $oID (int), $platba_id (string)
       //  Návratová hodnota: array('paid' => bool, 'status' => string, ...)
       //                 alebo false pri chybe komunikácie
       // ---------------------------------------------------------------
       if (!function_exists('atac_payment_status')) {
       function atac_payment_status ($oID, $platba_id) {
               if (QR_TEST_FAIL_STATUS) {
                       error_log('[QR TEST STUB] atac_payment_status: simulovaná chyba statusu');
                       return false;
               }

               $stav = _qr_test_nacitaj_stav();
               $started_at = isset($stav['started_at']) ? (int)$stav['started_at'] : 0;
               $elapsed = time() - $started_at;
               $paid = ($started_at > 0 && $elapsed >= QR_TEST_PAID_AFTER_SECONDS);

               error_log('[QR TEST STUB] atac_payment_status: oID=' . $oID . ' platba_id=' . $platba_id . ' elapsed=' . $elapsed . 's paid=' . ($paid ? 'true' : 'false'));

               return array(
                       'payment_id' => $platba_id,
                       'status'     => ($paid ? 'paid' : 'pending'),
                       'paid'       => $paid,
                       'elapsed'    => $elapsed,
               );
       }
       }

       // alias - niektoré verzie bridge používajú atac_payment_check
       if (!function_exists('atac_payment_check')) {
       function atac_payment_check ($oID, $platba_id) {
               return atac_payment_status($oID, $platba_id);
       }
       }

       // ---------------------------------------------------------------
       //  ZRUŠENIE QR PLATBY
       //  Parametre: $oID (int), $platba_id (string)
       //  Návratová hodnota: true / false
       // ---------------------------------------------------------------
       if (!function_exists('atac_cancel_qr_payment')) {
       function atac_cancel_qr_payment ($oID, $platba_id) {
               _qr_test_zapis_stav(array());
               error_log('[QR TEST STUB] atac_cancel_qr_payment: oID=' . $oID . ' platba_id=' . $platba_id);
               return true;
       }
       }

       // ---------------------------------------------------------------
       //  THANK YOU na zákazníckom displeji
       //  Parameter: $oID (int)
       //  Návratová hodnota: true / false
       // ---------------------------------------------------------------
       if (!function_exists('atac_display_thank_you')) {
       function atac_display_thank_you ($oID) {
               error_log('[QR TEST STUB] atac_display_thank_you: oID=' . $oID);
               return true;
       }
       }

       // ---------------------------------------------------------------
       //  IDLE na zákazníckom displeji
       //  Parameter: $oID (int)
       //  Návratová hodnota: true / false
       // ---------------------------------------------------------------
       if (!function_exists('atac_display_idle')) {
       function atac_display_idle ($oID) {
               error_log('[QR TEST STUB] atac_display_idle: oID=' . $oID);
               return true;
       }
       }

       // ---------------------------------------------------------------
       //  Odoslanie objednávky na displej (SHOPPING režim, volá admin/edit_orders.php)
       //  Parameter: $oID (int)
       //  Návratová hodnota: true / false
       // ---------------------------------------------------------------
       if (!function_exists('atac_display_send_order')) {
       function atac_display_send_order ($oID) {
               error_log('[QR TEST STUB] atac_display_send_order: oID=' . $oID);
               return true;
       }
       }

       // ---------------------------------------------------------------
       //  Odoslanie objednávky v SHOPPING preview fáze (pôvodné ceny)
       //  Parameter: $oID (int)
       //  Návratová hodnota: true / false
       // ---------------------------------------------------------------
       if (!function_exists('atac_pos_preview_order')) {
       function atac_pos_preview_order ($oID) {
               error_log('[QR TEST STUB] atac_pos_preview_order: oID=' . $oID);
               return true;
       }
       }

       // ---------------------------------------------------------------
       //  Odoslanie objednávky so zľavou (SHOPPING discounted fáza)
       //  Parametre: $oID (int), $zlava_percent (float 1-15)
       //  Návratová hodnota: true / false
       // ---------------------------------------------------------------
       if (!function_exists('atac_pos_discount_order')) {
       function atac_pos_discount_order ($oID, $zlava_percent) {
               error_log('[QR TEST STUB] atac_pos_discount_order: oID=' . $oID . ' zlava=' . $zlava_percent . '%');
               return true;
       }
       }
