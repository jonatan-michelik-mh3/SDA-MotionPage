# Migrácia sda-mp-slideshow na Motion Page v3 / @motion.page/sdk

## Kľúčové zistenie po inštalácii SDK

**`@motion.page/sdk` je kompletne nový, nezávislý engine — GSAP nie je zahrnutý vôbec.**

- `window.gsap` nebude existovať
- `gsap.getById('mpSequence')` nebude fungovať
- Globálne premenné v browseri: `window.Motion` a `window.MotionTimeline`

---

## Aktuálne závislosti v `test-external.js` a ich v3 ekvivalent

### 1. Získanie timeline

| v2 (GSAP) | v3 (SDK) | Stav |
|-----------|----------|------|
| `window.gsap` check | `window.Motion` check | **Zmeniť** |
| `gsap.getById('mpSequence')` | `Motion.get('mpSequence')` | **Zmeniť** |

```javascript
// v2 — nebude fungovať:
if (window.jQuery && window.gsap && gsap.getById('mpSequence') && seqEnabled)
mpSeq = gsap.getById('mpSequence');

// v3 — nový kód:
if (window.jQuery && window.Motion && Motion.has('mpSequence') && seqEnabled)
mpSeq = Motion.get('mpSequence');
```

---

### 2. Prístup k custom dátam slidov (NAJVÄČŠÍ PROBLÉM)

| v2 | v3 | Stav |
|----|-----|------|
| `mpSeq.data['numSlides']` | **Neznáme** | **Kritické** |
| `mpSeq.data['timeSlide1']` ... | **Neznáme** | **Kritické** |

SDK `Timeline` nemá žiadnu `.data` vlastnosť. Nevieme, ako Motion Page v3 bude tieto dáta vystavovať.

**Možné scenáre:**
- Motion Page v3 stále nastaví `.data` dynamicky na SDK Timeline objekte (JS to umožňuje aj bez TS definícií)
- Dáta presunú do `window.MOTIONPAGE_FRONT.sequences`
- Dáta budú v HTML `data-*` atribútoch na canvas elemente

**Overenie po upgrade na v3 (v DevTools):**
```javascript
var tl = Motion.get('mpSequence');
console.log(tl.data);                      // existuje?
console.log(tl['data']);                   // alternatívne
console.log(window.MOTIONPAGE_FRONT);      // nové polia?
console.log(document.querySelector('[data-mp-num-slides]')); // HTML atribúty?
```

---

### 3. Parent timeline

| v2 | v3 | Stav |
|----|-----|------|
| `mpTL = mpSeq.parent` | `mpTL = mpSeq` (priamo) | **Zmeniť** |

SDK `Timeline` nemá `.parent`. `mpSequence` je v v3 priamo root timeline.

```javascript
// v2:
mpTL = mpSeq.parent;

// v3:
mpTL = mpSeq; // mpSequence IS the main timeline
```

---

### 4. addLabel + call → priamo call s pozíciou

| v2 | v3 | Stav |
|----|-----|------|
| `mpTL.addLabel('label1', 2.5)` + `mpTL.call(fn, [1], 'label1')` | `mpTL.call(fn, [1], 2.5)` | **Zjednodušiť** |

SDK `Timeline.call()` akceptuje priamo čas v sekundách ako tretí parameter — `addLabel` nie je potrebný.

```javascript
// v2 (2 kroky):
mpTL.addLabel('label' + i, timeSlide[i]);
mpTL.call(showSlide, [i], 'label' + i);

// v3 (1 krok):
mpTL.call(showSlide, [i], timeSlide[i]);
```

---

### 5. Setter `reversed(false)` → `play()` + `seek()`

| v2 | v3 | Stav |
|----|-----|------|
| `mpTL.reversed(false)` (setter) | Neexistuje setter | **Zmeniť** |
| `mpTL.time(value)` | `mpTL.seek(value)` alebo `mpTL.time(value)` | Overiť |

SDK `.reversed()` je **len getter** (vracia boolean). Na "odreversovanie" treba použiť `.play()`.

```javascript
// v2 (test-external.js:187-188):
mpTL.reversed(false);
mpTL.time(timeSlide[actSlide] + 0.01);

// v3:
mpTL.play();
mpTL.seek(timeSlide[actSlide] + 0.01); // seek = skok bez spustenia
// alebo mpTL.time(timeSlide[actSlide] + 0.01) — otestovať čo funguje
```

---

### 6. `playingBackwards()` funkcia — zjednodušiť

Aktuálna funkcia (`test-external.js:298-309`) používa GSAP-špecifické metódy:

| Použitá metóda | SDK ekvivalent | Stav |
|----------------|---------------|------|
| `animation.reversed()` | `.reversed()` ✓ | OK |
| `animation.totalTime()` | **Neexistuje** | Odstrániť |
| `animation.yoyo()` | **Neexistuje** | Odstrániť |
| `animation.repeat()` (getter) | **Neexistuje** | Odstrániť |
| `animation.totalDuration()` | `.totalDuration()` ✓ | OK |
| `animation.repeatDelay()` | **Neexistuje** | Odstrániť |

SDK `.reversed()` getter by mal správne vracať smer aj počas yoyo — komplexná logika nie je potrebná.

```javascript
// v2 (komplexná GSAP logika):
function playingBackwards(animation) {
    var reversed = animation.reversed(),
        totalTime = animation.totalTime(), // neexistuje
        cycleDuration;
    if (animation.repeat && animation.yoyo() && ...) { // neexistuje
        ...
    }
    return reversed;
}

// v3 (zjednodušené):
function playingBackwards(animation) {
    return animation.reversed();
}
```

---

### 7. Timeline metódy — porovnanie

| Metóda | v2 (GSAP) | v3 (SDK) | Stav |
|--------|-----------|----------|------|
| `mpTL.play()` | ✓ | ✓ | OK |
| `mpTL.pause()` | ✓ | ✓ | OK |
| `mpTL.reverse()` | ✓ | ✓ | OK |
| `mpTL.reversed()` getter | ✓ | ✓ | OK |
| `mpTL.reversed(bool)` setter | ✓ | **Neexistuje** | Zmeniť |
| `mpTL.time()` getter | ✓ | ✓ | OK |
| `mpTL.time(value)` setter | ✓ | Overiť | Overiť |
| `mpTL.progress()` getter | ✓ | ✓ | OK |
| `mpTL.seek(value)` | — | ✓ (nový) | Použiť |
| `mpTL.addLabel()` | ✓ | **Neexistuje** | Nahradiť |
| `mpTL.call(fn, args, pos)` | ✓ | ✓ | OK |
| `mpTL.totalTime()` | ✓ | **Neexistuje** | Odstrániť |
| `mpTL.yoyo()` | ✓ | **Neexistuje** | Odstrániť |
| `mpTL.repeat()` getter | ✓ | **Neexistuje** | Odstrániť |
| `mpTL.repeatDelay()` | ✓ | **Neexistuje** | Odstrániť |
| `mpTL.parent` | ✓ | **Neexistuje** | Zmeniť |
| `.data` property | ✓ (GSAP ext.) | **Neexistuje v type def.** | Kritické |

---

### 8. PHP script handle (`sda-mp-slideshow.php`)

Aktuálne: `$handle = 'mp-ImageSequence'`

V3 pravdepodobne premenuje alebo zlúči skripty. Overiť po upgrade na v3 WordPress plugin:
```php
// Ak sa zmení handle, aktualizovať:
$handle = 'mp-ImageSequence'; // ← možno bude 'mp-motion-sdk' alebo iný
```

---

## Poradie akcií pri upgrade

1. **Pred upgradom:** Nič neinštalovať — poznač si tieto body
2. **Po upgrade v3 WordPress pluginu:** Overiť verifikačné kroky (DevTools)
3. **Podľa výsledkov:** Implementovať zmeny v kóde (viď sekciu nižšie)

---

## Verifikačné kroky v DevTools (po upgrade na v3)

```javascript
// === Krok 1: Základné globály ===
console.log('GSAP:', window.gsap);           // Malo by byť undefined
console.log('Motion:', window.Motion);        // Malo by existovať
console.log('MotionTimeline:', window.MotionTimeline); // Malo by existovať

// === Krok 2: Timeline prístup ===
console.log('has:', Motion.has('mpSequence'));       // true?
var tl = Motion.get('mpSequence');
console.log('timeline:', tl);

// === Krok 3: Custom data (KRITICKÉ) ===
console.log('tl.data:', tl.data);            // undefined alebo objekt s numSlides?
console.log('MOTIONPAGE_FRONT:', window.MOTIONPAGE_FRONT); // nové polia?

// === Krok 4: Parent (v3 by nemalo existovať) ===
console.log('parent:', tl.parent);           // undefined očakávané

// === Krok 5: Metódy ===
console.log('reversed:', tl.reversed());     // boolean?
console.log('time:', tl.time());             // číslo v sekundách?
console.log('duration:', tl.duration());     // číslo?

// === Krok 6: Script handles (v WP admin) ===
// wp_scripts()->registered — zoznam všetkých registrovaných handles
```

---

## Súbory na úpravu

- [sda-mp-slideshow/js/test-external.js](sda-mp-slideshow/js/test-external.js) — všetky JS zmeny
- [sda-mp-slideshow/sda-mp-slideshow.php](sda-mp-slideshow/sda-mp-slideshow.php) — PHP handle

---

## Kompletný prepis `test-external.js` pre v3 (template)

Po overení verifikačných krokov bude kód vyzerať takto (predpoklad: `.data` stále funguje):

```javascript
console.log("Slideshow script loaded (v3).");

var seqEnabled = false;
setTimeout(function() { seqEnabled = true; }, 2000);

function deferSlideshow(useMethod) {
    // v3: Motion namiesto gsap
    if (window.jQuery && window.Motion && Motion.has('mpSequence') && seqEnabled) {
        useMethod();
    } else {
        setTimeout(function() { deferSlideshow(useMethod) }, 500);
    }
}

deferSlideshow(function() {
    console.log("DOM is ready");

    // v3: Motion.get() namiesto gsap.getById()
    mpSeq = Motion.get('mpSequence');

    // POZOR: .data musí byť overené — možno iný zdroj dát v v3
    numSlides = mpSeq.data['numSlides'];
    timeSlide = [];
    errData = false;
    if (numSlides && numSlides > 0) {
        for (let i = 1; i <= numSlides; i++) {
            timeValue = mpSeq.data['timeSlide' + i];
            if (Number(parseFloat(timeValue)) === timeValue) {
                timeSlide[i] = timeValue;
            } else {
                errData = true;
            }
        }
    } else {
        errData = true;
    }
    if (errData) { console.log('Check MP sequence custom data!'); }

    // ... jQuery DOM kod zostáva rovnaký ...

    // v3: mpSeq IS the main timeline (no .parent)
    mpTL = mpSeq;

    // v3: call priamo s časom, bez addLabel
    for (let i = 1; i <= numSlides; i++) {
        mpTL.call(showSlide, [i], timeSlide[i]); // žiadny addLabel!
    }

    function showSlide(actSlide) {
        console.log(playingBackwards(mpTL));
        if (tweenSlideNum !== 0 && tweenSlideNum == actSlide) {
            tweenSlideNum = 0;
            tweenActive = false;
            if (playingBackwards(mpTL)) {
                // v3: play() namiesto reversed(false), seek() namiesto time()
                mpTL.play();
                mpTL.seek(timeSlide[actSlide] + 0.01);
            }
        }
        // ... zvyšok showSlide zostáva rovnaký ...
    }

    // v3: zjednodušená playingBackwards (bez GSAP-špecifickej logiky)
    function playingBackwards(animation) {
        return animation.reversed();
    }

    // ... zvyšok kódu zostáva rovnaký ...
    mpTL.play();
});
```
