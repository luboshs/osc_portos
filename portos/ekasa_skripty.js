// Pokladnik moze zadat sumu s desatinnou ciarkou - vzdy ju prepiseme na bodku
function naCislo (hodnota) {
              if (hodnota === null || hodnota === undefined) {return 0;}
              var text = String(hodnota).replace(/\s/g, "").replace(/,/g, ".");
              var cislo = Number(text);
              if (isNaN(cislo)) {return 0;}
              return cislo;
}

function qrPlatbaVynuluj (dovod) {
              var bolaAktivna = qrPlatbaPrebieha;
              var oIDpole = document.getElementById("oID");
              var qrIdPole = document.getElementById("qr_platba_id");
              if (bolaAktivna && oIDpole && oIDpole.value) {
                    var platbaId = qrIdPole ? qrIdPole.value : '';
                    var data = "akcia=qr_cancel&oID=" + encodeURIComponent(oIDpole.value) + "&platba_id=" + encodeURIComponent(platbaId);
                    qrApi(data, function () {});
              }
              qrPlatbaZrus(dovod);
              if (document.getElementById('qr_platba')) {document.getElementById('qr_platba').value = 0;}
              if (document.getElementById('qr_platba_potvrdena')) {document.getElementById('qr_platba_potvrdena').value = "0";}
              if (document.getElementById('qr_platba_id')) {document.getElementById('qr_platba_id').value = "";}
              nastavPaymeLinkNaPc("");
              qrStav("QR platba nie je aktívna.");
}

// prepise ciarku na bodku priamo v policku formulara a vrati cislo
function cisloZPolicka (policko) {
              if (!policko) {return 0;}
              var cislo = naCislo(policko.value);
              policko.value = cislo;
              return cislo;
}

function platbaKartou () {
              qrPlatbaVynuluj("prepínam na inú platbu");
              var celaSuma = naCislo(suma.value);
              console.log(celaSuma);
              var zlava_sum = naCislo(zlava_suma.value);
              console.log(zlava_sum);
              var medzisucet_zlava = (Math.round((celaSuma - zlava_sum)*100))/100;
              console.log(medzisucet_zlava);
              var kartou = prompt("Aká suma bola zaplatená terminálom?",medzisucet_zlava);
              if (kartou === null) {return;}
              kartou = naCislo(kartou);
              var ma_dat = (Math.round((celaSuma - zlava_sum - kartou)*100))/100;
              var zaokruhli = zaokruhlit(ma_dat);
              zaokruhlenie.value = zaokruhli;
              var hotovost_prepocet = (Math.round((ma_dat + zaokruhli)*100))/100;
              if (0 > ma_dat) {alert("CHYBA - Pri platbe kartou sa nevydáva. Zadajte znova správnu sumu!");}
              else {
                  karta.value = kartou;
                  vydavok.value = 'NIE'; 
                  
                  hotovost.value = hotovost_prepocet; 
                  hotovost_ma_dat.value  =ma_dat;
                  document.getElementById('hotovost').focus(); 
                  //hotovost.focus();
              }

}

var qrPlatbaPolling = null;
var qrPlatbaPrebieha = false;
var qrPlatbaPokusy = 0;
var qrPlatbaMaxPokusov = 60;
var qrPlatbaChyby = 0;

function qrStav (sprava) {
              var stav = document.getElementById('qr_status');
              if (stav) {stav.innerHTML = sprava;}
}

function nastavPaymeLinkNaPc (paymeLink) {
              var riadky = document.querySelectorAll('.payme_link_row');
              var odkazy = document.querySelectorAll('.payme_link_pc');
              if (!riadky.length || !odkazy.length) {return;}
              for (var i = 0; i < riadky.length; i++) {
                    var odkaz = odkazy[i];
                    if (!odkaz) {continue;}
                    if (paymeLink) {
                          odkaz.href = paymeLink;
                          odkaz.style.display = "";
                          riadky[i].style.display = "";
                    } else {
                          odkaz.href = "#";
                          odkaz.style.display = "none";
                          riadky[i].style.display = "none";
                    }
              }
}

function qrPlatbaZrus (dovod) {
              if (qrPlatbaPolling) {
                    clearTimeout(qrPlatbaPolling);
                    qrPlatbaPolling = null;
              }
              qrPlatbaPrebieha = false;
              qrPlatbaPokusy = 0;
              qrPlatbaChyby = 0;
              if (dovod) {console.log("QR stop: " + dovod);}
}

function qrSumaNaUhradu () {
              var celaSuma = naCislo(suma.value);
              var zlava_sum = naCislo(zlava_suma.value);
              var spolu = (Math.round((celaSuma - zlava_sum) * 100)) / 100;
              if (spolu < 0) {spolu = 0;}
              return spolu;
}

function qrApi (data, callback) {
              try {
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "kasa_displej_portos.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function () {
                              if (xhr.readyState === 4) {
                                    var odpoved = null;
                                    try {odpoved = JSON.parse(xhr.responseText);} catch (e) {odpoved = null;}
                                    callback(xhr.status, odpoved, xhr.responseText);
                              }
                    };
                    xhr.send(data);
              } catch (e) {
                    callback(0, null, String(e));
              }
}

function qrPlatba () {
              if (qrPlatbaPrebieha) {
                    alert("QR platba už prebieha.");
                    return;
              }

              var oIDpole = document.getElementById("oID");
              if (!oIDpole || !oIDpole.value) {
                    alert("Nie je dostupné číslo objednávky (oID).");
                    return;
              }

              var sumaNaUhradu = qrSumaNaUhradu();
              if (sumaNaUhradu <= 0) {
                    alert("Suma pre QR platbu musí byť väčšia ako 0.");
                    return;
              }

              qrPlatbaZrus();
              qrPlatbaPrebieha = true;
              qrPlatbaPokusy = 0;
              qrPlatbaChyby = 0;
              qrStav("Spúšťam QR platbu, čakaj...");
              var qrPotvrdena = document.getElementById('qr_platba_potvrdena');
              if (qrPotvrdena) {qrPotvrdena.value = "0";}

              var data = "akcia=qr_start&oID=" + encodeURIComponent(oIDpole.value) + "&suma=" + encodeURIComponent(sumaNaUhradu);
              qrApi(data, function (status, odpoved) {
                    console.log("QR start odpoveď:", status, odpoved);
                    if (status !== 200 || !odpoved || !odpoved.success) {
                          qrPlatbaZrus("start failed");
                          nastavPaymeLinkNaPc("");
                          qrStav("QR platbu sa nepodarilo spustiť. " + (odpoved && odpoved.stav ? odpoved.stav : ""));
                          alert("QR platbu sa nepodarilo spustiť.\n" + (odpoved && odpoved.stav ? odpoved.stav : ""));
                          return;
                    }

                    var qrId = "";
                    if (odpoved.data) {
                          if (odpoved.data.payment_id) {qrId = odpoved.data.payment_id;}
                          else if (odpoved.data.id) {qrId = odpoved.data.id;}
                    }
                    var qrIdPole = document.getElementById('qr_platba_id');
                    if (qrIdPole) {qrIdPole.value = qrId;}
                    var paymeLink = "";
                    if (odpoved.data && odpoved.data.payme_link) {paymeLink = odpoved.data.payme_link;}
                    nastavPaymeLinkNaPc(paymeLink);
                    qrStav("QR kód zobrazený na zákazníckom displeji. Čakám na potvrdenie platby...");
                    qrPlatbaKontrola();
              });
}

function qrPlatbaKontrola () {
              if (!qrPlatbaPrebieha) {return;}
              qrPlatbaPokusy++;
              if (qrPlatbaPokusy > qrPlatbaMaxPokusov) {
                    qrPlatbaZrus("timeout");
                    qrStav("QR platba nebola potvrdená v limite.");
                    alert("QR platba nebola potvrdená v limite. Skontroluj stav platby a skús znovu.");
                    return;
              }
              var oIDpole = document.getElementById("oID");
              if (!oIDpole || !oIDpole.value) {
                    qrPlatbaZrus("missing oid");
                    qrStav("QR platba zastavená - chýba oID.");
                    return;
              }

              var qrIdPole = document.getElementById('qr_platba_id');
              var platbaId = qrIdPole ? qrIdPole.value : '';
              var data = "akcia=qr_status&oID=" + encodeURIComponent(oIDpole.value) + "&platba_id=" + encodeURIComponent(platbaId);
              qrApi(data, function (status, odpoved) {
                    if (!qrPlatbaPrebieha) {return;}
                    if (status !== 200 || !odpoved || !odpoved.success) {
                          qrPlatbaChyby++;
                          if (qrPlatbaChyby >= 5) {
                                qrPlatbaZrus("status error");
                                var stavInfo = (odpoved && odpoved.stav ? ' ' + odpoved.stav : '');
                                qrStav("Chyba komunikácie pri overovaní QR platby." + stavInfo);
                                alert("Chyba komunikácie pri overovaní QR platby. Skontroluj internet/API a skús znova." + stavInfo);
                                return;
                          }
                          qrStav("Čakám na potvrdenie QR platby... (" + qrPlatbaPokusy + "/" + qrPlatbaMaxPokusov + "), chyba " + qrPlatbaChyby + "/5");
                          qrPlatbaPolling = setTimeout(qrPlatbaKontrola, 3000);
                          return;
                    }
                    qrPlatbaChyby = 0;

                    if (odpoved.paid) {
                          qrPlatbaZrus("paid");
                          var qrSuma = qrSumaNaUhradu();
                          karta.value = 0;
                          hotovost.value = 0;
                          hotovost_ma_dat.value = 0;
                          zaokruhlenie.value = 0;
                          vydavok.value = 'NIE';
                          if (document.getElementById('qr_platba')) {document.getElementById('qr_platba').value = qrSuma;}
                          if (document.getElementById('qr_platba_potvrdena')) {document.getElementById('qr_platba_potvrdena').value = "1";}
                          qrStav("Platba QR potvrdená. Tlačím doklad...");
                          generujBlocek(false);
                          return;
                    }

                    qrStav("Čakám na potvrdenie QR platby... (" + qrPlatbaPokusy + "/" + qrPlatbaMaxPokusov + ")");
                    qrPlatbaPolling = setTimeout(qrPlatbaKontrola, 3000);
              });
}
        
function OtvorZasuvku() {
                  // neotestované
                  const Url = "http://localhost:3010/api/v1/printers/open_drawer";
                  const Data = {method: "POST"};
                  const response = fetch(Url,Data)
                        .then(responses => responses.json())
                        .then(json => console.log(json));
                  // asi bez odpovedí 
                  }

function PredajCasopis() {
                  var name = prompt ("Názov položky","Casopis Zeleznicni magazin");
                  var pocet = prompt ("Zadaj Množstvo?","1");
                  var cena = prompt ("Zadaj cenu za kus","8");
                  var privesok = "akcia=PredajCasopis&description=CASOPIS&pocet=" + encodeURIComponent(pocet) + "&cena=" + encodeURIComponent(cena) + "&name=" + encodeURIComponent(name);                     
                  var link = "kasa_okno_portos.php?" + privesok; 
                  window.open(link, '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,top=500,left=500,width=500,height=400'); 
                  }


function zmenaHotovosti () {             
              var celaSuma = naCislo(suma.value);
              var zlava_sum = naCislo(zlava_suma.value);
              var medzisucet_zlava = celaSuma - zlava_sum;
              var v_hotovost = naCislo(hotovost.value);
              var kartou = naCislo(karta.value);
              var qr = naCislo(document.getElementById('qr_platba') ? document.getElementById('qr_platba').value : 0);
              var platba = v_hotovost + kartou + qr;
              var zaokruhli = naCislo(zaokruhlenie.value);                  
              var vydaj =  platba - medzisucet_zlava - zaokruhli;
              vydaj = ((Math.round(vydaj * 100)) / 100);
              
              if (vydaj > 0) {vydavok.value = vydaj;} 
              else if (vydaj < 0) {vydavok.value = 'MÁLO';}
              else {vydavok.value = 'NIE';}     
              zaokruhlenie.value = zaokruhli;       
}
                                                            

function faktura() {
            alert ('Upozornenie!\n\nTúto funkciu používaj iba na faktúry ktoré nevieš uhradiť priamo z objednávky!','Upozornenie!');
            var suma_0 = prompt ('Zadaj sumu','EUR');
            if (suma_0 === null) {return;}
            var suma = naCislo(suma_0);
            var faktura = prompt ("Zadaj číslo faktúry","Cislo faktury");
            if (faktura === null) {return;}
            var link = 'https://shop.modelovazeleznica.sk/admin/kasa_okno_portos.php?akcia=FAKTURA&zdroj=manual&suma=' + encodeURIComponent(suma) + '&cislo_faktury=' + encodeURIComponent(faktura);
            window.open(link, '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,top=500,left=500,width=500,height=400'); 
            
}



function generujBlocek (naEmail) {
                            var premenna_karta = cisloZPolicka(karta);
                            var qr_pole = document.getElementById('qr_platba');
                            var premenna_qr = qr_pole ? naCislo(qr_pole.value) : 0;
                            var premenna_hotovost_ma_dat = cisloZPolicka(hotovost_ma_dat);
                            var premenna_hotovost = cisloZPolicka(hotovost);
                            //var premenna_vydavok = vydavok.value;
                            if (vydavok.value == "NIE") {var premenna_vydavok =0;}
                            else {var premenna_vydavok = naCislo(vydavok.value);}
                            
                            var premenna_zaokruhlenie = cisloZPolicka(zaokruhlenie);
                            
                            var platba = premenna_karta + premenna_hotovost + premenna_qr - premenna_vydavok;
                            platba = (Math.round((platba)*100))/100;
                            var celaSuma = naCislo(suma.value);
                            var zlava_sum = naCislo(zlava_suma.value);
                            var medzisucet_zlava = (Math.round((celaSuma - zlava_sum)*100))/100;
                            var premenna_ma_dat = medzisucet_zlava + premenna_zaokruhlenie;
                            premenna_ma_dat = (Math.round((premenna_ma_dat)*100))/100;

                            console.log("ma dat (hotovosť)= " + premenna_hotovost_ma_dat);
                            console.log("=============================");
                            console.log("karta = " + premenna_karta);
                            console.log("qr = " + premenna_qr);
                            console.log("hotovost = " + premenna_hotovost);
                            console.log("zaokruhlenie = " + premenna_zaokruhlenie);
                            console.log("vydavok = " + premenna_vydavok);
                            console.log("SPOLU (platba) = " + platba);
                            console.log("=============================");
                            console.log("suma = " + celaSuma);
                            console.log("zlava = " + zlava_sum);
                            console.log("SPOLU (ma dat) = " + medzisucet_zlava);
                            console.log("=============================");
                            console.log( premenna_ma_dat + " <?> " + platba);
                            console.log("=============================");
                            console.log("*****************************");
                            
                       if (naEmail) {
                          document.getElementById('email').value = document.getElementById('email_input').value;
                       }  else  {
                          document.getElementById('email').disabled = true;
                       }

                       if (premenna_ma_dat == platba) {
                         //alert ("test OK");

                         document.getElementById('akcia').value='blocek_generuj';
                         document.getElementById('karta').disabled = false;
                         if (qr_pole) {qr_pole.disabled = false;}
                         document.getElementById('zlava_p').disabled = false;
                         document.getElementById('zlava_suma').disabled = false;
                         document.getElementById('zapis').submit();

                       } else {
                           alert ('Chyba - suma platieb musí byť zhodná so sumou bločka. Skontrolujte sumy pre hotovosť, kartu alebo QR platbu.');
                       }


              }



function kontrola_storna() {
               cakaj.style = "display:none;";
               var kontrola = confirm ("Prosím počkajte chvíľu a potvďte OK ak bol stiahnutý súbor a vytlačený bloček.\n\nAk bloček vytlačený nebol stlačte ZRUŠIŤ.");
               
               if (kontrola) {
                          akcia.value = 'zapis';
                          zapis.submit();
               
               } else {
                          alert ('Uviedli ste, že bloček sa nestiahol a nevytlačil. \n Nebol vykonaný záznam o bločku. Zavrite toto okno.');
                          
               }
                

}




function stiahni(oID) {
        var v_hotovosti = cisloZPolicka(hotovost);
        var kartou = cisloZPolicka(karta);
        var kontrola = confirm ('Naozaj chceš vygenerovať bloček?');
        
        if (kontrola) {
                $.ajax({
                    data: 'oID=' + oID + '&hotovost=' + v_hotovosti + '&karta=' + kartou,
                    url:  'kasa/blocek_generuj.php',
                    method: 'POST',
                    cache: false,
                    async: false, 
                    success: function(msg) {
                        
                    }
                });   
                                   
        var subor = 'blocek-'+ oID +'.txt';
        var link = 'kasa/koncepty/' + subor;
                
        var txt = document.createElement('a');
        txt.setAttribute('href', link);
        txt.setAttribute('download', subor);
        txt.style.display = 'none';
        document.body.appendChild(txt);
        txt.click();
                       
                
        var vymazat = confirm ('\n\nBol bloček vytlačený?\n\nPokiaľ kliknete OK uloží sa záznam do databázy. Pokiaľ klinete Zrušiť, skript sa pokúsi bloček stiahnuť ešte raz.');             
        if (vymazat) {  alert ('Dokončiť skript na vymazanie.');
        } else {txt.click();}
        
        document.body.removeChild(txt); 
      
        
        }

}   


// vráti položky, na ktoré je možné dať zľavu (bez položiek s konečnou cenou [KC])
function zlavovePolozky () {
              var policko = document.getElementById('zlava_polozky');
              if (!policko || !policko.value) {return null;}
              try {
                    var polozky = JSON.parse(policko.value);
                    if (!polozky || !polozky.length) {return [];}
                    return polozky;
              } catch (e) {
                    return null;
              }
}

// základ pre výpočet zľavy - suma položiek bez [KC], ak nie je známy použije sa celá suma
function zlavovyZaklad () {
              var policko = document.getElementById('zlava_zaklad');
              if (policko && policko.value !== "") {return naCislo(policko.value);}
              return naCislo(suma.value);
}

// výpočet zľavy rovnakým spôsobom, ako sa počíta na doklade (po položkách)
function vypocetZlavy (percento) {
              var polozky = zlavovePolozky();
              var zlava_sum = 0;
              if (polozky === null) {
                    // nepoznáme rozpis položiek - počítame zo základu
                    zlava_sum = Math.round(zlavovyZaklad() * percento) / 100;
              } else {
                    for (var i = 0; i < polozky.length; i++) {
                          var jednotkova_cena = naCislo(polozky[i].unitPrice);
                          var mnozstvo = Math.abs(naCislo(polozky[i].quantity));
                          var zlava_polozky = jednotkova_cena * (percento / 100);
                          zlava_sum += Math.round(Math.abs(zlava_polozky * mnozstvo) * 100) / 100;
                    }
              }
              return Math.round(zlava_sum * 100) / 100;
}

// odoslanie zadanej zlavy na zakaznicky displej (rezim SHOPPING, faza discounted)
// zlava 0 = zrusenie zlavy, displej ukaze povodne ceny
function posliDisplejZlavu (zlava) {
              var policko_oID = document.getElementById("oID");
              if (!policko_oID || !policko_oID.value) {return;}

              var data = "oID=" + encodeURIComponent(policko_oID.value) + "&zlava_p=" + encodeURIComponent(naCislo(zlava));

              try {
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "kasa_displej_portos.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function () {
                              if (xhr.readyState === 4) {
                                    console.log("displej (zlava " + zlava + "%): " + xhr.status + " " + xhr.responseText);
                              }
                    };
                    xhr.send(data);
              } catch (e) {
                    // displej nikdy nesmie zablokovat kasu
                    console.log("displej - chyba: " + e);
              }
}


function dajZlavu(zlava_0) {
     
     var zlava = prompt ("Akú percentuálnu zľavu chcete pridať?\n(možno zadať hodnoty od 1 do 15 %)",zlava_0);
     if (zlava === null) {return;}
   
       zlava = naCislo(zlava);
        
       if (zlava >= 1 && zlava <= 15) {
                           
                          var celkom = naCislo(suma.value);
                          var zaklad = zlavovyZaklad();
                           
                          if (zaklad <= 0) {
                                alert ("Zľavu nie je možné poskytnúť!\nV objednávke nie sú položky, na ktoré je možné dať zľavu\n(položky s konečnou cenou [KC] sú zo zliav vylúčené).");
                                return;
                          }
                           
                          var zlava_sum = vypocetZlavy(zlava);
                           
                          var ma_dat = (Math.round((celkom - zlava_sum)*100))/100;
                           
                     //   alert (ma_dat);
                           
                          zlava_p.value = zlava + "%";
                          zlava_suma.value = zlava_sum;
                          posliDisplejZlavu(zlava);
                          qrPlatbaVynuluj("zmena zľavy");
                          karta.value = 0.00;
                          hotovost.value = ma_dat; 
                          vydavok.value = 'NIE'; 
                          hotovost_ma_dat.value  = ma_dat;
                     //   hotovost.focus();
                      
                        var zaokruhli = zaokruhlit(ma_dat);
                        zaokruhlenie.value = zaokruhli;
                             
                        if (zaklad < celkom) {
                              alert ("Zľava " + zlava + "% bola vypočítaná iba zo sumy " + zaklad.toFixed(2) + " €.\nPoložky s konečnou cenou [KC] sú zo zľavy vylúčené.\n\nZľava = " + zlava_sum.toFixed(2) + " €\nSpolu k úhrade = " + ma_dat.toFixed(2) + " €");
                        }
                             
                       } 
       else           {alert ("Nesprávna hodnota!\nMožno zadať iba zľavu od 1 do 15 %.");}
}


function zaokruhlit (vstup) {

           
              var cifra_cela = naCislo(vstup);
              var cifra_string = cifra_cela.toFixed(2);
              var cifra = Number(cifra_string.slice(-1));
              var zaokruhli = 0;
                                        switch (cifra) {
                                            case 0:
                                                zaokruhli = 0;
                                                break;
                                            case 1:
                                                zaokruhli = -0.01;
                                                break;
                                            case 2:
                                                zaokruhli = -0.02;
                                                break;
                                            case 3:
                                                zaokruhli = 0.02;
                                                break;
                                            case 4:
                                                zaokruhli = 0.01;
                                                break;
                                            case 5:
                                                zaokruhli = 0;
                                                break;
                                            case 6:
                                                zaokruhli = -0.01;
                                                break;
                                            case 7:
                                                zaokruhli = -0.02;
                                                break;
                                            case 8:
                                                zaokruhli = 0.02;
                                                break;    
                                            case 9:
                                                zaokruhli = 0.01;
                                                break;
                                        }
                return zaokruhli;
}


function showHide(shID) {
   if (document.getElementById(shID)) {
      if (document.getElementById(shID+'-show').style.display != 'none') {
         document.getElementById(shID+'-show').style.display = 'none';
         document.getElementById(shID).style.display = 'block';
      }
      else {
         document.getElementById(shID+'-show').style.display = 'inline';
         document.getElementById(shID).style.display = 'none';
      }
   }
}


// Funkcia na uloženie cookies
function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

// Funkcia na získanie cookies
function getCookie(name) {
    const cookies = document.cookie.split("; ");
    for (let i = 0; i < cookies.length; i++) {
        const [key, value] = cookies[i].split("=");
        if (key === name) {
            return value;
        }
    }
    return null;
}

// Funkcia na validáciu IP adresy (IPv4)
function isValidIP(ip) {
    const ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    return ipRegex.test(ip);
}

// Funkcia na spracovanie IP adresy
function handleIPAddress() {
    const currentIP = getCookie("ip_address");
    const promptText = currentIP ?
        `Aktuálna IP adresa: ${currentIP}. IP adresu zistíš napr. na webe https://whatismyipaddress.com/. Zadajte novú IP adresu:` :
        "IP adresu zistíš napr. na webe https://whatismyipaddress.com/. Zadajte IP adresu:";
    const ip = prompt(promptText);

    if (ip && isValidIP(ip)) {
        setCookie("ip_address", ip, 999); // Uloží IP na 999 dní
        const infoElement = document.getElementById("ip-info");
        if (infoElement) {
            infoElement.innerText = `IP adresa uložená: ${ip}`;
        }
    } else if (ip) {
        alert("Neplatná IP adresa! Skúste znova.");
    }
}

// Funkcia na zobrazenie aktuálnej IP adresy
function displaySavedIP() {
    const savedIP = getCookie("ip_address");
    const infoElement = document.getElementById("ip-info");
    if (savedIP && infoElement) {
        infoElement.innerText = `${savedIP}`;
    }
}
