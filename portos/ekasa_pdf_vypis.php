<?php
/*******************************************************************************
PDF VYPIS
*******************************************************************************/
define('FPDF_FONTPATH','fpdf/font/');
require('fpdf/fpdf.php');
require('fpdf/ean13.php');
require('fpdf/WriteHTML.php');

    $title = 'Mesa�n� v�pis pokladne - '.$rok.' / '.$mesiac;
    $pdf=new PDF_HTML();
    $pdf->AliasNbPages();
    $pdf->SetMargins(24,25,10);
    $pdf->AddFont('Arialce','','arial.php') ;
    $pdf->AddFont('Arialce','B','arialb.php');
    $pdf->SetAuthor('ATaC s.r.o., ICO: 45 317 950, www.modelovazeleznica.sk');
    $pdf->SetTitle($title);

    $pdf->AddPage();
// hlavi�ka
  $pdf->SetFont('Arialce','B',12);
  $pdf->Cell(0,5,$title,0,1,'L');
  $pdf->Image('portos/logo_natlac.jpg',150,23,50);
  $pdf->Image('portos/linajka.gif',25,31,175,0.4);

  $pdf->Ln(4);
  $r_hl = 5;
  $YY = $pdf->GetY();

  $pdf->SetFont('Arialce','',9);
  $pdf->Cell(0,$r_hl,'Firma:',0,0,'L');
  $pdf->SetX(60);
  $pdf->SetFont('Arialce','B',9);
  $pdf->Cell(0,$r_hl,'ATaC s.r.o.',0,1,'L');

  $pdf->SetFont('Arialce','',9);
  $pdf->Cell(0,$r_hl,'��slo pokladne:',0,0,'L');
  $pdf->SetX(60);
  $pdf->SetFont('Arialce','B',9);
  $pdf->Cell(0,$r_hl,$cashRegisterCode,0,1,'L');

  $pdf->SetFont('Arialce','',9);
  $pdf->Cell(0,$r_hl,'Popis pokladne:',0,0,'L');
  $pdf->SetX(60);
  $pdf->SetFont('Arialce','B',9);
  $pdf->Cell(0,$r_hl,'poklad�a na predajni - ekasa Portos',0,1,'L');

  $pdf->Ln(2);

   $YY = $pdf->GetY();
   $pdf->Image('portos/linajka.gif',25,$YY,175,0.4);
   $pdf->Ln(4);

   // �hrady fakt�r
   $YY_faktury = $pdf->GetY();
   $pdf->Cell(0,$r_hl,'TR�BY - �HRADY FAKT�R',0,1,'L');
   $pdf->Ln(2);


    $pdf->SetFont('Arialce','B',7);
    $r1 = 4;
    $pdf->SetX(25);
    $pdf->Cell(9,$r1,'eID',1,0,'C');
    $pdf->Cell(9,$r1,'Blo�ek',1,0,'C');
    $pdf->Cell(15,$r1,'D�tum',1,0,'C');
    $pdf->Cell(15,$r1,'�. fakt�ry',1,0,'C');
    $pdf->Cell(13,$r1,'Suma',1,0,'C');
    $pdf->Cell(13,$r1,'Hotovos�',1,0,'C');
    $pdf->Cell(13,$r1,'Karta',1,1,'C');

    $celkom_suma = 0;
    $celkom_hotovost = 0;
    $celkom_karta =0;

    $YY= $pdf->GetY();
    $pdf->SetFont('Arialce','',7);

    while ($vypis_a = tep_db_fetch_array($vypis_faktury)) {
             //if ($vypis_a['type']=='invoice') {
                    $pdf->SetX(25);
                    $pdf->Cell(9,$r1,$vypis_a['eID'],1,0,'C');
                    $pdf->Cell(9,$r1,$vypis_a['receiptNumber'],1,0,'C');
                    $pdf->Cell(15,$r1,$vypis_a['date'],1,0,'C');
                    $pdf->Cell(15,$r1,$vypis_a['invoiceNumber'],1,0,'C');
                        $suma = $vypis_a['amount'];
                        $string = number_format ($suma, 2, ',', ' ');
                        $celkom_suma += $suma;
                    $pdf->Cell(13,$r1,$string,1,0,'R');
                        $suma = $vypis_a['hotovost_kredit'];
                        $string = number_format ($suma, 2, ',', ' ');
                        $celkom_hotovost += $suma;
                    $pdf->Cell(13,$r1,$string,1,0,'R');
                        $suma = $vypis_a['platobna_karta'];
                        $string = number_format ($suma, 2, ',', ' ');
                        $celkom_karta += $suma;
                    $pdf->Cell(13,$r1,$string,1,1,'R');
             //}
          }
    $pdf->SetX(25);
    $pdf->SetFont('Arialce','B',7);
    $pdf->Cell(48,$r1,'CELKOM',1,0,'C');
        $string = number_format ($celkom_suma, 2, ',', ' ');
    $pdf->Cell(13,$r1,$string,1,0,'C');
        $string = number_format ($celkom_hotovost, 2, ',', ' ');
    $pdf->Cell(13,$r1,$string,1,0,'R');
        $string = number_format ($celkom_karta, 2, ',', ' ');
    $pdf->Cell(13,$r1,$string,1,1,'R');

    $YY_faktury_end = $pdf->GetY();

    // vklad do banky
    $r1 = 4;
    $pdf->SetY($YY_faktury);
    $XX = 115;
    $pdf->SetX($XX);
    $pdf->SetFont('Arialce','B',9);
    $pdf->Cell(0,$r_hl,'V�BERY - na VKLAD DO BANKY',0,1,'L');
    $pdf->Ln(2);

    $pdf->SetFont('Arialce','B',7);
    $pdf->SetX($XX);
    $pdf->Cell(9,$r1,'eID',1,0,'C');
    $pdf->Cell(9,$r1,'Blo�ek',1,0,'C');
    $pdf->Cell(15,$r1,'D�tum',1,0,'C');
    $pdf->Cell(35,$r1,'Pozn�mka',1,0,'C');
    $pdf->Cell(13,$r1,'Suma',1,0,'C');
    $pdf->Cell(4,$r1,'A',1,1,'C');

    $celkom_suma = 0;

    $YY= $pdf->GetY();
    $pdf->SetFont('Arialce','',7);

    while ($vypis_b = tep_db_fetch_array($vypis_banky)) {
             //if ($vypis_b['type']=='withdraw-bank') {
                    $pdf->SetX($XX);
                    $pdf->SetFont('Arialce','',7);
                    $pdf->Cell(9,$r1,$vypis_b['eID'],1,0,'C');
                    $pdf->Cell(9,$r1,$vypis_b['receiptNumber'],1,0,'C');
                    $pdf->Cell(15,$r1,$vypis_b['date'],1,0,'C');
                    $pdf->SetFont('Arialce','',5);
                    $pdf->Cell(35,$r1,substr($vypis_b['poznamka'],0,28),1,0,'L');
                        $suma = $vypis_b['amount'];
                        $string = number_format ($suma, 2, ',', ' ');
                        $celkom_suma += $suma;
                    $pdf->SetFont('Arialce','',7);
                    $pdf->Cell(13,$r1,$string,1,0,'C');
                    if ($vypis_b['autorizovane']) {$string='OK';}
                    else {$string='X';}
                    $pdf->SetFont('Arialce','',5);
                    $pdf->Cell(4,$r1,$string,1,1,'C');
             //}
          }
    $pdf->SetX($XX);
    $pdf->SetFont('Arialce','B',7);
    $pdf->Cell(68,$r1,'CELKOM',1,0,'C');
        $string = number_format ($celkom_suma, 2, ',', ' ');
    $pdf->Cell(13,$r1,$string,1,0,'C');
        $string = number_format ($celkom_hotovost, 2, ',', ' ');
    $pdf->Cell(4,$r1,'',1,1,'R');
    $pdf->Ln(4);

    $YY_vybery_end = $pdf->GetY();


    $YY = max ($YY_faktury_end, $YY_vybery_end) + 4;

    $pdf->SetY($YY);

    $pdf->Image('portos/linajka.gif',25,$YY,175,0.4);
    $pdf->Ln(4);

    $Y_poloha =  $pdf->GetY();

    if ($Y_poloha > 200 ) {
    	$pdf->AddPage();
    }

    $pdf->SetFont('Arialce','B',9);
    $pdf->Cell(0,$r_hl,'MESA�N� INTERVALOV� UZ�VIERKA',0,1,'L');
    $pdf->Ln(2);

    $l1 = 45;
    $l2 = 25;
    $l3 = $l1 + $l2;

    $r1 = 5;
    $YYM = $pdf->GetY();
    $pdf->SetX(25);
    $pdf->SetFont('Arialce','',9);
    $pdf->Cell($l1,$r1,'�daje od: ',1,0,'R');
    $pdf->Cell($l2,$r1,$prvy_den,1,1,'C');

    $pdf->SetX(25);
    $pdf->Cell($l1,$r1,'�daje do: ',1,0,'R');
    $pdf->Cell($l2,$r1,$posledny_den,1,1,'C');
    $pdf->Ln($r1);

    $obrat = 0;
    $bez_dph = 0;
    $bez_dph1 = 0;
    $bez_dph2 = 0;
    $bez_dph2_2 = 0;
    $bez_dph3 = 0;
    $dph = 0;
    $hotovost = 0;
    $kartou = 0;
    $qr = 0;
    $pokladnicne_doklady = 0;
    $faktury = 0;
    $vklad = 0;
    $vyber = 0;
 
    $vat_znizena = 0;
    $vat_zakladna = 0;
    $vat_znizena2 = 0;
    
    $qVklady = 0;
    $qVybery = 0;
    $qFaktury = 0;
    $qBlocky = 0;

    $blocky = array ();

    while ($riadok = tep_db_fetch_array($vypis_q)) {


            $chyba = true;

            if ($riadok['cashRegisterCode']==$cashRegisterCode) {

              if (is_null($response['response']['error'])) {
              $response  = json_decode($riadok['response'], true);
              
              $vat_zakladna += $response['request']['data']['basicVatAmount'];
              $vat_znizena += $response['request']['data']['reducedVatAmount'];
              $vat_znizena2 += $response['request']['data']['secondReducedVatAmount'];
              
              $bez_dph1 += $response['request']['data']['taxBaseBasic'];
              $bez_dph2 += $response['request']['data']['taxBaseReduced'];
              $bez_dph2_2 += $response['request']['data']['taxBaseSecondReduced'];
              $bez_dph3 += $response['request']['data']['taxFreeAmount'];
              
       /*       
             pridali sme:
             $vat_znizena2
             $bez_dph2_2
             
             ***"basicVatAmount":26.95,
             ***"reducedVatAmount":null,
             ***"secondReducedVatAmount":null,
             ***"taxFreeAmount":null,
             "nonTaxableAmount":null,
             
             ***"taxBaseBasic":117.15,
             ***"taxBaseReduced":null,
             ***"taxBaseSecondReduced":null," 
         */     
              
              $kartou += isset($response['request']['data']['payments'][1]['amount']) ? $response['request']['data']['payments'][1]['amount'] : 0;
              //$riadok['platobna_karta'];
              $hotovost += isset($response['request']['data']['payments'][0]['amount']) ? $response['request']['data']['payments'][0]['amount'] : 0;
              $qr += isset($response['request']['data']['payments'][2]['amount']) ? $response['request']['data']['payments'][2]['amount'] : 0;
              $chyba = false;
              }

              if (($response['status']==500)AND($response['receiptId']['receiptNumber']>0)) {
                   $vat_zakladna += (float)$riadok['vat'];
                   $bez_dph1 += (float)$riadok['amount']- (float)$riadok['vat'];
                   mail ('antal@atac-sro.eu', 'vypis', 'Vo vypise je zahrnuta aj chybova polozka - eID = '.$riadok['eID']);
              }


              switch ($riadok['type']) {
                            case 'cash_register':
                                //$pokladnicne_doklady += $riadok['amount'];
                                //$obrat += $riadok['amount'];
                                //$kartou += $riadok['platobna_karta'];
                                //$hotovost = $hotovost + $riadok['hotovost_kredit'] - $riadok['hotovost_debit'];
                                
                                //$pokladnicne_doklady += $response['request']['data']['amount'];
                                $pokladnicne_doklady += (float)$riadok['amount'];
                                
                                $qBlocky += 1;

                                $blocek     = array($riadok['eID'],$riadok['oID'],$riadok['client_name'],$riadok['date'],$riadok['amount'],$riadok['vat']);
                                $blocky[]   = $blocek;

                                break;
                            case 'invoice':
                                if($riadok['receiptNumber']>0) {$faktury += $riadok['amount'];}
                                //$obrat += $riadok['amount'];
                                $qFaktury += 1;
                                break;
                            case 'deposit':
                                $vklad += $riadok['amount'];
                                $qVklady += 1;
                                break;
                            case 'withdraw':
                                $vyber += $riadok['amount'];
                                $qVybery += 1;
                                break;
                            case 'withdraw-bank':
                                $vyber += $riadok['amount'];
                                $qVybery += 1;
                                break;
              }

          }
         }
    //$bez_dph = $obrat - $dph;
    $dph = $vat_znizena + $vat_zakladna + $vat_znizena2;

    $pdf->SetX(25);
    $pdf->SetFont('Arialce','B',9);
    $pdf->Cell($l3,$r1,'ROZPIS PLATIDIEL',1,1,'C');
    $pdf->SetFont('Arialce','',9);

    $pdf->SetX(25);
    $pdf->Cell($l1,$r1,'Platby hotovos�:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($hotovost, 2, ',', ' ').' � ',1,1,'R');

    $pdf->SetX(25);
    $pdf->Cell($l1,$r1,'Platby kartou:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($kartou, 2, ',', ' ').' � ',1,1,'R');

    $pdf->SetX(25);
    $pdf->Cell($l1,$r1,'Platby QR:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($qr, 2, ',', ' ').' � ',1,1,'R');

    $platby_spolu = $hotovost + $kartou + $qr;
    $pdf->SetX(25);
    $pdf->SetFont('Arialce','',9);
    $pdf->Cell($l1,$r1,'Platby CELKOM:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($platby_spolu, 2, ',', ' ').' � ',1,1,'R');
    $pdf->SetFont('Arialce','',9);

    $pdf->Ln($r1);

    $pdf->SetX(25);
    $string = 'Vklady ('.$qVklady.'x):';
    $pdf->Cell($l1,$r1,$string,1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($vklad, 2, ',', ' ').' �',1,1,'R');

    $pdf->SetX(25);
    $string = 'V�bery ('.$qVybery.'x):';
    $pdf->Cell($l1,$r1,$string,1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($vyber, 2, ',', ' ').' �',1,1,'R');


    $pdf->SetY($YYM);
    $pdf->SetX($XX);

    $pdf->SetFont('Arialce','B',9);
    $pdf->Cell($l3,$r1,'Z�KLAD PRE DPH',1,1,'C');
    //$pdf->Cell($l2,$r1,number_format ($bez_dph1, 2, ',', ' ').' � ',1,1,'R');
    $pdf->SetFont('Arialce','',9);

    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'Z�kladn� sadzba:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($bez_dph1, 2, ',', ' ').' � ',1,1,'R');

    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'Zn�en� sadzba:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($bez_dph2, 2, ',', ' ').' � ',1,1,'R');
    
    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'2. zn�en� sadzba:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($bez_dph2_2, 2, ',', ' ').' � ',1,1,'R');

    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'Nepodlieha dani:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($bez_dph3, 2, ',', ' ').' � ',1,1,'R');

    $pdf->SetX($XX);
    $bez_dph = $bez_dph1 + $bez_dph2 + $bez_dph2_2 + $bez_dph3;
    $pdf->Cell($l1,$r1,'SPOLU z�klad:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($bez_dph, 2, ',', ' ').' � ',1,1,'R');

    $pdf->Ln($r1);

    $pdf->SetX($XX);
    $pdf->SetFont('Arialce','B',9);
    $pdf->Cell($l3,$r1,'DPH',1,1,'C');
    $pdf->SetFont('Arialce','',9);

    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'Z�kladn� sadzba DPH:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($vat_zakladna, 2, ',', ' ').' � ',1,1,'R');

    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'Zn�en� sadzba DPH:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($vat_znizena, 2, ',', ' ').' � ',1,1,'R');

    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'2. zn�en� sadzba DPH:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($vat_znizena2, 2, ',', ' ').' � ',1,1,'R');
    
    
    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'DPH SPOLU:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($dph, 2, ',', ' ').' � ',1,1,'R');

    $pdf->Ln($r1);

    $pdf->SetX($XX);
    $pdf->SetFont('Arialce','B',9);
    $pdf->Cell($l3,$r1,'OBRATY',1,1,'C');
    $pdf->SetFont('Arialce','',9);

    $pdf->SetX($XX);
    $string = 'Pokladni�n� doklady ('.$qBlocky.'x):';
    $pdf->Cell($l1,$r1,$string,1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($pokladnicne_doklady, 2, ',', ' ').' �',1,1,'R');

    $pdf->SetX($XX);
    $string = '�hrady fakt�r ('.$qFaktury.'x):';
    $pdf->Cell($l1,$r1,$string,1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($faktury, 2, ',', ' ').' �',1,1,'R');

    $trzby = $pokladnicne_doklady + $faktury;
    $pdf->SetX($XX);
    $pdf->Cell($l1,$r1,'Obraty SPOLU:',1,0,'R');
    $pdf->Cell($l2,$r1,number_format ($trzby, 2, ',', ' ').' �',1,1,'R');



    $pdf->AddPage();
    //$blocek     = array($riadok['eID'],$riadok['oID'],$riadok['client_name'],$riadok['date'],$riadok['amount'],$riadok['vat']);
     $pdf->SetFont('Arialce','B',9);
    $pdf->Cell($l1,$r1,'V�pis dokladov',0,1,'L');
    $pdf->Ln(2);
    $pdf->SetFont('Arialce','',7);
    $arrayLength = count($blocky);
    $i = 0;
    $medzisucet_o = 0;
    $medzisucet_dph =0;

    while ($i < $arrayLength) {
      $poradie = ($i+1).') ';
       $medzisucet_o +=  $blocky[$i][4];
       $medzisucet_dph += $blocky[$i][5];
     $pdf->Cell(7,$r1,$poradie,1,0,'R');
     $pdf->Cell(15,$r1,$blocky[$i][0],1,0,'R');
     $pdf->Cell(15,$r1,$blocky[$i][1],1,0,'R');
     $pdf->Cell(30,$r1,$blocky[$i][2],1,0,'R');
     $pdf->Cell(20,$r1,$blocky[$i][3],1,0,'R');
     $pdf->Cell(15,$r1,$blocky[$i][4],1,0,'R');
     $pdf->Cell(15,$r1,$blocky[$i][5],1,0,'R');
     $pdf->Cell(30,$r1,$medzisucet_o,1,0,'R');
     $pdf->Cell(30,$r1,$medzisucet_dph,1,1,'R');
      $i++;
    }

  $pdf->Output($rok.$mesiac.'_Vypis-Ekasa-Portos.pdf','D');

?>
