// Test embedding external JS
console.log("Slideshow script loaded.");

// S.D.A. Slideshow script

var seqEnabled = false; // enable start of image sequence after minimum delay
setTimeout(function() {
		seqEnabled = true;
}, 2000);
	
// Dual-engine lookup: works with the Motion.page SDK (v3) AND GSAP (v2 / v3
// legacy mode). Returns the first registered timeline, or null if not ready.
// SDK: Motion.getNames()[0] (timeline name = builder display name, e.g. 'Open Copy')
// GSAP: gsap.getById('mpSequence')
function mpGetSequence() {
    if (window.Motion && typeof Motion.getNames === 'function') {
        var names = Motion.getNames();
        return names.length > 0 ? Motion.get(names[0]) : null;
    }
    if (window.gsap && gsap.getById) return gsap.getById('mpSequence');
    return null;
}

// MP v3 bug workaround: generated_code wraps all timeline creation inside
// window.addEventListener("createImgSeq", ...) but nothing in the SDK or
// plugin dispatches this event. We dispatch it once the SDK and the
// ImageSequence class are both present.
var createImgSeqDispatched = false;

//Make script execution wait until jQuery and the animation engine are loaded
function deferSlideshow(useMethod) {
    // SDK mode (v3): wait for Motion, dispatch createImgSeq to unlock timeline
    // creation, then wait until a timeline appears in Motion.getNames().
    if (window.Motion && typeof Motion.getNames === 'function') {
        if (mpGetSequence() && window.jQuery && seqEnabled) {
            return useMethod();
        }
        if (!createImgSeqDispatched &&
            window.MOTIONPAGE_FRONT &&
            typeof window.MOTIONPAGE_FRONT.imageSequence === 'function') {
            createImgSeqDispatched = true;
            window.dispatchEvent(new CustomEvent('createImgSeq'));
            console.log('[SDA Slideshow] createImgSeq dispatched');
        }
        setTimeout(function() { deferSlideshow(useMethod); }, 500);
        return;
    }
    // GSAP mode (v2 / v3 legacy)
    if (window.jQuery && mpGetSequence() && seqEnabled) {
        return useMethod();
    }
    setTimeout(function() { deferSlideshow(useMethod) }, 500);
}

// Diagnostics — set to false to silence the source/value log in the console.
var SDA_SLIDESHOW_DEBUG = true;

// Log which data source was used + the resolved values, e.g.:
//   [SDA Slideshow] zdroj dát: baked (window.SDA_SLIDESHOW_DATA) | numSlides: 3 | časy: [1.1, 2.2, 3.5]
function sdaLogSource(source, data) {
    if (!SDA_SLIDESHOW_DEBUG) return;
    var times = [];
    if (data && data.numSlides) {
        for (var i = 1; i <= data.numSlides; i++) { times.push(data['timeSlide' + i]); }
    }
    console.log('[SDA Slideshow] zdroj dát: ' + source +
        ' | numSlides: ' + (data && data.numSlides != null ? data.numSlides : '—') +
        ' | časy: [' + times.join(', ') + ']');
}

// Resolve the sequence's custom data (numSlides, timeSlide1...). Hybrid strategy:
//   1) NEW sequences  -> Elementor data-attributes on the sequence wrapper:
//        data-mp-slides="3"   data-mp-times="1.1,2.2,3.5"
//   2) EXISTING        -> baked per-page into sda-mp-slideshow.php (window.SDA_SLIDESHOW_DATA)
//   3) LEGACY fallback -> GSAP timeline.data on the un-migrated 2.5.3 site
// All three resolve to the same { numSlides, timeSlide1... } shape used below.
function seqData(seq) {
    // 1) NEW — Elementor data-attributes (fully decoupled from Motion.page).
    var el = document.querySelector('[data-mp-slides]');
    if (el) {
        var n = parseInt(el.getAttribute('data-mp-slides'), 10);
        if (n > 0) {
            var times = (el.getAttribute('data-mp-times') || '')
                .split(',').map(function (s) { return parseFloat(s); });
            var d = { numSlides: n };
            for (var i = 1; i <= n; i++) { d['timeSlide' + i] = times[i - 1]; }
            sdaLogSource('Elementor (data-mp-slides)', d);
            return d;
        }
    }
    // 2) EXISTING — values baked per page into sda-mp-slideshow.php.
    if (window.SDA_SLIDESHOW_DATA && typeof window.SDA_SLIDESHOW_DATA === 'object') {
        sdaLogSource('baked (window.SDA_SLIDESHOW_DATA)', window.SDA_SLIDESHOW_DATA);
        return window.SDA_SLIDESHOW_DATA;
    }
    // 3) LEGACY — GSAP timeline.data (current 2.5.3 site, before migration).
    if (seq && seq.data && typeof seq.data === 'object') {
        sdaLogSource('legacy (mpSeq.data)', seq.data);
        return seq.data;
    }
    // 4) Nothing found — diagnostics.
    console.warn('[SDA Slideshow] custom data (numSlides/timeSlideN) not found.');
    console.log('  [data-mp-slides]  :', document.querySelector('[data-mp-slides]'));
    console.log('  SDA_SLIDESHOW_DATA:', window.SDA_SLIDESHOW_DATA);
    console.log('  timeline.data     :', seq && seq.data);
    return null;
}

deferSlideshow(function() {
    //jQuery and GSAP are now loaded
    //Put the motion.page animation script here
		/*
    // Store a reference to the original gsap.timeline function
    var originalTimeline = gsap.timeline;

    // Create a wrapper function for gsap.timeline
    gsap.timeline = function() {
        // Create a new Timeline instance using the original function
        var timelineInstance = originalTimeline.apply(this, arguments);

        // Trigger a custom event when a Timeline instance is created
        var event = new CustomEvent('timelineCreated', {
            detail: timelineInstance
        });
        window.dispatchEvent(event);

        // Return the newly created Timeline instance
        return timelineInstance;
    };

    // Add an event listener for the custom 'timelineCreated' event
    window.addEventListener('timelineCreated', function(event) {
        var timelineInstance = event.detail;
        console.log('Timeline created:', timelineInstance);
        // You can perform additional actions with the created timeline instance
    });
		*/
	
    //window.addEventListener("motionpage:sequence:loaded", function(event) {
    //  alert("Sequence loaded");
    //});

    // wait until DOM is loaded
    //jQuery(document).ready(function() {
        console.log("DOM is ready");

        // wait until window is loaded (images, stylesheets, links etc...)
        //
        // jQuery(window).on("load", function() {
        //jQuery(window).on('pageshow', function() { 
            //console.log("Window is loaded");
					
            // load parameters from MP image sequence
            // dual-engine: Motion.getNames()[0] (SDK) or gsap.getById() (GSAP)
            mpSeq = mpGetSequence();
            // v3: custom data resolved via seqData() (no guaranteed .data on SDK Timeline)
            var seqDataObj = seqData(mpSeq);
            numSlides = seqDataObj ? seqDataObj['numSlides'] : undefined;
            timeSlide = [];
            errData = false;
            if (numSlides && numSlides > 0) {
                for (let i = 1; i <= numSlides; i++) {
                    timeValue = seqDataObj['timeSlide' + i];
                    if (Number(parseFloat(timeValue)) === timeValue) {
                        timeSlide[i] = timeValue;
                    } else {
                        errData = true;
                    }
                }
            } else {
                errData = true;
            }
            if (errData) {
                console.log('Check MP sequence custom data!');
            }

            jQuery('[class*="loadHidden"]').css({
                'opacity': '0',
                'visibility': 'hidden'
            });

            var timerSlideActive = null;
            var timerDelay = null;
            var slideActive = false;
            var playDelay = false;
            var actSlideNum = 0;
            var clickVar = 0;
            var tweenSlideNum = 0;
            var tweenActive = false;

            function resetTimers() {
                clearTimeout(timerSlideActive);
                timerSlideActive = null;
                clearTimeout(timerDelay);
                timerDelay = null;
            }

            //jQuery('#mpAnimation').parent().append('<p id="infoBox"></p>');
						jQuery('#mpAnimation').parent().append('<div id="infoBox"></div>');

            // click event for mobile view
            for (let i = 1; i <= numSlides; i++) {
                jQuery('[class*="slide' + i + '"]').each(function(index) {
                    //infoName = 'infoBox'+i+'-'+(index+1);
                    //jQuery(this).data("infoName", infoName);
                    jQuery(this).click(function(event) {
                        event.preventDefault();
                        if (!playDelay) {
                            resetTimers();
                            //jQuery('#infoBox').text(jQuery('p:first', this).text());
                            jQuery('#infoBox').html(jQuery('div:has(> p):first', this).html());
														jQuery('#infoBox p').removeClass();
                            jQuery('[class*="slide"]').addClass('hideSlide');
                            jQuery('#infoBox').addClass('viewInfo');
                        }
                    });
                });
            }

            //jQuery('[class*="viewInfo"]').click(function (event) {
            //	event.preventDefault();
            //	jQuery('[class*="slide"]').toggleClass('hideSlide');
            //	jQuery('[class*="viewInfo"]').toggleClass('viewInfo');
            //});

            jQuery(document).on("click", "#infoBox", function() {
                event.preventDefault();
                jQuery('[class*="slide"]').removeClass('hideSlide');
                jQuery(this).removeClass('viewInfo');
                restartTime();
            });

            function restartTime() {
                resetTimers();
                actSlideClass = 'slide' + actSlideNum;
                var items = jQuery('[class*=' + actSlideClass + ']');
                timerSlideActive = setTimeout(function() {
                    for (var i = 0; i < items.length; i++) {
                        var item = items.eq(i);
                        var delay = i / 10;
                        item.css({
                            'transition-delay': 0 + "s",
                            'animation-delay': 0 + "s"
                        });
                    };
                    items.removeClass('elasticObject');
                    items.removeClass('active');
                    playDelay = true;
                    timerDelay = setTimeout(function() {
                        mpTL.play();
                        slideActive = false;
                        actSlideNum = 0;
                        playDelay = false;
                        jQuery('[id*="btnSlide"]').removeClass('activeSlide');
                    }, 500);
                }, 3500);
            }

            // schedule slide callbacks on the MP timeline
            // GSAP: the controllable timeline is mpSeq.parent (the main timeline).
            // SDK: mpSequence IS the root timeline (no .parent), so fall back to it.
            mpTL = mpSeq.parent || mpSeq;
            // call() takes the time (seconds) directly as its position argument in
            // both engines, so the GSAP-era addLabel() step is no longer needed.
            for (let i = 1; i <= numSlides; i++) {
                mpTL.call(showSlide, [i], timeSlide[i]);
            }

            //console.log(mpTL);
            //mpTL.play();
            //pauseSlide = false;

            function showSlide(actSlide) {
                console.log(playingBackwards(mpTL));
                if (tweenSlideNum !== 0 && tweenSlideNum == actSlide) {
                    tweenSlideNum = 0;
                    tweenActive = false;
                    //mpTL.play();
                    if (playingBackwards(mpTL)) {
                        // play(from) works in BOTH engines: sets forward direction
                        // AND resumes from the given time in one call (replaces the
                        // GSAP-only reversed(false) + time(value) pair).
                        mpTL.play(timeSlide[actSlide] + 0.01);
                    }
                }
                if (!tweenActive && !playingBackwards(mpTL) && (clickVar == actSlide || clickVar == 0)) {
                    mpTL.pause();
                    //resetTimers();
                    actSlideNum = actSlide;
                    if (clickVar == actSlide) {
                        clickVar = 0;
                    }
                    for (var i = 1; i <= numSlides; i++) {
                        if (i == actSlideNum) {
                            jQuery('#btnSlide' + i).addClass('activeSlide');
                        } else {
                            jQuery('#btnSlide' + i).removeClass('activeSlide');
                        }
                    };
                    actSlideClass = 'slide' + actSlide;
                    //console.log(mpSeq.timeline);
                    console.log(actSlideClass);
                    var items = jQuery('[class*=' + actSlideClass + ']');
                    //jQuery('[class*='+actSlideClass+']').toggleClass('active');
                    if (!slideActive || clickVar == actSlide) {
                        slideActive = true;
                        resetTimers();
                        //var items = jQuery('[class*='+actSlideClass+']');
                        for (var i = 0; i < items.length; i++) {
                            var item = items.eq(i);
                            var delay = i / 10;
                            item.css({
                                'transition-delay': delay + "s",
                                'animation-delay': delay + "s"
                            });
                        };
                        items.stop(true, true).addClass('active elasticObject');
                        timerSlideActive = setTimeout(function() {
                            for (var i = 0; i < items.length; i++) {
                                var item = items.eq(i);
                                var delay = i / 10;
                                item.css({
                                    'transition-delay': 0 + "s",
                                    'animation-delay': 0 + "s"
                                });
                            };
                            items.removeClass('elasticObject');
                            items.removeClass('active');
                            playDelay = true;
                            timerDelay = setTimeout(function() {
                                mpTL.play();
                                slideActive = false;
                                actSlideNum = 0;
                                playDelay = false;
                                jQuery('[id*="btnSlide"]').removeClass('activeSlide');
                            }, 500);
                        }, 4500);
                    }
                }
            }
						jQuery('div.elementor-widget-html:has(* div.sda-loader-ring)').hide();
						jQuery('[id*="btnSlide"]').addClass('clickSlide');
            mpTL.play();

            for (let btn = 1; btn <= numSlides; btn++) {
                jQuery('#btnSlide' + btn).click(function(event) {
                    console.log(slideActive + ' ' + clickVar + ' ' + actSlideNum);
                    event.preventDefault();
                    //clickVar = btn;
                    if (!playDelay && btn !== actSlideNum) {
                        resetTimers();
                        clickVar = btn;
                        if (slideActive) {
                            jQuery('[class*="slide"]').removeClass('hideSlide');
                            jQuery('#infoBox').removeClass('viewInfo');
                            //var items = jQuery('[class*="slide"].active');
                            var items = jQuery('[class*="slide"]');
                            for (var i = 0; i < items.length; i++) {
                                var item = items.eq(i);
                                item.css({
                                    'transition-delay': 0 + "s",
                                    'animation-delay': 0 + "s"
                                });
                            }
                            items.removeClass('elasticObject');
                            items.removeClass('active');
                            slideActive = false;
                            actSlideNum = 0;
                        }
                        if (!slideActive) {
                            tweenSlideNum = btn;
                            tweenActive = true;
                            if (timeSlide[btn] < mpTL.time()) {
                                mpTL.reverse();
                            } else {
                                mpTL.play();
                            }
                            //mpTL.tweenTo('label'+btn);
                            timerDelay = setTimeout(function() {
                                jQuery('#infoBox').removeClass('viewInfo');
                                jQuery('[class*="slide"]').removeClass('hideSlide');
                                jQuery('[class*="slide"]').removeClass('elasticObject');
                                jQuery('[class*="slide"]').removeClass('active');
                                jQuery('[id*="btnSlide"]').removeClass('activeSlide');
                            }, 100);
                        }
                    } else {
                        clickVar = 0;
                    }
                });
            };

            // Direction check, dual-engine. GSAP yoyo timelines flip direction each
            // repeat cycle, so we correct for that using GSAP-only methods. The SDK
            // has none of them (and no yoyo), so the typeof guards make this collapse
            // to `return animation.reversed()` there.
            function playingBackwards(animation) {
                var reversed = animation.reversed();
                if (typeof animation.totalTime === 'function' &&
                    typeof animation.yoyo === 'function' &&
                    typeof animation.repeat === 'function' &&
                    animation.yoyo() && animation.repeat()) {
                    var totalTime = animation.totalTime();
                    if (totalTime < animation.totalDuration()) {
                        var cycleDuration = animation.duration() +
                            (typeof animation.repeatDelay === 'function' ? animation.repeatDelay() : 0);
                        if (((totalTime / cycleDuration) | 0) & 1) {
                            reversed = !reversed;
                        }
                    }
                }
                return reversed;
            }

        //});

    //});
    
});