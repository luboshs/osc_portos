<?php


     require('includes/application_top.php');
     include(DIR_WS_CLASSES . 'order.php');
     $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
     if (isset($_POST["oID"])) {$oID = tep_db_prepare_input($HTTP_POST_VARS['oID']);}
     $order = new order($oID);
     
     if (isset($_GET["faktura"])) {$faktura=true;} else {$faktura=false;}
     

    switch ($_POST["akcia"]) {

      case 'zapis':
                // z·pis bloËka do tabuæky    - ch˝ba koniec bloËka
                   $hotovost_sql = str_replace (',','.',$_POST["hotovost_ma_dat"]);
                   $hotovost_sql = number_format($hotovost_sql,2,'.',''); 
                   
                   $karta_sql = str_replace (',','.',$_POST["karta"]);
                   //$karta_sql = number_format($karta_sql,2,'.','');
                   
                   $medzisucet = str_replace (',','.',$_POST["medzisucet"]);
                   $medzisucet =number_format($medzisucet,2,'.','');
                   
                   $dph = str_replace (',','.',$_POST["dph"]);
                   $dph = number_format($dph,2,'.','');
                   
                   $nakup = str_replace (',','.',$_POST["nakup"]);
                   $nakup = number_format($nakup,2,'.','');
                   
                   $blocek_vystup = $_POST["ostry_blocek"];  
                       
                   $my_account_query = tep_db_query ("SELECT admin_name FROM administrators WHERE user_name= '" . $admin['username'] ."'");
                            $myAccount = tep_db_fetch_array($my_account_query);

                  $zlava = $_POST["zlava_suma"];
                   if ($zlava > 0) {
                            $polozka_z = "Zæava " .$_POST["zlava_p"];
                            $zlava_cena = (0 - $zlava)/1.2; 
                            tep_db_query("insert into " . TABLE_ORDERS_PRODUCTS . " (orders_id, products_model, products_name, products_price, final_price, products_tax, products_quantity) values ('" . (int)$oID . "', 'ZLAVA', '" . tep_db_input($polozka_z) . "', " . tep_db_input($zlava_cena)  . ", " . tep_db_input($zlava_cena). ", 20, 1)");
 
                   
                   }
          
                  
                   $sql_data_array = array('datum' => 'now()',
                                          'orders_id' => tep_db_prepare_input($oID),
                                          'zakaznik' => tep_db_prepare_input($order->customer['name']),
                                          'faktura' => tep_db_prepare_input(''),
                                          'suma_celkom' => tep_db_prepare_input($medzisucet),
                                          'hotovost' => tep_db_prepare_input($hotovost_sql),
                                          'karta' => tep_db_prepare_input($karta_sql),
                                          'dph' => tep_db_prepare_input($dph),
                                          'nakup' => tep_db_prepare_input($nakup),
                                     //   'marza' => tep_db_prepare_input($profit),
                                          'uzavrety_cas' => 'now()',
                                          'zaznam_blocek' => tep_db_prepare_input($blocek_vystup. "e"));
                   $sql = tep_db_perform('pu_blocky', $sql_data_array);

                   $blocekID = mysql_insert_id();
                   $sql_order = tep_db_query("update orders set orders_status = 2, last_modified = now(), blocek = '" . (int)$blocekID . "' where orders_id = '" . (int)$oID . "'");

                   $komentar = "ERP / FT4000 - objedn·vka uzavret· a vy˙Ëtovan· pokladniËn˝m bloËkom v celkovej sume ".$medzisucet." Ä"."\n\nPlatidl·:\nHotovosù = ".$hotovost_sql."\nKarta= ".$karta_sql."\nID bloËka = ".(int)$blocekID;
                   $sql_history = tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, updated_by) values ('" . (int)$oID . "', 2, now(), 1, '" . tep_db_input($komentar) . "', '" . tep_db_input($myAccount['admin_name'])  . "')");
            

                  
                  if ($sql) {
                                echo '<br /><br />Z·znam bol uloûen˝ do datab·zy, mÙûete zavrieù okno.<br /><br />';
                                                    echo '<table>';
                                                    echo '<tr>';
                                                    echo '<td>Suma n·kupu:</td>';
                                                    echo '<td>';
                                                    echo '<input type="text" name="suma" id="suma" value="'.$medzisucet.'"  readonly disabled style="font-size: 25pt" size="8">';
                                                    echo '</td>';
                                                    echo '<td>';
                                                    echo 'Ä';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                
                                                    echo '<tr>';
                                                    echo '<td>Zæava:</td>';
                                                    echo '<td>';
                                                    echo '<input type="text" name="zlava_p" id="zlava_p" value="'.$_POST["zlava_p"].'" readonly disabled style="font-size: 20pt" size="2">';
                                                    echo ' <input type="text" name="zlava_suma" id="zlava_suma" value="'.$_POST["zlava_suma"].'" readonly disabled style="font-size: 20pt" size="3">';
                                                    echo '</td>';
                                                    echo '<td>';
                                                    echo '</td>';
                                                    echo '</tr>';    
                                                    
                                                    echo '<tr>';
                                                    echo '<td>Platba kartou:</td>';
                                                    echo '<td>';
                                                    echo '<input type="text" name="karta" readonly disabled value="'.$karta_sql.'"  style="font-size: 25pt" size="8">';
                                                    echo '</td>';
                                                    echo '<td>';
                                                    echo '</td>';
                                                    echo '</tr>';         
                                                    
                                                    echo '<tr>';
                                                    echo '<td>HOTOVOSç:</td>';
                                                    echo '<td>';
                                                    
                                                    echo '<input type="text" name="hotovost" value="'.$_POST["hotovost"].'" readonly disabled style="font-size: 25pt" size="8" >';
                                                    echo '</td>';
                                                    
                                                    echo '<td>';
                                                    echo '</td>';
                                                    echo '</tr>';      
                                                   
                                                    echo '<tr>';
                                                    echo '<td>V˝davok:</td>';
                                                    echo '<td>';
                                                    echo '<input type="text" name="suma" readonly disabled value="'.$_POST["vydavok"].'"  style="font-size: 25pt" size="8">';
                                                    echo '</td>';
                                                    echo '</tr>';       
                                                    
                                                    echo '</table>';   
                                   ?>   <script language="javascript">
                                        window.parent.opener.location.reload();
                                        </script>
                                   <?php                                              
                                }
                  else {echo '<br /><br />Nezn·ma chyba, kontaktujte spr·vcu.
                  ';}
        break;
    
      
      default:
        
        
        //     <meta http-equiv="Content-Type" content="text/html; charset=cp-1250">
        ?><!DOCTYPE html>
        <html>
              <head>
                  <meta http-equiv="Content-Type" content="text/html; charset=cp-1250">
                  <title>KASA 1.0</title>
                  <script language="javascript" src="kasa/jquery-2.2.4.min.js"></script>
                  <script language="javascript" src="kasa/kasa_funkcie.js"></script>
                  <link rel="stylesheet" type="text/css" href="kasa/kasa_okno.css">
              </head> 
              <body> 
              
        <?php
        include ('kasa/nastavenia.php');
        include ('kasa/blocek_polozky.php');
        include ('kasa/blocek_priprava.php');
        
        
        echo '<form name="zapis" id="zapis" method="POST">';
        echo '<table>';
        echo '<tr>';
        echo '<td>Suma n·kupu:</td>';
        echo '<td>';
        echo '<input type="text" name="suma" id="suma" value="'.$medzisucet.'"  readonly disabled style="font-size: 25pt" size="8">';
        echo '</td>';
        echo '<td>';
        echo '<button type="button" onclick="otvorZasuvku();" class="button_karta">OTVOR Z¡SUVKU</button>';
        echo '</td>';      
        echo '</tr>';
        
        echo '<tr>';
        echo '<td>Zæava:</td>';
        echo '<td>';
        echo '<input type="text" name="zlava_p" id="zlava_p" value="0%" readonly disabled style="font-size: 20pt" size="2">';
        echo ' <input type="text" name="zlava_suma" id="zlava_suma" value="0.00" readonly disabled style="font-size: 20pt" size="3">';
        echo '</td>';
        echo '<td>';
        echo '<button type="button" onclick="dajZlavu();" class="button_karta">PRIDAJ ZºAVU</button>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td>Platba kartou:</td>';
        echo '<td>';
        echo '<input type="text" name="karta" value="0" readonly disabled id="karta" style="font-size: 25pt" size="8">';
        echo '</td>';
        echo '<td>';
        echo '<button type="button" onclick="platbaKartou();" class="button_karta">PLATBA KARTOU</button>';
        
        echo '</td>';
        echo '</tr>';         
        
        echo '<tr>';
        echo '<td>HOTOVOSç:</td>';
        echo '<td>';
        
        echo '<input type="text" name="hotovost" value="'.$medzisucet.'" style="font-size: 25pt" size="8" autofocus tabindex=1 id = "hotovost" onfocus="this.select();" oninput= "zmenaHotovosti();">';
        echo '</td>';
        
        echo '<td>';
        echo '<button type="button" onclick="generujBlocek('.$oID.');" class="button_blocek">GENERUJ BLO»EK</button>';
        
        //echo '<button type="button" onclick="stiahni('.$oID.');">STIAHNI BLO»EK</button>';
         
        echo '</td>';
        echo '</tr>';      
       
        echo '<tr>';
        echo '<td>V˝davok:</td>';
        echo '<td>';
        echo '<input type="text" name="vydavok" value="NIE" readonly disabled id="vydavok"  style="font-size: 25pt" size="8">';
        echo '</td>';
        echo '<td>';
        echo '<button type="button" onclick="window.close();" class="button_zrusit">ZAVRIEç OKNO</button>';
        echo '</td>';
        echo '</tr>';       
                               
        echo '</table>';
        
        
        echo '<div id="cakaj" class="schovaj" style="display:none;">';
        echo '<font color="red" size="18">';
        echo '<b>PROSÕM »AKAJTE!!</b><br />';
        echo '</font>';
        echo '</div>';
        
        
        echo 'N·hæad bloËku:<br />';
        echo '<textarea name="telo_0" cols="44" rows="25" readonly disabled  style="font-size: 8pt" id="nahlad">'.$blocek_js.'</textarea>';

        echo '<br>Ostr˝ bloËek:<br />';
        echo '<input type="hidden" name="oID" value="'.$oID.'">';
        echo '<input type="hidden" name="medzisucet" id="medzisucet" value="'.$medzisucet.'">';
        echo '<input type="hidden" name="dph" value="'.$dph.'">';
        echo '<input type="hidden" name="nakup" value="'.$nakup.'">';
        echo '<input type="hidden" name="akcia" value="" id="akcia">';
        echo '<input type="hidden" name="hotovost_ma_dat" value="'.$medzisucet.'" id="hotovost_ma_dat">';
        echo '<textarea name="ostry_blocek" cols="44" rows="10" readonly disabled  style="font-size: 8pt" id="ostry_blocek"></textarea>';
        echo '</form>';
        echo '</body>'.'</html>';
        break;

    }
?>
            