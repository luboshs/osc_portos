<?php
//  ****************************************************************
//  ******* zákaznícky displej (ATaC display API) ******************
//  ****************************************************************
//  ****** verzia 1.02 *********************************************
//  ****************************************************************
//  Obálka nad integračnými skriptami displeja, ktoré sú uložené
//  v adresári admin/includes/ :
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
//  Skripty sa hľadajú v admin/includes/ aj priamo v admin/.
//
//  Ak integračné skripty na serveri nie sú, alebo displej nie je
//  dostupný, funkcie iba vrátia false a beh kasy nikdy neprerušia.
//  Dôvod neúspechu sa zapíše do $GLOBALS['ekasa_displej_stav'],
//  takže pri volaní okna kasy s ?diag=1 je vidieť, prečo sa nič neposlalo.

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
                 if (defined('DIR_FS_ADMIN')) {
                         $cesty[] = DIR_FS_ADMIN . 'includes/' . $subor;
                         if (defined('DIR_WS_INCLUDES')) { $cesty[] = DIR_FS_ADMIN . DIR_WS_INCLUDES . $subor; }
                         // integračné skripty môžu byť nahraté aj priamo v adresári admin/
                         $cesty[] = DIR_FS_ADMIN . $subor;
                 }
                 $cesty[] = 'includes/' . $subor;
                 $cesty[] = $subor;
                 if (defined('DIR_FS_DOCUMENT_ROOT')) {
                         $cesty[] = DIR_FS_DOCUMENT_ROOT . 'admin/includes/' . $subor;
                         $cesty[] = DIR_FS_DOCUMENT_ROOT . 'admin/' . $subor;
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

       // odoslanie nákupu na displej v režime SHOPPING, fáza preview (pôvodné ceny)
       if (!function_exists('ekasa_displej_nakup')) {
       function ekasa_displej_nakup ($oID) {
                 $oID = (int)$oID;
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
?>
