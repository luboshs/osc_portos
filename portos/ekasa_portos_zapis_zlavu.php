<?php
//  *****************************************************************  
//  ******* tento skript pridá z¾avu medzi položky objednávky********
//  *****************************************************************      
//  * keï je inicializovaný, predpokladá sa že z¾ava je deklarovaná *
//  *****************************************************************
//  ****** verzia 1.00 10.02.2020 ***********************************
//  *****************************************************************
//  úlohy:
//  - nie je dorobená funkcionalita bloèku na email (na okne)
        $price          =   (0 - abs($zlava))/1.2; 
        $final_price    =   $price;                          
        $model = 'ZLAVA '.$_POST["zlava_p"];
        
        $sql_zlava_hladaj =  tep_db_query ("SELECT orders_products_id, ekasa_zlava FROM ".TABLE_ORDERS_PRODUCTS." WHERE ekasa_zlava = true AND orders_id= " . (int)$oID );
        $sql_zlava = tep_db_fetch_array($sql_zlava_hladaj);
        
        if ($sql_zlava['ekasa_zlava']) {
                    $sql_zlava_polozka_update = tep_db_query("update " . TABLE_ORDERS_PRODUCTS . " SET products_price = '" . tep_db_input($price) . "', final_price ='" . tep_db_input($final_price)  . "', products_tax = 20 WHERE orders_id = " . (int)$oID . " AND ekasa_zlava = true");
        } else {
                    $sql_zlava_polozka_insert = tep_db_query("insert into " . TABLE_ORDERS_PRODUCTS . " (orders_id, products_model, products_price, final_price, products_tax, products_quantity, ekasa_zlava) values ('" . (int)$oID . "', '" . tep_db_input($model) . "', '" . tep_db_input($price) . "', '" . tep_db_input($final_price)  . "', 20, 1, true)");
        }

         
       //   $sql_order = tep_db_query("update orders set orders_status = 2, last_modified = now(), blocek = '" . (int)$blocekID . "' where orders_id = '" . (int)$oID . "'");
?>