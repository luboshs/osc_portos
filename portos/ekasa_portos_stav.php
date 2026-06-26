<?php
    if (!isset($hlasenie)) {
        $hlasenie = '';
    }
    // VOLANIE API 
    // stav tla�iarne
      $function_url = 'printers/status';
      $data_array = array();
      $response_json = callAPI('GET', $function_url, $data_array);
      $response  = json_decode($response_json, true);
      $stav_tlaciaren   = (is_array($response) && isset($response['state'])) ? $response['state'] : 'Unknown';
      $chyba_tlaciaren = (is_array($response) && isset($response['error']['message'])) ? $response['error']['message'] : '';
    // VOLANIE API
    // stav spojenia s ekasa 
      $function_url = 'connectivity/status';
      $data_array = array();
      $response_json = callAPI('GET', $function_url, $data_array);
      $response  = json_decode($response_json, true);
      $stav_spojenia   = (is_array($response) && isset($response['state'])) ? $response['state'] : 'Unknown';
      $chyba_spojenie = (is_array($response) && isset($response['error']['message'])) ? $response['error']['message'] : '';
   // spracovanie v�stupov do hl�sen� stavu   
                        $systemovy_stav ="";
                        if ($stav_spojenia=="Down") {
                                $class ="nadpis_chyba"; 
                                $systemovy_stav = 'CHYBA SPOJENIA s ekasa serverom';
                                $hlasenie .="<br />Skontroluj �i m� po��ta� spojenie s internetom, kontaktuj administr�tora!";
                                $hlasenie .="<br />Stav spojenia: ".$stav_spojenia;  
                                $hlasenie .="<br />Stav tla�iarne: ".$stav_tlaciaren;                                  
                        }
                        else if ($stav_spojenia=="Unknown") {
                                $class ="nadpis_chyba"; 
                                $systemovy_stav = 'NEZN�MY STAV SPOJENIA s ekasa serverom';
                                $hlasenie .="<br />Skontroluj �i m� po��ta� spojenie s internetom, kontaktuj administr�tora!";
                                $hlasenie .="<br />Stav spojenia: ".$stav_spojenia; 
                                $hlasenie .="<br />Stav tla�iarne: ".$stav_tlaciaren;                        
                        }
                        else if ($stav_tlaciaren == 'Ready') {
                                $class ="nadpis_ok"; 
                                $systemovy_stav .= 'tla�iare� online';
                        }
                        else {
                                $class ="nadpis_chyba"; 
                                $systemovy_stav = 'tla�iare� OFFLINE';
                                $hlasenie .="<br />Skontroluj �i je tla�iare� pripojen� a zapnut�!";
                                $hlasenie .="<br />Stav spojenia: ".$stav_spojenia;
                                $hlasenie .="<br />Stav tla�iarne: ".$stav_tlaciaren;                                
                        }      
                        if ($chyba_spojenie != '') {
                                $hlasenie .="<br />Detail spojenia: ".htmlspecialchars($chyba_spojenie, ENT_QUOTES, 'cp-1250');
                        }
                        if ($chyba_tlaciaren != '') {
                                $hlasenie .="<br />Detail tla�iarne: ".htmlspecialchars($chyba_tlaciaren, ENT_QUOTES, 'cp-1250');
                        }
                        if (isset($GLOBALS['portos_ip_warning']) && $GLOBALS['portos_ip_warning'] != '') {
                                $hlasenie .="<br /><b>Upozornenie:</b> ".htmlspecialchars($GLOBALS['portos_ip_warning'], ENT_QUOTES, 'cp-1250');
                        }
      
?>