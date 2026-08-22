<?php
//  ****************************************************************
//  ******* zákaznícky displej (ATaC display API) ******************
//  ****************************************************************
//  ****** verzia 1.06 *********************************************
//  ****************************************************************
//  Obálka nad integračnými skriptami displeja, ktoré sú uložené
//  priamo v adresári admin/ (staršie inštalácie ich môžu mať
//  v admin/includes/, preto sa hľadá na oboch miestach) :
//      - oscommerce_bridge.php       (základná komunikácia s API)
//      - oscommerce_pos_discount.php (atac_pos_preview_order, atac_pos_discount_order)
//      - oscommerce_edit_orders.php  (atac_display_send_order - hlavná cesta)
//
//  Displej pracuje v režime SHOPPING v dvoch fázach:
//      - preview    - nákup s pôvodnými cenami (pri otvorení okna kasy),
//      - discounted - nákup po zadaní zľavy tlačidlom ZADAJ ZĽAVU.
//
//  Pri otvorení okna kasy sa najprv skúsi atac_display_send_order() zo skriptu
//  oscommerce_edit_orders.php (rovnaké volanie ako v admin/edit_orders.php)
//  a až potom novšie atac_pos_preview_order().
//  Skripty sa hľadajú najprv priamo v admin/, potom v admin/includes/.
//
//  Ak integračné skripty na serveri nie sú, alebo displej nie je
//  dostupný, funkcie iba vrátia false a beh kasy nikdy neprerušia.
//  Dôvod neúspechu sa zapíše do $GLOBALS['ekasa_displej_stav'],
//  takže pri volaní okna kasy s ?diag=1 je vidieť, prečo sa nič neposlalo.

       // verzia obálky displeja - v diagnostike je vidieť, či server beží aktuálny súbor
       if (!defined('EKASA_DISPLEJ_VERZIA')) { define('EKASA_DISPLEJ_VERZIA', '1.06'); }

       // poznámka o poslednom volaní displeja (pre diagnostiku)
       if (!function_exists('ekasa_displej_stav')) {
       function ekasa_displej_stav ($sprava) {
                 $GLOBALS['ekasa_displej_stav'] = $sprava;
                 if (!isset($GLOBALS['ekasa_displej_log'])) { $GLOBALS['ekasa_displej_log'] = array(); }
                 $GLOBALS['ekasa_displej_log'][] = $sprava;
                 if (function_exists('portos_diag')) { portos_diag('Zákaznícky displej: '.$sprava); }
       }
       }

       // možné umiestnenia integračných skriptov displeja
       if (!function_exists('ekasa_displej_cesty')) {
       function ekasa_displej_cesty ($subor) {
                 $cesty = array();
                 // integračné skripty sú nahraté priamo v adresári admin/
                 if (defined('DIR_FS_ADMIN')) { $cesty[] = DIR_FS_ADMIN . $subor; }
                 // adresár admin/ odvodený z umiestnenia tohto súboru (admin/portos/ekasa_displej.php),
                 // aby hľadanie fungovalo aj keď DIR_FS_ADMIN nie je definovaná alebo je nastavená inak
                 $adresar_portos = dirname(__FILE__);
                 $cesty[] = dirname($adresar_portos) . '/' . $subor;
                 $cesty[] = $subor;
                 // staršie inštalácie mohli mať skripty v admin/includes/
                 if (defined('DIR_FS_ADMIN')) {
                         $cesty[] = DIR_FS_ADMIN . 'includes/' . $subor;
                         if (defined('DIR_WS_INCLUDES')) { $cesty[] = DIR_FS_ADMIN . DIR_WS_INCLUDES . $subor; }
                 }
                 $cesty[] = dirname($adresar_portos) . '/includes/' . $subor;
                 $cesty[] = 'includes/' . $subor;
                 if (defined('DIR_FS_DOCUMENT_ROOT')) {
                         $cesty[] = DIR_FS_DOCUMENT_ROOT . 'admin/' . $subor;
                         $cesty[] = DIR_FS_DOCUMENT_ROOT . 'admin/includes/' . $subor;
                 }
                 return array_values(array_unique($cesty));
       }
       }

       // načítanie integračného skriptu (vráti false, ak na serveri nie je)
       if (!function_exists('ekasa_displej_nacitaj')) {
       function ekasa_displej_nacitaj ($subor) {
                 $cesty = ekasa_displej_cesty($subor);
                 foreach ($cesty as $cesta) {
                         if (file_exists($cesta)) {
                                 require_once($cesta);
                                 ekasa_displej_stav('načítaný integračný skript '.$cesta);
                                 return true;
                         }
                 }
                 ekasa_displej_stav('integračný skript '.$subor.' sa nenašiel ('.implode(', ', $cesty).')');
                 return false;
       }
       }

       // pomocné volanie funkcie integračného skriptu - skúsi viac názvov
       if (!function_exists('ekasa_displej_volanie')) {
       function ekasa_displej_volanie ($subor, $funkcie, $argumenty, $nazov_akcie) {
                if (!ekasa_displej_nacitaj($subor)) { return array('success' => false, 'source' => null, 'result' => null); }
                if (!is_array($funkcie)) { $funkcie = array($funkcie); }

                foreach ($funkcie as $funkcia) {
                        if (function_exists($funkcia)) {
                                $vysledok = call_user_func_array($funkcia, $argumenty);
                                $ok = true;
                                if ($vysledok === false || $vysledok === null || $vysledok === '' || (is_array($vysledok) && count($vysledok) === 0)) { $ok = false; }
                                ekasa_displej_stav($nazov_akcie.': '.$funkcia.' = '.($ok ? 'OK' : 'neúspech'));
                                return array('success' => $ok, 'source' => $funkcia, 'result' => $vysledok);
                        }
                }

                ekasa_displej_stav($nazov_akcie.': nenašla sa žiadna integračná funkcia ('.implode(', ', $funkcie).')');
                return array('success' => false, 'source' => null, 'result' => null);
       }
       }

       // odoslanie nákupu na displej v režime SHOPPING, fáza preview (pôvodné ceny)
       if (!function_exists('ekasa_displej_nakup')) {
       function ekasa_displej_nakup ($oID) {
                 $oID = (int)$oID;
                 ekasa_displej_stav('verzia obálky displeja '.EKASA_DISPLEJ_VERZIA);
                 if ($oID <= 0) {
                         ekasa_displej_stav('nákup sa neodoslal - chýba číslo objednávky');
                         return false;
                 }

                 // rovnaké volanie, aké používa admin/edit_orders.php - overená cesta na displej
                 if (ekasa_displej_nacitaj('oscommerce_edit_orders.php') AND function_exists('atac_display_send_order')) {
                         $vysledok = atac_display_send_order($oID);
                         ekasa_displej_stav('atac_display_send_order('.$oID.') = '.($vysledok ? 'OK' : 'neúspech'));
                         if ($vysledok) { return $vysledok; }
                 }

                 // novšia integrácia s fázou preview (pôvodné ceny pred zľavou)
                 if (ekasa_displej_nacitaj('oscommerce_pos_discount.php') AND function_exists('atac_pos_preview_order')) {
                         $vysledok = atac_pos_preview_order($oID);
                         ekasa_displej_stav('atac_pos_preview_order('.$oID.') = '.($vysledok ? 'OK' : 'neúspech'));
                         return $vysledok;
                 }

                 ekasa_displej_stav('nákup sa na displej neodoslal - žiadne z volaní atac_display_send_order / atac_pos_preview_order neuspelo');
                 return false;
       }
       }

       // odoslanie nákupu so zľavou (fáza discounted), zľava 0 = späť na pôvodné ceny
       if (!function_exists('ekasa_displej_zlava')) {
       function ekasa_displej_zlava ($oID, $zlava_percent) {
                 $oID = (int)$oID;
                 if ($oID <= 0) {
                         ekasa_displej_stav('zľava sa neodoslala - chýba číslo objednávky');
                         return false;
                 }
                 $zlava_percent = (float)$zlava_percent;
                 if (!ekasa_displej_nacitaj('oscommerce_pos_discount.php')) { return false; }

                 if ($zlava_percent > 0) {
                         if (!function_exists('atac_pos_discount_order')) {
                                 ekasa_displej_stav('zľava sa neodoslala - funkcia atac_pos_discount_order nie je dostupná');
                                 return false;
                         }
                         $vysledok = atac_pos_discount_order($oID, $zlava_percent);
                         ekasa_displej_stav('atac_pos_discount_order('.$oID.', '.$zlava_percent.') = '.($vysledok ? 'OK' : 'neúspech'));
                         return $vysledok;
                 }

                 if (!function_exists('atac_pos_preview_order')) {
                         ekasa_displej_stav('zrušenie zľavy sa neodoslalo - funkcia atac_pos_preview_order nie je dostupná');
                         return false;
                 }
                 $vysledok = atac_pos_preview_order($oID);
                 ekasa_displej_stav('atac_pos_preview_order('.$oID.') = '.($vysledok ? 'OK' : 'neúspech'));
                 return $vysledok;
       }
       }

       // spustenie QR platby na zákazníckom displeji
       if (!function_exists('ekasa_displej_qr_start')) {
       function ekasa_displej_qr_start ($oID, $suma) {
                $oID = (int)$oID;
                $suma = (float)$suma;
                if ($oID <= 0 || $suma <= 0) {
                        ekasa_displej_stav('QR štart sa neodoslal - chýba oID alebo suma');
                        return array('success' => false, 'data' => array());
                }

                // načítanie položiek objednávky z DB (CP1250 - bridge si ich sám konvertuje do UTF-8)
                $items = array();
                if (function_exists('tep_db_query') && defined('TABLE_ORDERS_PRODUCTS')) {
                        $sql = tep_db_query("SELECT products_name, products_model, products_quantity, final_price, products_tax FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = " . $oID);
                        while ($row = tep_db_fetch_array($sql)) {
                                $items[] = array(
                                        'name'     => $row['products_name'],
                                        'sku'      => $row['products_model'],
                                        'quantity' => (float)$row['products_quantity'],
                                        'price'    => (float)$row['final_price'],
                                        'vat'      => (float)$row['products_tax'],
                                );
                        }
                }

                $volanie = ekasa_displej_volanie(
                        'oscommerce_bridge.php',
                        array('atac_start_qr_payment', 'atac_display_qr_payment'),
                        array($oID, $items, $suma),
                        'QR štart'
                );

                $data = array('oID' => $oID, 'amount' => $suma);
                if (is_array($volanie['result'])) { $data = array_merge($data, $volanie['result']); }
                return array('success' => ($volanie['success'] ? true : false), 'data' => $data);
       }
       }

       // kontrola stavu QR platby
       if (!function_exists('ekasa_displej_qr_status')) {
       function ekasa_displej_qr_status ($oID, $platba_id) {
                $oID = (int)$oID;
                if ($oID <= 0) {
                        ekasa_displej_stav('QR status sa neoveril - chýba oID');
                        return array('success' => false, 'paid' => false, 'data' => array());
                }

                $volanie = ekasa_displej_volanie(
                        'oscommerce_bridge.php',
                        array('atac_payment_status', 'atac_payment_check'),
                        array($oID, $platba_id),
                        'QR status'
                );

                $vysledok = $volanie['result'];
                $paid = false;
                if (is_array($vysledok)) {
                        if (!empty($vysledok['paid'])) { $paid = true; }
                        if (isset($vysledok['status']) && is_string($vysledok['status'])) {
                                $stav = strtolower(trim($vysledok['status']));
                                if ($stav === 'paid' || $stav === 'confirmed' || $stav === 'success') { $paid = true; }
                        }
                } elseif ($vysledok === true) {
                        $paid = true;
                }

                return array('success' => ($volanie['success'] ? true : false), 'paid' => ($paid ? true : false), 'data' => (is_array($vysledok) ? $vysledok : array()));
       }
       }

       // prepnutie zákazníckeho displeja na thank_you režim
       if (!function_exists('ekasa_displej_thank_you')) {
       function ekasa_displej_thank_you ($oID) {
                $oID = (int)$oID;
                // thank_you a idle sú v oscommerce_bridge.php
                $volanie = ekasa_displej_volanie(
                       'oscommerce_bridge.php',
                       array('atac_display_thank_you', 'atac_display_idle'),
                       array($oID),
                       'thank_you'
                );

                return ($volanie['success'] ? true : false);
       }
       }
?>
