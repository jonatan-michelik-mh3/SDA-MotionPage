# Migrácia `sda-mp-slideshow` na Motion Page v3 / @motion.page/sdk

> **Stav: NASADENÉ A OVERENÉ na živom GSAP webe.** Plugin funguje v **GSAP** (v2.5.3 / v3 legacy) **aj SDK** (v3) režime.
> Dátum: 2026-06-18 · SDK `@motion.page/sdk` **1.2.1** · MP WordPress plugin v3 = **3.2.0**
>
> ✅ Overené naživo: routing podľa `product_cat` slug-u, a všetky 3 zdroje dát — **Elementor**, **baked**, **legacy**.
> Zostáva: (1) doriešiť 2 legacy stránky 2897/6432, (2) samotná migrácia na v3 (keď budeš chcieť) + re-test v SDK režime. Viď koniec.

---

## TL;DR — čo a prečo

Motion Page prešiel z GSAP na vlastný SDK. Tvoj slideshow plugin závisel od `gsap.getById('mpSequence')`,
timeline metód a custom dát `mpSeq.data` (počet slidov + časy zastavení). Riešenie:

1. **`test-external.js` je dual-mode** — deteguje GSAP aj SDK a funguje v oboch.
2. **Slideshow dáta sú odpojené od Motion Page** (vlastníš ich):
   - **existujúce** → vyexportované z DB, zapečené v plugine, **kľúčované podľa animácie (`timelineUID`)**,
   - **nové** → Elementor data-atribúty (`data-mp-slides` / `data-mp-times`).

Tým je plugin **nezávislý od verzie Motion Page**.

---

## Motion Page v3 (plugin 3.2.0) — ako to funguje

Analyzované z lokálnej kópie [motionpage-v3/](motionpage-v3/):

- **Hybrid — dva režimy** ([wp/App/Frontend/Enqueue.php](motionpage-v3/wp/App/Frontend/Enqueue.php)):
  - **Legacy GSAP** — načíta `mp-gsap` (GSAP je stále v balíku),
  - **SDK** — načíta `mp-motion-sdk` (bundle z `wp-content/uploads/`).
  - Rozhoduje `$sdk_bundle_url`.
- **Inštalácia ≠ migrácia.** Upgrade pluginu **nespúšťa** migráciu — len zobrazí admin notice. Reálne sa migruje
  **až otvorením buildera** (`useAutoMigrate`). Pôvodný `script_value` (GSAP) **ostáva v DB** vedľa nového
  `generated_code` → rollback cez DB backup je možný.
- **Script handle ostáva `mp-ImageSequence`** v oboch režimoch ([Enqueue.php:810](motionpage-v3/wp/App/Frontend/Enqueue.php#L810)).
  ✅ **Tvoj PHP netreba meniť** (pôvodný otáznik vyriešený).
- Globál `window.MOTIONPAGE_FRONT` existuje (nesie `.bp`, `.imageSequence`, `.isw`).

> **Bezpečné nasadenie:** keďže inštalácia v3 nemigruje, web ostane v GSAP režime a tvoj plugin beží ďalej.
> „Bod zlomu" je otvorenie buildera, nie inštalácia.

---

## Custom data — prečo sme ich odpojili

Custom property `data` si v MP editore zadal cez **Custom / Advanced Code Field** ako GSAP `data` property.
GSAP vystavoval `vars.data` ako `timeline.data` — preto `mpSeq.data` fungovalo. **SDK to nemá:**

- SDK `Timeline` nemá `.data` ([Timeline.d.ts](node_modules/@motion.page/sdk/dist/core/Timeline.d.ts)),
- SDK `Animation` si vars neuchováva čitateľne — mení ich na render-only `PropTween`-y ([Animation.d.ts](node_modules/@motion.page/sdk/dist/core/Animation.d.ts)),
- v3 editor Custom Field zostáva, ale `data:{…}` sa už nedá prečítať späť.

→ Po migrácii by sa `numSlides`/`timeSlideN` **stratili**. Preto sme ich **vyexportovali a zapiekli** do pluginu
(model: dáta patria **animácii**, nie stránke — viď [docs/data-mapping.md](sda-mp-slideshow/docs/data-mapping.md)).

Pre **nové** animácie: SDK vie čítať CSS premenné (`--var`), ale zvolili sme robustnejšie **Elementor
data-atribúty** (`data-mp-slides`, `data-mp-times`) — úplne nezávislé od MP.

---

## `test-external.js` — dual-mode (GSAP + SDK)

Engine sa deteguje cez `mpGetSequence()`:

```javascript
function mpGetSequence(id) {
    if (window.Motion && Motion.get) return Motion.get(id);   // v3 SDK
    if (window.gsap && gsap.getById) return gsap.getById(id);  // v2 / legacy GSAP
    return null;
}
```

Väčšina API je zdieľaná (SDK je zámerne GSAP-kompatibilné), vetviť bolo treba len:

| miesto | GSAP | SDK | riešenie |
|---|---|---|---|
| získanie timeline | `gsap.getById` | `Motion.get` | `mpGetSequence()` |
| hlavná timeline | `mpSeq.parent` | `mpSeq` (root) | `mpSeq.parent \|\| mpSeq` |
| `playingBackwards` | yoyo logika (`totalTime/yoyo/repeat`) | len `reversed()` | `typeof` guard |

Zdieľané v oboch: `play()`, `play(from)`, `pause()`, `reverse()`, `time()`, `reversed()`, `call(fn,args,čas)`.

### `seqData()` — zdroj dát (priorita)

1. **Nové** → Elementor `[data-mp-slides]` / `[data-mp-times]`
2. **Existujúce** → `window.SDA_SLIDESHOW_DATA` (injektne PHP plugin)
3. **Legacy** → `mpSeq.data` (kým je MP v GSAP režime)

Všetky vracajú rovnaký tvar `{ numSlides, timeSlideN }`.

---

## Architektúra pluginu

```
sda-mp-slideshow/
├── sda-mp-slideshow.php      # enqueue + routing stránka→timelineUID→dáta → inject
├── data/
│   └── slideshow-data.php    # zapečené dáta: animations (by timelineUID) + pages (routing)
├── js/
│   └── test-external.js      # dual-mode slideshow logika
└── docs/
    ├── db-export.md          # návod na DB export
    └── data-mapping.md       # model dáta↔animácia↔stránka
```

**Routing (server-side, PHP):** `kategória/stránka → slug → timelineUID → dáta animácie → window.SDA_SLIDESHOW_DATA`.
Animácie sú priradené k **WooCommerce produktovým kategóriám** (`product_cat` = `WP_Term`), preto routing **podľa slug-u**
(`$term->slug`), cez `get_queried_object()`. ✅ overené (term `otvorene-systemy`). Export `post_id` ≠ term_id (228 vs 4657),
takže `by_post` je len fallback pre 2 staré riadky bez slug-u.

Zapečené z exportu: **30 animácií + 2 legacy** ([wp_Ekupa97M_motionpage_code.sql](wp_Ekupa97M_motionpage_code.sql)).

---

## Overené SDK API (referencia, proti `Timeline.d.ts` / `Motion.d.ts`)

Funguje v oboch enginoch, ak nie je uvedené inak:

| Metóda | GSAP | SDK | pozn. |
|--------|------|-----|------|
| `play()` / `play(from)` | ✓ | ✅ `play(from?: number)` | |
| `pause()` / `reverse()` / `restart()` | ✓ | ✅ | |
| `reversed()` getter | ✓ | ✅ `reversed(): boolean` | |
| `reversed(bool)` **setter** | ✓ | ❌ | → `play(from)` |
| `time()` get/set | ✓ | ✅ `time()` / `time(v)` | |
| `seek(value)` | — | ✅ | |
| `progress()` / `timeScale()` / `duration()` / `totalDuration()` / `isActive()` | ✓ | ✅ | |
| `call(fn, args, pos)` | ✓ | ✅ `pos: string \| number` | číselná pozícia → bez `addLabel` |
| `addLabel()` | ✓ | ❌ | nahradené číselnou pozíciou |
| `totalTime()` / `yoyo()` / `repeat()` / `repeatDelay()` | ✓ | ❌ | v `playingBackwards` cez `typeof` guard |
| `.parent` | ✓ | ❌ | `mpSeq.parent \|\| mpSeq` |
| `.data` | ✓ (GSAP ext.) | ❌ | → odpojené (baked / Elementor) |

**Motion API:** `Motion(name)` (factory), `Motion.get(name)` → `Timeline|undefined`, `Motion.has(name)`,
`Motion.getNames()`, `Motion.kill/killAll/cleanup`.

---

## ✅ Vykonané

- SDK `@motion.page/sdk` 1.1.4 → **1.2.1** ([package.json](package.json)). (Doinštalovaný Node.js 24.16.0.)
- [test-external.js](sda-mp-slideshow/js/test-external.js): dual-mode, `seqData()` hybrid, `playingBackwards` guard. `node --check` ✅
- [sda-mp-slideshow.php](sda-mp-slideshow/sda-mp-slideshow.php) (v1.2): routing + injekcia dát; handle ostal `mp-ImageSequence`.
- [data/slideshow-data.php](sda-mp-slideshow/data/slideshow-data.php): 30 animácií + 2 legacy, animation-centric.
- Docs: [db-export.md](sda-mp-slideshow/docs/db-export.md), [data-mapping.md](sda-mp-slideshow/docs/data-mapping.md).

---

## ✅ Overené naživo (GSAP web, plugin nasadený)

- **Routing** podľa `product_cat` slug-u (`get_queried_object()->slug`) — sedí.
- **Všetky 3 zdroje dát** cez `seqData()`: **Elementor** data-atribúty (per-kategória, pre nové sekvencie),
  **baked** `window.SDA_SLIDESHOW_DATA` (existujúce), **legacy** `mpSeq.data` (poistka). Diagnostika to potvrdila.

## ⏳ Zostáva

1. **Legacy stránky `post_id` 2897, 6432** — zistiť čo sú zač a či ešte majú slideshow; inak vyhoď z dátového súboru.
2. **Migrácia na v3** (keď budeš chcieť): backup DB + ZIP 2.5.3 → otvor builder (spustí auto-migráciu) → re-test
   debugom (engine má byť `SDK`, `mp-motion-sdk` ✓, zdroj `baked`/`Elementor`). Legacy `mpSeq.data` po migrácii zmizne,
   ale baked/Elementor to pokryjú.
3. **Cleanup** po overení: deaktivuj `sda-mp-debug` plugin a v `test-external.js` daj `SDA_SLIDESHOW_DEBUG = false`.
