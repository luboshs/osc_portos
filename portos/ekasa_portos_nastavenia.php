<?php
//  NASTAVENIA e-kasa portos
//  verzia 1.1.2025
// 
        function portos_get_client_public_ip_candidate() {
            $candidate_headers = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
            foreach ($candidate_headers as $header_name) {
                if (!isset($_SERVER[$header_name]) || $_SERVER[$header_name] == '') {
                    continue;
                }

                $header_value = $_SERVER[$header_name];
                $ip_list = explode(',', $header_value);
                foreach ($ip_list as $ip_item) {
                    $ip = trim($ip_item);
                    if ($ip == '') {
                        continue;
                    }

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
            return '';
        }

        define('PORT', '88');
        define('TIMEOUT', '4000');
        define('CONNECT_TIMEOUT', '3');
        define('APP_NAME', 'E-KASA pre PORTOS');
        define('APP_VERSION', '1.2');

        $ip_lubos_doma = '89.173.21.148';
        
       //  ip - zoberie z cookies alebo defaulte iné
        if(isset($_COOKIE['ip_address'])) {
            $ip_address = $_COOKIE['ip_address'];
            // Uložená IP adresa z cookies
            define('IP', $ip_address);
            define('IP_SOURCE', 'cookie');
        } else {
            // IP adresa nie je v cookies uložená.
            // ************************************************
            define('IP', '91.127.65.37');
            define('IP_SOURCE', 'default');
        }       
        define('IP_FALLBACK', portos_get_client_public_ip_candidate());
                
        // identifikuj ekasu         
        if (IP==$ip_lubos_doma) {
            // toto je tlačiareň doma - testovacie            
             define('CASH_REGISTER_CODE', '88812345678900001');
        } else {
           // toto je tlačiareň na prevádzke
             define('CASH_REGISTER_CODE', '88820229533830001');        
        }
        
        define('OPEN_DRAWER', true);  
        define('LOGO_PRINT', true);                                                   
        define('LOGO_MEMORY_ADDRESS', '1');
        define('EMAIL_PREDMET', 'Vas e-doklad k nakupu');
        define('EMAIL_TEXT', 'Dakujeme za Vas nakup, Vas doklad je v prilohe tohto emailu.');
        define('EKASA_PAYME_BASE_URL', 'https://payme.sk/2/e/PME');
        define('EKASA_PAYME_IBAN', 'SK1183300000002700576798');
        define('EKASA_PAYME_CREDITOR_NAME', 'ATaC s.r.o.');
        define('EKASA_PAYME_VS', '9059059050');   // variabilný symbol - fixný
        define('EKASA_PAYME_KS', '0008');          // konštantný symbol - fixný
        // FIO Banka API - token pre overovanie platieb (nastaviť na produkcii)
        define('EKASA_FIO_API_TOKEN', '');         // token z FIO internet bankingu
        define('EKASA_FIO_POLL_INTERVAL', 30);     // interval pollovania v sekundách (FIO API rate limit je 30s)
        define('EKASA_FIO_TIMEOUT', 120);          // max čas čakania na platbu v sekundách
        define('EKASA_QR_WINDOW_DELAY', 8);        // sekundy pred štartom FIO kontroly po otvorení QR okna

        // URL pre pokladničný doklad je 'requests/receipts/cash_register'
        define('HEADER_TEXT', 'PREDAJNA MODELOVA ZELEZNICA TRNAVA'."\n");
        define('FOOTER_TEXT', 'www.modelovazeleznica.sk'."\n".'Dakujeme za Vas nakup!'."\n".'Nase ID-dokladu: ');

?>                  
