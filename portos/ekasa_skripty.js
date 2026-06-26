function platbaKartou () {
              var celaSuma = Number(suma.value);
              console.log(celaSuma);
              var zlava_sum = zlava_suma.value;
              console.log(zlava_sum);
              var medzisucet_zlava = (Math.round((celaSuma - zlava_sum)*100))/100;
              console.log(medzisucet_zlava);
              var kartou = prompt("Ak· suma bola zaplaten· termin·lom?",medzisucet_zlava);
              kartou = kartou.replace(",",".");
              var ma_dat = (Math.round((celaSuma - zlava_sum - kartou)*100))/100;
              var zaokruhli = zaokruhlit(ma_dat);
              zaokruhlenie.value = zaokruhli;
              var hotovost_prepocet = (Math.round((ma_dat + zaokruhli)*100))/100;
              if (0 > ma_dat) {alert("CHYBA - Pri platbe kartou sa nevyd·va. Zadajte znova spr·vnu sumu!");}
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
                  // neotestovanÈ
                  const Url = "http://localhost:3010/api/v1/printers/open_drawer";
                  const Data = {method: "POST"};
                  const response = fetch(Url,Data)
                        .then(responses => responses.json())
                        .then(json => console.log(json));
                  // asi bez odpovedÌ 
                  }

function PredajCasopis() {
                  var name = prompt ("N·zov poloûky","Casopis Zeleznicni magazin");
                  var pocet = prompt ("Zadaj Mnoûstvo?","1");
                  var cena = prompt ("Zadaj cenu za kus","8");
                  var privesok = "akcia=PredajCasopis&description=CASOPIS&pocet=" + pocet + "&cena=" + cena + "&name=" + name;                     
                  var link = "kasa_okno_portos.php?" + privesok; 
                  window.open(link, '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,top=500,left=500,width=500,height=400'); 
                  }


function zmenaHotovosti () {             
              var celaSuma = Number(suma.value);
              var zlava_sum = zlava_suma.value;
              var medzisucet_zlava = celaSuma - zlava_sum;
              var v_hotovost = hotovost.value.replace(",",".");
              var kartou = Number (karta.value);
              v_hotovost = Number(v_hotovost);
              var platba = v_hotovost + kartou;
              var zaokruhli = Number (zaokruhlenie.value);                  
              var vydaj =  platba - medzisucet_zlava - zaokruhli;
              vydaj = ((Math.round(vydaj * 100)) / 100);
              
              if (vydaj > 0) {vydavok.value = vydaj;} 
              else if (vydaj < 0) {vydavok.value = 'M¡LO';}
              else {vydavok.value = 'NIE';}     
              zaokruhlenie.value = zaokruhli;       
}
                                                            

function faktura() {
            alert ('Upozornenie!\n\nT˙to funkciu pouûÌvaj iba na fakt˙ry ktorÈ nevieö uhradiù priamo z objedn·vky!','Upozornenie!');
            var suma_0 = prompt ('Zadaj sumu','EUR');
            var suma = 0;
            suma = suma_0.replace(",",".");
            var faktura = prompt ("Zadaj ËÌslo fakt˙ry","Cislo faktury");
            var link = 'https://shop.modelovazeleznica.sk/admin/kasa_okno_portos.php?akcia=FAKTURA&zdroj=manual&suma=' + suma + '&cislo_faktury=' +faktura;
            window.open(link, '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,top=500,left=500,width=500,height=400'); 
            
}



function generujBlocek (naEmail) {
                            var premenna_karta = Number(karta.value);
                            var premenna_hotovost_ma_dat = Number(hotovost_ma_dat.value);
                            var premenna_hotovost = Number(hotovost.value);
                            //var premenna_vydavok = vydavok.value;
                            if (vydavok.value == "NIE") {var premenna_vydavok =0;}
                            else {var premenna_vydavok = Number(vydavok.value);}
                            
                            var premenna_zaokruhlenie = Number(zaokruhlenie.value);
                            
                            var platba = premenna_karta + premenna_hotovost - premenna_vydavok;
                            platba = (Math.round((platba)*100))/100;
                            var celaSuma = Number(suma.value);
                            var zlava_sum = zlava_suma.value;
                            var medzisucet_zlava = (Math.round((celaSuma - zlava_sum)*100))/100;
                            var premenna_ma_dat = medzisucet_zlava + premenna_zaokruhlenie;
                            premenna_ma_dat = (Math.round((premenna_ma_dat)*100))/100;

                            console.log("ma dat (hotovosù)= " + premenna_hotovost_ma_dat);
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
                            alert ('Chyba - suma platieb musÌ byù zhodn· so sumou bloËka. Skontrolujte Ëi ste zadali spr·vnu sumu pre platbu kartou!');
                       }


              }



function kontrola_storna() {
               cakaj.style = "display:none;";
               var kontrola = confirm ("ProsÌm poËkajte chvÌæu a potvÔte OK ak bol stiahnut˝ s˙bor a vytlaËen˝ bloËek.\n\nAk bloËek vytlaËen˝ nebol stlaËte ZRUäIç.");
               
               if (kontrola) {
                          akcia.value = 'zapis';
                          zapis.submit();
               
               } else {
                          alert ('Uviedli ste, ûe bloËek sa nestiahol a nevytlaËil. \n Nebol vykonan˝ z·znam o bloËku. Zavrite toto okno.');
                          
               }
                

}




function stiahni(oID) {
        var v_hotovosti = hotovost.value.replace(',','.');
        var kartou = karta.value.replace(',','.');
        var kontrola = confirm ('Naozaj chceö vygenerovaù bloËek?');
        
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
                       
                
        var vymazat = confirm ('\n\nBol bloËek vytlaËen˝?\n\nPokiaæ kliknete OK uloûÌ sa z·znam do datab·zy. Pokiaæ klinete Zruöiù, skript sa pok˙si bloËek stiahnuù eöte raz.');             
        if (vymazat) {  alert ('DokonËiù skript na vymazanie.');
        } else {txt.click();}
        
        document.body.removeChild(txt); 
      
        
        }

}   


function dajZlavu(zlava_0) {
     
     var zlava = prompt ("Ak˙ percentu·lnu chcete pridaù?\n(moûno zadaù iba hodnoty 0, 3, 5, 7 a 10)",zlava_0);
   
       if ((zlava == 0)|(zlava == 3)|(zlava==5)|(zlava==7)|(zlava==10)) {
                           
                          var celkom = suma.value;
                          var zlava_sum = 0.01;
                          
                          zlava_sum = celkom * zlava;
                          zlava_sum = Math.round(zlava_sum);
                          zlava_sum = zlava_sum / 100;
                          
                          var ma_dat = (Math.round((celkom - zlava_sum)*100))/100;
                          
                     //   alert (ma_dat);
                          
                          zlava_p.value = zlava + "%";
                          zlava_suma.value = zlava_sum;
                          karta.value = 0.00;
                          hotovost.value = ma_dat; 
                          vydavok.value = 'NIE'; 
                          hotovost_ma_dat.value  = ma_dat;
                     //   hotovost.focus();
                     
                        var zaokruhli = zaokruhlit(ma_dat);
                        zaokruhlenie.value = zaokruhli;
                            
                           
                       } 
       else           {alert ("Nespr·vna hodnota!\nMoûno zadaù iba zæavu v urËitej hodnote %.");}
}   


function zaokruhlit (vstup) {

           
              var cifra_cela = Number(vstup);
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


// Funkcia na uloûenie cookies
function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

// Funkcia na zÌskanie cookies
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

// Funkcia na valid·ciu IP adresy (IPv4)
function isValidIP(ip) {
    const ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    return ipRegex.test(ip);
}

// Funkcia na spracovanie IP adresy
function handleIPAddress() {
    const currentIP = getCookie("ip_address");
    const promptText = currentIP ?
        `Aktu·lna IP adresa: ${currentIP}. IP adresu zistÌö napr. na webe https://whatismyipaddress.com/. Zadajte nov˙ IP adresu:` :
        "IP adresu zistÌö napr. na webe https://whatismyipaddress.com/. Zadajte IP adresu:";
    const ip = prompt(promptText);

    if (ip && isValidIP(ip)) {
        setCookie("ip_address", ip, 999); // UloûÌ IP na 999 dnÌ
        const infoElement = document.getElementById("ip-info");
        if (infoElement) {
            infoElement.innerText = `IP adresa uloûen·: ${ip}`;
        }
    } else if (ip) {
        alert("Neplatn· IP adresa! Sk˙ste znova.");
    }
}

// Funkcia na zobrazenie aktu·lnej IP adresy
function displaySavedIP() {
    const savedIP = getCookie("ip_address");
    const infoElement = document.getElementById("ip-info");
    if (savedIP && infoElement) {
        infoElement.innerText = `${savedIP}`;
    }
}
