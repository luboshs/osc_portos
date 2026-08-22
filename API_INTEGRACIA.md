# Detailná API dokumentácia pre integráciu + analýza nedostatkov

Tento dokument je orientovaný na integračný tím (POS/kasa, osCommerce, PrestaShop) a popisuje:

1. presné API kontrakty,
2. očakávané chybové stavy,
3. integračné flow,
4. aktuálne chyby/nedostatky a chýbajúce funkcie v implementácii.

---

## 1) Autentifikácia a všeobecné pravidlá

- **Write endpointy** vyžadujú hlavičku `X-API-Key`:
  - `POST /api/display/update.php`
  - `POST /api/display/thank_you.php`
  - `POST /api/payment/manual_confirm.php`
  - `POST /api/payment/receipt.php`
  - `POST /api/payment/cancel.php`
- Pri neplatnom kľúči: **401 Unauthorized**
- Pri nezadanom/placeholder kľúči na serveri: **503 Service Unavailable**
- API očakáva JSON pre všetky POST endpointy.
- Všetky odpovede sú `application/json`.

---

## 2) API endpointy (integračný prehľad)

| Endpoint | Method | Auth | Účel |
|---|---|---:|---|
| `/api/display/update.php` | POST | ✅ | Nastavenie režimu displeja + payload košíka/QR |
| `/api/display/thank_you.php` | POST | ✅ | Prepnutie na fullscreen thank-you obrazovku |
| `/api/display/poll.php` | GET | ❌ | Polling stavu displeja z iPadu |
| `/api/payment/check.php` | GET | ❌ | Kontrola platby cez Fio API (rate-limit) |
| `/api/payment/status.php` | GET | ❌ | Rýchly DB status bez volania banky |
| `/api/payment/manual_confirm.php` | POST | ✅ | Ručné potvrdenie platby pokladníkom |
| `/api/payment/receipt.php` | POST | ✅ | Render + tlač účtenky (idempotentne) |
| `/api/payment/cancel.php` | POST | ✅ | Zrušenie čakajúcej QR platby |

---

## 3) Detail kontraktov

### `POST /api/display/update.php`

### Režimy
- Povolené: `idle`, `shopping`, `qr_payment`, `success`, `cancel`, `thank_you`

### Špecifiká
- Pre `shopping` a `qr_payment` sa payload normalizuje cez `normalize_cart_payload()`.
- Pre `qr_payment`:
  - povinné: `order_id`, `amount`
  - `order_id` musí byť 1–10 číslic (VS)
  - server doplní `payload.qr_code_base64`
  - vytvorí/aktualizuje `qr_payments` so stavom `PENDING`

### Chyby
- `400` neplatný mode/payload/chýbajúce polia
- `401` neplatný API key
- `503` API key servera nie je nakonfigurovaný
- `500` DB chyba

---

### `GET /api/display/poll.php`

### Špecifiká
- Vracia aktuálny stav z `display_state`.
- `thank_you` sa po `THANK_YOU_DURATION_SECONDS` vracia ako `idle` (bez blokujúceho sleep).
- Pri `thank_you` vracia aj `expires_in`.

---

### `GET /api/payment/check.php?order_id=...&amount=...`

### Účel
- Trigger Fio kontroly (ťažší endpoint, treba volať riedko).

### Parametre
- `order_id` (string, povinné)
- `amount` (float > 0, povinné)

### Poznámka pre integráciu
- Pre časté pollingovanie používajte `status.php`.
- `check.php` volajte typicky max raz za 30 s.

---

### `GET /api/payment/status.php?order_id=...`

### Účel
- Rýchly stav z DB bez volania Fio API.

### Vracia
- `status`, `amount`, `fio_tx_id`, `confirmed_manually`, `confirmed_by`, `created_at`, `paid_at`, `age_seconds`.
- Neexistujúca platba: `status = NOT_FOUND`.
- Staré `PENDING` sa lazy prepnú na `EXPIRED`.

---

### `POST /api/payment/manual_confirm.php`

### Body
```json
{"order_id":"12345","confirmed_by":"Pokladník","reason":"Fio timeout"}
```

### Správanie
- Platbu prepne na `PAID`, nastaví `confirmed_manually = 1`, uloží audit log.
- Prepne displej na `success`.
- Odošle e-mail notifikáciu.
- Ak je už `PAID`, odpoveď je úspešná a idempotentná (`already_paid: true`).

---

### `POST /api/payment/receipt.php`

### Body
```json
{"order_id":"12345"}
```

### Správanie
- Funguje len pre `PAID`.
- Idempotentný claim cez `receipt_printed_at`.
- Pri zlyhaní tlače sa claim uvoľní, aby bolo možné retry.
- Tlačové módy: `none` / `file` / `command`.

---

### `POST /api/payment/cancel.php`

### Body
```json
{"order_id":"12345","reason":"switched to cash","display_mode":"cancel"}
```

### Správanie
- Nastaví platbu na `CANCELLED` (ak ešte nie je `PAID`).
- Prepne displej podľa `display_mode` (`cancel`/`shopping`/`idle`).

---

## 4) Odporúčaný integračný flow

1. `update.php` (`shopping`, `phase=preview`)
2. Po zľave `update.php` (`shopping`, `phase=discounted`)
3. `update.php` (`qr_payment`)
4. Polling:
   - `status.php` každé 2–3 s
   - `check.php` max každých 30 s
5. Pri `PAID` → `receipt.php`
6. Pri timeout/fail:
   - `manual_confirm.php` alebo
   - `cancel.php`
7. Koniec predaja → `update.php` (`idle`) alebo `thank_you.php`

---

## 5) Analýza chýb a nedostatkov (aktuálny stav)

### A) Nekonzistentné rate-limit správanie v `check.php` (**vyššia priorita**)

- V dokumentácii/komentároch sa ráta so stavom `RATE_LIMITED`, ale `FioApiManager::verifyPayment()` pri limite vracia aktuálny DB status (`PENDING`/iný), nie explicitne `RATE_LIMITED`.
- Dopad: integrácia môže mylne považovať odpoveď za bežné `PENDING`, bez vedomia, že banka nebola vôbec volaná.

### B) `manual_confirm.php` a `cancel.php` aktualizujú `display_state` iba cez `UPDATE` (**vyššia priorita**)

- Na rozdiel od `update.php` a `thank_you.php` nepoužívajú UPSERT.
- Ak chýba seed riadok `display_state(id=1)`, endpoint môže vrátiť úspech, ale displej sa neprepne.
- Dopad: nekonzistentné správanie po čistej inštalácii alebo poškodenej DB.

### C) Slabšia validácia `order_id` mimo `qr_payment` (**stredná priorita**)

- `update.php` pre `qr_payment` striktne validuje VS (1–10 číslic).
- `check.php`, `status.php`, `manual_confirm.php`, `cancel.php`, `receipt.php` akceptujú prakticky ľubovoľný string.
- Dopad: vyšší šum v logoch, slabší kontrakt API pre klientov.

---

## 6) Nedokončené alebo chýbajúce funkcie (backlog návrhy)

### 1) Chýba health endpoint (**P1**)
- Nie je dedikovaný endpoint typu `/api/health` (DB pripojenie, config stav, verzia).
- Operatíva/integrácia nemá rýchly technický self-check.

### 2) Chýba server-side webhook/callback po zmene stavu platby (**P2**)
- K dispozícii je len polling.
- Pri viacerých pokladniach to zvyšuje počet dotazov a reakčný čas.

### 3) Chýba endpoint na audit históriu pre konkrétny `order_id` (**P2**)
- Dnes je audit v DB/log poli, ale bez bezpečného API pre servisné nástroje.

### 4) Chýba systematický retry orchestration pre tlač účteniek (**P2**)
- Retry je možný, ale zostáva plne na POS klientovi.
- Centrálna queue/retry politika by znížila chybovosť pri výpadkoch tlačiarne.

---

## 7) Integrácia helperov

- **osCommerce 2.2 RC2a**: `admin/oscommerce_bridge.php`, `admin/oscommerce_edit_orders.php`, `admin/oscommerce_pos_discount.php`
- **PrestaShop 8**: `admin/prestashop_bridge.php`
- **Thank-you one-shot skript**: `admin/display_thank_you.php`

Odporúčanie:
- používať helpery `*_cart_preview`, `*_cart_discounted`, `*_start_qr_payment`, `*_wait_for_payment`,
- držať `order_id` unikátne a numerické (1–10 číslic),
- nikdy nevkladať `X-API-Key` do frontend JS.
