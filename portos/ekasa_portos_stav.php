<?php
    // VOLANIE API 
    // stav tlaèiarne
      $function_url = 'printers/status';
      $data_array = array();
      $response_json = callAPI('GET', $function_url, $data_array);
      $response  = json_decode($response_json, true);
      $stav_tlaciaren   = $response['state'];
    // VOLANIE API
    // stav spojenia s ekasa 
      $function_url = 'connectivity/status';
      $data_array = array();
      $response_json = callAPI('GET', $function_url, $data_array);
      $response  = json_decode($response_json, true);
      $stav_spojenia   = $response['state'];      
   // spracovanie výstupov do hlásení stavu   
                        $systemovy_stav ="";
                        if ($stav_spojenia=="Down") {
                                $class ="nadpis_chyba"; 
                                $systemovy_stav = 'CHYBA SPOJENIA s ekasa serverom';
                                $hlasenie .="<br />Skontroluj èi má poèítaè spojenie s internetom, kontaktuj administrátora!";
                                $hlasenie .="<br />Stav spojenia: ".$stav_spojenia;  
                                $hlasenie .="<br />Stav tlaèiarne: ".$stav_tlaciaren;                                  
                        }
                        else if ($stav_spojenia=="Unknown") {
                                $class ="nadpis_chyba"; 
                                $systemovy_stav = 'NEZNÁMY STAV SPOJENIA s ekasa serverom';
                                $hlasenie .="<br />Skontroluj èi má poèítaè spojenie s internetom, kontaktuj administrátora!";
                                $hlasenie .="<br />Stav spojenia: ".$stav_spojenia; 
                                $hlasenie .="<br />Stav tlaèiarne: ".$stav_tlaciaren;                        
                        }
                        else if ($stav_tlaciaren == 'Ready') {
                                $class ="nadpis_ok"; 
                                $systemovy_stav .= 'tlaèiareò online';
                        }
                        else {
                                $class ="nadpis_chyba"; 
                                $systemovy_stav = 'tlaèiareò OFFLINE';
                                $hlasenie .="<br />Skontroluj èi je tlaèiareò pripojená a zapnutá!";
                                $hlasenie .="<br />Stav spojenia: ".$stav_spojenia;
                                $hlasenie .="<br />Stav tlaèiarne: ".$stav_tlaciaren;                                
                        }      
      
?>