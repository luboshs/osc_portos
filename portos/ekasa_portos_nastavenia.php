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
            // toto je tlaèiareò doma - testovacie            
             define('CASH_REGISTER_CODE', '88812345678900001');
        } else {
           // toto je tlaèiareò na prevádzke
             define('CASH_REGISTER_CODE', '88820229533830001');        
        }
        
        define('OPEN_DRAWER', true);  
        define('LOGO_PRINT', true);                                                   
        define('LOGO_MEMORY_ADDRESS', '1');
        define('EMAIL_PREDMET', 'Vas e-doklad k nakupu');
        define('EMAIL_TEXT', 'Dakujeme za Vas nakup, Vas doklad je v prilohe tohto emailu.');

        // URL pre pokladnièný doklad je 'requests/receipts/cash_register'
        define('HEADER_TEXT', 'PREDAJNA MODELOVA ZELEZNICA TRNAVA'."\n");
        define('FOOTER_TEXT', 'www.modelovazeleznica.sk'."\n".'Dakujeme za Vas nakup!'."\n".'Nase ID-dokladu: ');

?>                  
