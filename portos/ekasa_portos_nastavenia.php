<?php
//  NASTAVENIA e-kasa portos
//  verzia 1.1.2025
// 
        define('PORT', '88');
        define('TIMEOUT', '4000');
        define('APP_NAME', 'E-KASA pre PORTOS');
        define('APP_VERSION', '1.2');

        $ip_lubos_doma = '89.173.21.148';
        
       //  ip - zoberie z cookies alebo defaulte inú
        if(isset($_COOKIE['ip_address'])) {
            $ip_address = $_COOKIE['ip_address'];
            // Uložená IP adresa z cookies
            define('IP', $ip_address);
        } else {
            // IP adresa nie je v cookies uložená.
            // ************************************************
            define('IP', '91.127.65.37');
        }       
               
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
