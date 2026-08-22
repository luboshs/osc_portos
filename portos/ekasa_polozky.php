<?php
//  ****************************************************************   
//  ******* príprava položiek pre doklad ***************************   
//  ****************************************************************       
//  ****** verzia 1.00 01.02.2020 **********************************
//  ****************************************************************
//  úlohy:
//  - je treba dopracovať funkcionalitu na vracanie tovaru
//  - REFAKTORIZOVANÉ: zľava sa teraz aplikuje na každú položku jednotlivo (nie na konci dokladu)

       $medzisucet = 0;
       $dph =0;
       $nakup =0;                                                                                                               
       $profit=0;
       $blocek_polozky = "";
       $ekasa_zlava =0;
       $zlava_pritomna = false;
       $celkova_usporena = 0;
        
       // Extrahovanie percentuálnej zľavy z POST
       // povolený rozsah je 1 až 15 %, iné hodnoty sa ignorujú
       $zlava_percent = 0;
       if (isset($_POST["zlava_p"])) {
           $zlava_percent = ekasa_cislo(str_replace('%', '', trim($_POST["zlava_p"])));
       }
       if ($zlava_percent < 1 OR $zlava_percent > 15) { $zlava_percent = 0; }
        
       // položky, na ktoré je možné dať zľavu (bez [KC]) - základ pre výpočet zľavy v GUI
       $zlava_polozky = array ();
       $zlava_zaklad = 0;
        
       $items = array ();
       
       for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
                                if (!($order->products[$i]['qty']==0)) {
        
                                        $quantity   = $order->products[$i]['qty'];
                                        // množstvo - quantity: object with required amount property with positive numeric value and precision 
                                        // up to 4 decimal places and optional unit field. If unit field will not be specified, 
                                        // default value x will be used. The unit field must not be empty string (""),
                                        $mnozstevna_jednotka = "x";
                                        
                                        // jednotková cena, 6 desatinných miest
                                        $unitprice  =  $order->products[$i]['final_price'] * (($order->products[$i]['tax']+100)/100); 
                                        $unitprice  =  round($unitprice,6);
                                        
                                        // ak ide o vrátenie položky treba mať tento parameter vyplnený
                                        $referenceReceiptId = $order->products[$i]['referenceReceiptId'];
                                      /*
                                        if ( $referenceReceiptId <> '') {
                                                $unitprice = 0 - $unitprice;
                                                $quantity = abs ($quantity);
                                        }
                                        */
                                         // celková cena produktu, 2 desatinné miesta = musí sa rovnať násobku UnitPrice a Quantity
                                        $price      =  $unitprice * $order->products[$i]['qty'];
                                        $price      =  round($price,2);
                                        
                                        // type = "Positive" "ReturnedContainer" "Returned" "Correction" "Discount" "Advance" "Voucher"
                                        if ($unitprice >= 0) {
                                                    if ($quantity > 0) { $type = 'Positive'; } 
                                                    else if ($referenceReceiptId <> '') { $type = 'Returned';} 
                                                    else {  $type = 'Discount'; 
                                                            $unitprice  = 0 - abs($unitprice);
                                                            }      
                                        }  else {
                                                    $type = 'Discount';
                                                    $unitprice  = 0 - abs($unitprice);
                                        }
                                        
                                        if ($order->products[$i]['ekasa']) {
                                        
                                        }
                                        
                                        // name: do 255 znakov, sem budeme dávať katalógové číslo
                                        $products_model = str_replace (' [KC]','',$order->products[$i]['model']);
                                        $products_model = str_replace ('[KC]','',$products_model);
                                        $model_ocisteny = ocisti($products_model);
                                        // kazda nova polozka zacina znackou "> "
                                        $name           = '> ' . $model_ocisteny;
                                         
                                        // Kontrola či položka obsahuje [KC] - ak áno, nebude sa na ňu aplikovať zľava
                                        $contains_kc = (stripos($order->products[$i]['model'], '[kc]') !== false) || (stripos($order->products[$i]['name'], '[kc]') !== false);
                                         
                                        // description - nelimitovaný popis položky
                                        $nazov = str_replace('"','',$order->products[$i]['name']);          
                                        $nazov = str_replace("'",'',$nazov); 
                                        $description= '  ' . ocisti($nazov);
                                        
                                        // sadzba dph - percentual VAT rate. In current version, the only allowed values are 20, 10 and 0.
                                        // od 1.1.2025 zmena sadzieb DPH
                                        // korektívny kód pre staršie objednávky s DPH sadzbou 20% a 10%
                                        
                                      
                                    
                                        switch ($order->products[$i]['tax']) {
                                        
                                            case 20:
                                                //if ($type ==! "Returned") {
                                                //$sadzba_DPH = 23; } else{
                                                //$sadzba_DPH = $order->products[$i]['tax'];
                                                //$sadzba_DPH  =  round($sadzba_DPH,0);
                                                //}
                                                $sadzba_DPH = 23;
                                                break;
                                                
                                            case 10:
                                                //if ($type ==! "Returned") {
                                                //$sadzba_DPH = 5; } else{
                                                //$sadzba_DPH = $order->products[$i]['tax'];
                                                //$sadzba_DPH  =  round($sadzba_DPH,0);
                                                //}
                                                $sadzba_DPH = 5;
                                                break;
                                                
                                            default:
                                                $sadzba_DPH = $order->products[$i]['tax'];
                                                $sadzba_DPH  =  round($sadzba_DPH,0);
                                                break;
                                        }
                                        
                                          
                                        
                            // SPRACOVANIE RIADKU
                                        // Zber položiek, na ktoré je možné dať zľavu (kladná položka bez [KC])
                                        if ($type == "Positive" && !$contains_kc) {
                                                $zlava_polozky [] = array ('unitPrice' => $unitprice, 'quantity' => abs($quantity));
                                                $zlava_zaklad += $price;
                                        }
                                        
                                        // Aplikovanie zľavy na položku (ak nie je KC a ide o kladnú cenu)
                                        if ($type == "Positive" && !$contains_kc && $zlava_percent > 0) {
                                                $discount_amount = $unitprice * ($zlava_percent / 100);
                                                  
                                                // Pridaj pôvodnú položku
                                                $items [] =   array (   'type'        =>    $type,
                                                                        'name'        =>    $name,
                                                                        'description' =>    $description,
                                                                        'price'       =>    $price,
                                                                        'unitPrice'   =>    $unitprice,
                                                                        'quantity'    =>    array ("amount" => abs($quantity), "unit" => $mnozstevna_jednotka),
                                                                        'vatRate'     =>    $sadzba_DPH);
                                                $medzisucet += $price;
                                                  
                                                // Pridaj diskontnú položku (negatívnu)
                                                $discount_price = round(-abs($discount_amount * $quantity), 2);
                                                $discount_unitprice = round(-abs($discount_amount), 6);
                                                  
                                                // nulové zľavy (0,00) sú zbytočné riadky - na doklad ich nedávame
                                                if (abs($discount_price) >= 0.01) {
                                                        // riadok so zľavou nezačína značkou "> ", ale textom "ZĽAVA"
                                                        $items [] =   array (   'type'        =>    'Discount',
                                                                                'name'        =>    'ZĽAVA ' . $zlava_percent . '% - ' . $model_ocisteny,
                                                                            //  'description' =>    $description . ' (zľava)',
                                                                                'price'       =>    $discount_price,
                                                                                'unitPrice'   =>    $discount_unitprice,
                                                                                'quantity'    =>    array ("amount" => abs($quantity), "unit" => $mnozstevna_jednotka),
                                                                                'vatRate'     =>    $sadzba_DPH);
                                                          
                                                        // Počítaj cenu zľavy do medzisúčtu
                                                        $medzisucet       += $discount_price;
                                                        $celkova_usporena += abs($discount_price);
                                                        $zlava_pritomna    = true;
                                                }
                                        }
                                        else if ($type == "Returned") {
                                                $items [] =   array (   'type'        =>    $type,
                                                                        'name'        =>    $name,
                                                                        'description' =>    $description,
                                                                        'price'       =>    -abs($price),
                                                                        'referenceReceiptId' => $referenceReceiptId,
                                                                        'unitPrice'   =>    -$unitprice,
                                                                        'quantity'    =>    array ("amount" => abs($quantity), "unit" => $mnozstevna_jednotka),
                                                                        'vatRate'     =>    $sadzba_DPH,
                                                            );
                                                $medzisucet += -abs($price);
                                        }

                                        else if ($type == "Correction") {
                                                $items [] =   array (   'type'        =>    $type,
                                                                        'name'        =>    $name,
                                                                        'description' =>    $description,
                                                                        'price'       =>    $price,
                                                                        'referenceReceiptId' => $referenceReceiptId,
                                                                        'unitPrice'   =>    $unitprice,
                                                                        'quantity'    =>    array ("amount" => abs($quantity), "unit" => $mnozstevna_jednotka),
                                                                        'vatRate'     =>    $sadzba_DPH,
                                                            );
                                                $medzisucet += $price;
                                        }
                                        else {
                                                // Ostatné typy (Positive bez zľavy, KC položky, Discount, Voucher, atď.)
                                                $items [] =   array (   'type'        =>    $type,
                                                                        'name'        =>    $name,
                                                                        'description' =>    $description,
                                                                        'price'       =>    $price,
                                                                        'unitPrice'   =>    $unitprice,
                                                                        'quantity'    =>    array ("amount" => abs($quantity), "unit" => $mnozstevna_jednotka),
                                                                        'vatRate'     =>    $sadzba_DPH);
                                                $medzisucet += $price;
                                        }

                      	     }
        }
       
       // Celková ušetrená suma sa sčítava priamo pri tvorbe zľavových riadkov
       $medzisucet = round($medzisucet, 2);
       $celkova_usporena = round($celkova_usporena, 2);
       $zlava_zaklad = round($zlava_zaklad, 2);
       // suma poskytnutej zľavy (používa sa pri zápise do objednávky a do histórie)
       $zlava_m = $celkova_usporena;
       
       // Informatívny text o ušetrenej sume sa netlačí medzi položky,
       // ale ako nefiškálny text v pätičke dokladu (ekasa_priprav_data.php)
       $info_text = '';
       if ($celkova_usporena > 0) {
          $info_text = sprintf("Celkovo ste ušetrili: %.2f EUR", $celkova_usporena);
       }


                   if (isset($_POST["casopis"]) && $_POST["casopis"] == true) {
                            $description = $_GET["description"];
                            $pocet = ekasa_cislo($_GET["pocet"]);
                            $cena = ekasa_cislo($_GET["cena"]);
                            $name = $_GET["name"];
                            $medzisucet = round($cena * $pocet, 2);    
                                           
                                $items [] =   array (   'type'        =>    'Positive',
                                                        'name'        =>    $name,
                                                    //    'description' =>    $description,
                                                        'price'       =>    $medzisucet,
                                                        'unitPrice'   =>    $cena,
                                                        'quantity'    =>    array ("amount" => $pocet, "unit" => 'x'),
                                                        'vatRate'     =>    10,
                                                    );
                                                    
                           // časopis je bežná položka - zľavu je možné dať na celú jeho cenu
                           $zlava_polozky [] = array ('unitPrice' => $cena, 'quantity' => $pocet);
                           $zlava_zaklad     = $medzisucet;
                           
                           if ($zlava_percent > 0) {
                                   $discount_amount = $cena * ($zlava_percent / 100);
                                   $discount_price  = round(-abs($discount_amount * $pocet), 2);
                                   
                                   // nulovú zľavu (0,00) na doklad neuvádzame
                                   if (abs($discount_price) >= 0.01) {
                                           $items [] =   array (   'type'        =>    'Discount',
                                                                   'name'        =>    '  ZĽAVA ' . $zlava_percent . '% - ' . $name,
                                                                   'price'       =>    $discount_price,
                                                                   'unitPrice'   =>    round(-abs($discount_amount), 6),
                                                                   'quantity'    =>    array ("amount" => $pocet, "unit" => 'x'),
                                                                   'vatRate'     =>    10,
                                                               );
                                           $medzisucet       = round($medzisucet + $discount_price, 2);
                                           $zlava_pritomna   = true;
                                           $celkova_usporena = abs($discount_price);
                                           $zlava_m          = $celkova_usporena;
                                           $info_text        = sprintf("Celkovo ste ušetrili: %.2f EUR", $celkova_usporena);
                                   }
                           }
                           $poznamkaInterna = $name; 
                   }  
          
          
                                              // test zaokrúhľovanie
                                    $cifra = substr(number_format($medzisucet, 2,'.',''),-1);
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
                                   // $zaokruhlenie = (int)$zaokruhlenie /100;
                                    
                                   // $medzisucet = $medzisucet + $zaokruhlenie;
                                    
?>

