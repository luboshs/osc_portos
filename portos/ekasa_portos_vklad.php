<?php
     // naËÌtanie z·kladn˝ch funkciÌ eshopu
        require('../includes/application_top.php');
        include(DIR_WS_CLASSES . 'order.php');
        $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
        if (isset($_POST["oID"])) {$oID = tep_db_prepare_input($HTTP_POST_VARS['oID']);}
        $order = new order($oID);
     // naËÌtanie nastavenÌ a funkciÌ ekasa
        include ('../portos/ekasa_portos_nastavenia.php');
        include ('../portos/ekasa_portos.php');
    
        // echo IP.'***<br />';
              
     // zistenie POST / GET d·t
        if (isset($_GET["faktura"])) {$faktura=true;} else {$faktura=false;}
        $akcia = (isset($HTTP_GET_VARS['akcia']) ? $HTTP_GET_VARS['akcia'] : $HTTP_POST_VARS['akcia']);

        switch ($akcia) {

            case 'blocek_generuj':

            // ========>
            // ========>
                    $id_doklad      =   $_POST["id_doklad"];
                    $id_doklad      =   'dsad2das1';
                    // PLATBY
                    $hotovost       =   $_POST["hotovost_ma_dat"];
                    if (isset($_POST["hotovost_ma_dat"])) {$hotovost=$_POST["hotovost_ma_dat"];} else {$hotovost=0;}
                    $platba_kartou  =   $_POST["karta"];  
                    if (isset($_POST["karta"])) {$platba_kartou=$_POST["karta"];} else {$platba_kartou=0;}
               //   prÌprava premenn˝ch pre doklad     
                    include ('portos/ekasa_priprav_data.php'); 
            // ========>
            // ========>
                    print("<pre>".print_r($data_array,true)."</pre>");
                    
                    echo '<br /><br />';
                    /*
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
                   
                   */
                   
                   
                    // VOLANIE API 
                    $response_json = callAPI('POST', 'requests/receipts/cash_register', json_encode($data_array));
                    $response  = json_decode($response_json, true);
  
                    //var_dump($response);
                    print("<pre>".print_r($response,true)."</pre>");
                    
                    // spracovanie odpovede
                    /* Several HTTP Status codes are used in response:
                          200: receipt was successfully registered in "eKasa" server of tax authority. We call this "online mode".
                          202: receipt was accepted by Portos eKasa system, but was not registered in "eKasa" server of tax authority due to internet connectivity issue (also referred as "offline mode").
                          400: the request contains validation errors.
                          403: the operation could not be completed due to error.
                          500: server-side error occurs.
                    */
      
      
                                // poradovÈ ËÌslo dokladu
                               $receipt_number = $response['request']['data']['receiptNumber'];
                               $okp = $response['request']['data']['okp'];
                               // celÈ pole s obsahom doklada a d·tami
                               $receipt_data = $response['request']['data'];
                               // ˙daje z ekasa serveru
                               $UID = $response['response']['data']['id'];
                               $processDate = $response['response']['processDate'];
                               $isSuccessful = $response['isSuccessful'];
                               // z·znamy o chyb·ch zo systÈmu ekasa
                               $error_code =  $response['error']['code'];
                               $error_message =  $response['error']['message'];
                              
                               var_dump($error);     
                                
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
      
                   $my_account_query = tep_db_query ("SELECT admin_name FROM administrators WHERE user_name= '" . $admin['username'] ."'");
                            $myAccount = tep_db_fetch_array($my_account_query);


          
                   /*   
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
                       $sql = tep_db_perform('ekasa_doklady', $sql_data_array);
    
                       $blocekID = mysql_insert_id();
                       $sql_order = tep_db_query("update orders set orders_status = 2, last_modified = now(), blocek = '" . (int)$blocekID . "' where orders_id = '" . (int)$oID . "'");
    
                       $komentar = "ekasa / Portos - objedn·vka uzavret· a vy˙Ëtovan· pokladniËn˝m bloËkom v celkovej sume ".$medzisucet." Ä"."\n\nPlatidl·:\nHotovosù = ".$hotovost_sql."\nKarta= ".$karta_sql."\nID bloËka = ".(int)$blocekID;
                       $sql_history = tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, updated_by) values ('" . (int)$oID . "', 2, now(), 1, '" . tep_db_input($komentar) . "', '" . tep_db_input($myAccount['admin_name'])  . "')");
                */
                
                $sql = true;
                  
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
                                                    echo '<input type="text" name="zlava_suma" id="zlava_suma" value="'.$_POST["zlava_suma"].'" readonly disabled style="font-size: 20pt" size="3">';
                                                    echo '</td>';
                                                    echo '<td>';
                                                    echo '</td>';
                                                    echo '</tr>';    
                                                    
                                                    echo '<tr>';
                                                    echo '<td>Platba kartou:</td>';
                                                    echo '<td>';
                                                    $karta_sql = str_replace (',','.',$_POST["karta"]);
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
                        else {echo '<br /><br />Nezn·ma chyba, kontaktujte spr·vcu.';}
                    break;
    
      
                default:
        
        //     <meta http-equiv="Content-Type" content="text/html; charset=cp-1250">
        ?><!DOCTYPE html>
        <html>
              <head>
                  <meta http-equiv="Content-Type" content="text/html; charset=cp-1250">
                  <title>E-KASA pre PORTOS 1.0</title>
                  <script language="javascript" src="portos/jquery-2.2.4.min.js"></script>
                  <script language="javascript" src="portos/ekasa_skripty.js"></script>
                  <link rel="stylesheet" type="text/css" href="portos/ekasa_portos.css">
              </head> 
              <body> 
              
              <form name="zapis" id="zapis" method="POST">
        <?php
          //include ('portos/nastavenia.php');
          $cID = (int)$order->customer['cid'];
        
        switch ($akcia) { 
                
                case 'faktura':
                    echo '˙hrada fakt˙ry';
                break;  
                    
                default:
                        echo '<table>';

                        include ('portos/ekasa_portos_stav.php');
                        
                        echo '<tr class="'.$class.'">'; 
                        echo '<td colspan="3">eKASA - Portos '.$br.'['.$systemovy_stav.']'.$hlasenie.'</td>';
                        echo '</tr>';                           
                        

                        echo '<tr id="prvy_riadok">';
                        echo '<td>Klient:</td>';
                        echo '<td>';
                        echo '<input type="text" name="klient" id="klient" value="'.$order->customer['name'].'"  readonly disabled style="font-size: 12pt" size="30">';
                        if ($order->customer['zlava']>0) {echo '<b><font color="red">Klient m· nastaven˙ zæavu '.$order->customer['zlava'].'%</font></b>';}
                        echo '</td>';
                        echo '<td>';
                        if ( $cID > 0)   {
                                    echo '<button type="button"  onclick="window.open('."'".FILENAME_ORDERS.'?cID='.$cID."'".', '."'".'_blank'."'".' );" class="button_karta">HIST”RIA KLIENTA</button>';
                                }
                        echo '</td>';      
                        echo '</tr>';
                                                 
                        echo '<tr>';
                        echo '<td>Suma n·kupu:</td>';
                        echo '<td>';
                        echo '<input type="text" name="suma" id="suma" value="'.$medzisucet.'"  readonly disabled style="font-size: 20pt" size="10">';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" onclick="window.open('."'http://localhost:3010/api/v1/printers/open_drawer', '_blank'".');" class="button_karta">OTVOR Z¡SUVKU</button>';
                        echo '</td>';      
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td>Zæava:</td>';
                        echo '<td>';
                        echo '<input type="text" name="zlava_p" id="zlava_p" value="0%" readonly style="font-size: 20pt" size="2"> ' ;
                        echo '&nbsp <input type="text" name="zlava_suma" id="zlava_suma" value="0.00" readonly style="font-size: 20pt" size="3">';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" onclick="dajZlavu();" class="button_karta">ZADAJ ZºAVU</button>';
                        echo '</td>';
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td>';
                        echo '';
                        echo '</td>';
                        echo '<td>';
                        echo '<br />Pre pokraËovanie sa sp˝taj klienta na spÙsob platby a klikni niûöie:<br /><br />';
                        echo '</td>';
                        echo '<td>';
                        echo '';
                        echo '</td>';
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td>';                                                                                   
                        echo '';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" onclick="location.hash = '."'#HotovostTR'".'; document.getElementById('."'hotovost'".').focus();" class="button_platba">IBA <br />HOTOVOSç</button> &nbsp';
                        echo '<button type="button" onclick="document.getElementById('."'hotovost'".').focus(); location.hash = '."'#PlatbaKartou'".'; platbaKartou();" class="button_platba">PLATBA <br />KARTOU</button>';
                        echo '</td>';
                        echo '<td>';                                                                                                        
            //          echo '<button type="button" onclick="window.close();" class="button_zrusit">ZAVRIEç OKNO</button>';
            //          echo '<button type="button" onclick='.'"javascript:var win = window.open'."('', '_self')".';win.close();return false;"'.' class="button_zrusit">ZAVRIEç OKNO</button>';
                        echo '</td>';
                        echo '</tr>';
                        
                        echo '<tr class="oddelovac">';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';  
                        
                        echo '<tr id="PlatbaKartou">';
                        echo '<td>Platba kartou:</td>';
                        echo '<td>';
            //          echo '<input type="text" name="karta" value="0" disabled id="karta" style="font-size: 25pt" size="8">';
                        echo '<input type="text" name="karta" value="0"  id="karta" style="font-size: 25pt" size="8">';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" name="karta_button" id="karta_button" onclick="platbaKartou(); zmenaHotovosti(); document.getElementById('."'hotovost'".').focus();" class="button_karta">UPRAVIç PLATBU KARTOU</button>';
                        
                        echo '</td>';
                        echo '</tr>';         
           
           
                     
                        echo '<tr id="HotovostTR">';
                        echo '<td>HOTOVOSç:</td>';
                        echo '<td>';
                        echo '<input type="text" name="hotovost" value="'.$medzisucet.'" style="font-size: 25pt" size="8" tabindex=1 id = "hotovost" onfocus="this.select();" oninput= "zmenaHotovosti();">';
                        echo '</td>';
                        echo '<td>';
         // =====> doplniù funkcie    
         //              echo '<button type="button" onclick="alert(455555555);" class="button_blocek">VYTLA»Iç BLO»EK</button>';
                        echo '</td>';
                        echo '</tr>';      
          
          
                     
                        echo '<tr id="VydavokTR">';
                        echo '<td>V˝davok:</td>';
                        echo '<td>';
                        echo '<input type="text" name="vydavok" value="NIE" readonly id="vydavok" style="font-size: 25pt" size="8">';
                        echo '</td>';
                        echo '<td>';
                   //     echo '<button type="button" onclick="location.hash = '."'#prvy_riadok'".';" class="button_zrusit">NA<br />ZA»IATOK</button>';
                        echo '</td>';
                        echo '</tr>';       
           
           
           /*  dorobiù moûnosù posielaù bloËek na email                                   
                        $email = $order->customer['email_address'];
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                echo '<tr id="email_tr">';
                                echo '<td>Email:</td>';
                                echo '<td>';
                                echo 'V objedn·vke je zadan˝ email, <b>sp˝taj sa z·kaznÌka, Ëi chce bloËek vytlaËiù alebo poslaù na email?</b><br />Email je potrebnÈ skontrolovaù. BloËek nie je moûnÈ zaslaù opakovane, ani ho neskÙr vytlaËiù.<br /><br />';
                                echo '<input type="text" name="email" value="'.$email.'" style="font-size: 12pt" size="30"  id = "email">';
                                echo '</td>';
                                echo '<td>';
                            //    echo '<button type="button" onclick="generujBlocek('.$oID.');" class="button_blocek">GENERUJ BLO»EK</button>';
                                echo '</td>';
                                echo '</tr>';                              
                        }          
            */


                        echo '<tr>';
                        echo '<td>';                                                                                   
                        echo '';
                        echo '</td>';
                        echo '<td><br />';
                        echo '<button type="button" onclick="generujBlocek();" class="button_blocek">VYTLA» DOKLAD</button> &nbsp';
                  //      echo '<button type="button" onclick="document.getElementById('."'hotovost'".').focus(); location.hash = '."'#PlatbaKartou'".'; platbaKartou();" class="button_platba">PLATBA <br />KARTOU</button>';
                        echo '</td>';
                        echo '<td>';                                                                                                        
                        echo '</td>';
                        echo '</tr>';

                        echo '<tr id="vyddavokTR2">';
                        echo '<td> </td>';
                        echo '<td>';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';  

                        echo '<tr class="oddelovac">';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';  

                        echo '</table>';
                        
                        
                        echo '<input type="hidden" name="oID" value="'.$oID.'">';
                        echo '<input type="hidden" name="medzisucet" id="medzisucet" value="'.$medzisucet.'">';
                        echo '<input type="hidden" name="dph" value="'.$dph.'">';
                        echo '<input type="hidden" name="nakup" value="'.$nakup.'">';
                        echo '<input type="hidden" name="akcia" value="" id="akcia">';
                        echo '<input type="hidden" name="hotovost_ma_dat" value="'.$medzisucet.'" id="hotovost_ma_dat">';                        
                        
                 }
    }
    
?>    
               </form>
              </body>
         </html>
