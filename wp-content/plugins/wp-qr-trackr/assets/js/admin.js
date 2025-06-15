/**
 * QR Trackr Admin JavaScript
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		var debug = window.qrTrackrDebugMode && window.qrTrackrDebugMode.debug;
		var isMainPage = window.location.href.indexOf('page=qr-trackr') !== -1 && 
						window.location.href.indexOf('page=qr-trackr-stats') === -1;

		// Only poll for form on the main QR Trackr page
		if (debug && isMainPage) {
			// Poll for the form and button up to 10 times (5 seconds)
			var pollCount = 0;
			function pollForForm() {
				var $form = $('form.qr-trackr-create-form');
				var $btn = $form.find('button[type="submit"]');
				if ($form.length && $btn.length) {
					if (debug) {
						console.log('[QR Trackr Debug] Found form:', $form.length, $form.get(0));
						console.log('[QR Trackr Debug] Found button:', $btn.length, $btn.get(0));
					}
				} else if (pollCount < 10) {
					pollCount++;
					setTimeout(pollForForm, 500);
				} else if (debug) {
					console.log('[QR Trackr Debug] Form/button not found after polling.');
				}
			}
			if (debug) {
				console.log('[QR Trackr Debug] JS loaded. Debug mode:', debug);
				console.log('[QR Trackr Debug] Current page:', window.location.href);
				pollForForm();
			}
		}

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

		// Handle edit destination
		$(document).on('click', '.edit-destination', function(e) {
			e.preventDefault();
			var $link = $(this);
			var linkId = $link.data('link-id');
			var currentDestination = $link.data('destination');
			
			var newDestination = prompt('Enter new destination URL:', currentDestination);
			if (newDestination === null) {
				return; // User cancelled
			}
			
			if (newDestination === currentDestination) {
				return; // No change
			}
			
			// Validate URL
			try {
				new URL(newDestination);
			} catch (e) {
				alert('Please enter a valid URL');
				return;
			}
			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'qr_trackr_update_destination',
					link_id: linkId,
					destination: newDestination,
					nonce: qrTrackrNonce.nonce
				},
				success: function(response) {
					if (response.success) {
						$link.data('destination', newDestination);
						$link.closest('tr').find('.column-destination_url').html(
							'<a href="' + newDestination + '" target="_blank">' + newDestination + '</a> ' +
							'<span class="row-actions"><span class="edit">' +
							'<a href="#" class="edit-destination" data-link-id="' + linkId + '" data-destination="' + newDestination + '">Edit</a>' +
							'</span></span>'
						);
						alert('Destination updated successfully!');
					} else {
						alert(response.data.message || 'Error updating destination');
					}
				},
				error: function() {
					alert('Error updating destination. Please try again.');
				}
			});
		});
	});

})(jQuery); 