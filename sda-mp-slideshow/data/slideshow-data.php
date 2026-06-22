<?php
/**
 * S.D.A. Slideshow — baked sequence data (EXISTING animations).
 *
 * Generated from a ONE-TIME export of wp_Ekupa97M_motionpage_code (the GSAP
 * `data` custom property: numSlides + timeSlideN). See docs/db-export.md and
 * docs/data-mapping.md.
 *
 * MODEL: the slide data belongs to the ANIMATION (identified by `timelineUID`),
 * not to the page. So:
 *   - `animations` is keyed by timelineUID — the data lives here, once each.
 *   - `pages` only routes a page (slug or post_id) to the timelineUID shown on it.
 * One animation can appear on several pages (e.g. SK + EN), all pointing to the
 * same timelineUID.
 *
 * @return array{
 *   animations: array<string, array<string, int|float>>,
 *   pages: array{by_slug: array<string,string>, by_post: array<int,string>}
 * }
 */

return array(

	// === DATA — keyed by timelineUID (one entry per animation) ===
	'animations' => array(
		'_mp_1702044797' => array('numSlides' => 4, 'timeSlide1' => 0.6,  'timeSlide2' => 1.35, 'timeSlide3' => 2.7,  'timeSlide4' => 3.8),                      // MKH
		'_mp_1702312396' => array('numSlides' => 3, 'timeSlide1' => 1,    'timeSlide2' => 2,    'timeSlide3' => 3.8),                                             // U10
		'_mp_1702463476' => array('numSlides' => 3, 'timeSlide1' => 1,    'timeSlide2' => 2,    'timeSlide3' => 3.9),                                             // U15
		'_mp_1702464273' => array('numSlides' => 3, 'timeSlide1' => 1,    'timeSlide2' => 2,    'timeSlide3' => 3.9),                                             // U20
		'_mp_1702465111' => array('numSlides' => 5, 'timeSlide1' => 0.5,  'timeSlide2' => 1.3,  'timeSlide3' => 2.5,  'timeSlide4' => 3.75, 'timeSlide5' => 4.8), // VCL
		'_mp_1702466691' => array('numSlides' => 3, 'timeSlide1' => 0.9,  'timeSlide2' => 2.1,  'timeSlide3' => 3.8),                                             // VKS
		'_mp_1702467039' => array('numSlides' => 3, 'timeSlide1' => 1,    'timeSlide2' => 2.05, 'timeSlide3' => 3.9),                                             // VKS10
		'_mp_1702811009' => array('numSlides' => 4, 'timeSlide1' => 0.6,  'timeSlide2' => 1.3,  'timeSlide3' => 2.5,  'timeSlide4' => 3.9),                       // KBH
		'_mp_1702811602' => array('numSlides' => 3, 'timeSlide1' => 1,    'timeSlide2' => 2.1,  'timeSlide3' => 3.9),                                             // LSV
		'_mp_1702812516' => array('numSlides' => 2, 'timeSlide1' => 2.5,  'timeSlide2' => 3.9),                                                                   // Large festoon
		'_mp_1702812863' => array('numSlides' => 2, 'timeSlide1' => 2.5,  'timeSlide2' => 3.9),                                                                   // Small festoon
		'_mp_1702813178' => array('numSlides' => 3, 'timeSlide1' => 1.5,  'timeSlide2' => 3,    'timeSlide3' => 3.9),                                             // Motor-powered cable
		'_mp_1702813485' => array('numSlides' => 3, 'timeSlide1' => 1.5,  'timeSlide2' => 3,    'timeSlide3' => 3.9),                                             // Spring-powered cable
		'_mp_1702818639' => array('numSlides' => 3, 'timeSlide1' => 0.8,  'timeSlide2' => 2.4,  'timeSlide3' => 3.9),                                             // CPS 140kHz
		'_mp_1702828411' => array('numSlides' => 3, 'timeSlide1' => 0.8,  'timeSlide2' => 2.4,  'timeSlide3' => 3.9),                                             // CPS 20kHz
		'_mp_1702848406' => array('numSlides' => 3, 'timeSlide1' => 1,    'timeSlide2' => 2.6,  'timeSlide3' => 3.9),                                             // Charging contacts
		'_mp_1702848609' => array('numSlides' => 3, 'timeSlide1' => 0.7,  'timeSlide2' => 1.8,  'timeSlide3' => 3.9),                                             // Shuttle charging
		'_mp_1702897230' => array('numSlides' => 3, 'timeSlide1' => 0.5,  'timeSlide2' => 1.8,  'timeSlide3' => 3.9),                                             // SMGX
		'_mp_1702897807' => array('numSlides' => 3, 'timeSlide1' => 0.5,  'timeSlide2' => 1.8,  'timeSlide3' => 3.9),                                             // SMGM
		'_mp_1702913265' => array('numSlides' => 2, 'timeSlide1' => 1.7,  'timeSlide2' => 3.9),                                                                   // Powercom
		'_mp_1702982049' => array('numSlides' => 3, 'timeSlide1' => 0.9,  'timeSlide2' => 2.1,  'timeSlide3' => 3.9),                                             // APOS touchless
		'_mp_1702982363' => array('numSlides' => 2, 'timeSlide1' => 1.2,  'timeSlide2' => 3.9),                                                                   // APOS gliding
		'_mp_1702982596' => array('numSlides' => 2, 'timeSlide1' => 1.8,  'timeSlide2' => 3.95),                                                                  // APOS optic
		'_mp_1702982873' => array('numSlides' => 2, 'timeSlide1' => 1.8,  'timeSlide2' => 3.9),                                                                   // VCS1
		'_mp_1702983567' => array('numSlides' => 2, 'timeSlide1' => 1.8,  'timeSlide2' => 3.9),                                                                   // VCSX
		'_mp_1702989478' => array('numSlides' => 2, 'timeSlide1' => 1.85, 'timeSlide2' => 3.9),                                                                   // VCS-SMG-safe
		'_mp_1703028531' => array('numSlides' => 4, 'timeSlide1' => 1.4,  'timeSlide2' => 2.4,  'timeSlide3' => 3.4,  'timeSlide4' => 3.9),                       // Smart collector
		'_mp_1703032013' => array('numSlides' => 3, 'timeSlide1' => 2.2,  'timeSlide2' => 3.1,  'timeSlide3' => 3.9),                                             // ERTG
		'_mp_1703032255' => array('numSlides' => 3, 'timeSlide1' => 1,    'timeSlide2' => 2,    'timeSlide3' => 3.9),                                             // EMS
		'_mp_1767957520' => array('numSlides' => 3, 'timeSlide1' => 1.1,  'timeSlide2' => 2.2,  'timeSlide3' => 3.5),                                             // Open systems

		// Legacy rows (old code formats, default values 1.1/2.2/3.5) — ⚠️ verify
		// pages 2897 / 6432 still use the slideshow, otherwise drop these.
		'_mp_1678280599' => array('numSlides' => 3, 'timeSlide1' => 1.1,  'timeSlide2' => 2.2,  'timeSlide3' => 3.5),                                             // legacy (post 2897)
		'_mp_1685807214' => array('numSlides' => 3, 'timeSlide1' => 1.1,  'timeSlide2' => 2.2,  'timeSlide3' => 3.5),                                             // legacy (post 6432)
	),

	// === ROUTING — which animation (timelineUID) is shown on which page ===
	'pages' => array(

		// Primary: by page slug (post_name), SK + EN variants -> same animation.
		'by_slug' => array(
			'mkh-en'                                    => '_mp_1702044797',
			'system-uzavretych-vodicov-mkh'             => '_mp_1702044797',
			'izolovane-systemy-u10-faba100'             => '_mp_1702312396',
			'insulated-u15-en'                          => '_mp_1702463476',
			'izolovane-systemy-u15-u25-u35'             => '_mp_1702463476',
			'insulated-u20-en'                          => '_mp_1702464273',
			'izolovane-systemy-u20-u30'                 => '_mp_1702464273',
			'vcl-en'                                    => '_mp_1702465111',
			'kompaktny-vodicovy-system-vcl'             => '_mp_1702465111',
			'vks-en'                                    => '_mp_1702466691',
			'kompaktny-vodicovy-system-vks'             => '_mp_1702466691',
			'vks10-en'                                  => '_mp_1702467039',
			'kompaktny-vodicovy-system-vks10'           => '_mp_1702467039',
			'kbh-en'                                    => '_mp_1702811009',
			'system-uzavretych-vodicov-kbh'             => '_mp_1702811009',
			'lsv-en'                                    => '_mp_1702811602',
			'system-uzavretych-vodicov-lsv-lsvg'        => '_mp_1702811602',
			'large-festoon-en'                          => '_mp_1702812516',
			'velke-festonove-systemy'                   => '_mp_1702812516',
			'small-festoon-en'                          => '_mp_1702812863',
			'male-festonove-systemy'                    => '_mp_1702812863',
			'motor-powered-cable-en'                    => '_mp_1702813178',
			'motorom-pohanane-kablove-navijaky'         => '_mp_1702813178',
			'spring-powered-cable-en'                   => '_mp_1702813485',
			'pruzinove-kablove-navijaky'                => '_mp_1702813485',
			'cps-140khz-en'                             => '_mp_1702818639',
			'cps-140khz-sk'                             => '_mp_1702818639',
			'cps-20khz-en'                              => '_mp_1702828411',
			'cps-20khz-sk'                              => '_mp_1702828411',
			'charging-contacts-en'                      => '_mp_1702848406',
			'shuttle-charging-system-en'                => '_mp_1702848609',
			'system-kyvadloveho-nabijania'              => '_mp_1702848609',
			'smgx-en'                                   => '_mp_1702897230',
			'slotted-microwave-guide-sk'                => '_mp_1702897230',
			'smgm-en'                                   => '_mp_1702897807',
			'slotted-microwave-guide-mini-smgm-sk'      => '_mp_1702897807',
			'powercom-en'                               => '_mp_1702913265',
			'powercom-485-sk'                           => '_mp_1702913265',
			'apos-magnetic-touchless-en'                => '_mp_1702982049',
			'apos-magnetic-touchless-sk'                => '_mp_1702982049',
			'apos-magnetic-gliding-en'                  => '_mp_1702982363',
			'apos-magnetic-sk'                          => '_mp_1702982363',
			'apos-optic-en'                             => '_mp_1702982596',
			'apos-optic-sk'                             => '_mp_1702982596',
			'vcs1-en'                                   => '_mp_1702982873',
			'riadiaci-system-vcs1'                      => '_mp_1702982873',
			'vcsx-en'                                   => '_mp_1702983567',
			'modularne-ovladacie-prvky-vcsx'            => '_mp_1702983567',
			'vcs-smg-safe-en'                           => '_mp_1702989478',
			'bezpecne-ovladanie-aplikacii-vcs-smg-safe' => '_mp_1702989478',
			'smart-collector-en'                        => '_mp_1703028531',
			'smart-collectors-sk'                       => '_mp_1703028531',
			'ertg-system-en'                            => '_mp_1703032013',
			'ertg-system-sk'                            => '_mp_1703032013',
			'ems-system-en'                             => '_mp_1703032255',
			'ems-system-sk'                             => '_mp_1703032255',
			'otvorene-systemy'                          => '_mp_1767957520',
			'open-en'                                   => '_mp_1767957520',
			'open-conductor-system'                     => '_mp_1767957520',
		),

		// Fallback by queried-object id — ONLY for the 2 legacy rows that had no
		// category slug (cats=''). For product categories the export's post_id is
		// NOT the term_id (confirmed live: term_id 228 vs export post_id 4657), so
		// categories route by slug only — listing their post_ids here would never
		// match a term archive. ⚠️ verify pages 2897 / 6432 still use the slideshow.
		'by_post' => array(
			2897 => '_mp_1678280599', // legacy, default data
			6432 => '_mp_1685807214', // legacy, default data
		),
	),
);
