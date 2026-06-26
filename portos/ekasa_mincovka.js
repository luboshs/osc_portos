function pripocitaj(id) {
         var id_displej = "displej-"+id;
         var medzisucet = document.getElementById(id_displej).value;
         console.log("id:"+id+" medzisucet:"+medzisucet);
         return Number(medzisucet);
}

function prepocitaj() {
              var suma = 0;
              suma += pripocitaj(500);
              suma += pripocitaj(1000);
              suma += pripocitaj(2000);
              suma += pripocitaj(5000);
              suma += pripocitaj(10000);
              suma += pripocitaj(20000);
              suma += pripocitaj(50000);
              suma += pripocitaj(200);
              suma += pripocitaj(100);
              suma += pripocitaj(50);
              suma += pripocitaj(20);
              suma += pripocitaj(10);
              suma += pripocitaj(5);
              suma += pripocitaj(2);
              suma += pripocitaj(1);
              document.getElementById("suma_spolu").value = suma;
}


function prepocitaj_input (input_id) {
              console.log("id:"+input_id);

              var mnozstvo =  document.getElementById(input_id).value;
              console.log("id:"+input_id+" mnozstvo:"+mnozstvo);

              var medzisucet =  mnozstvo * input_id /100;
              console.log("id:"+input_id+" medzisucet:"+medzisucet);

              var id_displej = "displej-"+input_id;
              console.log(id_displej);

              document.getElementById(id_displej).value = medzisucet;
              prepocitaj();
}