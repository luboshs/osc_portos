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
               break;

               case 'qr_status':
                       $platba_id = isset($_POST['platba_id']) ? trim($_POST['platba_id']) : '';
                       $vysledok = ekasa_displej_qr_status($oID, $platba_id);
                       $odpoved['success'] = !empty($vysledok['success']);
                       $odpoved['paid'] = !empty($vysledok['paid']);
                       $odpoved['data'] = isset($vysledok['data']) ? $vysledok['data'] : array();
               break;

               case 'qr_cancel':
                       // zrušenie QR je voliteľné - ak integračný bridge funkciu nemá, iba vrátime false
                       $platba_id = isset($_POST['platba_id']) ? trim($_POST['platba_id']) : '';
                       $volanie = ekasa_displej_volanie(
                               'oscommerce_bridge.php',
                               array('atac_pos_qr_cancel', 'atac_display_qr_cancel', 'atac_qr_payment_cancel'),
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
