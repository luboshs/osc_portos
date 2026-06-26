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
             $url_portos = 'http://'.IP.':'.PORT.'/api/v1/'.$function_url;
             //echo $url_portos;
             curl_setopt($curl, CURLOPT_URL, $url_portos);
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
             // EXECUTE:
             $result = curl_exec($curl);
             if ($result === false) {
                $curl_errno = curl_errno($curl);
                $curl_error = curl_error($curl);
                curl_close($curl);
                return json_encode(array(
                    'isSuccessful' => false,
                    'state' => 'Unknown',
                    'error' => array(
                        'code' => 'CURL_ERROR',
                        'message' => $curl_error,
                        'errno' => $curl_errno,
                        'url' => $url_portos
                    )
                ));
             }
         //    if(!$result){die("Connection Failure");}
             curl_close($curl);
      //     $result_decoded = json_decode($result, true);
      //     return $result_decoded;
             return $result;
    }
    
        function ocisti($string){      
            $ocistene_1 = strtr($string, '�����������������������������ͼ�����������ݎ�', 'aacdeeeilnooorrstuuuyzAACDEEEILNOOORRSTUUUYZs');
            $ocistene = substr($ocistene_1,0,42);
            return $ocistene;    
    }
?>