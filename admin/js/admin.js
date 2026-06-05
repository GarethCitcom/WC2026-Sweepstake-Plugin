/* global wp, jQuery */
( function ( $ ) {
	'use strict';

	// ── Media library (photo picker) ──────────────────────────────
	var mediaFrame;

	$( document ).on( 'click', '.wc2026-open-media', function ( e ) {
		e.preventDefault();
		var staffId = $( this ).data( 'staff-id' );

		if ( ! mediaFrame ) {
			mediaFrame = wp.media( {
				title:    'Select Staff Photo',
				button:   { text: 'Use this photo' },
				multiple: false,
				library:  { type: 'image' }
			} );
		}

		// Rebind select handler each time so the correct staffId is captured.
		mediaFrame.off( 'select' ).on( 'select', function () {
			var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			$( '#staff-preview-' + staffId ).attr( 'src', attachment.url );
			$( '#wc2026-photo-staff-id' ).val( staffId );
			$( '#wc2026-photo-attachment-id' ).val( attachment.id );
			$( '#wc2026-photo-form' ).submit();
		} );

		mediaFrame.open();
	} );

	// ── Staff modal ───────────────────────────────────────────────
	function openModal( title, staffId, name, colour, countryIds ) {
		$( '#wc2026-modal-title' ).text( title );
		$( '#modal-staff-id' ).val( staffId );
		$( '#modal-staff-name' ).val( name );
		$( '#modal-staff-colour' ).val( colour );
		$( '#modal-colour-preview' ).css( 'background', colour );

		// Reset all checkboxes, then tick the supplied ones.
		$( '.wc2026-country-check' ).prop( 'checked', false );
		$.each( countryIds, function ( i, id ) {
			$( '#country-' + id ).prop( 'checked', true );
		} );

		// Dim countries owned by a different staff member.
		$( '.wc2026-country-owner' ).each( function () {
			var ownerStaffId = parseInt( $( this ).data( 'staff-id' ), 10 );
			var isOther      = staffId > 0 && ownerStaffId !== parseInt( staffId, 10 );
			$( this ).closest( '.wc2026-country-label' ).toggleClass( 'is-other-owner', isOther );
		} );

		$( '#wc2026-staff-modal' ).fadeIn( 150 );
		setTimeout( function () { $( '#modal-staff-name' ).trigger( 'focus' ); }, 160 );
	}

	function closeModal() {
		$( '#wc2026-staff-modal' ).fadeOut( 150 );
	}

	// Colour preview swatch in modal.
	$( document ).on( 'input', '#modal-staff-colour', function () {
		$( '#modal-colour-preview' ).css( 'background', $( this ).val() );
	} );

	// Open modal — Edit.
	$( document ).on( 'click', '.wc2026-edit-staff', function () {
		var btn        = $( this );
		var countryIds = btn.data( 'country-ids' ) || [];
		if ( typeof countryIds === 'string' ) {
			try { countryIds = JSON.parse( countryIds ); } catch ( err ) { countryIds = []; }
		}
		openModal(
			'Edit Staff Member',
			parseInt( btn.data( 'staff-id' ), 10 ),
			btn.data( 'staff-name' ),
			btn.data( 'staff-colour' ),
			countryIds
		);
	} );

	// Open modal — Add.
	$( document ).on( 'click', '#wc2026-add-staff', function () {
		openModal( 'Add Staff Member', 0, '', '#3498db', [] );
	} );

	// Close modal — button or backdrop click.
	$( document ).on( 'click', '.wc2026-modal-close', closeModal );
	$( document ).on( 'click', '#wc2026-staff-modal', function ( e ) {
		if ( e.target === this ) { closeModal(); }
	} );
	$( document ).on( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && $( '#wc2026-staff-modal' ).is( ':visible' ) ) { closeModal(); }
	} );

	// ── Delete confirmation ───────────────────────────────────────
	$( document ).on( 'click', '.wc2026-confirm-delete', function () {
		var name   = $( this ).data( 'staff-name' );
		var formId = $( this ).data( 'form-id' );
		if ( window.confirm( 'Delete "' + name + '"? Their country assignments will be removed.' ) ) {
			$( '#' + formId ).submit();
		}
	} );

} )( jQuery );
