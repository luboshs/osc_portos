<?php
//  ****************************************************************
//  ******* AJAX endpoint pre zákaznícky displej *******************
//  ****************************************************************
//  ****** verzia 1.00 *********************************************
//  ****************************************************************
//  Kasa (portos/ekasa_skripty.js) sem pošle zadanú zľavu a skript
//  prepne zákaznícky displej do režimu SHOPPING vo fáze discounted.
//  Zľava 0 % znamená zrušenie zľavy - displej ukáže pôvodné ceny.

       require('includes/application_top.php');
       include ('portos/ekasa_portos_nastavenia.php');
       include ('portos/ekasa_portos.php');
       include ('portos/ekasa_displej.php');

       if (!headers_sent()) {
           header('Content-Type: application/json; charset=utf-8');
       }

       $oID = isset($_POST['oID']) ? (int)$_POST['oID'] : 0;
       $zlava_percent = isset($_POST['zlava_p']) ? ekasa_cislo(str_replace('%', '', trim($_POST['zlava_p']))) : 0;

       // povolený rozsah zľavy je rovnaký ako v ekasa_polozky.php (1 - 15 %),
       // každá iná hodnota (aj 0) znamená nákup bez zľavy - fáza preview
       if ($zlava_percent < 1 OR $zlava_percent > 15) { $zlava_percent = 0; }

       $vysledok = false;
       if ($oID > 0) {
               $vysledok = ekasa_displej_zlava($oID, $zlava_percent);
       }

       echo json_encode(array('success' => ($vysledok ? true : false),
                              'oID' => $oID,
                              'zlava_p' => $zlava_percent,
                              'stav' => isset($GLOBALS['ekasa_displej_log']) ? implode(' | ', $GLOBALS['ekasa_displej_log']) : ''));
?>
