<?php
//  ****************************************************************   
//  ******* pr�prava polo�iek pre doklad ***************************   
//  ****************************************************************       
//  ****** verzia 1.00 01.02.2020 **********************************
//  ****************************************************************
//  �lohy:
//  - je treba dopracova� funkcionalitu na vracanie tovaru
//  - zlavu riesit zapisom medzi polozky objednavky a nie na konci dokladu

       $medzisucet = 0;
       $dph =0;
       $nakup =0;                                                                                                               
       $profit=0;
       $blocek_polozky = "";
       $ekasa_zlava =0;
       
       $items = array (); 
       
       for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
                                if (!($order->products[$i]['qty']==0)) {
        
                                        $quantity   = $order->products[$i]['qty'];
                                        // mno�stvo - quantity: object with required amount property with positive numeric value and precision 
                                        // up to 4 decimal places and optional unit field. If unit field will not be specified, 
                                        // default value x will be used. The unit field must not be empty string (""),
                                        $mnozstevna_jednotka = "x";
                                        
                                        // jednotkov� cena, 6 desatinn�ch miest
                                        $unitprice  =  $order->products[$i]['final_price'] * (($order->products[$i]['tax']+100)/100); 
                                        $unitprice  =  round($unitprice,6);
                                        
                                        // ak ide o vr�tenie polo�ky treba ma� tento parameter vyplnen�
                                        $referenceReceiptId = $order->products[$i]['referenceReceiptId'];
                                      /*
                                        if ( $referenceReceiptId <> '') {
                                                $unitprice = 0 - $unitprice;
                                                $quantity = abs ($quantity);
                                        }
                                        */
                                        // celkov� cena produktu, 2 desatinn� miesta = mus� sa rovna� n�sobku UnitPrice a Quantity
                                        $price      =  $unitprice * $order->products[$i]['qty'];
                                        $price      =  round($price,2);
                                        $medzisucet += $price; 
                                        
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
                                        
                                        // name: do 255 znakov, sem budeme d�va� katal�gov� ��slo
                                        $products_model = str_replace (' [KC]','',$order->products[$i]['model']);
                                        $products_model = str_replace ('[KC]','',$products_model);
                                        $name           = '> ' . ocisti($products_model);
                                        
                                        // description - nelimitovan� popis polo�ky
                                        $nazov = str_replace('"','',$order->products[$i]['name']);          
                                        $nazov = str_replace("'",'',$nazov); 
                                        $description= '  ' . ocisti($nazov);
                                        
                                        // sadzba dph - percentual VAT rate. In current version, the only allowed values are 20, 10 and 0.
                                        // od 1.1.2025 zmena sadzieb DPH
                                        // korekt�vny k�d pre star�ie objedn�vky s DPH sadzbou 20% a 10%
                                        
                                      
                                    
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
                                        if ($type == "Returned") {
                                                $items [] =   array (   'type'        =>    $type,
                                                                        'name'        =>    $name,
                                                                        'description' =>    $description,
                                                                        'price'       =>    -abs($price),
                                                                        'referenceReceiptId' => $referenceReceiptId,
                                                                        'unitPrice'   =>    -$unitprice,
                                                                        'quantity'    =>    array ("amount" => abs($quantity), "unit" => $mnozstevna_jednotka),
                                                                        'vatRate'     =>    $sadzba_DPH,
                                                            );}

                                        else if ($type == "Correction") {
                                                $items [] =   array (   'type'        =>    $type,
                                                                        'name'        =>    $name,
                                                                        'description' =>    $description,
                                                                        'price'       =>    $price,
                                                                        'referenceReceiptId' => $referenceReceiptId,
                                                                        'unitPrice'   =>    $unitprice,
                                                                        'quantity'    =>    array ("amount" => abs($quantity), "unit" => $mnozstevna_jednotka),
                                                                        'vatRate'     =>    $sadzba_DPH,
                                                            );}
                                        else {
                                                $items [] =   array (   'type'        =>    $type,
                                                                        'name'        =>    $name,
                                                                        'description' =>    $description,
                                                                        'price'       =>    $price,
                                                                        'unitPrice'   =>    $unitprice,
                                                                        'quantity'    =>    array ("amount" => abs($quantity), "unit" => $mnozstevna_jednotka),
                                                                        'vatRate'     =>    $sadzba_DPH);                                        
                                        }

                      	     }
        }
             
             
                  
                  $zlava = isset($_POST["zlava_suma"]) ? $_POST["zlava_suma"] : 0;
                  $zlava_pritomna = false;
                   if ($zlava > 0) {
                            $polozka_z = "Z�ava " .$_POST["zlava_p"];
                            $zlava_cena = (0 - $zlava)/1.23; 
                            //tep_db_query("insert into " . TABLE_ORDERS_PRODUCTS . " (orders_id, products_model, products_name, products_price, final_price, products_tax, products_quantity) values ('" . (int)$oID . "', 'ZLAVA', '" . tep_db_input($polozka_z) . "', " . tep_db_input($zlava_cena)  . ", " . tep_db_input($zlava_cena). ", 20, 1)");
                            $zlava_m = 0 - abs($zlava);
                                $items [] =   array (   'type'        =>    'Discount',
                                                        'name'        =>    '> ZLAVA '.$_POST["zlava_p"].'',
                                                     // 'description' =>    $description,
                                                        'price'       =>    $zlava_m,
                                                        'unitPrice'   =>    $zlava_m,
                                                        'quantity'    =>    array ("amount" => 1, "unit" => 'x'),
                                                        'vatRate'     =>    23,
                                                    ); 
                            $medzisucet  -= $zlava; 
                            $zlava_pritomna = true;
                   }        


                   if (isset($_POST["casopis"]) && $_POST["casopis"] == true) {
                            $description = $_GET["description"];
                            $pocet = $_GET["pocet"];
                            $cena = $_GET["cena"];
                            $name = $_GET["name"];
                            $medzisucet = $cena * $pocet;    
                                           
                                $items [] =   array (   'type'        =>    'Positive',
                                                        'name'        =>    $name,
                                                    //    'description' =>    $description,
                                                        'price'       =>    $medzisucet,
                                                        'unitPrice'   =>    $cena,
                                                        'quantity'    =>    array ("amount" => $pocet, "unit" => 'x'),
                                                        'vatRate'     =>    10,
                                                    );
                           $poznamkaInterna = $name; 
                   }  
          
          
                                              // test zaokr�h�ovanie
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

