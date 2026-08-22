// Pokladnik moze zadat sumu s desatinnou ciarkou - vzdy ju prepiseme na bodku
function naCislo (hodnota) {
              if (hodnota === null || hodnota === undefined) {return 0;}
              var text = String(hodnota).replace(/\s/g, "").replace(/,/g, ".");
              var cislo = Number(text);
              if (isNaN(cislo)) {return 0;}
              return cislo;
}

// prepise ciarku na bodku priamo v policku formulara a vrati cislo
function cisloZPolicka (policko) {
              if (!policko) {return 0;}
              var cislo = naCislo(policko.value);
              policko.value = cislo;
              return cislo;
}

function platbaKartou () {
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
              var platba = v_hotovost + kartou;
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
                            var premenna_hotovost_ma_dat = cisloZPolicka(hotovost_ma_dat);
                            var premenna_hotovost = cisloZPolicka(hotovost);
                            //var premenna_vydavok = vydavok.value;
                            if (vydavok.value == "NIE") {var premenna_vydavok =0;}
                            else {var premenna_vydavok = naCislo(vydavok.value);}
                            
                            var premenna_zaokruhlenie = cisloZPolicka(zaokruhlenie);
                            
                            var platba = premenna_karta + premenna_hotovost - premenna_vydavok;
                            platba = (Math.round((platba)*100))/100;
                            var celaSuma = naCislo(suma.value);
                            var zlava_sum = naCislo(zlava_suma.value);
                            var medzisucet_zlava = (Math.round((celaSuma - zlava_sum)*100))/100;
                            var premenna_ma_dat = medzisucet_zlava + premenna_zaokruhlenie;
                            premenna_ma_dat = (Math.round((premenna_ma_dat)*100))/100;

                            console.log("ma dat (hotovosť)= " + premenna_hotovost_ma_dat);
                            console.log("=============================");
                            console.log("karta = " + premenna_karta);
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
                         document.getElementById('zlava_p').disabled = false;
                         document.getElementById('zlava_suma').disabled = false;
                         document.getElementById('zapis').submit();

                       } else {
                            alert ('Chyba - suma platieb musí byť zhodná so sumou bločka. Skontrolujte či ste zadali správnu sumu pre platbu kartou!');
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
