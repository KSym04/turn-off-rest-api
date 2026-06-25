/* Main JS */
jQuery(document).ready(function($) {

	jQuery(':checkbox').change(function(event) {
		var cboxidNumber = jQuery(this).attr('data-counter');
		if( jQuery(this).is(':checked') ) {
			jQuery( '#child-check-'+ cboxidNumber ).find('input:checkbox').prop( 'checked', true );
		} else {
			jQuery( '#child-check-'+ cboxidNumber ).find('input:checkbox').prop( 'checked', false );
		}
	});

});