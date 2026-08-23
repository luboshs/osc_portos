<?php
//  ****************************************************************
//  ******* zákaznícky displej (ATaC display API) ******************
//  ****************************************************************
//  ****** verzia 1.09 *********************************************
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
       if (!defined('EKASA_DISPLEJ_VERZIA')) { define('EKASA_DISPLEJ_VERZIA', '1.10'); }

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

       // pomocné serializovanie návratovej hodnoty pre diagnostický log
       if (!function_exists('ekasa_displej_dump')) {
       function ekasa_displej_dump ($val) {
                if ($val === false)  { return 'false'; }
                if ($val === null)   { return 'null'; }
                if ($val === '')     { return '""'; }
                if (is_array($val))  {
                        $json = @json_encode($val, JSON_UNESCAPED_UNICODE);
                        if ($json === false) { $json = print_r($val, true); }
                        return substr($json, 0, 300);
                }
                return substr((string)$val, 0, 200);
       }
       }

       // pomocné volanie funkcie integračného skriptu - skúsi viac názvov
       if (!function_exists('ekasa_displej_volanie')) {
       function ekasa_displej_volanie ($subor, $funkcie, $argumenty, $nazov_akcie, $argumenty_podla_funkcie = array()) {
                if (!ekasa_displej_nacitaj($subor)) { return array('success' => false, 'source' => null, 'result' => null); }
                if (!is_array($funkcie)) { $funkcie = array($funkcie); }
                $bolo_volanie = false;
                $posledna_funkcia = null;
                $posledny_vysledok = null;

                // diagnostika konfigurácie bridge
                if (defined('DISPLAY_API_URL')) {
                        $url_skratena = preg_replace('#(https?://[^/]+).*#', '$1/...', DISPLAY_API_URL);
                        ekasa_displej_stav($nazov_akcie.': DISPLAY_API_URL='.$url_skratena);
                } else {
                        ekasa_displej_stav($nazov_akcie.': DISPLAY_API_URL nie je definovaná');
                }
                if (defined('DISPLAY_API_KEY')) {
                        $key = DISPLAY_API_KEY;
                        $key_info = (strlen($key) > 4) ? substr($key, 0, 4).'***' : ($key === '' ? '(prázdny)' : '***');
                        ekasa_displej_stav($nazov_akcie.': DISPLAY_API_KEY='.$key_info);
                } else {
                        ekasa_displej_stav($nazov_akcie.': DISPLAY_API_KEY nie je definovaná');
                }

                foreach ($funkcie as $funkcia) {
                        if (function_exists($funkcia)) {
                                $bolo_volanie = true;
                                $argumenty_pre_funkciu = isset($argumenty_podla_funkcie[$funkcia]) ? $argumenty_podla_funkcie[$funkcia] : $argumenty;
                                try {
                                        $vysledok = call_user_func_array($funkcia, $argumenty_pre_funkciu);
                                } catch (Throwable $e) {
                                        ekasa_displej_stav($nazov_akcie.': '.$funkcia.' vyhodila chybu '.$e->getMessage());
                                        continue;
                                }
                                $ok = true;
                                if ($vysledok === false || $vysledok === null || $vysledok === '' || (is_array($vysledok) && count($vysledok) === 0)) { $ok = false; }
                                $detail = $ok ? 'OK' : ('neúspech [vrátilo: '.ekasa_displej_dump($vysledok).']');
                                ekasa_displej_stav($nazov_akcie.': '.$funkcia.' = '.$detail);
                                if ($ok) { return array('success' => true, 'source' => $funkcia, 'result' => $vysledok); }
                                $posledna_funkcia = $funkcia;
                                $posledny_vysledok = $vysledok;
                        }
                }

                if ($bolo_volanie) {
                        ekasa_displej_stav($nazov_akcie.': žiadna integračná funkcia neuspela');
                        return array('success' => false, 'source' => $posledna_funkcia, 'result' => $posledny_vysledok);
                }
                ekasa_displej_stav($nazov_akcie.': nenašla sa žiadna integračná funkcia ('.implode(', ', $funkcie).')');
                return array('success' => false, 'source' => null, 'result' => null);
       }
       }

       // zostavenie Payme linku pre QR platbu
       if (!function_exists('ekasa_displej_payme_link')) {
       function ekasa_displej_payme_link ($oID, $suma) {
                $oID = (int)$oID;
                $suma = (float)$suma;
                if ($oID <= 0 || $suma <= 0) { return ''; }

                if (!defined('EKASA_PAYME_BASE_URL') || !defined('EKASA_PAYME_IBAN') || !defined('EKASA_PAYME_CREDITOR_NAME')) {
                        ekasa_displej_stav('Payme link sa nevytvoril - chýbajú konfigurácie EKASA_PAYME_*');
                        return '';
                }
                $baseUrl = EKASA_PAYME_BASE_URL;
                $iban = EKASA_PAYME_IBAN;
                $creditorName = EKASA_PAYME_CREDITOR_NAME;
                $vs = defined('EKASA_PAYME_VS') ? EKASA_PAYME_VS : '9059059050';
                $ks = defined('EKASA_PAYME_KS') ? EKASA_PAYME_KS : '0008';
                $ss = (string)$oID;

                // Payment Identification pre SR symboly platby
                $pi = '/VS' . $vs . '/SS' . $ss . '/KS' . $ks;

                // Správa pre príjemcu
                $message = 'POS_QR_PLATBA modelovazeleznica.sk (' . $oID . ')';

                $queryParams = array(
                        'IBAN' => $iban,
                        'AM'   => number_format((float)$suma, 2, '.', ''),
                        'CC'   => 'EUR',
                        'PI'   => $pi,
                        'CN'   => $creditorName,
                        'MSG'  => $message,
                );

                return $baseUrl . '?' . http_build_query($queryParams);
       }
       }

       // overenie platby cez FIO Banka API
       if (!function_exists('ekasa_fio_over_platbu')) {
       function ekasa_fio_over_platbu ($oID, $suma) {
                $oID = (int)$oID;
                $suma = (float)$suma;
                if ($oID <= 0 || $suma <= 0) {
                        return array('success' => false, 'paid' => false, 'detail' => 'Chýba oID alebo suma');
                }

                if (!defined('EKASA_FIO_API_TOKEN') || EKASA_FIO_API_TOKEN === '') {
                        ekasa_displej_stav('FIO API token nie je nastavený');
                        return array('success' => false, 'paid' => false, 'detail' => 'FIO API token nie je nastavený');
                }

                $token = EKASA_FIO_API_TOKEN;
                $vs = defined('EKASA_PAYME_VS') ? EKASA_PAYME_VS : '9059059050';
                $ss = (string)$oID;
                $url = 'https://fioapi.fio.cz/v1/rest/last/' . rawurlencode($token) . '/transactions.json';

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'ekasa-portos/1.0');
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError !== '' || $response === false) {
                        ekasa_displej_stav('FIO API curl chyba: ' . $curlError);
                        return array('success' => false, 'paid' => false, 'detail' => 'FIO API nedostupné: ' . $curlError);
                }
                if ($httpCode === 409) {
                        // FIO API vracia 409 ak sa volá príliš často (rate limit 30s)
                        ekasa_displej_stav('FIO API rate limit (429/409), skús neskôr');
                        return array('success' => false, 'paid' => false, 'detail' => 'FIO API rate limit, skús neskôr');
                }
                if ($httpCode !== 200) {
                        ekasa_displej_stav('FIO API HTTP ' . $httpCode);
                        return array('success' => false, 'paid' => false, 'detail' => 'FIO API HTTP ' . $httpCode);
                }

                $data = json_decode($response, true);
                if (!is_array($data) || !isset($data['accountStatement']['transactionList']['transaction'])) {
                        ekasa_displej_stav('FIO API - žiadne transakcie alebo neočakávaný formát');
                        return array('success' => true, 'paid' => false, 'detail' => 'Zatiaľ žiadne nové transakcie');
                }

                $transakcie = $data['accountStatement']['transactionList']['transaction'];
                // FIO vracia transakcie ako asociatívne pole stĺpcov alebo list; normalizujeme
                if (isset($transakcie['column1'])) { $transakcie = array($transakcie); }

                $suma_rounded = round($suma, 2);

                foreach ($transakcie as $t) {
                        // column1 = suma, column5 = VS, column6 = SS, column7 = uživatelská identifikácia
                        $t_suma = isset($t['column1']['value']) ? (float)$t['column1']['value'] : null;
                        $t_vs   = isset($t['column5']['value']) ? (string)$t['column5']['value'] : '';
                        $t_ss   = isset($t['column6']['value']) ? (string)$t['column6']['value'] : '';

                        if ($t_vs === $vs && $t_ss === $ss && $t_suma !== null && round($t_suma, 2) == $suma_rounded) {
                                ekasa_displej_stav('FIO - platba nájdená: VS=' . $t_vs . ' SS=' . $t_ss . ' suma=' . $t_suma);
                                return array('success' => true, 'paid' => true, 'detail' => array('vs' => $t_vs, 'ss' => $t_ss, 'suma' => $t_suma));
                        }
                }

                ekasa_displej_stav('FIO - platba s VS=' . $vs . ' SS=' . $ss . ' suma=' . $suma_rounded . ' nenájdená');
                return array('success' => true, 'paid' => false, 'detail' => 'Platba zatiaľ neevidovaná');
       }
       }

       // argumenty pre bridge funkciu QR štartu (payme link iba ak je na to pripravená signatúra)
       if (!function_exists('ekasa_displej_qr_start_argumenty')) {
       function ekasa_displej_qr_start_argumenty ($funkcia, $oID, $suma, $payme_link) {
                $argumenty = array($oID, $suma);
                if ($payme_link === '' || !function_exists($funkcia)) { return $argumenty; }
                try {
                        $ref = new ReflectionFunction($funkcia);
                        $parametre = $ref->getParameters();
                        foreach ($parametre as $idx => $parameter) {
                                if ($idx < 2) { continue; }
                                $nazov = strtolower($parameter->getName());
                                if ($nazov === 'payme_link' || $nazov === 'paymelink' || $nazov === 'paymeurl') {
                                        $argumenty[] = $payme_link;
                                        break;
                                }
                        }
                } catch (Throwable $e) {
                        // bez zmeny argumentov
                }
                return $argumenty;
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
       function ekasa_displej_qr_start ($oID, $suma, $payme_link = '') {
                $oID = (int)$oID;
                $suma = (float)$suma;
                if ($oID <= 0 || $suma <= 0) {
                        ekasa_displej_stav('QR štart sa neodoslal - chýba oID alebo suma');
                        return array('success' => false, 'data' => array());
                }

                if ($payme_link === '') { $payme_link = ekasa_displej_payme_link($oID, $suma); }

                $argumenty_podla_funkcie = array(
                        'atac_start_qr_payment' => ekasa_displej_qr_start_argumenty('atac_start_qr_payment', $oID, $suma, $payme_link),
                        'atac_display_qr_payment' => ekasa_displej_qr_start_argumenty('atac_display_qr_payment', $oID, $suma, $payme_link),
                );

                $volanie = ekasa_displej_volanie(
                        'oscommerce_bridge.php',
                        array('atac_start_qr_payment', 'atac_display_qr_payment'),
                        array($oID, $suma),
                        'QR štart',
                        $argumenty_podla_funkcie
                );

                $data = array('oID' => $oID, 'amount' => $suma);
                if (is_array($volanie['result'])) { $data = array_merge($data, $volanie['result']); }

                // ak API vrátilo qr_id_suffix, pripojíme ho na koniec payment linku
                if (!empty($data['qr_id_suffix']) && $payme_link !== '') {
                        $payme_link = $payme_link . (string)$data['qr_id_suffix'];
                        ekasa_displej_stav('payme_link rozšírený o qr_id_suffix: '.(string)$data['qr_id_suffix']);
                }

                $data['payme_link'] = $payme_link;
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
