<?php
//  ****************************************************************   
//  ******* príprava dát pre skript ********************************   
//  ****************************************************************       
//  ** premenná pre položky je už definovaná z e_kasa_polozky.php **
//  ****************************************************************
//  ****** verzia 1.00 31.01.2020 **********************************
//  ****************************************************************
//  úlohy:
//  - nie je dorobená funkcionalita bloèku na email (na okne)
   
        // deklarácia premenných z eshopu 
                    $zakaznik_meno  =   ocisti($order->customer['name']);
                    // email, ak bude prázdny tak sa doklad vytlaèí cez POS, ak bude email definovaný tak pošleme doklad na email
                    if (isset($_POST["email"]) AND (strlen($_POST["email"])>6)) {
                                //$email = $_POST["email"];
                                $print_array = array ('printerName'   =>'email',
                                                                                            'options'       => array (
                                                                                                                'To' => $_POST["email"],
                                                                                                                'Subject' => EMAIL_PREDMET,
                                                                                                                'Body' => EMAIL_TEXT
                                                                ));
                                $email_log = "\n\n".'Doklad zaslaný na email: '. $_POST["email"];
                                $doklad_na_email = true;
                    } else {
                               $print_array =  array ('printerName'=>'pos',
                                                      'options' => array ( 'OpenDrawer' => OPEN_DRAWER,
                                                                         'PrintLogo' => LOGO_PRINT,
                                                                         'LogoMemoryAddress' => LOGO_MEMORY_ADDRESS  ));
                               $email_log = '';
                               $doklad_na_email = false;
                            }
        // SPRACOVANIE            
                    $externalId     =   'obj'.$oID.'-eID:'.$eID;

        // telo pre doklad  
                    if ($doklad_na_email) {$nadpis= HEADER_TEXT;} else {$nadpis='';}

                    $headerText = '------------------------------------------'."\n".$nadpis;
                    if ($oID >0) {  $headerText .= 'C. obj. : '.$oID."\n";}
                    if (strlen($zakaznik_meno)>5 AND $zakaznik_meno<>"Predaj na kase")   {$headerText .= 'Zakaznik: '.$zakaznik_meno."\n";}
                    $footerText = FOOTER_TEXT.$externalId;
                    $hotovost_zaokruhlena = $hotovost + $roundingAmount;
                    $payments   = array(    array ('name' => "Hotovost", 'amount' => $hotovost_zaokruhlena),
                                            array ('name' => "Platba kartou", 'amount' => $platba_kartou));
                    

                    $data_array = array ( 'request'=> array (   'data' => array (  'items'     =>  $items,
                                                                                'payments'  =>  $payments,
                                                                                'roundingAmount' => $roundingAmount,
                                                                                'receiptType'=> 'CashRegister',
                                                                                'headerText' => $headerText,
                                                                                'footerText' => $footerText,
                                                                                'cashRegisterCode'=> CASH_REGISTER_CODE ),
                                                                'externalId' => $eID),
                                           'print' => $print_array
                                         );



                 //   print("<pre>".print_r(json_encode($data_array),true)."</pre>");
?>