/**
 * QR Trackr Admin JavaScript
 */
(function($) {
	'use strict';

	// Handle QR code creation form
	$('.qr-trackr-create-form').on('submit', function(e) {
		var $form = $(this);
		var $button = $form.find('button[type="submit"]');
		var $select = $form.find('select');

		if (!$select.val()) {
			e.preventDefault();
			$('.qr-trackr-message').html('<div class="notice notice-error"><p>Please select a post or page.</p></div>');
			return false;
		}

		$button.prop('disabled', true).text('Creating...');
	});

	// Handle debug mode toggle
	$('input[name="qr_trackr_debug_mode"]').on('change', function() {
		var $checkbox = $(this);
		var $form = $checkbox.closest('form');
		var $button = $form.find('button[type="submit"]');

		$button.prop('disabled', false);
	});

	// Handle debug log clearing
	$('form[action*="qr-trackr-debug"]').on('submit', function() {
		var $form = $(this);
		var $button = $form.find('button[type="submit"]');

		$button.prop('disabled', true).text('Clearing...');
	});

	// Handle QR code list actions
	$('.qr-trackr-list').on('click', '.qr-trackr-update-link', function(e) {
		e.preventDefault();
		var linkId = $(this).data('link-id');
		$('.qr-trackr-update-row').hide();
		$('#qr-trackr-update-row-' + linkId).show();
	});

	$('.qr-trackr-list').on('click', '.qr-trackr-cancel-update', function(e) {
		e.preventDefault();
		var linkId = $(this).data('link-id');
		$('#qr-trackr-update-row-' + linkId).hide();
	});

})(jQuery); 