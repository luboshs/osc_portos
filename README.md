# osc_portos
webové rozhranie POS pokladne pre oscommerce pracujúce z kasou portos a portos API

## Povinné technické parametre e-shopu

Súbory e-shopu musia vždy bežať s týmito parametrami:

- platforma: **osCommerce Online Merchant v2.2 RC2a**
- verzia PHP: **5.3.29**
- databáza osCommerce: staré kódovanie **cp1250** (windows-1250)

## Kódovanie

Aby sa už neopakovali problémy s diakritikou, platí jedno pravidlo:

- **zdrojové súbory tohto modulu (PHP, JS, CSS) sú v UTF-8** – rovnako aj HTML výstup
  (`Content-Type: text/html; charset=utf-8`) a komunikácia s Portos API (JSON v UTF-8),
- **databáza osCommerce zostáva v cp1250**, preto sa každý text prekóduje až na hranici databázy:
  - `ekasa_do_db()` – UTF-8 → cp1250 pri zápise do databázy (komentáre k objednávke, poznámky,
    meno klienta, položka zľavy),
  - `ekasa_z_db()` – cp1250 → UTF-8 pri čítaní z databázy (výpis na stránke, údaje pre bloček),
  - `ocisti()` – `ekasa_z_db()` + skrátenie textu na 42 znakov pre bloček,
  - `ekasa_html()` – bezpečný výpis textu do HTML (htmlspecialchars v UTF-8).

Výnimka: `portos/ekasa_pdf_vypis.php` zostáva v cp1250, pretože knižnica FPDF pracuje
s jednobajtovým kódovaním.

## Sumy zadané pokladníkom

Pokladník môže sumu zadať s desatinnou čiarkou aj bodkou. Čiarka sa automaticky prepíše
na bodku – v prehliadači funkciami `naCislo()` / `cisloZPolicka()`
(`portos/ekasa_skripty.js`) a na serveri funkciou `ekasa_cislo()` (`portos/ekasa_portos.php`).
