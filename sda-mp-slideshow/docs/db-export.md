# Export dát z databázy (jednorazový)

> **Cieľ:** vytiahnuť z Motion.page databázy hodnoty custom property `data`
> (`numSlides`, `timeSlide1…N`) pre všetky **existujúce** sekvencie, aby sa
> zapiekli do pluginu ([../data/slideshow-data.php](../data/slideshow-data.php))
> a fungovali nezávisle od verzie Motion.page.
>
> Je to **iba čítanie (SELECT) → nulové riziko.** Napriek tomu si pred akoukoľvek
> migráciou sprav zálohu databázy.

---

## 1. Predpoklady

- Prístup do **phpMyAdmin / Adminer** (cez hosting panel).
- Vedieť **prefix tabuliek** (zvyčajne `wp_`). Zistíš ho v ľavom paneli phpMyAdmin —
  tabuľky sa volajú napr. `wp_motionpage_code`. Ak máš iný prefix, nahraď `wp_` všade nižšie.

---

## 2. Kde dáta sú (schéma)

Tabuľka `wp_motionpage_code`:

| stĺpec | význam |
|---|---|
| `post_id` | ID WordPress stránky, na ktorej sekvencia je |
| `script_value` | vygenerovaný kód — obsahuje `"timelineUID":"_mp_…"` aj `data:{numSlides:…}` |
| `data_id` | odkaz na `wp_motionpage_data` |
| `is_active` | `1` = aktívna animácia |
| `is_global` | `1` = globálna (vtedy `post_id` môže byť `NULL`) |

---

## 3. SQL dotaz

**Základný** (vráti všetko potrebné):

```sql
SELECT c.post_id, c.is_global, c.script_value
FROM wp_motionpage_code AS c
WHERE c.is_active = 1
  AND c.script_value LIKE '%numSlides%';
```

**Rozšírený** (vytiahne `timelineUID` ako samostatný stĺpec, nech to hneď vidíš):

```sql
SELECT
  c.post_id,
  c.is_global,
  SUBSTRING_INDEX(SUBSTRING_INDEX(c.script_value, '"timelineUID":"', -1), '"', 1) AS timelineUID,
  c.script_value
FROM wp_motionpage_code AS c
WHERE c.is_active = 1
  AND c.script_value LIKE '%numSlides%';
```

> Ak dotaz nič nevráti, dáta môžu byť v `wp_motionpage_data.reload`:
> ```sql
> SELECT id, script_name, reload FROM wp_motionpage_data WHERE reload LIKE '%numSlides%';
> ```
> Pošli mi výsledok z tohto a vyriešim to z neho.

---

## 4. Čo je v `script_value`

Každý riadok = jedna sekvencia. V texte sú dva dôležité kúsky:

- `"timelineUID":"_mp_1767957520"` → **jednoznačný identifikátor** sekvencie
- `data:{numSlides:3,timeSlide1:1.1,timeSlide2:2.2,timeSlide3:3.5}` → **tvoje slide dáta**

Ako sa to priradí k správnej stránke je popísané v [data-mapping.md](data-mapping.md).

---

## 5. Export výsledku

1. Spusti dotaz (záložka **SQL** → **Go** / **Vykonať**).
2. Pod výsledkami: riadok odkazov → **Export** → formát **JSON** (alebo CSV) → **Go** → stiahne sa súbor.
3. ⚠️ `script_value` je dlhý text a phpMyAdmin ho na obrazovke **oreže**. Preto použi
   **Export** (ten dá celý obsah), nie ručné kopírovanie z bunky. Ak chceš vidieť plný
   text na obrazovke, zapni hore **Options → Full texts**.

---

## 6. Pošli mne

Pošli stiahnutý JSON/CSV (alebo aspoň **1 vzorový riadok** najprv, nech potvrdím formát).
Ja z neho:

1. vyparsujem `timelineUID` + `data:{…}` + `post_id`,
2. vygenerujem obsah [../data/slideshow-data.php](../data/slideshow-data.php),
3. ty nahráš ten jeden súbor a existujúce sekvencie sú hotové.
