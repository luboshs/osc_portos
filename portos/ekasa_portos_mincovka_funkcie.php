<?php

function bankovkaTR($bankovka,$index,$autofocus) {
                        $mena = "EUR";
                        echo '<tr>';
                        $nominal = $bankovka /100;
                        if ($autofocus){$autofocus=' autofocus';} else {$autofocus='';}
                        echo '<td>['.$nominal.' '.$mena.']</td>'."\n";
                        echo '<td>'."\n";
                        echo '<input type="text" name="'.$bankovka.'" id="'.$bankovka.'" value="0" style="font-size: 10pt" size="3" onfocus="this.select();" onchange="prepocitaj_input('.$bankovka.');" tabindex='.$index.$autofocus.'> ks = '."\n";
                        echo '<input type="text" name="displej-'.$bankovka.'" id="displej-'.$bankovka.'" readonly disabled value="0" style="font-size: 10pt" size="3">'."\n";
                        echo '</td>'."\n";
                        echo '<td>';
                        echo '</td>'."\n";
                        echo '</tr>'."\n\n";
                   //   $i += $i;
}

function mincaTR($minca,$index,$autofocus) {
                        if ($minca > 99) {
                                $mena = "EUR";
                                $nominal = $minca /100;
                        } else {
                                $mena = "cent.";
                                $nominal = $minca  ;
                        }

                        echo '<tr>';
                        echo '<td>('.$nominal.' '.$mena.')</td>'."\n";
                        echo '<td>'."\n";
                        echo '<input type="text" name="'.$minca.'" id="'.$minca.'" value="0" style="font-size: 10pt" size="3" onfocus="this.select();" onchange="prepocitaj_input('.$minca.');" tabindex='.$index.$autofocus.'> ks = '."\n";
                        echo '<input type="text" name="displej-'.$minca.'" id="displej-'.$minca.'" readonly disabled value="0" style="font-size: 10pt" size="3">'."\n";
                        echo '</td>'."\n";
                        echo '<td>';
                        echo '</td>'."\n";
                        echo '</tr>'."\n\n";
                   //   $i += $i;
}

?>








