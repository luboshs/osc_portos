<?php
    function callAPI($method, $function_url, $data){
             $curl = curl_init();
             switch ($method){
                case "POST":
                   curl_setopt($curl, CURLOPT_POST, 1);
                   if ($data)
                      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                   break;
             /*      
                case "PUT":
                   curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                   if ($data)
                      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
                   break;
             */      
                default:
                   if ($data)
                      $url = sprintf("%s?%s", $url, http_build_query($data));
             }
             // OPTIONS:
             curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json; charset=utf-8',
                'Accept:application/json'
             ));
             $timeout = defined('TIMEOUT') ? (int)TIMEOUT : 10;
             if ($timeout <= 0) {
                $timeout = 10;
             }
             $connect_timeout = defined('CONNECT_TIMEOUT') ? (int)CONNECT_TIMEOUT : 3;
             if ($connect_timeout <= 0) {
                $connect_timeout = 3;
             }
             if ($timeout < $connect_timeout) {
                $timeout = $connect_timeout + 2;
             }
             curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
             curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
             curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
             curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            $ip_candidates = array(IP);
            if (defined('IP_FALLBACK') && IP_FALLBACK != '' && IP_FALLBACK != IP) {
               $ip_candidates[] = IP_FALLBACK;
            }

            $last_error = array(
               'code' => 'CURL_ERROR',
               'message' => 'Neznama chyba pripojenia.',
               'errno' => 0,
               'url' => ''
            );

            foreach ($ip_candidates as $try_ip) {
               $url_portos = 'http://'.$try_ip.':'.PORT.'/api/v1/'.$function_url;
               curl_setopt($curl, CURLOPT_URL, $url_portos);
               // EXECUTE:
               $result = curl_exec($curl);
               if ($result !== false) {
                   if ($try_ip != IP) {
                       $GLOBALS['portos_ip_warning'] = 'Konfigurovana IP adresa pokladne ('.IP.') pravdepodobne nie je spravna alebo je nedostupna. Pouzita bola nahradna IP z klienta: '.$try_ip.'.';
                   }
                   curl_close($curl);
                   return $result;
               }

               $last_error = array(
                   'code' => 'CURL_ERROR',
                   'message' => curl_error($curl),
                   'errno' => curl_errno($curl),
                   'url' => $url_portos
               );
            }

            if (count($ip_candidates) > 1) {
               $GLOBALS['portos_ip_warning'] = 'Konfigurovana IP adresa pokladne ('.IP.') je pravdepodobne nespravne nastavena alebo nedostupna. Kontrolujte cookie ip_address.';
            }

            curl_close($curl);
            return json_encode(array(
                'isSuccessful' => false,
                'state' => 'Unknown',
                'error' => $last_error
            ));
        //    if(!$result){die("Connection Failure");}
      //     $result_decoded = json_decode($result, true);
      //     return $result_decoded;
    }
    
        function ocisti($string){
            $converted = iconv('Windows-1250', 'UTF-8//TRANSLIT', $string);
            if ($converted === false) {
                $converted = $string;
            }
            return mb_substr($converted, 0, 42, 'UTF-8');
    }
?>