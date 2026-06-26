<?php
    if (!isset($hlasenie)) {
        $hlasenie = '';
    }
    $br = '<br />';
    // VOLANIE API 
    // stav tlaèiarne
      $function_url = 'printers/status';
      $data_array = array();
      $response_json = callAPI('GET', $function_url, $data_array);
      $response  = json_decode($response_json, true);
      $stav_tlaciaren   = (is_array($response) && isset($response['state'])) ? $response['state'] : 'Unknown';
      $chyba_tlaciaren = (is_array($response) && isset($response['error']['message'])) ? iconv('UTF-8', 'Windows-1250//TRANSLIT', $response['error']['message']) : '';
    // VOLANIE API
    // stav spojenia s ekasa 
      $function_url = 'connectivity/status';
      $data_array = array();
      $response_json = callAPI('GET', $function_url, $data_array);
      $response  = json_decode($response_json, true);
      $stav_spojenia   = (is_array($response) && isset($response['state'])) ? $response['state'] : 'Unknown';
      $chyba_spojenie = (is_array($response) && isset($response['error']['message'])) ? iconv('UTF-8', 'Windows-1250//TRANSLIT', $response['error']['message']) : '';
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
                        if ($chyba_spojenie != '') {
                                $hlasenie .="<br />Detail spojenia: ".htmlspecialchars($chyba_spojenie, ENT_QUOTES, 'ISO-8859-1');
                        }
                        if ($chyba_tlaciaren != '') {
                                $hlasenie .="<br />Detail tlaèiarne: ".htmlspecialchars($chyba_tlaciaren, ENT_QUOTES, 'ISO-8859-1');
                        }
                        if (isset($GLOBALS['portos_ip_warning']) && $GLOBALS['portos_ip_warning'] != '') {
                                $hlasenie .="<br /><b>Upozornenie:</b> ".htmlspecialchars($GLOBALS['portos_ip_warning'], ENT_QUOTES, 'ISO-8859-1');
                        }
      
?>