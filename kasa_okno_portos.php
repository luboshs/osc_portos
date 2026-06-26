<!DOCTYPE html>
 <?php
 
       ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=cp-1250');
        }

        function portos_diag($message, $context = array(), $type = 'INFO') {
            $bg = '#eef';
            if ($type === 'ERROR') {
                $bg = '#fdd';
            } elseif ($type === 'WARNING') {
                $bg = '#fff2cc';
            }

            echo '<div style="font-family: monospace; font-size: 12px; border:1px solid #666; background:' . $bg . '; margin:5px 0; padding:6px;">';
            echo '<b>[PORTOS DIAG][' . htmlspecialchars($type, ENT_QUOTES, 'cp-1250') . ']</b> ';
            echo htmlspecialchars($message, ENT_QUOTES, 'cp-1250');
            if (!empty($context)) {
                echo '<pre style="margin:6px 0 0 0;">';
                echo htmlspecialchars(print_r($context, true), ENT_QUOTES, 'cp-1250');
                echo '</pre>';
            }
            echo '</div>';
        }

        function portos_diag_error_handler($errno, $errstr, $errfile, $errline) {
            portos_diag('PHP warning/notice', array(
                'errno' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ), 'WARNING');
            return false;
        }

        function portos_diag_exception_handler($exception) {
            portos_diag('Neodchytena vynimka', array(
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ), 'ERROR');
        }

        function portos_diag_shutdown_handler() {
            $error = error_get_last();
            if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
                portos_diag('Fatalna chyba pri spracovani poziadavky', $error, 'ERROR');
            }
        }

        set_error_handler('portos_diag_error_handler');
        set_exception_handler('portos_diag_exception_handler');
        register_shutdown_function('portos_diag_shutdown_handler');
        portos_diag('Spustenie kasa_okno_portos.php', array(
            'time' => date('Y-m-d H:i:s'),
            'php_version' => phpversion(),
            'request_method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A',
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A',
            'get' => $_GET,
            'post' => $_POST
        ));
        
     // na��tanie z�kladn�ch funkci� eshopu
        require('includes/application_top.php');
        include(DIR_WS_CLASSES . 'order.php');
        $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);
        if (isset($_POST["oID"])) {$oID = tep_db_prepare_input($HTTP_POST_VARS['oID']);}
        $order = new order($oID);
     // na��tanie nastaven� a funkci� ekasa
        include ('portos/ekasa_portos_nastavenia.php');
        include ('portos/ekasa_portos.php');

        
   /*
    //  toto kr�sne vyp�e POST premenn�
    echo "<table>";
    foreach ($_POST as $key => $value) {
        echo "<tr>";
        echo "<td>";
        echo $key;
        echo "</td>";
        echo "<td>";
        echo $value;
        echo "</td>";
        echo "</tr>";

    }
    echo "</table>";
     */


     // pr�prava polo�iek dokladu     
        include ('portos/ekasa_polozky.php');
              
     // zistenie POST / GET d�t
        if (isset($_GET["faktura"])) {$faktura=true;} else {$faktura=false;}
        if (isset($HTTP_POST_VARS['akcia']) && $HTTP_POST_VARS['akcia'] !== '') {
            $akcia = $HTTP_POST_VARS['akcia'];
        } elseif (isset($HTTP_GET_VARS['akcia']) && $HTTP_GET_VARS['akcia'] !== '') {
            $akcia = $HTTP_GET_VARS['akcia'];
        } else {
            $akcia = '';
        }
        if ($akcia === '') {
            portos_diag('Parameter "akcia" nebol odovzdany alebo je prazdny.', array(
                'HTTP_GET_VARS' => isset($HTTP_GET_VARS) ? $HTTP_GET_VARS : array(),
                'HTTP_POST_VARS' => isset($HTTP_POST_VARS) ? $HTTP_POST_VARS : array()
            ), 'ERROR');
        } else {
            portos_diag('Spracovava sa akcia: ' . $akcia);
        }

        switch ($akcia) {                            

            case 'VKLAD_ZAPIS':
            
                    $datum = date('Y-m-d');
                    $vypis = tep_db_query("select hotovost_zostatok from ekasa_doklady WHERE date <='$datum' ORDER BY eID DESC LIMIT 1");
                        while ( $zostatok_a = tep_db_fetch_array($vypis)) {
                                $zostatok = $zostatok_a['hotovost_zostatok'];                                                                     
                                }
                    $sql_zaloz_id = tep_db_query("insert into ekasa_doklady (type, cashRegisterCode, date, hotovost_zostatok) values ('deposit', '".CASH_REGISTER_CODE."', '" . tep_db_input($datum) . "', '".tep_db_input($zostatok)."')");
                    $eID = mysql_insert_id();
                    //echo $eID;
                    
                    $suma           =   $_POST["suma"];
                    $suma           =   str_replace (',','.',$suma);
                    $novy_zostatok  =   $zostatok + $suma;            
                    $poznamkaInterna=   $_POST["poznamka"].', '.$_POST["poznamkaInterna"];
                    $poznamka       =   $_POST["poznamka"];
                    $function_url = 'requests/receipts/deposit'; 
                    $data_array = array ( 'request'=> array ('data' => array ('cashRegisterCode'=> CASH_REGISTER_CODE,
                                                                              'amount'=> $suma,
                                                                              "headerText" => "\n"."Poznamka: ".$poznamka."\n\n",
                                                                              //"footerText" => "This text will be printed at the end of receipt"
                                                                               )
                                                            )                  
                                             );                                        
                    $my_account_query = tep_db_query ("SELECT admin_name, pristup FROM administrators WHERE user_name= '" . $admin['username'] ."'");
                            $myAccount = tep_db_fetch_array($my_account_query);
                        //    if ($myAccount['pristup']==100) {$autorizoval = $myAccount['admin_name'];}
                        //    else {$autorizoval ="";}
                        $autorizoval = $myAccount['admin_name'];
                            
                    // VOLANIE API 
                    $response_json = callAPI('POST', $function_url, json_encode($data_array));
                    $response  = json_decode($response_json, true);
                    
                                // poradov� ��slo dokladu
                               $receipt_number = $response['request']['data']['receiptNumber'];
                               $okp = $response['request']['data']['okp'];
                               // cel� pole s obsahom doklada a d�tami
                               $receipt_data = $response['request']['data'];
                               // �daje z ekasa serveru
                               $UID = $response['response']['data']['id'];
                               $processDate = $response['response']['processDate'];
                               $isSuccessful = $response['isSuccessful'];
                               // z�znamy o chyb�ch zo syst�mu ekasa
                               $error =  $response['error'];
                               $error_code =  $response['error']['code'];
                               $error_message =  $response['error']['message'];
                               
                              
                              //'roundingAmount' => tep_db_prepare_input($hotovost_zaokruhlenie),
                              
                              
                              
                               if ($isSuccessful) {
                                       echo 'Z�pis OK. M��e� zavrie� okno.';
                                       ?>
                                       <script language="javascript">
                                        window.parent.opener.location.reload();
                                        </script> <br><br>
                                        <button type="button" 
                                            onclick="window.open('', '_self', ''); window.close();">Zavrie� okno</button>
                                       <?php
                                       $request_sent = 'zaevidovane';
                               } else {
                                       echo 'Vyskytla sa chyba! Pros�m informuj administr�tora!<br /><br />Error log:<br />';
                                       echo $response_json; 
                                       $email = "eID: ".$eID."\n\n".$response_json;
                                       tep_mail('Admin', 'antal@atac-sro.eu', 'Notifikacia - chyba portos kasa', $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
                                       $request_sent = 'chyba';
                               }
                      $year = date('Y');
                      $month =  date('m');
                      $sql_data_array = array(  'UID' => $UID,
                                                'receiptNumber' => tep_db_prepare_input($receipt_number),
                                                'year' => tep_db_prepare_input($year),
                                                'month' => tep_db_prepare_input($month),
                                                'request_sent' => tep_db_prepare_input($request_sent),
                                                'okp' => tep_db_prepare_input($okp),
                                                'processDate' => tep_db_prepare_input($processDate),
                                                'amount' => tep_db_prepare_input($suma),
                                                'hotovost_kredit' => tep_db_prepare_input($suma),
                                                'hotovost_zostatok' => tep_db_prepare_input($novy_zostatok),
                                                'response' => $response_json,
                                                'error' => tep_db_prepare_input($error),
                                                'admin' => tep_db_prepare_input($autorizoval),
                                                'poznamka' => tep_db_prepare_input($poznamkaInterna));
                                                
                          $sql = tep_db_perform('ekasa_doklady', $sql_data_array,'update',"eID = '".$eID."'");
                
               break;


            case 'FAKTURA_ZAPIS':
            
                    $datum = date('Y-m-d');
                    $vypis = tep_db_query("select hotovost_zostatok from ekasa_doklady WHERE date <='$datum' ORDER BY eID DESC LIMIT 1");
                        while ( $zostatok_a = tep_db_fetch_array($vypis)) {
                                $zostatok = $zostatok_a['hotovost_zostatok'];                                                                     
                                }
                    $sql_zaloz_id = tep_db_query("insert into ekasa_doklady (type, cashRegisterCode, date, hotovost_zostatok) values ('invoice', '".CASH_REGISTER_CODE."', '" . tep_db_input($datum) . "', '".tep_db_input($zostatok)."')");
                    $eID = mysql_insert_id();
               
                    $suma           =   $_POST["suma"];
                    $suma           =   str_replace (',','.',$suma);
                    $novy_zostatok  =   $zostatok + $suma;            
                    $poznamkaInterna=   $_POST["poznamkaInterna"];
                    $faktura        =   $_POST["cislo_faktury"];
                    $function_url = 'requests/receipts/invoice'; 
                    $roundingAmount = $_POST["zaokruhlenie"];
// !!!!!!!!!!!                     
                    $hotovost       =   $_POST["hotovost_ma_dat"];
                     $novy_zostatok  =   $zostatok + $hotovost;       
                    if (isset($_POST["hotovost_ma_dat"])) {$hotovost=$_POST["hotovost_ma_dat"];} else {$hotovost=0;}
                    $platba_kartou  =   $_POST["karta"];  
                    if (isset($_POST["karta"])) {$platba_kartou=$_POST["karta"];} else {$platba_kartou=0;}
                    $payments   = array(    array ('name' => "Hotovost", 'amount' => $hotovost),
                                            array ('name' => "Platba kartou", 'amount' => $platba_kartou));                    
                    //VYMAZ print("<pre>".print_r($payments,true)."</pre>");
                                                          
                                    $my_account_query = tep_db_query ("SELECT admin_name, sf_email, sf_kluc, pristup FROM administrators WHERE user_name= '" . $admin['username'] ."'");
                                    $myAccount = tep_db_fetch_array($my_account_query);
                                    $admin_name = $myAccount['admin_name'];
                                    $sf_email = $myAccount['sf_email'];
                                    $sf_kluc = $myAccount['sf_kluc'];
                                    $autorizoval = $myAccount['admin_name'];
                            
                    if ($oID>0) { $zakaznik_meno  =   ocisti($order->customer['name']);
                                  if ($doklad_na_email) {$nadpis= HEADER_TEXT;} else {$nadpis='';}
                                  $hlavicka = $nadpis.'------------------------------------------'."\n".'Cislo objednavky: '.$oID."\n".'Zakaznik: '.$zakaznik_meno."\n";
                    } else {$hlavicka = "";}

                    //$roundingAmount = 0;

                    $data_array = array ( 'request'=> array ('data' => array (
                                                                              'amount'=> $suma,
                                                                              'invoiceNumber'=> $faktura,
                                                                              'payments' => $payments,
                                                                              'roundingAmount' => $roundingAmount,
                                                                              'receiptType' => 'Invoice',
                                                                              'headerText' => $hlavicka,
                                                                              'footerText' => '',
                                                                              'cashRegisterCode' => CASH_REGISTER_CODE
                                                                               )
                                                            ),
                                          'print' => array ('printerName'=>'pos')                  
                                             ); 
                     //vymaz print("<pre>".print_r($data_array,true)."</pre>");
                    
                    // VOLANIE API 
                    $response_json = callAPI('POST', $function_url, json_encode($data_array));
                    $response  = json_decode($response_json, true);
                    
                                // poradov� ��slo dokladu
                               $receipt_number = $response['request']['data']['receiptNumber'];
                               $okp = $response['request']['data']['okp'];
                               // cel� pole s obsahom doklada a d�tami
                               $receipt_data = $response['request']['data'];
                               // �daje z ekasa serveru
                               $UID = $response['response']['data']['id'];
                               $processDate = $response['response']['processDate'];
                               $isSuccessful = $response['isSuccessful'];
                               // z�znamy o chyb�ch zo syst�mu ekasa
                               $error =  $response['error'];
                               $error_code =  $response['error']['code'];
                               $error_message =  $response['error']['message'];
                              
                               if ($isSuccessful) {
                                       echo 'Z�pis OK. M��e� zavrie� okno.';
                                       ?>
                                       <script language="javascript">
                                        window.parent.opener.location.reload();
                                        </script> <br><br>
                                        <button type="button" 
                                            onclick="window.open('', '_self', ''); window.close();">Zavrie� okno</button>
                                       <?php
                                       
                               } else {
                                       echo 'Vyskytla sa chyba! Pros�m informuj administr�tora!<br /><br />Error log:<br />';
                                       echo  $response_json; 
                                       $email = "eID: ".$eID."\n\n".$response_json;
                                       tep_mail('Admin', 'antal@atac-sro.eu', 'Notifikacia - chyba portos kasa', $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);  
                                       // ke�e sa blo�ek nebude evidova�, tak sa nebude meni� ani zostatok
                                       $novy_zostatok = $zostatok;
                               }
                      $year = date('Y');
                      $month =  date('m');
                      $sql_data_array = array(  'UID' => $UID,
                                                'receiptNumber' => tep_db_prepare_input($receipt_number),
                                                'invoiceNumber' => tep_db_prepare_input($faktura),
                                                'year' => tep_db_prepare_input($year),
                                                'month' => tep_db_prepare_input($month),
                                                'request_sent' => 'odoslane',
                                                'okp' => tep_db_prepare_input($okp),
                                                'processDate' => tep_db_prepare_input($processDate),
                                                'amount' => tep_db_prepare_input($suma),
                                                'hotovost_kredit' => tep_db_prepare_input($hotovost),
                                                'hotovost_zostatok' => tep_db_prepare_input($novy_zostatok),
                                                'platobna_karta' => tep_db_prepare_input($platba_kartou),                                                
                                                'response' => $response_json,
                                                'error' => tep_db_prepare_input($error),
                                                'admin' => tep_db_prepare_input($autorizoval),
                                                'poznamka' => tep_db_prepare_input($poznamkaInterna));
                                                
                          $sql = tep_db_perform('ekasa_doklady', $sql_data_array,'update',"eID = '".$eID."'");

                             if ($isSuccessful) {
                                   $sql_order = tep_db_query("update orders set orders_status = 2, last_modified = now(), blocek = '" . (int)$eID . "' where orders_id = '" . (int)$oID . "'");
             // dokon�i� !!!
                                   $komentar = "ekasa/CHDU portos - Fakt�ra bola uhraden� - �hrada bola vy��tovan� pokladni�n�m blo�kom v celkovej sume ".$suma." � (zaokr�hlenie = ".$roundingAmount.")"."\n\nPlatidl�:\nHotovos� = ".$hotovost."\nKarta= ".$platba_kartou."\nUID blo�ka = ".$UID."\n��slo blo�ka = ".$receipt_number."\nNa�e ID blo�ka = ".$eID.$email_log;
                                   $sql_history = tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, updated_by) values ('" . (int)$oID . "', 2, now(), 1, '" . tep_db_input($komentar) . "', '" . tep_db_input($myAccount['admin_name'])  . "')");
                                } else {
                                   // $sql_order = tep_db_query("update orders set orders_status = 2, last_modified = now(), blocek = '" . (int)$eID . "' where orders_id = '" . (int)$oID . "'");
                                   $komentar = "ekasa/CHDU portos - chyba, �hrada fakt�ry ne�spe�n�"."\n\n".$email."\n\n"."Platidl�:\nHotovos� = ".$hotovost."\nKarta= ".$platba_kartou."\nUID blo�ka = ".$UID."\n��slo blo�ka = ".$receipt_number."\nNa�e ID blo�ka = ".$eID.$email_log;
                                   $sql_history = tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, updated_by) values ('" . (int)$oID . "', 2, now(), 1, '" . tep_db_input($komentar) . "', '" . tep_db_input($myAccount['admin_name'])  . "')");

                                }



                                    $header = array ("Authorization: SFAPI email=".$sf_email."&apikey=".$sf_kluc."&company_id=16393"); 
                                    $invoice_id = $order->info['superfaktura_id'];
                                    $link = 'https://moja.superfaktura.sk/invoice_payments/add/ajax:1/api:1';
                                    $datum = date('Y-m-d'); 
                                            $request_data = array ();
                                            $request_data['InvoicePayment'] = array(
                                                                                      'invoice_id'       => $invoice_id,
                                                                                      'payment_type'     => 'cash',
                                                                                      'amount'           => $suma,
                                                                                      'currency'         => 'EUR',
                                                                                      'created'          => $datum
                                                                                      );                                                                                 
                                                                                  
                                     $data = array ('data' => json_encode($request_data));
                                     $ch = curl_init(); 
                                     curl_setopt($ch, CURLOPT_URL, $link);
                                     curl_setopt($ch, CURLOPT_POST, 1);
                                     curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                               //      curl_setopt($ch, CURLOPT_HEADER, true);
                                     curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
                               //    curl_setopt($ch, CURLOPT_TIMEOUT,TIMEOUT);
                                     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                     curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                                     // EXECUTE:
                                     $response_json2 = curl_exec($ch);
                                     $response2 = json_decode($response_json2, true);
                                     $error = $response2['error'] ;
                                     curl_close($ch);      
                                      
                                       if ($error == 0) {
                                                 echo '�hrada bola zap�san� do superfakt�ry.';
                                       } else {
                                                 echo 'Vyskytla sa chyba pri z�pise do superfakt�ry! Pros�m informuj administr�tora!<br />';
                                                 echo  $response_json2; 
                                                 $email = "chyba z�pisu do superfakt�ry, eID: ".$eID."\n\n".$data."\n\n".$response_json2;
                                                 tep_mail('Admin', 'antal@atac-sro.eu', 'Notifikacia - chyba portos kasa', $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);  
                                       }

                          if ($sql) {
                                        echo '<br /><br />Z�znam bol ulo�en� do datab�zy, m��ete zavrie� okno.<br />';
                                                            echo '<table>';
        
                                                            echo '<tr>';
                                                            echo '<td>Suma:</td>';
                                                            echo '<td>';
                                                            echo '<input type="text" name="suma" id="suma" value="'.$suma.'"  readonly disabled style="font-size: 25pt" size="8">';
                                                            echo '</td>';
                                                            echo '<td>';
                                                            echo '�';
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
                                                            echo '<td>ZAOKR�HLENIE:</td>';
                                                            echo '<td>';                                                            
                                                            echo '<input type="text" name="hotovost" value="'.$_POST["zaokruhlenie"].'" readonly disabled style="font-size: 25pt" size="8" >';
                                                            echo '</td>';                                                            
                                                            
                                                            echo '<tr>';
                                                            echo '<td>HOTOVOS�:</td>';
                                                            echo '<td>';
                                                            
                                                            echo '<input type="text" name="hotovost" value="'.$_POST["hotovost"].'" readonly disabled style="font-size: 25pt" size="8" >';
                                                            echo '</td>';
                                                            
                                                            echo '<td>';
                                                            echo '</td>';
                                                            echo '</tr>';      
                                                           
                                                            echo '<tr>';
                                                            echo '<td>V�davok:</td>';
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
                                                                            
                                else {echo '<br /><br />Nezn�ma chyba, kontaktujte spr�vcu.';}
                
               break;



            case 'VYBER_ZAPIS':
            
                    $datum = date('Y-m-d');
                    $vypis = tep_db_query("select hotovost_zostatok from ekasa_doklady WHERE date <='$datum' ORDER BY eID DESC LIMIT 1");
                        while ( $zostatok_a = tep_db_fetch_array($vypis)) {
                                $zostatok = $zostatok_a['hotovost_zostatok'];                                                                     
                                }
                    if ($_POST["banka"]=="FIO") {$type = "withdraw-bank";} 
                    else if ($_POST["banka"]=="TABA") {$type = "withdraw-bank";} 
                    else {$type = "withdraw";}
                    $sql_zaloz_id = tep_db_query("insert into ekasa_doklady (type, cashRegisterCode, date, hotovost_zostatok) values ('". tep_db_input($type) ."', '".CASH_REGISTER_CODE."', '" . tep_db_input($datum) . "', '".tep_db_input($zostatok)."')");
                    $eID = mysql_insert_id();
                    //echo $eID;
                    
                    $suma           =   $_POST["suma"];
                    $suma           =   str_replace (',','.',$suma);
                    $suma           =   abs ($suma);
                    $novy_zostatok  =   $zostatok - $suma;       
                    $suma_negativna = 0 -$suma;      
                    $poznamkaInterna=   $_POST["poznamka"].', '.$_POST["poznamkaInterna"];
                    $poznamka       =   $_POST["poznamka"];
                    $function_url = 'requests/receipts/withdraw'; 
                    $data_array = array ( 'request'=> array ('data' => array ('cashRegisterCode'=> CASH_REGISTER_CODE,
                                                                              'amount'=> $suma_negativna,
                                                                              "headerText" => "\n"."Poznamka: ".$poznamka."\n\n",
                                                                              //"footerText" => "This text will be printed at the end of receipt"
                                                                               )
                                                            )                  
                                             );                                        
                    $my_account_query = tep_db_query ("SELECT admin_name, pristup FROM administrators WHERE user_name= '" . $admin['username'] ."'");
                            $myAccount = tep_db_fetch_array($my_account_query);
                           if ($myAccount['pristup']==100) {$autorizovane = true;}
                            else {$autorizovane=false;}
                          $autorizoval = $myAccount['admin_name'];
                            
                    // VOLANIE API 
                    $response_json = callAPI('POST', $function_url, json_encode($data_array));
                    $response  = json_decode($response_json, true);
                    
                                // poradov� ��slo dokladu
                               $receipt_number = $response['request']['data']['receiptNumber'];
                               $okp = $response['request']['data']['okp'];
                               // cel� pole s obsahom doklada a d�tami
                               $receipt_data = $response['request']['data'];
                               // �daje z ekasa serveru
                               $UID = $response['response']['data']['id'];
                               $processDate = $response['response']['processDate'];
                               $isSuccessful = $response['isSuccessful'];
                               // z�znamy o chyb�ch zo syst�mu ekasa
                               $error =  $response['error'];
                               $error_code =  $response['error']['code'];
                               $error_message =  $response['error']['message'];
                              
                               if ($isSuccessful)  {
                                       echo 'Z�pis OK. M��e� zavrie� okno.';
                                       ?>
                                       <script language="javascript">
                                        window.parent.opener.location.reload();
                                        </script> <br><br>
                                        <button type="button" 
                                            onclick="window.open('', '_self', ''); window.close();">Zavrie� okno</button>
                                       <?php
                                       
                               } else {
                                       echo 'Vyskytla sa chyba! Pros�m informuj administr�tora!<br /><br />Error log:<br />';
                                       echo $response_json; 
                                       $email = "eID: ".$eID."\n\n".$response_json;
                                       tep_mail('Admin', 'antal@atac-sro.eu', 'Notifikacia - chyba portos kasa', $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);  
                               }
                      $year = date('Y');
                      $month =  date('m');
                      $sql_data_array = array(  'UID' => $UID,
                                                'receiptNumber' => tep_db_prepare_input($receipt_number),
                                                'year' => tep_db_prepare_input($year),
                                                'month' => tep_db_prepare_input($month),
                                                'request_sent' => 'odoslane',
                                                'okp' => tep_db_prepare_input($okp),
                                                'processDate' => tep_db_prepare_input($processDate),
                                                'amount' => tep_db_prepare_input($suma),
                                                'hotovost_debit' => tep_db_prepare_input($suma),
                                                'hotovost_zostatok' => tep_db_prepare_input($novy_zostatok),
                                                'response' => $response_json,
                                                'error' => tep_db_prepare_input($error),
                                                'admin' => tep_db_prepare_input($autorizoval),
                                                'autorizovane' => tep_db_prepare_input($autorizovane),
                                                'poznamka' => tep_db_prepare_input($poznamkaInterna));
                                                
                          $sql = tep_db_perform('ekasa_doklady', $sql_data_array,'update',"eID = '".$eID."'");
                
               break;




            
            
            case 'blocek_generuj':

            // ========> zalo�im riadok dokladu v databaze a zistim jeho id
                    $datum = date('Y-m-d');
                    $vypis = tep_db_query("select hotovost_zostatok from ekasa_doklady WHERE date <='$datum' ORDER BY eID DESC LIMIT 1");
                        while ( $zostatok_a = tep_db_fetch_array($vypis)) {
                                $zostatok = $zostatok_a['hotovost_zostatok'];                                                                     
                                }
                    $sql_zaloz_id = tep_db_query("insert into ekasa_doklady (type, cashRegisterCode, date, hotovost_zostatok) values ('cash_register', '".CASH_REGISTER_CODE."', '" . tep_db_input($datum) . "', '".tep_db_input($zostatok)."')");
                    $eID = mysql_insert_id();
                    
                    $roundingAmount =   $_POST["zaokruhlenie"];
                    $hotovost       =   $_POST["hotovost_ma_dat"];
                    $novy_zostatok  =   $zostatok + $hotovost;
                    if (isset($_POST["hotovost_ma_dat"])) {$hotovost=$_POST["hotovost_ma_dat"];} else {$hotovost=0;}
                    $platba_kartou  =   $_POST["karta"];  
                    if (isset($_POST["karta"])) {$platba_kartou=$_POST["karta"];} else {$platba_kartou=0;}
               //   pr�prava premenn�ch pre doklad     
                    include ('portos/ekasa_priprav_data.php');
            // ========>
            // ========>  premenn� => po�iadavka
                    echo '<br /><br />';

                    // VOLANIE API 
                    $response_json = callAPI('POST', 'requests/receipts/cash_register', json_encode($data_array));
                    $response  = json_decode($response_json, true);
                    //var_dump($response);
                    //print("<pre>".print_r($response,true)."</pre>");
                    
                    // spracovanie odpovede
                    /* Several HTTP Status codes are used in response:
                          200: receipt was successfully registered in "eKasa" server of tax authority. We call this "online mode".
                          202: receipt was accepted by Portos eKasa system, but was not registered in "eKasa" server of tax authority due to internet connectivity issue (also referred as "offline mode").
                          400: the request contains validation errors.
                          403: the operation could not be completed due to error.
                          500: server-side error occurs.
                    */
                    
                    // poradov� ��slo dokladu
                               $receipt_number = $response['request']['data']['receiptNumber'];
                               $amount = $response['request']['data']['amount'];
                               $okp = $response['request']['data']['okp'];
                               // cel� pole s obsahom doklada a d�tami
                               $receipt_data = $response['request']['data'];
                               // �daje z ekasa serveru
                               $UID = $response['response']['data']['id'];
                               $processDate = $response['response']['processDate'];
                               $isSuccessful = $response['isSuccessful'];
                               // z�znamy o chyb�ch zo syst�mu ekasa
                               $error =  $response['error'];
                               $error_code =  $response['error']['code'];
                               $error_message =  $response['error']['message'];
                               
                               $vat = $response['request']['data']['basicVatAmount']+$response['request']['data']['reducedVatAmount'];
                               
                            $my_account_query = tep_db_query ("SELECT admin_name FROM administrators WHERE user_name= '" . $admin['username'] ."'");
                            $myAccount = tep_db_fetch_array($my_account_query);
                            $autorizoval = $myAccount['admin_name'];
                              
                               if ($isSuccessful) {
                                       echo 'Z�pis OK. M��e� zavrie� okno.';
                                       ?>
                                       <script language="javascript">
                                        window.parent.opener.location.reload();
                                        </script> 
                                        <button type="button" 
                                            onclick="window.open('', '_self', ''); window.close();">Zavrie� okno</button>
                                       <?php
                                       
                               } else {
                                       echo 'Vyskytla sa chyba! Pros�m informuj administr�tora!<br /><br />Error log:<br />';
                                       echo $response_json; 
                                       $email = "eID: ".$eID."\n\n".$response_json;
                                       tep_mail('Admin', 'antal@atac-sro.eu', 'Notifikacia - chyba portos kasa', $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
                                       $novy_zostatok = $zostatok;
                               }
                               
                      $year = date('Y');
                      $month =  date('m');
                      if ($hotovost>0) {$hotovost_debit=0; $hotovost_kredit=$hotovost;}
                      else {$hotovost_debit=0-abs($hotovost); $hotovost_kredit=0;}
                      $zakaznik_meno  =   ocisti($order->customer['name']);
                      if ($doklad_na_email) {$zakaznik_email=$_POST["email"];} else {$zakaznik_email='';}
                      $sql_data_array = array(  'UID' => tep_db_prepare_input($UID),                                           
                                                'oID' => $oID,                                                                   
                                                'client_name' => tep_db_prepare_input($zakaznik_meno),
                                                'email' => tep_db_prepare_input($zakaznik_email),
                                                'receiptNumber' => tep_db_prepare_input($receipt_number),
                                                'year' => tep_db_prepare_input($year),
                                                'month' => tep_db_prepare_input($month),
                                                'request_sent' => 'odoslane',
                                                'okp' => tep_db_prepare_input($okp),
                                                'processDate' => tep_db_prepare_input($processDate),
                                                'amount' => tep_db_prepare_input($amount),
                                                'vat' => tep_db_prepare_input($vat),
                                                'hotovost_kredit' => tep_db_prepare_input($hotovost_kredit),
                                                'hotovost_debit' => tep_db_prepare_input($hotovost_debit),
                                                'platobna_karta'  => tep_db_prepare_input($platba_kartou),
                                                'hotovost_zostatok' => tep_db_prepare_input($novy_zostatok),
                                                'response' => $response_json,
                                                'error' => tep_db_prepare_input($error),
                                                'admin' => tep_db_prepare_input($autorizoval),
                                                'poznamka' => tep_db_prepare_input($poznamkaInterna));
                                                
                          $sql = tep_db_perform('ekasa_doklady', $sql_data_array,'update',"eID = '".$eID."'");

                if ($zlava_pritomna) {
                            $polozka_z = "Z�ava " .$_POST["zlava_p"];
                            $zlava_m_bez_dph = $zlava_m / 1.2;
                            tep_db_query("insert into " . TABLE_ORDERS_PRODUCTS . " (orders_id, products_model, products_name, products_price, final_price, products_tax, products_quantity) values ('" . (int)$oID . "', 'ZLAVA', '" . tep_db_input($polozka_z) . "', " . tep_db_input($zlava_m_bez_dph)  . ", " . tep_db_input($zlava_m_bez_dph). ", 20, 1)");
                }

                if ($isSuccessful){
                   $sql_order = tep_db_query("update orders set orders_status = 2, last_modified = now(), blocek = '" . (int)$eID . "' where orders_id = '" . (int)$oID . "'");
                   $komentar = "ekasa/CHDU portos - objedn�vka uzavret� a vy��tovan� pokladni�n�m blo�kom v celkovej sume ".$amount." �"."\n"." (zaokr�hlenie: ".$roundingAmount.")";
                   if ($zlava_pritomna) {$komentar .= "\n\n" . "Z�AVA: ". $zlava_m . " � [".$_POST["zlava_p"]."]";}
                   $komentar .= "\n\nPlatidl�:\nHotovos� = ".$hotovost."\nKarta= ".$platba_kartou."\nUID blo�ka = ".$UID."\n��slo blo�ka = ".$receipt_number."\nNa�e ID blo�ka = ".$eID.$email_log;
                   $sql_history = tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, updated_by) values ('" . (int)$oID . "', 2, now(), 1, '" . tep_db_input($komentar) . "', '" . tep_db_input($myAccount['admin_name'])  . "')");
                } else {
                   //$sql_order = tep_db_query("update orders set orders_status = 2, last_modified = now(), blocek = '" . (int)$eID . "' where orders_id = '" . (int)$oID . "'");
                   $komentar = "ekasa/CHDU portos - chyba pri tla�i blo�ka"."\n\n".$response_json."\n\n";
                   if ($zlava_pritomna) {$komentar .= "\n\n" . "Z�AVA: ". $zlava_m . " � [".$_POST["zlava_p"]."]";}
                   $komentar .= "\n\nPlatidl�:\nHotovos� = ".$hotovost."\nKarta= ".$platba_kartou."\nUID blo�ka = ".$UID."\n��slo blo�ka = ".$receipt_number."\nNa�e ID blo�ka = ".$eID.$email_log;
                   $sql_history = tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, updated_by) values ('" . (int)$oID . "', 2, now(), 1, '" . tep_db_input($komentar) . "', '" . tep_db_input($myAccount['admin_name'])  . "')");
                }


          
                // $sql = true;
                  
                  if ($sql) {
                                echo '<br />Z�znam bol ulo�en� do datab�zy, m��ete zavrie� okno.<br /><br />';
                                                    echo '<table>';

                                                    echo '<tr>';
                                                    echo '<td>Suma n�kupu:</td>';
                                                    echo '<td>';
                                                    echo '<input type="text" name="suma" id="suma" value="'.$medzisucet.'"  readonly disabled style="font-size: 25pt" size="8">';
                                                    echo '</td>';
                                                    echo '<td>';
                                                    echo '�';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                
                                                    echo '<tr>';
                                                    echo '<td>Z�ava:</td>';
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
                                                    echo '<td>ZAOKR�HLENIE:</td>';
                                                    echo '<td>';
                                                    
                                                    echo '<input type="text" name="hotovost" value="'.$_POST["zaokruhlenie"].'" readonly disabled style="font-size: 25pt" size="8" >';
                                                    echo '</td>';


                                                    echo '<tr>';
                                                    echo '<td>HOTOVOS�:</td>';
                                                    echo '<td>';
                                                    
                                                    echo '<input type="text" name="hotovost" value="'.$_POST["hotovost"].'" readonly disabled style="font-size: 25pt" size="8" >';
                                                    echo '</td>';
                                                    
                                                    echo '<td>';
                                                    echo '</td>';
                                                    echo '</tr>';      
                                                   
                                                    echo '<tr>';
                                                    echo '<td>V�davok:</td>';
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
                        else {echo '<br /><br />Nezn�ma chyba, kontaktujte spr�vcu.';}
                    break;
    
      
                default:
        
        //     <meta http-equiv="Content-Type" content="text/html; charset=cp-1250">
        ?> 
        <html>
              <head>
                  <meta http-equiv="Content-Type" content="text/html; charset=cp-1250">
                  <title><?php echo APP_NAME.' '.APP_VERSION;?></title>
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
                
              
               
                case 'VKLAD':
                        echo '<table>';

                        include ('portos/ekasa_portos_stav.php');
                        
                        echo '<tr class="'.$class.'">'; 
                        echo '<td colspan="3">eKASA - Portos '.$br.'['.$systemovy_stav.']'.$hlasenie.'</td>';
                        echo '</tr>';                           

                        echo '<tr>';
                        echo '<td colspan="3" class="nadpis" align="center"><h1>VKLAD DO POKLADNE</h1></td>';
                        echo '</tr>';
                        
                        echo '<tr>';
                        echo '<td>Suma vkladu:</td>';
                        echo '<td>';
                        echo '<input type="text" name="suma" id="suma" value="0" autofocus style="font-size: 20pt" size="10" onfocus="this.select();" tabindex=1> EUR';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';      
                        echo '</tr>';

                        echo '<tr id="HotovostTR">';
                        echo '<td>Pozn�mka na doklad:</td>';
                        echo '<td>';
                        echo '<input type="text" name="poznamka" value="VKLAD" style="font-size: 20pt" size="20" tabindex=2  onfocus="this.select();" >';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';      
          
                        echo '<tr id="HotovostTR">';
                        echo '<td>Intern� Pozn�mka:</td>';
                        echo '<td>';
                        echo '<input type="text" name="poznamkaInterna" value="" style="font-size: 20pt" size="20" tabindex=3  onfocus="this.select();" >';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';    

                        echo '<tr>';
                        echo '<td>';                                                                                   
                        echo ' ';
                        echo '</td>';
                        echo '<td>';
                        echo '<input type="submit" class="button_blocek" value="Vytla� doklad">';
                        echo '<button type="button" class="button_karta" onclick="OtvorZasuvku();">OTVOR Z�SUVKU</button>';    
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';

                        echo '</table>';
                        
                        echo '<input type="hidden" name="akcia" value="VKLAD_ZAPIS">';
                         
                break;                 
               
               


                case 'VYBER':
                        echo '<table>';

                        include ('portos/ekasa_portos_stav.php');
                        
                        echo '<tr class="'.$class.'">'; 
                        echo '<td colspan="3">eKASA - Portos '.$br.'['.$systemovy_stav.']'.$hlasenie.'</td>';
                        echo '</tr>';                           
 
                        echo '<tr>';
                        echo '<td colspan="3" class="nadpis" align="center"><h1>V�BER Z POKLADNE</h1></td>';
                        echo '</tr>';
                        
                        echo '<tr>';
                        echo '<td>Suma v�beru:</td>';
                        echo '<td>';
                        echo '<input type="text" name="suma" id="suma" value="0" autofocus style="font-size: 20pt" size="10" onfocus="this.select();" tabindex=1> EUR';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';      
                        echo '</tr>';

                        echo '<tr id="HotovostTR">';
                        echo '<td>Pozn�mka na doklad:</td>';
                        echo '<td>';

                        if ($_GET["banka"]=="FIO") {
                                $poznamka = "VYBER-FIO";
                                $interna_poznamka = "vklad hotovosti na ��et FIO";
                                $readonly = "readonly";
                                echo '<input type="hidden" name="banka" value="FIO">';
                        } else if ($_GET["banka"]=="TABA") {
                                $poznamka = "VYBER-TABA";
                                $interna_poznamka = "vklad hotovosti na ��et Tatra banka";
                                $readonly = "readonly";
                                echo '<input type="hidden" name="banka" value="FIO">';
                        } else {
                                $poznamka = "VYBER";
                                $interna_poznamka = "";
                                $readonly = "";
                        }

                        echo '<input type="text" name="poznamka" value="'.$poznamka.'" style="font-size: 20pt" size="20" tabindex=2  onfocus="this.select();" '.$readonly.'>';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';      
          
                        echo '<tr id="HotovostTR">';
                        echo '<td>Intern� Pozn�mka:</td>';
                        echo '<td>';
                        echo '<input type="text" name="poznamkaInterna" value="'.$interna_poznamka.'" style="font-size: 20pt" size="20" tabindex=3  onfocus="this.select();" '.$readonly.'>';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';    

                        echo '<tr>';
                        echo '<td>';                                                                                   
                        echo ' ';
                        echo '</td>';
                        echo '<td>';

                        echo '<input type="submit" class="button_blocek" value="Vytla� doklad">';
                        echo '<button type="button" class="button_karta" onclick="OtvorZasuvku();">OTVOR Z�SUVKU</button>';

                        echo '<input type="submit" class="button_blocek" value="Vytla� doklad"> ';
                        echo '<input type="submit" class="button_karta" onclick="OtvorZasuvku();" value="Z�SUVKA">';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';

                        echo '</table>';
                        
                        echo '<input type="hidden" name="akcia" value="VYBER_ZAPIS">';
                break;                 
            


                case 'FAKTURA':
                        echo '<table>';

                        include ('portos/ekasa_portos_stav.php');
                        
                        echo '<tr class="'.$class.'">'; 
                        echo '<td colspan="3">eKASA - Portos '.$br.'['.$systemovy_stav.']'.$hlasenie.'</td>';
                        echo '</tr>';                           
                         if ($_GET["zdroj"]=="manual") {
                                 $suma = $_GET["suma"];
                                 $cislo_faktury = ocisti($_GET["cislo_faktury"]);
                         } else if ($_GET["zdroj"]=="objednavka") {
                                    $cislo_faktury = ocisti($_GET["cislo_faktury"]);
                                    $my_account_query = tep_db_query ("SELECT admin_name, sf_email, sf_kluc FROM administrators WHERE user_name= '" . $admin['username'] ."'");
                                    $myAccount = tep_db_fetch_array($my_account_query);
                                    $admin_name = $myAccount['admin_name'];
                                    $sf_email = $myAccount['sf_email'];
                                    $sf_kluc = $myAccount['sf_kluc'];
                                    $header = array ("Authorization: SFAPI email=".$sf_email."&apikey=".$sf_kluc."&company_id=16393"); 
                                    $invoice_id = $order->info['superfaktura_id'];
                                    $link = 'https://moja.superfaktura.sk/invoices/view/'.$invoice_id.'.json';
                                    $ch = curl_init(); 
                                     curl_setopt($ch, CURLOPT_URL, $link);
                                     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                                     curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
                                     $response_json = curl_exec($ch);
                                    curl_close($ch);       
                                    $response  = json_decode($response_json, true);
                                    $suma = $response[0]['total'];                               
                         }
                                    $cifra = substr(number_format($suma, 2,'.',''),-1);
                                                  switch ($cifra) {
                                                      case 0:
                                                          $zaokruhlenie = 0;
                                                          break;
                                                      case 1:
                                                          $zaokruhlenie = -0.01;
                                                          break;
                                                      case 2:
                                                          $zaokruhlenie = -0.02;
                                                          break;
                                                      case 3:
                                                          $zaokruhlenie = 0.02;
                                                          break;
                                                      case 4:
                                                          $zaokruhlenie = 0.01;
                                                          break;
                                                      case 5:
                                                          $zaokruhlenie = 0;
                                                          break;
                                                      case 6:
                                                          $zaokruhlenie = -0.01;
                                                          break;
                                                      case 7:
                                                          $zaokruhlenie = -0.02;
                                                          break;
                                                      case 8:
                                                          $zaokruhlenie = 0.02;
                                                          break;    
                                                      case 9:
                                                          $zaokruhlenie = 0.01;
                                                          break;
                                                  }


                        echo '<tr>';
                        echo '<td colspan="3" class="nadpis" align="center"><h1>�HRADA FAKT�RY</h1></td>';
                        echo '</tr>';
                        
                        echo '<tr>';
                        echo '<td>Suma fakt�ry:</td>';
                        echo '<td>';
              //        echo '<input type="text" name="suma" id="suma" value="'.$suma.'" autofocus style="font-size: 20pt" size="10" onfocus="this.select();" tabindex=1> EUR';
                        echo '<input type="text" name="suma" id="suma" value="'.$suma.'"           style="font-size: 20pt" size="10" onfocus="this.select();"> EUR';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';      
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td>��slo fakt�ry:</td>';
                        echo '<td>';
                        echo '<input type="text" name="cislo_faktury" value="'.$cislo_faktury.'" style="font-size: 20pt" size="20" tabindex=1  onfocus="this.select();" >';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';      
          
                        echo '<tr>';
                        echo '<td>Intern� Pozn�mka:</td>';
                        echo '<td>';
                        echo '<input type="text" name="poznamkaInterna" value="" style="font-size: 20pt" size="20" tabindex=2  onfocus="this.select();" >';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';    



                        echo '<tr>';
                        echo '<td>';
                        echo '';
                        echo '</td>';
                        echo '<td>';
                        echo '<br />Sp�taj sa na sp�sob platby a klikni ni��ie:<br />';
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
                        echo '<button type="button" onclick="location.hash = '."'#HotovostTR'".'; document.getElementById('."'hotovost'".').focus();" class="button_platba">IBA <br />HOTOVOS�</button> &nbsp';
                        echo '<button type="button" onclick="document.getElementById('."'hotovost'".').focus(); location.hash = '."'#PlatbaKartou'".'; platbaKartou();" class="button_platba">PLATBA <br />KARTOU</button>';
                        echo '</td>';
                        echo '<td>';                                                                                                        
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
                        echo '<button type="button" name="karta_button" id="karta_button" onclick="platbaKartou(); zmenaHotovosti(); document.getElementById('."'hotovost'".').focus();" class="button_karta">UPRAVI� PLATBU KARTOU</button>';
                        
                        echo '</td>';
                        echo '</tr>';         
           
           
                        $hotovost = $suma + $zaokruhlenie;
                        echo '<tr id="HotovostTR">';
                        echo '<td>HOTOVOS�:</td>';
                        echo '<td>';
                        echo '<input type="text" name="hotovost" value="'.$hotovost.'" style="font-size: 25pt" size="8" tabindex=1 id = "hotovost" onfocus="this.select();" oninput= "zmenaHotovosti();">';
                        echo '</td>';
                        echo '<td>';
         // =====> doplni� funkcie    
         //              echo '<button type="button" onclick="alert(455555555);" class="button_blocek">VYTLA�I� BLO�EK</button>';
                        echo '</td>';
                        echo '</tr>';      
          
                        echo '<tr id="ZaokruhlenieTR">';
                        echo '<td>ZAOKR�HLENIE:</td>';
                        echo '<td>';
                        echo '<input type="text" name="zaokruhlenie" value="'.$zaokruhlenie.'" style="font-size: 25pt" size="8" tabindex=1 id = "zaokruhlenie" readonly>';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';              
                     
                        echo '<tr id="VydavokTR">';
                        echo '<td>V�davok:</td>';
                        echo '<td>';
                        echo '<input type="text" name="vydavok" value="NIE" readonly id="vydavok" style="font-size: 25pt" size="8">';
                        echo '</td>';
                        echo '<td>';
                   //     echo '<button type="button" onclick="location.hash = '."'#prvy_riadok'".';" class="button_zrusit">NA<br />ZA�IATOK</button>';
                        echo '</td>';
                        echo '</tr>';       
           
           
           /*  dorobi� mo�nos� posiela� blo�ek na email                                   
                        $email = $order->customer['email_address'];
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                echo '<tr id="email_tr">';
                                echo '<td>Email:</td>';
                                echo '<td>';
                                echo 'V objedn�vke je zadan� email, <b>sp�taj sa z�kazn�ka, �i chce blo�ek vytla�i� alebo posla� na email?</b><br />Email je potrebn� skontrolova�. Blo�ek nie je mo�n� zasla� opakovane, ani ho nesk�r vytla�i�.<br /><br />';
                                echo '<input type="text" name="email" value="'.$email.'" style="font-size: 12pt" size="30"  id = "email">';
                                echo '</td>';
                                echo '<td>';
                            //    echo '<button type="button" onclick="generujBlocek('.$oID.');" class="button_blocek">GENERUJ BLO�EK</button>';
                                echo '</td>';
                                echo '</tr>';                              
                        }          
            */


                        echo '<tr>';
                        echo '<td>';                                                                                   
                        echo ' ';
                        echo '</td>';
                        echo '<td>';
                        echo '<input type="submit" class="button_blocek" value="Vytla� doklad"> ';
                        echo '</td>';
                        echo '<td>';                        
                        echo '<button type="button" class="button_karta" onclick="onclick="OtvorZasuvku();"></button>';    

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
                        echo '<input type="hidden" name="medzisucet" id="medzisucet" value="'.$suma.'">';
                        echo '<input type="hidden" name="hotovost_ma_dat" value="'.$suma.'" id="hotovost_ma_dat">';     
                        echo '<input type="hidden" name="akcia" value="FAKTURA_ZAPIS">';
                        echo '<input type="hidden" name="dph" value="'.$dph.'">';
                        echo '<input type="hidden" name="nakup" value="'.$nakup.'">';
                        echo '<input type="hidden" name="zlava_suma" id="zlava_suma" value="0.00">';


                         
                break;  


                case 'PredajCasopis':
                        
                        $description = $_GET["description"];
                        $pocet = $_GET["pocet"];
                        $cena = $_GET["cena"];
                        $name = $_GET["name"];
                        $medzisucet = $cena * $pocet;
                        
                        echo '<table>';
                        include ('portos/ekasa_portos_stav.php');
                        echo '<tr class="'.$class.'">'; 
                        echo '<td colspan="3">eKASA - Portos '.$br.'['.$systemovy_stav.']'.$hlasenie.'</td>';
                        echo '</tr>';                           
                        echo '<tr id="prvy_riadok">';
                        echo '<td>Klient:</td>';
                        echo '<td>';
                        echo '<input type="text" name="klient" id="klient" value=""  readonly disabled style="font-size: 12pt" size="30">';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';      
                        echo '</tr>';
                                                 
                        echo '<tr>';
                        echo '<td>Suma n�kupu:</td>';
                        echo '<td>';
                        echo '<input type="text" name="suma" id="suma" value="'.$medzisucet.'"  readonly disabled style="font-size: 20pt" size="10">';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" onclick="OtvorZasuvku();" class="button_karta">OTVOR Z�SUVKU</button>';
                        echo '</td>';      
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td>Z�ava:</td>';
                        echo '<td>';
                        echo '<input type="text" name="zlava_p" id="zlava_p" value="0%" readonly style="font-size: 20pt" size="2"> ' ;
                        echo '&nbsp <input type="text" name="zlava_suma" id="zlava_suma" value="0.00" readonly style="font-size: 20pt" size="3">';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" onclick="dajZlavu();" class="button_karta">ZADAJ Z�AVU</button>';
                        echo '</td>';
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td>';
                        echo '';
                        echo '</td>';
                        echo '<td>';
                        echo '<br />Pre pokra�ovanie sa sp�taj klienta na sp�sob platby a klikni ni��ie:<br /><br />';
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
                         echo '<input type="hidden" name="email" value="" id="email">'; //nem� funkciu, vol� ho v�ak javascript
                        echo '<button type="button" onclick="location.hash = '."'#HotovostTR'".'; document.getElementById('."'hotovost'".').focus();" class="button_platba">IBA <br />HOTOVOS�</button> &nbsp';
                        echo '<button type="button" onclick="document.getElementById('."'hotovost'".').focus(); location.hash = '."'#PlatbaKartou'".'; platbaKartou();" class="button_platba">PLATBA <br />KARTOU</button>';
                        echo '</td>';
                        echo '<td>';                                                                                                        
                        echo '</td>';
                        echo '</tr>';
                        
                        echo '<tr class="oddelovac">';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';  
                        
                        echo '<tr id="PlatbaKartou">';
                        echo '<td>Platba kartou:</td>';
                        echo '<td>';
                        echo '<input type="text" name="karta" value="0"  id="karta" style="font-size: 25pt" size="8">';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" name="karta_button" id="karta_button" onclick="platbaKartou(); zmenaHotovosti(); document.getElementById('."'hotovost'".').focus();" class="button_karta">UPRAVI� PLATBU KARTOU</button>';
                        
                        echo '</td>';
                        echo '</tr>';         
                     
                        echo '<tr id="HotovostTR">';
                        echo '<td>HOTOVOS�:</td>';
                        echo '<td>';
                        echo '<input type="text" name="hotovost" value="'.$medzisucet.'" style="font-size: 25pt" size="8" tabindex=1 id = "hotovost" onfocus="this.select();" oninput= "zmenaHotovosti();">';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';      
                     
                        echo '<tr id="VydavokTR">';
                        echo '<td>V�davok:</td>';
                        echo '<td>';
                        echo '<input type="text" name="vydavok" value="NIE" readonly id="vydavok" style="font-size: 25pt" size="8">';
                        echo '</td>';
                        echo '<td>';
                        echo '</td>';
                        echo '</tr>';       
           
                        echo '<tr>';
                        echo '<td>';                                                                                   
                        echo '';
                        echo '</td>';
                        echo '<td><br />';
                        echo '<button type="button" onclick="generujBlocek();" class="button_blocek">VYTLA� DOKLAD</button> &nbsp';
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
                        
                        //echo '<input type="hidden" name="oID" value="'.$oID.'">';
                        echo '<input type="hidden" name="medzisucet" id="medzisucet" value="'.$medzisucet.'">';
                        echo '<input type="hidden" name="dph" value="10">';
                        echo '<input type="hidden" name="casopis" value="true">';
                        echo '<input type="hidden" name="nakup" value="'.$nakup.'">';
                        echo '<input type="hidden" name="akcia" value="" id="akcia">';
                        echo '<input type="hidden" name="hotovost_ma_dat" value="'.$medzisucet.'" id="hotovost_ma_dat">';                        
                        
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
                        if ($order->customer['zlava']>0) {echo '<b><font color="red">Klient m� nastaven� z�avu '.$order->customer['zlava'].'%</font></b>';}
                        echo '</td>';
                        echo '<td>';
                        if ( $cID > 0)   {
                                    echo '<button type="button"  onclick="window.open('."'".FILENAME_ORDERS.'?cID='.$cID."'".', '."'".'_blank'."'".' );" class="button_karta">HIST�RIA KLIENTA</button>';
                                }
                        echo '</td>';      
                        echo '</tr>';
                                                 
                        echo '<tr>';
                        echo '<td>Suma n�kupu:</td>';
                        echo '<td>';
                        echo '<input type="text" name="suma" id="suma" value="'.$medzisucet.'"  readonly disabled style="font-size: 20pt" size="10">';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" onclick="OtvorZasuvku();" class="button_karta">OTVOR Z�SUVKU</button>';
                        echo '</td>';      
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td>Z�ava:</td>';
                        echo '<td>';
                        echo '<input type="text" name="zlava_p" id="zlava_p" value="0%" readonly style="font-size: 20pt" size="2"> ' ;
                        echo '&nbsp <input type="text" name="zlava_suma" id="zlava_suma" value="0.00" readonly style="font-size: 20pt" size="3">';
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" onclick="dajZlavu();" class="button_karta">ZADAJ Z�AVU</button>';
                        echo '</td>';
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td>';
                        echo '';
                        echo '</td>';
                        echo '<td>';
                        echo '<br />Pre pokra�ovanie sa sp�taj klienta na sp�sob platby a klikni ni��ie:<br /><br />';
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
                        echo '<button type="button" onclick="location.hash = '."'#HotovostTR'".'; document.getElementById('."'hotovost'".').focus();" class="button_platba">IBA <br />HOTOVOS�</button> &nbsp';
                        echo '<button type="button" onclick="document.getElementById('."'hotovost'".').focus(); location.hash = '."'#PlatbaKartou'".'; platbaKartou();" class="button_platba">PLATBA <br />KARTOU</button>';
                        echo '</td>';
                        echo '<td>';                                                                                                        
            //          echo '<button type="button" onclick="window.close();" class="button_zrusit">ZAVRIE� OKNO</button>';
            //          echo '<button type="button" onclick='.'"javascript:var win = window.open'."('', '_self')".';win.close();return false;"'.' class="button_zrusit">ZAVRIE� OKNO</button>';
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
                        echo '<button type="button" name="karta_button" id="karta_button" onclick="platbaKartou(); zmenaHotovosti(); document.getElementById('."'hotovost'".').focus();" class="button_karta">UPRAVI� PLATBU KARTOU</button>';
                        
                        echo '</td>';
                        echo '</tr>';         
           

                        
                                   
                     
                        echo '<tr id="HotovostTR">';
                        echo '<td>HOTOVOS�:</td>';
                        echo '<td>';
                        $hotovost = $medzisucet + $zaokruhlenie;
                        echo '<input type="text" name="hotovost" value="'.$hotovost.'" style="font-size: 25pt" size="8" tabindex=1 id = "hotovost" onfocus="this.select();" oninput= "zmenaHotovosti();">';
                        echo '</td>';
                        echo '<td>';
         // =====> doplni� funkcie    
         //              echo '<button type="button" onclick="alert(455555555);" class="button_blocek">VYTLA�I� BLO�EK</button>';
                        echo '</td>';
                        echo '</tr>';      
          
                        echo '<tr id="ZaokruhlenieTR">';
                        echo '<td>ZAOKR�HLENIE:</td>';
                        echo '<td>';
                        echo '<input type="text" name="zaokruhlenie" value="'.$zaokruhlenie.'" style="font-size: 25pt" size="8" tabindex=1 id = "zaokruhlenie" readonly>';
                        echo '</td>';
                        echo '<td>';
         // =====> doplni� funkcie    
         //              echo '<button type="button" onclick="alert(455555555);" class="button_blocek">VYTLA�I� BLO�EK</button>';
                        echo '</td>';
                        echo '</tr>';             
                     
                        echo '<tr id="VydavokTR">';
                        echo '<td>V�davok:</td>';
                        echo '<td>';
                        echo '<input type="text" name="vydavok" value="NIE" readonly id="vydavok" style="font-size: 25pt" size="8">';
                        echo '</td>';
                        echo '<td>';
                   //     echo '<button type="button" onclick="location.hash = '."'#prvy_riadok'".';" class="button_zrusit">NA<br />ZA�IATOK</button>';
                        echo '</td>';
                        echo '</tr>';       
           
           
           //  dorobi� mo�nos� posiela� blo�ek na email
                  //    dovol� iba mne!
                  //if (CASH_REGISTER_CODE =='88812345678900001') {
                        $email = $order->customer['email_address'];
                        $email_button = false;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $email_button = true;
                                echo '<tr id="email_tr">';
                                echo '<td>Email:</td>';
                                echo '<td>';
                                echo '<br />V objedn�vke je zadan� email, <b>Email je potrebn� skontrolova�!</b><br /><br />';
                                echo '<input type="text" name="email_input" value="'.$email.'" style="font-size: 12pt" size="30"  id = "email_input">';
                                echo '<input type="hidden" name="email" value="" id="email">';
                                echo '</td>';
                                echo '<td>';
                                echo '<button type="button" onclick="generujBlocek(true);" class="button_blocek">DOKLAD NA EMAIL<br />(BEZ TLA�E)</button>';
                                echo '</td>';
                                echo '</tr>';                              
                        } else {
                                echo '<tr>';
                                echo '<td>';
                                echo '<input type="hidden" name="email" value="" id="email">';
                                echo '</td>';
                                echo '<td><br />';

                        }
                   //  }
                                
               // */
                        echo '<tr>';
                        echo '<td>';                                                                                   
                        echo '';
                        echo '</td>';
                        echo '<td><br />';
                        echo '<button type="button" onclick="generujBlocek(false);" class="button_blocek">VYTLA� DOKLAD</button> &nbsp';
                    /*
                        if ($email_button) {
                        echo '<button type="button" onclick="generujBlocek(true);" class="button_blocek">DOKLAD NA EMAIL</button> &nbsp';
                        }
                    */
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
