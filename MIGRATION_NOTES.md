# Migrácia sda-mp-slideshow na Motion Page v3 / @motion.page/sdk

## Context

Motion Page vydal npm balík `@motion.page/sdk` (aktuálne v1.1.4), ktorý mení spôsob, akým sú GSAP animácie vytvárané a sprístupňované. Ak WordPress plugin Motion Page v3 používa tento SDK interne, existujúci custom plugin `sda-mp-slideshow` sa môže rozbiť, pretože závisí na priamom prístupe k GSAP timelinám cez `gsap.getById('mpSequence')`.

**Cieľ:** Identifikovať presné miesta v `sda-mp-slideshow` kde sa zmenia závislosti, a napísať defensívny kód, ktorý funguje s oboma verziami (v2 aj v3).

---

## Aktuálne závislosti (Motion Page v2.5.3)

`test-external.js` závisí na 4 konkrétnych veciach, ktoré Motion Page poskytuje:

| # | Závislosť | Súbor:riadok | Čo robí |
|---|-----------|-------------|---------|
| 1 | `window.gsap` a `gsap.getById('mpSequence')` | `test-external.js:14, 66` | Získa GSAP timeline sekvencie |
| 2 | `mpSeq.data['numSlides']`, `mpSeq.data['timeSlide'+i]` | `test-external.js:67–78` | Čita custom data (počet slidov + časy) |
| 3 | `mpSeq.parent` | `test-external.js:170` | Získa parent (hlavný) timeline pre play/pause |
| 4 | WP script handle `mp-ImageSequence` | `sda-mp-slideshow.php:9` | PHP podmienka enqueue |

---

## Čo mení `@motion.page/sdk`

### Nový API
- **`Motion(name, target?, config?)`** — namiesto `gsap.timeline({id: 'mpSequence'})`
- **`Motion.get('mpSequence')`** — namiesto `gsap.getById('mpSequence')`
- **`Motion.has('mpSequence')`** — kontrola existencie
- SDK má vlastný register timelin — **oddelený od GSAP registra**

### Čo pravdepodobne stále funguje
- Štandardné GSAP metódy (`.play()`, `.pause()`, `.addLabel()`, `.call()`, `.time()`, `.reversed()`) — GSAP je stále použité interne
- `.data` vlastnosť na timeline — GSAP toto stále podporuje
- jQuery DOM manipulácia — nezávislá od Motion Page

### Čo sa pravdepodobne rozbije
1. **`gsap.getById('mpSequence')`** — ak SDK neregistruje timeline v GSAP registri, vráti `null`
2. **`window.gsap` check** — ak SDK nebundluje GSAP globálne, `window.gsap` nemusí existovať
3. **`mp-ImageSequence` script handle** — v3 môže mať iný handle alebo bundle štruktúru

---

## Konkrétne zmeny potrebné v kóde

### Zmena 1 — `test-external.js:14` — `deferSlideshow` funkcia

**Súčasný kód:**
```javascript
if (window.jQuery && window.gsap && gsap.getById('mpSequence') && seqEnabled) {
```

**Nový kód (defensívny — funguje s v2 aj v3):**
```javascript
function getMpTimeline() {
    if (window.gsap && gsap.getById('mpSequence')) return gsap.getById('mpSequence');
    if (window.Motion && typeof Motion.has === 'function' && Motion.has('mpSequence')) return Motion.get('mpSequence');
    return null;
}

function deferSlideshow(useMethod) {
    if (window.jQuery && getMpTimeline() && seqEnabled) {
        useMethod();
    } else {
        setTimeout(function() { deferSlideshow(useMethod) }, 500);
    }
}
```

### Zmena 2 — `test-external.js:66` — získanie timeline

**Súčasný kód:**
```javascript
mpSeq = gsap.getById('mpSequence');
```

**Nový kód:**
```javascript
mpSeq = getMpTimeline();
```

### Zmena 3 — `test-external.js:170` — parent timeline

`mpSeq.parent` by mal stále fungovať, ak je `mpSeq` raw GSAP timeline. Ak SDK vracia wrapper objekt, môže byť potrebné:
```javascript
mpTL = mpSeq.parent || mpSeq; // fallback ak parent neexistuje
```

Toto treba overiť po inštalácii v3 (pozri sekciu Verifikácia).

### Zmena 4 — Zvážiť event-based inicializáciu

V `test-external.js:51–53` je zakomentovaný kód pre event `motionpage:sequence:loaded`. Ak v3 tento event stále vysiela, nahradiť polling s event listenerom je robustnejšie:

```javascript
window.addEventListener("motionpage:sequence:loaded", function(event) {
    if (!seqEnabled) return; // počkaj na minimálny delay
    initSlideshow();
});
```

Kde `initSlideshow()` je obsah aktuálneho `deferSlideshow` callback-u.

### Zmena 5 — `sda-mp-slideshow.php:9` — script handle

**Súčasný kód:**
```php
$handle = 'mp-ImageSequence';
if (wp_script_is( $handle, $list )) { ... }
```

Ak v3 premenuje handle alebo bundluje inak, aktualizovať `$handle` na nový názov. Alternatívne použiť neskorší hook ako zálohu:
```php
// Záloha: hook na wp_footer ak mp-ImageSequence nie je enqueued
add_action( 'wp_footer', 'sda_mp_slideshow_footer' );
function sda_mp_slideshow_footer() {
    if (!wp_script_is('test-external', 'done')) {
        // Enqueuenúť priamo bez závislosti na mp-ImageSequence
    }
}
```

---

## Priorita zmien (aké poradie)

1. **Najskôr (pred upgradom):** Implementovať defensívnu `getMpTimeline()` funkciu — funguje s oboma verziami
2. **Po upgrade na v3:** Overiť verifikačné kroky nižšie a podľa potreby opraviť `.parent` a PHP handle
3. **Voliteľné:** Prejsť na event-based inicializáciu ak v3 event vysiela

---

## Verifikácia po upgrade na v3

Otvoriť browser DevTools na stránke so slideshow a postupne spustiť v konzole:

```javascript
// 1. Skontrolovať či GSAP je stále globálny
console.log(window.gsap);  // undefined = GSAP nie je globálny → väčší problém

// 2. Skontrolovať GSAP register
console.log(gsap.getById('mpSequence'));  // null = závislosť 1 sa rozbíja

// 3. Skontrolovať SDK
console.log(window.Motion);  // undefined = SDK nie je globálny
console.log(Motion.get('mpSequence'));  // null = timeline nie je v SDK registri

// 4. Skontrolovať custom data
var tl = (window.gsap && gsap.getById('mpSequence')) || (window.Motion && Motion.get('mpSequence'));
console.log(tl.data);  // musí obsahovať numSlides a timeSlide1, timeSlide2...

// 5. Skontrolovať parent
console.log(tl.parent);  // musí byť timeline objekt
```

Ak `tl.data` neobsahuje custom data, znamená to že Motion Page v3 zmenil spôsob ukladania konfigurácie slidov — vyžaduje hlbšiu analýzu v3 kódu.

---

## Súbory na úpravu

- [sda-mp-slideshow/js/test-external.js](sda-mp-slideshow/js/test-external.js) — hlavné zmeny (Zmeny 1–4)
- [sda-mp-slideshow/sda-mp-slideshow.php](sda-mp-slideshow/sda-mp-slideshow.php) — prípadná zmena script handle (Zmena 5)
