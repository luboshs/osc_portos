<?php
//  ****************************************************************
//  ******* AJAX endpoint pre zákaznícky displej *******************
//  ****************************************************************
//  ****** verzia 1.01 *********************************************
//  ****************************************************************
//  Kasa (portos/ekasa_skripty.js) sem pošle:
//   - zľavu (prepnutie SHOPPING preview/discounted),
//   - požiadavky pre QR platbu (štart / status / zrušenie / thank_you).

       require('includes/application_top.php');
       include ('portos/ekasa_portos_nastavenia.php');
       include ('portos/ekasa_portos.php');
       include ('portos/ekasa_displej.php');

       if (!headers_sent()) {
           header('Content-Type: application/json; charset=utf-8');
       }

       $akcia = isset($_POST['akcia']) ? trim($_POST['akcia']) : '';
       $oID = isset($_POST['oID']) ? (int)$_POST['oID'] : 0;

       $odpoved = array('success' => false, 'oID' => $oID, 'stav' => '');

       switch ($akcia) {
               case 'qr_start':
                       $suma = isset($_POST['suma']) ? ekasa_cislo($_POST['suma']) : 0;
                       $vysledok = ekasa_displej_qr_start($oID, $suma);
                       $odpoved['success'] = !empty($vysledok['success']);
                       $odpoved['suma'] = $suma;
                       $odpoved['data'] = isset($vysledok['data']) ? $vysledok['data'] : array();

                       // tichý zápis do histórie objednávky o požiadavke QR platby
                       if ($oID > 0) {
                               $data_qr = $odpoved['data'];
                               $qr_id        = isset($data_qr['qr_id'])        ? $data_qr['qr_id']        : '';
                               $qr_id_suffix = isset($data_qr['qr_id_suffix']) ? $data_qr['qr_id_suffix'] : '';
                               $payme_link   = isset($data_qr['payme_link'])   ? $data_qr['payme_link']   : '';
                               $komentar_qr  = 'QR platba - požiadavka odoslaná'
                                       . "\nSuma: " . number_format((float)$suma, 2, '.', '') . ' EUR'
                                       . ($payme_link   !== '' ? "\nPayment link: " . $payme_link   : '')
                                       . ($qr_id        !== '' ? "\nQR ID: "        . $qr_id        : '')
                                       . ($qr_id_suffix !== '' ? "\nQR ID suffix: " . $qr_id_suffix : '');
                               tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, updated_by) values ('" . (int)$oID . "', 2, now(), 0, '" . tep_db_input(ekasa_do_db($komentar_qr)) . "', 'Portos/QR')");
                       }
               break;

               case 'qr_status':
                       $platba_id = isset($_POST['platba_id']) ? trim($_POST['platba_id']) : '';
                       $vysledok = ekasa_displej_qr_status($oID, $platba_id);
                       $odpoved['success'] = !empty($vysledok['success']);
                       $odpoved['paid'] = !empty($vysledok['paid']);
                       $odpoved['data'] = isset($vysledok['data']) ? $vysledok['data'] : array();
               break;

               case 'fio_status':
                       $suma = isset($_POST['suma']) ? ekasa_cislo($_POST['suma']) : 0;
                       $vysledok = ekasa_fio_over_platbu($oID, $suma);
                       $odpoved['success'] = !empty($vysledok['success']);
                       $odpoved['paid'] = !empty($vysledok['paid']);
                       $odpoved['detail'] = isset($vysledok['detail']) ? $vysledok['detail'] : '';
                       // pri potvrdení platby zapíšeme komentar do histórie objednávky
                       if (!empty($vysledok['paid']) && $oID > 0) {
                               $komentar_fio = 'QR platba overená cez FIO Bank'
                                       . "\nVS: " . (defined('EKASA_PAYME_VS') ? EKASA_PAYME_VS : '9059059050')
                                       . "\nSS (oID): " . $oID
                                       . "\nSuma: " . number_format((float)$suma, 2, '.', '') . ' EUR'
                                       . "\nDátum overenia: " . date('Y-m-d H:i:s');
                               tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, updated_by) values ('" . (int)$oID . "', 2, now(), 0, '" . tep_db_input(ekasa_do_db($komentar_fio)) . "', 'Portos/FIO')");
                       }
               break;

               case 'qr_cancel':
                       // zrušenie QR je voliteľné - ak integračný bridge funkciu nemá, iba vrátime false
                       $platba_id = isset($_POST['platba_id']) ? trim($_POST['platba_id']) : '';
                       $volanie = ekasa_displej_volanie(
                               'oscommerce_bridge.php',
                               array('atac_cancel_qr_payment'),
                               array($oID, $platba_id),
                               'QR cancel'
                       );
                       $odpoved['success'] = !empty($volanie['success']);
               break;

               case 'thank_you':
                       $odpoved['success'] = ekasa_displej_thank_you($oID);
               break;

               default:
                       $zlava_percent = isset($_POST['zlava_p']) ? ekasa_cislo(str_replace('%', '', trim($_POST['zlava_p']))) : 0;
                       // povolený rozsah zľavy je rovnaký ako v ekasa_polozky.php (1 - 15 %),
                       // každá iná hodnota (aj 0) znamená nákup bez zľavy - fáza preview
                       if ($zlava_percent < 1 OR $zlava_percent > 15) { $zlava_percent = 0; }

                       $vysledok = false;
                       if ($oID > 0) {
                               $vysledok = ekasa_displej_zlava($oID, $zlava_percent);
                       }
                       $odpoved['success'] = ($vysledok ? true : false);
                       $odpoved['zlava_p'] = $zlava_percent;
               break;
       }

       $odpoved['stav'] = isset($GLOBALS['ekasa_displej_log']) ? implode(' | ', $GLOBALS['ekasa_displej_log']) : '';
       echo json_encode($odpoved);
?>
