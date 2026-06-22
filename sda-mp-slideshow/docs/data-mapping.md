# Identifikácia: ktoré slide-dáta patria ktorej animácii / stránke

> Model: **počet slidov a časy sú vlastnosťou ANIMÁCIE, nie stránky.** Stránka
> animáciu len zobrazuje. Jedna animácia môže byť na viacerých stránkach (SK + EN).

---

## Tri entity

| entita | identifikátor | príklad | poznámka |
|---|---|---|---|
| **Animácia** | **`timelineUID`** | `_mp_1767957520` | **nositeľ dát** (`numSlides`, `timeSlideN`) |
| Stránka / kategória | slug (`product_cat` term / post) | `otvorene-systemy` | len zobrazuje animáciu |
| Slide-dáta | `numSlides`, `timeSlideN` | `3` ; `1.1, 2.2, 3.5` | patria animácii |

`timelineUID` je názov parametra v generovanom kóde; jeho hodnota (napr.
`_mp_1767957520`) je pre každú animáciu iná a je **jednoznačná a stabilná**.

---

## Štruktúra dátového súboru

[../data/slideshow-data.php](../data/slideshow-data.php) má dve časti:

```php
return array(
    // 1) DÁTA — kľúč = timelineUID (jeden záznam na animáciu)
    'animations' => array(
        '_mp_1767957520' => array('numSlides'=>3, 'timeSlide1'=>1.1, 'timeSlide2'=>2.2, 'timeSlide3'=>3.5),
        // ...
    ),
    // 2) ROUTING — ktorá animácia je na ktorej stránke
    'pages' => array(
        'by_slug' => array(                       // primárne: podľa slug-u (post_name)
            'otvorene-systemy'      => '_mp_1767957520',
            'open-en'               => '_mp_1767957520',
            'open-conductor-system' => '_mp_1767957520',
        ),
        'by_post' => array(                       // fallback: podľa post_id
            4657 => '_mp_1767957520',
        ),
    ),
);
```

- **`animations`** — dáta existujú **raz na animáciu** (žiadna duplikácia, aj keď je
  animácia na 3 stránkach). Keď zmeníš časy, meníš ich na jednom mieste.
- **`pages`** — len mapuje stránku → `timelineUID`.

---

## Prečo routing podľa slug-u (a nie `post_id`)

Animácie prideľuješ k **WooCommerce produktovým kategóriám** (`product_cat`) — to sú
taxonomické **termy** s vlastným slug-om, nie posty. V exporte sú ich slugy v stĺpci
`cats`, zvyčajne **SK + EN pár** (napr. `/otvorene-systemy/` + `/open-en/` +
`/open-conductor-system/`), s **rôznymi id**. Preto:

- **primárne podľa slug-u** (term slug pre kategórie, `post_name` pre stránky) —
  pokryje všetky jazykové varianty,
- **id ako fallback** (`get_queried_object_id()` = term_id alebo post ID) — najmä pre
  staré riadky bez URL zoznamu (`cats=''`).

---

## Runtime (ako to beží)

V [../sda-mp-slideshow.php](../sda-mp-slideshow.php), funkcia `sda_mp_slideshow_current_data()`:

1. `get_queried_object()` → aktuálna entita. **WooCommerce produktové kategórie sú
   taxonómia `product_cat`, čiže `WP_Term`** → slug = `$term->slug`. Bežná stránka =
   `WP_Post` → slug = `$post->post_name`.
2. `pages.by_slug[slug]` → **`timelineUID`** (kategórie aj stránky). Fallback:
   `pages.by_post[ get_queried_object_id() ]` (term_id alebo post ID).
3. `animations[timelineUID]` → `{ numSlides, timeSlideN }`,
4. injektne sa ako `window.SDA_SLIDESHOW_DATA`.

`test-external.js` → `seqData()` to prečíta (priorita: Elementor data-atribúty →
`SDA_SLIDESHOW_DATA` → legacy `mpSeq.data`).

---

## Nová sekvencia / kategória (workflow)

Obsah kategórií je **per-kategória** (každá má vlastnú „Slideshow" sekciu v Elementore) —
potvrdené. Preto pre **novú** sekvenciu netreba meniť plugin ani DB:

1. Postav kategóriu a jej Slideshow sekciu v Elementore ako zvyčajne.
2. Na **Slideshow (section)** pridaj Custom Attributes:
   - `data-mp-slides|N`        — počet slidov
   - `data-mp-times|t1,t2,…`   — N časov zastavení (v poradí slide 1…N)
3. Hotovo — `seqData()` ich číta s **najvyššou prioritou** (pred baked aj legacy).

Existujúce (30) kategórie ostávajú na baked (`data/slideshow-data.php`). Voliteľne ich
vieš postupne presunúť na Elementor atribúty (a ich baked záznam zmazať), ale netreba.

---

## Hraničné prípady / na overenie

- **Slug routing — ✅ overené naživo:** `$term->slug` (napr. `otvorene-systemy`) sa
  porovnáva s kľúčmi v `by_slug`. Potvrdené na živom webe (`product_cat` archív, slug
  sedí). Hĺbka URL nehrá rolu — slug je vždy posledný segment.
- **`by_post` je len pre legacy:** pri produktových kategóriách export `post_id` ≠ term_id
  (potvrdené: term_id `228` vs export `post_id` `4657`), takže kategórie routujú **iba
  podľa slug-u**. `by_post` ostáva len pre 2 staré riadky bez slug-u (`cats=''`).
- **Legacy riadky (`post_id` 2897, 6432):** staré formáty kódu, default hodnoty
  `1.1/2.2/3.5`, `cats=''`. Sú v `by_post`. **Over, či tie stránky ešte používajú
  slideshow** — ak nie, vyhoď ich z `animations` aj `pages.by_post`.
- **Duplicitný `post_id` 4657:** v exporte bol dvakrát (starý `_mp_1702311923` +
  aktuálny `_mp_1767957520`) s rovnakými dátami. Použitý je aktuálny `_mp_1767957520`.
- **Max. jedna animácia na stránku:** potvrdené — preto stačí vrátiť jednu sadu dát
  na stránku.

---

## Aktuálny príklad

Animácia `_mp_1767957520` (Open systems) má `{numSlides:3, timeSlide1:1.1, …}` a je na
stránkach `otvorene-systemy`, `open-en`, `open-conductor-system`. V dátovom súbore je
dáta **raz** v `animations`, a tri slug riadky v `pages.by_slug` naň ukazujú.
