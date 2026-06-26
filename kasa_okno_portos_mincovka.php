<?php
     // načítanie základných funkcií eshopu
        require ('includes/application_top.php');
     // načítanie nastavení a funkcií ekasa
        require ('portos/ekasa_portos_nastavenia.php');
     // načíta funkcie pre mincovku
        require ('portos/ekasa_portos_mincovka_funkcie.php');

        $my_account_query = tep_db_query ("SELECT admin_name, sf_email, sf_kluc FROM administrators WHERE user_name= '" . $admin['username'] ."'");
        $myAccount = tep_db_fetch_array($my_account_query);
     // $admin_name = utf8_encode ($myAccount['admin_name']);
        $admin_name = iconv("windows-1250","utf-8",$myAccount['admin_name']);
?>

<?php
   /*
    //  toto krásne vypíše POST premenné
    echo '<table>';
    foreach ($_POST as $key => $value) {
        echo "<tr>";
        echo "<td>";
        echo $key;
        echo "</td>";
        echo "<td>";
        echo $value;
        echo "</td>";
        echo "</tr>";
    }
     echo '</table>';
     */
?>

  <!DOCTYPE html>
        <html>
              <head>
                  <meta http-equiv="Content-Type" content="text/html; charset=utf8">
                  <title><?php echo APP_NAME.' '.APP_VERSION;?></title>
                  <script language="javascript" src="portos/jquery-2.2.4.min.js"></script>
                  <script language="javascript" src="portos/ekasa_mincovka.js"></script>
                  <link rel="stylesheet" type="text/css" href="portos/ekasa_portos.css">
              </head>

              <body>

              <form name="mincovka" id="form_mincovka" method="POST">
        <?php

                        echo '<table width ="100%">';

                        echo '<tr>';
                        echo '<td colspan="2" align="center"><b>M I N C O V K A</b></td>';
                        echo '</tr>';


                        echo '<tr>';
                    //  stĺpec 1
                        echo '<td colspan="1">';
                        echo '<table>';
                        echo    '<tr>';
                        echo        '<td colspan="3" align="center"><b>BANKOVKY</b></td>';
                        echo    '</tr>';
                                bankovkaTR(500,1,true);
                                bankovkaTR(1000,2,false);
                                bankovkaTR(2000,3,false);
                                bankovkaTR(5000,4,false);
                                bankovkaTR(10000,7,false);
                                bankovkaTR(20000,0,false);
                                bankovkaTR(50000,0,false);
                        echo    '<tr><td colspan="3">&nbsp</td></tr>';
                        echo '</table>';
                        echo '</td>';

                    //  stĺpec 2
                        echo '<td colspan="1">';
                        echo '<table>';
                        echo    '<tr>';
                        echo        '<td colspan="3" align="center"><b>MINCE</b></td>';
                        echo    '</tr>';
                                mincaTR(200,8,false);
                                mincaTR(100,9,false);
                                mincaTR(50,10,false);
                                mincaTR(20,11,false);
                                mincaTR(10,12,false);
                                mincaTR(5,13,false);
                                mincaTR(2,14,false);
                                mincaTR(1,15,false);
                        echo '</table>';
                        echo '</td>';

                        echo '</tr>';


                        echo '<tr>';
                        echo '<td colspan="2">Suma spolu: ';
                        echo '<input type="text" name="spolu" id="suma_spolu" value="0" style="font-size: 20pt" size="6" readonly disabled><br /><br />';
                        echo '</td>';
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td colspan="2">Spočítal: ';
                        echo '<input type="text" name="admin" value="'.$admin_name.'" style="font-size: 10pt" size="20"  onfocus="this.select();" > ';
                        echo '</td>';
                        echo '</tr>';

                        echo '<tr>';
                        echo '<td colspan="2" align="center"><br />';
                        echo '<button type="button" class="button_blocek" onclick="window.print();">Vytlačiť Mincovku</button> ';
                        echo '<button type="button" class="button_blocek" onclick="alert('."'ukladanie zatiaľ nefunguje'".');">Uložiť Mincovku</button> ';
                        echo '<button type="button" class="button_karta"  onclick="OtvorZasuvku();">OTVOR ZÁSUVKU</button> ';
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<input type="hidden" name="akcia" value="VKLAD_ZAPIS">';

?>
               </form>
              </body>
         </html>