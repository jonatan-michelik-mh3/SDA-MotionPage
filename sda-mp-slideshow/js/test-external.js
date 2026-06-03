// Test embedding external JS
console.log("Slideshow script loaded.");

// S.D.A. Slideshow script

var seqEnabled = false; // enable start of image sequence after minimum delay
setTimeout(function() {
		seqEnabled = true;
}, 2000);
	
//Make script execution wait until jQuery and GSAP are loaded
function deferSlideshow(useMethod) {
		var state = document.readyState;
    if (window.jQuery && window.gsap && gsap.getById('mpSequence') && seqEnabled) {
        useMethod();
    } else {
        setTimeout(function() { deferSlideshow(useMethod) }, 500);
    }
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
            mpSeq = gsap.getById('mpSequence');
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

            // add labels to MP timeline
            // mpTL = window['_mp_1678280599'];
            mpTL = mpSeq.parent;
            for (let i = 1; i <= numSlides; i++) {
                mpTL.addLabel('label' + i, timeSlide[i]);
                mpTL.call(showSlide, [i], 'label' + i);
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
                        mpTL.reversed(false);
                        mpTL.time(timeSlide[actSlide] + 0.01);
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

            function playingBackwards(animation) {
                var reversed = animation.reversed(),
                    totalTime = animation.totalTime(),
                    cycleDuration;
                if (animation.repeat && animation.yoyo() && animation.repeat() && totalTime < animation.totalDuration()) {
                    cycleDuration = animation.duration() + animation.repeatDelay();
                    if (((totalTime / cycleDuration) | 0) & 1) {
                        reversed = !reversed;
                    }
                }
                return reversed;
            }

        //});

    //});
    
});