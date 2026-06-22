// S.D.A. Slideshow — Debug. Step-by-step console diagnostics that mirror the
// detection logic of sda-mp-slideshow, so we can verify each piece and spot where
// things break. Prints an immediate snapshot, then again once the timeline is ready.

(function () {
    'use strict';

    var TAG = '[SDA DEBUG]';
    var mark = function (b) { return b ? '✓' : (b === null ? '?' : '✗'); };

    // Engine detection — same order as the SDA plugin (SDK preferred, GSAP fallback).
    function detectEngine() {
        if (window.Motion && typeof window.Motion.get === 'function') return 'SDK';
        if (window.gsap && typeof window.gsap.getById === 'function') return 'GSAP';
        return 'none';
    }

    function getSequence() {
        try {
            if (window.Motion && Motion.get) return Motion.get('mpSequence') || null;
            if (window.gsap && gsap.getById) return gsap.getById('mpSequence') || null;
        } catch (e) {}
        return null;
    }

    // Pull a readable times[] out of a { numSlides, timeSlideN } object.
    function timesOf(d) {
        var t = [];
        if (d && d.numSlides) { for (var i = 1; i <= d.numSlides; i++) { t.push(d['timeSlide' + i]); } }
        return t;
    }

    // Which source would sda seqData() use? (same priority order)
    function pickSource(seq) {
        var el = document.querySelector('[data-mp-slides]');
        if (el && parseInt(el.getAttribute('data-mp-slides'), 10) > 0) return 'Elementor (data-mp-slides)';
        if (window.SDA_SLIDESHOW_DATA && typeof window.SDA_SLIDESHOW_DATA === 'object') return 'baked (window.SDA_SLIDESHOW_DATA)';
        if (seq && seq.data && typeof seq.data === 'object') return 'legacy (mpSeq.data)';
        return '⚠️ ŽIADNY — dáta sa nenašli!';
    }

    function bodyInfo() {
        var cls = [].slice.call(document.body.classList);
        var slug = (cls.filter(function (c) { return /^term-/.test(c) && !/^term-\d+$/.test(c); })[0] || '').replace(/^term-/, '');
        var tid = (cls.filter(function (c) { return /^term-\d+$/.test(c); })[0] || '').replace(/^term-/, '');
        return { product_cat: document.body.classList.contains('tax-product_cat'), term_slug: slug, term_id: tid };
    }

    function report(phase, collapsed) {
        try {
            var seq = getSequence();
            var engine = detectEngine();
            var php = window.SDA_DEBUG_PHP || {};
            var b = bodyInfo();
            var el = document.querySelector('[data-mp-slides]');
            var wrap = document.querySelector('#mpAnimation');

            (collapsed ? console.groupCollapsed : console.group).call(console, TAG + ' stav — ' + phase);

            console.log('%c1) Knižnice / enginy', 'font-weight:bold');
            console.log('  ' + mark(!!window.jQuery), 'jQuery', window.jQuery && window.jQuery.fn ? '(' + window.jQuery.fn.jquery + ')' : '');
            console.log('  ' + mark(!!window.gsap), 'GSAP', window.gsap ? '(' + window.gsap.version + ')' : '');
            console.log('  ' + mark(!!window.Motion), 'Motion SDK', window.Motion && Motion.getNames ? '(names: ' + JSON.stringify(Motion.getNames()) + ')' : '');
            console.log('  ' + mark(!!window.MotionTimeline), 'MotionTimeline');
            console.log('  ' + mark(!!window.MOTIONPAGE_FRONT), 'MOTIONPAGE_FRONT');
            console.log('  → detegovaný engine:', engine);

            console.log('%c2) Stránka (server-side)', 'font-weight:bold');
            console.log('  queried object:', php.queried_type || '(n/a)');
            console.log('  slug:', php.queried_slug || '(žiadny)', '| id:', php.queried_id);
            console.log('  is_product_category():', php.is_product_cat);
            console.log('  body → product_cat:', mark(b.product_cat), '| term slug:', b.term_slug || '(žiadny)', '| term id:', b.term_id || '(žiadny)');

            console.log('%c3) Scripty', 'font-weight:bold');
            console.log('  [server @wp_enqueue_scripts]');
            console.log('    ' + mark(php.mp_imagesequence), 'mp-ImageSequence  (MP sekvencia na stránke)');
            console.log('    ' + mark(php.mp_gsap), 'mp-gsap           (MP legacy GSAP režim)');
            console.log('    ' + mark(php.mp_motion_sdk), 'mp-motion-sdk     (MP SDK režim)');
            console.log('    ' + mark(php.sda_test_external), 'test-external     (pozn.: starý plugin enqueueuje neskôr @wp_print_scripts → tu môže byť ✗)');
            console.log('  [runtime DOM — spoľahlivý check]');
            var sdaScript = document.querySelector('script[src*="test-external"]');
            console.log('    ' + mark(!!sdaScript), 'test-external.js načítané', sdaScript ? '(' + sdaScript.src + ')' : '');

            console.log('%c4) Timeline', 'font-weight:bold');
            console.log('  ' + mark(!!seq), "mpSequence timeline (engine: " + engine + ")");
            if (seq) {
                try { console.log('    reversed():', seq.reversed && seq.reversed(), '| time():', seq.time && seq.time(), '| duration():', seq.duration && seq.duration()); } catch (e) {}
                console.log('    .data (legacy):', seq.data || '(žiadne)');
            }

            console.log('%c5) Dáta pre slideshow', 'font-weight:bold');
            console.log('  ' + mark(!!el), 'Elementor [data-mp-slides]:', el ? el.getAttribute('data-mp-slides') : '—', '| [data-mp-times]:', el ? el.getAttribute('data-mp-times') : '—');
            console.log('  ' + mark(!!window.SDA_SLIDESHOW_DATA), 'baked window.SDA_SLIDESHOW_DATA:', window.SDA_SLIDESHOW_DATA || '—', window.SDA_SLIDESHOW_DATA ? '→ časy ' + JSON.stringify(timesOf(window.SDA_SLIDESHOW_DATA)) : '');
            console.log('  ' + mark(!!(seq && seq.data)), 'legacy mpSeq.data:', (seq && seq.data) || '—');
            console.log('  %c→ seqData() by vybral: ' + pickSource(seq), 'font-weight:bold;color:#0a7');

            console.log('%c6) DOM', 'font-weight:bold');
            var hasCanvas = !!(wrap && (wrap.tagName === 'CANVAS' || (wrap.querySelector && wrap.querySelector('canvas'))));
            console.log('  ' + mark(!!wrap), '#mpAnimation:', wrap ? wrap.tagName : '—', '| canvas vnútri:', mark(hasCanvas));
            console.log('  ' + mark(!!document.querySelector('#infoBox')), '#infoBox (snapshot — slideshow ho tvorí až po ~2 s; definitívny check je poll nižšie)');
            console.log('  [class*="slide"] prvkov:', document.querySelectorAll('[class*="slide"]').length);
            console.log('  [id*="btnSlide"] tlačidiel:', document.querySelectorAll('[id*="btnSlide"]').length);

            console.groupEnd();
        } catch (err) {
            console.error(TAG + ' chyba pri reporte (' + phase + '):', err);
        }
    }

    // Immediate snapshot (collapsed — early state).
    report('hneď pri načítaní', true);

    // Deferred snapshot — wait for engine + timeline (mirrors SDA deferSlideshow), max ~10 s.
    var tries = 0, MAX = 20;
    (function waitReady() {
        if (window.jQuery && detectEngine() !== 'none' && getSequence()) {
            report('po pripravení timeline', false);
        } else if (tries++ < MAX) {
            setTimeout(waitReady, 500);
        } else {
            console.warn(TAG + ' mpSequence sa nepripravil do ' + (MAX * 0.5) + ' s — posledný stav:');
            report('timeout', false);
        }
    })();

    // #infoBox is created by test-external.js only AFTER its 2s seqEnabled gate,
    // so poll for it separately — otherwise the early snapshot shows a misleading ✗.
    var ib = 0;
    (function waitInfoBox() {
        if (document.querySelector('#infoBox')) {
            console.log('%c' + TAG + ' ✓ #infoBox sa objavil → slideshow JS bežal (po ~' + (ib * 0.5) + ' s).', 'color:#0a7');
        } else if (ib++ < 16) {
            setTimeout(waitInfoBox, 500);
        } else {
            console.warn(TAG + ' ✗ #infoBox sa neobjavil ani do 8 s → slideshow JS pravdepodobne nebežal (skontroluj deferSlideshow / engine).');
        }
    })();
})();
