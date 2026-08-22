<?php
//  ****************************************************************
//  ******* zákaznícky displej (ATaC display API) ******************
//  ****************************************************************
//  ****** verzia 1.00 *********************************************
//  ****************************************************************
//  Obálka nad integračnými skriptami displeja, ktoré sú uložené
//  v adresári admin/includes/ :
//      - oscommerce_bridge.php       (základná komunikácia s API)
//      - oscommerce_edit_orders.php  (atac_display_send_order)
//      - oscommerce_pos_discount.php (atac_pos_discount_order, atac_pos_preview_order)
//
//  Ak integračné skripty na serveri nie sú, alebo displej nie je
//  dostupný, funkcie iba vrátia false a beh kasy nikdy neprerušia.

       // adresár, v ktorom sú uložené integračné skripty displeja
       if (!function_exists('ekasa_displej_cesta')) {
       function ekasa_displej_cesta ($subor) {
                 $zaklad = '';
                 if (defined('DIR_FS_ADMIN')) { $zaklad = DIR_FS_ADMIN; }
                 $includes = defined('DIR_WS_INCLUDES') ? DIR_WS_INCLUDES : 'includes/';
                 return $zaklad . $includes . $subor;
       }
       }

       // načítanie integračného skriptu (vráti false, ak na serveri nie je)
       if (!function_exists('ekasa_displej_nacitaj')) {
       function ekasa_displej_nacitaj ($subor) {
                 $cesta = ekasa_displej_cesta($subor);
                 if (!file_exists($cesta)) { return false; }
                 require_once($cesta);
                 return true;
       }
       }

       // odoslanie nákupu na displej v režime SHOPPING (pôvodné ceny)
       if (!function_exists('ekasa_displej_nakup')) {
       function ekasa_displej_nakup ($oID) {
                 $oID = (int)$oID;
                 if ($oID <= 0) { return false; }
                 if (!ekasa_displej_nacitaj('oscommerce_edit_orders.php')) { return false; }
                 if (!function_exists('atac_display_send_order')) { return false; }
                 return atac_display_send_order($oID);
       }
       }

       // odoslanie nákupu so zľavou (fáza discounted), zľava 0 = späť na pôvodné ceny
       if (!function_exists('ekasa_displej_zlava')) {
       function ekasa_displej_zlava ($oID, $zlava_percent) {
                 $oID = (int)$oID;
                 if ($oID <= 0) { return false; }
                 $zlava_percent = (float)$zlava_percent;
                 if (!ekasa_displej_nacitaj('oscommerce_pos_discount.php')) { return false; }

                 if ($zlava_percent > 0) {
                         if (!function_exists('atac_pos_discount_order')) { return false; }
                         return atac_pos_discount_order($oID, $zlava_percent);
                 }

                 if (!function_exists('atac_pos_preview_order')) { return false; }
                 return atac_pos_preview_order($oID);
       }
       }
?>
