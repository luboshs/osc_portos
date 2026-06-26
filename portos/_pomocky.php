<?


//vypise POST Premenne:


?>
<table>
<?php 
    
    //  toto krásne vypíše POST premenné
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

?>
</table>
  <?php


//vypise pole
 
 
      print("<pre>".print_r($data_array,true)."</pre>");
      
      
      
?>