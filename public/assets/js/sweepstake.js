/* global wc2026 */
( function () {
	'use strict';

	// ── Tab hub ───────────────────────────────────────────────────────────
	var hub = document.getElementById( 'wc2026-hub' );
	if ( hub ) {
		var tabs   = Array.from( hub.querySelectorAll( '.wc2026-hub-tab' ) );
		var panels = Array.from( hub.querySelectorAll( '.wc2026-hub-panel' ) );

		function activateTab( tabKey ) {
			tabs.forEach( function ( btn ) {
				var active = btn.dataset.tab === tabKey;
				btn.classList.toggle( 'is-active', active );
				btn.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );
			panels.forEach( function ( panel ) {
				var active = panel.id === 'wc2026-panel-' + tabKey;
				if ( active ) {
					panel.removeAttribute( 'hidden' );
				} else {
					panel.setAttribute( 'hidden', '' );
				}
			} );
		}

		// Resolve initial tab from URL hash, defaulting to first tab.
		function tabFromHash() {
			var hash = ( window.location.hash || '' ).replace( '#', '' );
			return tabs.some( function ( t ) { return t.dataset.tab === hash; } ) ? hash : tabs[0].dataset.tab;
		}

		activateTab( tabFromHash() );

		tabs.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var key = btn.dataset.tab;
				activateTab( key );
				history.replaceState( null, '', '#' + key );
			} );
		} );

		window.addEventListener( 'hashchange', function () {
			activateTab( tabFromHash() );
		} );
	}

	// ── Staff popup ───────────────────────────────────────────────────────
	var overlay = document.getElementById( 'wc2026-staff-popup-overlay' );
	if ( ! overlay ) { return; }

	var popupContent = document.getElementById( 'wc2026-popup-content' );

	function openPopup( slug ) {
		popupContent.innerHTML = '<div class="wc2026-popup-loading"><span class="wc2026-spinner"></span></div>';
		overlay.removeAttribute( 'aria-hidden' );
		overlay.classList.add( 'is-open' );
		document.body.classList.add( 'wc2026-popup-open' );

		var formData = new FormData();
		formData.append( 'action',   'wc2026_staff_popup' );
		formData.append( 'slug',     slug );
		formData.append( '_wpnonce', wc2026.nonce );

		fetch( wc2026.ajax_url, { method: 'POST', body: formData } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( data.success ) {
					popupContent.innerHTML = data.data.html;
				} else {
					popupContent.innerHTML = '<p>Could not load profile.</p>';
				}
			} )
			.catch( function () {
				popupContent.innerHTML = '<p>Could not load profile.</p>';
			} );
	}

	function closePopup() {
		overlay.setAttribute( 'aria-hidden', 'true' );
		overlay.classList.remove( 'is-open' );
		document.body.classList.remove( 'wc2026-popup-open' );
		popupContent.innerHTML = '';
	}

	// Delegate: any .wc2026-staff-trigger anywhere on the page.
	document.addEventListener( 'click', function ( e ) {
		var trigger = e.target.closest( '.wc2026-staff-trigger' );
		if ( trigger ) {
			e.preventDefault();
			openPopup( trigger.dataset.staffSlug );
			return;
		}
		// Close on backdrop click.
		if ( e.target === overlay ) {
			closePopup();
		}
		// Close button.
		if ( e.target.closest( '.wc2026-popup-close' ) ) {
			closePopup();
		}
	} );

	// Close on Escape.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && overlay.classList.contains( 'is-open' ) ) {
			closePopup();
		}
	} );

	// ── Wall chart zoom ───────────────────────────────────────────────────
	var chartViewport = document.querySelector( '.wc2026-chart-viewport' );
	if ( chartViewport ) {
		var chartEl     = chartViewport.querySelector( '.wc2026-chart' );
		var zoomLabel   = document.querySelector( '.wc2026-zoom-level' );
		var naturalW    = 0;
		var currentZoom = 1;

		function setZoom( z ) {
			currentZoom = Math.min( 2.0, Math.max( 0.2, z ) );
			chartEl.style.zoom = currentZoom;
			if ( zoomLabel ) {
				zoomLabel.textContent = Math.round( currentZoom * 100 ) + '%';
			}
		}

		// Measure natural width (must happen while chart is visible, before any zoom).
		function tryAutoFit() {
			if ( naturalW > 0 ) { return; }
			naturalW = chartEl.offsetWidth;
			if ( naturalW < 100 ) { return; } // still in a hidden panel
			var fitScale = chartViewport.clientWidth / naturalW;
			if ( fitScale < 0.95 ) {
				// Round down to nearest 5% for a clean number.
				setZoom( Math.floor( fitScale * 20 ) / 20 );
			}
		}

		// Use IntersectionObserver so auto-fit fires when the tab is first shown.
		if ( window.IntersectionObserver ) {
			var chartIO = new IntersectionObserver( function ( entries ) {
				if ( entries[0].isIntersecting ) {
					tryAutoFit();
					chartIO.disconnect();
				}
			} );
			chartIO.observe( chartEl );
		} else {
			window.addEventListener( 'load', tryAutoFit );
		}

		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.wc2026-zoom-btn' );
			if ( ! btn ) { return; }
			var action = btn.dataset.zoom;
			var vw     = chartViewport.clientWidth;
			var nw     = naturalW || chartEl.offsetWidth;
			if      ( action === 'in' )    { setZoom( currentZoom + 0.05 ); }
			else if ( action === 'out' )   { setZoom( currentZoom - 0.05 ); }
			else if ( action === 'fit' )   { setZoom( vw / nw ); }
			else if ( action === 'reset' ) { setZoom( 1 ); }
		} );
	}

} )();
