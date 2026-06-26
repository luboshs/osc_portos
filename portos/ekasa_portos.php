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
             curl_setopt($curl, CURLOPT_TIMEOUT,TIMEOUT);
             curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
             curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
             // EXECUTE:
             $result = curl_exec($curl);
         //    if(!$result){die("Connection Failure");}
             curl_close($curl);
      //     $result_decoded = json_decode($result, true);
      //     return $result_decoded;
             return $result;
    }
    
        function ocisti($string){      
            $ocistene_1 = strtr($string, 'בהטןילכם¾עפףצארתשü‎ֱִָֹֻּֽֿ¼ׂ׃ײװ״ְÚÙÜÝ°', 'aacdeeeilnooorrstuuuyzAACDEEEILNOOORRSTUUUYZs');
            $ocistene = substr($ocistene_1,0,42);
            return $ocistene;    
    }
?>