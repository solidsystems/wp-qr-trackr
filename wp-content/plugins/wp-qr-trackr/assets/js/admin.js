/**
 * QR Trackr Admin JavaScript
 */
jQuery(document).ready(function($) {
	'use strict';

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

	// Debug logging
	console.log('[QR Trackr Debug] Current page:', window.location.href);
	
	// Handle destination type switching
	$('#destination_type').on('change', function() {
		var type = $(this).val();
		if (type === 'post') {
			$('.post-select').show();
			$('.external-url').hide();
		} else {
			$('.post-select').hide();
			$('.external-url').show();
		}
	});

	// Initialize the correct field visibility on page load
	$('#destination_type').trigger('change');

	// Enhance post/page search with Select2
	if ($('#post_id').length) {
		$('#post_id').select2({
			placeholder: 'Start typing to search posts/pages...',
			allowClear: true,
			ajax: {
				url: qrTrackrSelect2.ajaxurl,
				type: 'POST',
				dataType: 'json',
				delay: 250,
				data: function(params) {
					return {
						action: 'qr_trackr_search_posts',
						term: params.term || '',
						nonce: qrTrackrSelect2.nonce
					};
				},
				processResults: function(data) {
					if (data.success && data.data) {
						return {
							results: data.data.map(function(post) {
								return {
									id: post.ID,
									text: post.title + ' (' + post.ID + ')',
								};
							})
						};
					}
					return { results: [] };
				}
			},
			minimumInputLength: 0
		});
	}

	// Handle form submission
	$('#qr-trackr-create-form').on('submit', function(e) {
		e.preventDefault();
		var formData = {
			action: 'qr_trackr_create_qr_code',
			nonce: qrTrackrAdmin.nonce,
			destination_type: $('#destination_type').val()
		};
		if (formData.destination_type === 'post') {
			formData.post_id = $('#post_id').val();
			if (!formData.post_id) {
				alert('Please select a post or page from the dropdown.');
				return;
			}
		} else if (formData.destination_type === 'external') {
			formData.destination = $('#external_url').val();
		} else if (formData.destination_type === 'custom') {
			formData.destination = $('#custom_url').val();
		}
		$.post(qrTrackrAdmin.ajaxurl, formData, function(response) {
			if (response.success) {
				// Show success message first
				$('#qr-trackr-message').html('<div class="notice notice-success"><p>QR code created successfully!</p></div>');
				// Reset form
				$('#qr-trackr-create-form')[0].reset();
				$('#post_id').val(null).trigger('change'); // Clear Select2
				$('#destination_type').trigger('change'); // Reset form state
				// Reload after showing message
				setTimeout(function() {
					location.reload();
				}, 1500);
			} else {
				console.log('QR Trackr AJAX error:', response);
				// Handle different error response formats
				var errorMessage = 'Error creating QR code';
				if (typeof response.data === 'string') {
					errorMessage = response.data;
				} else if (response.data && response.data.message) {
					errorMessage = response.data.message;
				} else if (typeof response === 'string') {
					errorMessage = response;
				}
				alert(errorMessage);
			}
		}).fail(function() {
			alert('Server error occurred. Please try again.');
		});
	});

	// Handle edit destination
	$('.qr-trackr-edit-destination').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var $link = $(this).closest('tr');
		var linkId = $link.find('.qr-trackr-edit-destination').data('link-id');
		var currentDestination = $link.find('.qr-trackr-edit-destination').data('destination') || '';
		
		if (!linkId) {
			console.error('Missing link ID for edit operation');
			return;
		}

		// Clean up any existing modals
		$('.qr-trackr-modal').remove();
		
		// Create modal
		var $modal = $('<div class="qr-trackr-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:999999;">' +
			'<div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border-radius:5px; min-width:400px;">' +
			'<h2>Edit Destination</h2>' +
			'<form id="qr_trackr_edit_form">' +
			'<input type="hidden" name="link_id" value="' + linkId + '">' +
			'<div style="margin-bottom:15px;">' +
			'<label for="qr_trackr_edit_destination_type" style="display:block; margin-bottom:5px;">Destination Type:</label>' +
			'<select name="qr_trackr_edit_destination_type" id="qr_trackr_edit_destination_type" style="width:100%;">' +
			'<option value="post">WordPress Post/Page</option>' +
			'<option value="external">External Link</option>' +
			'</select>' +
			'</div>' +
			'<div class="qr-trackr-edit-post-selection" style="margin-bottom:15px;">' +
			'<label for="qr_trackr_edit_post_id" style="display:block; margin-bottom:5px;">Select Post/Page:</label>' +
			'<select name="qr_trackr_edit_post_id" id="qr_trackr_edit_post_id" style="width:100%;">' +
			'<option value="">Select a post or page...</option>' +
			'</select>' +
			'</div>' +
			'<div class="qr-trackr-edit-external-url" style="margin-bottom:15px; display:none;">' +
			'<label for="qr_trackr_edit_external_url" style="display:block; margin-bottom:5px;">External URL:</label>' +
			'<input type="url" name="qr_trackr_edit_external_url" id="qr_trackr_edit_external_url" style="width:100%;" placeholder="https://example.com">' +
			'</div>' +
			'<div style="text-align:right;">' +
			'<button type="button" class="button qr-trackr-cancel-edit" style="margin-right:10px;">Cancel</button>' +
			'<button type="submit" class="button button-primary qr-trackr-save-destination">Save Changes</button>' +
			'</div>' +
			'</form>' +
			'</div>' +
			'</div>');

		$('body').append($modal);
		$modal.fadeIn(200);

		// Load posts
		$.ajax({
			url: qrTrackrAdmin.ajaxurl,
			type: 'POST',
			data: {
				action: 'qr_trackr_get_posts',
				nonce: qrTrackrAdmin.nonce
			},
			success: function(response) {
				if (response.success && response.data) {
					var $select = $('#qr_trackr_edit_post_id');
					response.data.forEach(function(post) {
						$select.append($('<option>', {
							value: post.ID,
							text: post.post_title + ' (' + post.post_type + ')'
						}));
					});

					// Set initial values
					if (currentDestination && currentDestination.startsWith(window.location.origin)) {
						$('#qr_trackr_edit_destination_type').val('post');
						$('.qr-trackr-edit-post-selection').show();
						$('.qr-trackr-edit-external-url').hide();
						$('#qr_trackr_edit_post_id').prop('required', true);
						$('#qr_trackr_edit_external_url').prop('required', false);
						
						// Try to find matching post
						var postId = response.data.find(function(post) {
							return post.permalink === currentDestination;
						})?.ID;
						
						if (postId) {
							$('#qr_trackr_edit_post_id').val(postId);
						}
					} else if (currentDestination) {
						$('#qr_trackr_edit_destination_type').val('external');
						$('.qr-trackr-edit-post-selection').hide();
						$('.qr-trackr-edit-external-url').show();
						$('#qr_trackr_edit_post_id').prop('required', false);
						$('#qr_trackr_edit_external_url').prop('required', true);
						$('#qr_trackr_edit_external_url').val(currentDestination);
					} else {
						// Default to post selection for empty destinations
						$('#qr_trackr_edit_destination_type').val('post');
						$('.qr-trackr-edit-post-selection').show();
						$('.qr-trackr-edit-external-url').hide();
						$('#qr_trackr_edit_post_id').prop('required', true);
						$('#qr_trackr_edit_external_url').prop('required', false);
					}
				}
			},
			error: function(xhr, status, error) {
				console.error('Error loading posts:', error);
				alert(qrTrackrAdmin.i18n.error);
			}
		});

		// Handle destination type switching in edit modal
		$('#qr_trackr_edit_destination_type').on('change', function() {
			var type = $(this).val();
			if (type === 'external') {
				$('.qr-trackr-edit-post-selection').hide();
				$('.qr-trackr-edit-external-url').show();
				$('#qr_trackr_edit_post_id').prop('required', false);
				$('#qr_trackr_edit_external_url').prop('required', true);
			} else {
				$('.qr-trackr-edit-post-selection').show();
				$('.qr-trackr-edit-external-url').hide();
				$('#qr_trackr_edit_post_id').prop('required', true);
				$('#qr_trackr_edit_external_url').prop('required', false);
			}
		});

		// Handle cancel
		$('.qr-trackr-cancel-edit').on('click', function() {
			$modal.fadeOut(200, function() {
				$(this).remove();
			});
		});

		// Handle save
		$('#qr_trackr_edit_form').on('submit', function(e) {
			e.preventDefault();
			var $type = $('#qr_trackr_edit_destination_type');
			var $postSelect = $('#qr_trackr_edit_post_id');
			var $externalInput = $('#qr_trackr_edit_external_url');
			var newDestination;
			var error = '';
			
			if ($type.val() === 'external') {
				if (!$externalInput.val()) {
					error = 'Please enter an external URL.';
				} else {
					try {
						new URL($externalInput.val());
						if (!$externalInput.val().startsWith('http://') && !$externalInput.val().startsWith('https://')) {
							error = 'Please enter a valid URL with protocol (http:// or https://).';
						} else {
							newDestination = $externalInput.val();
						}
					} catch (e) {
						error = 'Please enter a valid URL.';
					}
				}
			} else {
				if (!$postSelect.val()) {
					error = 'Please select a post or page.';
				} else {
					// Find the selected post's permalink
					var selectedPost = response.data.find(function(post) {
						return post.ID === $postSelect.val();
					});
					if (!selectedPost) {
						error = 'Error: Could not find selected post.';
					} else {
						newDestination = selectedPost.permalink;
					}
				}
			}

			if (error) {
				alert(error);
				return;
			}

			// Update destination
			$.ajax({
				url: qrTrackrAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'qr_trackr_update_destination',
					link_id: linkId,
					destination: newDestination,
					nonce: qrTrackrAdmin.editNonce
				},
				success: function(response) {
					if (response.success) {
						location.reload();
						$('#qr-trackr-message').html('<div class="notice notice-success"><p>Destination updated successfully!</p></div>');
					} else {
						alert(response.data || qrTrackrAdmin.i18n.error);
						console.error('Update error:', response.data);
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX error:', status, error);
					alert(qrTrackrAdmin.i18n.error);
				}
			});
		});
	});

	// Handle QR code regeneration
	$('.regenerate-qr').on('click', function() {
		if (!confirm(qrTrackrAdmin.i18n.confirmRegenerate)) {
			return;
		}

		var button = $(this);
		var linkId = button.data('id');

		$.post(qrTrackrAdmin.ajaxurl, {
			action: 'qr_trackr_regenerate_qr_code',
			nonce: qrTrackrAdmin.regenerateNonce,
			link_id: linkId
		}, function(response) {
			if (response.success) {
				location.reload();
				$('#qr-trackr-message').html('<div class="notice notice-success"><p>QR code regenerated successfully!</p></div>');
			} else {
				alert(response.data || 'Error regenerating QR code');
			}
		}).fail(function() {
			alert('Server error occurred. Please try again.');
		});
	});

	// Handle QR code deletion
	$('.delete-qr').on('click', function() {
		if (!confirm(qrTrackrAdmin.i18n.confirmDelete)) {
			return;
		}

		var button = $(this);
		var linkId = button.data('id');

		$.post(qrTrackrAdmin.ajaxurl, {
			action: 'qr_trackr_delete_qr_code',
			nonce: qrTrackrAdmin.deleteNonce,
			link_id: linkId
		}, function(response) {
			if (response.success) {
				location.reload();
				$('#qr-trackr-message').html('<div class="notice notice-success"><p>QR code deleted successfully!</p></div>');
			} else {
				alert(response.data || 'Error deleting QR code');
			}
		}).fail(function() {
			alert('Server error occurred. Please try again.');
		});
	});

	// Handle QR code editing
	$('.edit-qr').on('click', function() {
		var button = $(this);
		var linkId = button.data('id');
		button.prop('disabled', true);

		// Get current link data
		$.post(qrTrackrAdmin.ajaxurl, {
			action: 'qr_trackr_get_link',
			nonce: qrTrackrAdmin.editNonce,
			link_id: linkId
		}, function(response) {
			button.prop('disabled', false);
			if (response.success && response.data) {
				var currentDestination = response.data.destination_url || '';
				// Remove any existing modals
				$('.qr-trackr-modal').remove();
				// Create modal
				var $modal = $('<div class="qr-trackr-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:999999;">' +
					'<div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border-radius:5px; min-width:400px;">' +
					'<h2>Edit Destination</h2>' +
					'<form id="qr_trackr_edit_form">' +
					'<input type="hidden" name="link_id" value="' + linkId + '">' +
					'<div style="margin-bottom:15px;">' +
					'<label for="qr_trackr_edit_destination" style="display:block; margin-bottom:5px;">Destination URL:</label>' +
					'<input type="url" name="qr_trackr_edit_destination" id="qr_trackr_edit_destination" style="width:100%;" value="' + currentDestination + '" required placeholder="https://example.com">' +
					'</div>' +
					'<div style="text-align:right;">' +
					'<button type="button" class="button qr-trackr-cancel-edit" style="margin-right:10px;">Cancel</button>' +
					'<button type="submit" class="button button-primary qr-trackr-save-destination">Save Changes</button>' +
					'</div>' +
					'</form>' +
					'</div>' +
					'</div>');
				$('body').append($modal);
				$modal.fadeIn(200);

				// Handle cancel
				$('.qr-trackr-cancel-edit').on('click', function() {
					$modal.fadeOut(200, function() { $(this).remove(); });
				});

				// Handle save
				$('#qr_trackr_edit_form').on('submit', function(e) {
					e.preventDefault();
					var newDestination = $('#qr_trackr_edit_destination').val();
					if (!newDestination) {
						alert('Please enter a destination URL.');
						return;
					}
					try {
						new URL(newDestination);
						if (!newDestination.startsWith('http://') && !newDestination.startsWith('https://')) {
							alert('Please enter a valid URL with protocol (http:// or https://).');
							return;
						}
					} catch (e) {
						alert('Please enter a valid URL.');
						return;
					}
					// Submit update
					$.ajax({
						url: qrTrackrAdmin.ajaxurl,
						type: 'POST',
						data: {
							action: 'qr_trackr_update_destination',
							link_id: linkId,
							destination: newDestination,
							nonce: qrTrackrAdmin.editNonce
						},
						success: function(resp) {
							if (resp.success) {
								$modal.fadeOut(200, function() { $(this).remove(); });
								// Update the table row
								button.closest('tr').find('a[target="_blank"]').text(newDestination).attr('href', newDestination);
								alert('Destination updated successfully!');
							} else {
								alert(resp.data && resp.data.message ? resp.data.message : 'Error updating destination');
							}
						},
						error: function() {
							alert('Error updating destination. Please try again.');
						}
					});
				});
			} else {
				alert(response.data || 'Error loading link data');
			}
		}).fail(function() {
			button.prop('disabled', false);
			alert('Server error occurred. Please try again.');
		});
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

	// Handle large QR code view modal
	$('.qr-trackr-list').on('click', 'img[src*="/qr-trackr/"]', function(e) {
		e.preventDefault();
		var $img = $(this);
		var imgSrc = $img.attr('src');
		if (!imgSrc) return;
		// Try to get SVG URL by replacing .png with .svg
		var svgSrc = imgSrc.replace(/\.png$/, '.svg');
		// Get destination URL from the same row
		var $row = $img.closest('tr');
		var destLink = $row.find('a[target="_blank"]');
		var destinationUrl = destLink.attr('href') || '';
		var linkId = $row.find('.edit-qr').data('id');
		// Remove any existing modals
		$('.qr-trackr-modal').remove();
		// Create modal
		var $modal = $('<div class="qr-trackr-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:999999;">' +
			'<div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border-radius:5px; min-width:340px; min-height:420px; box-shadow:0 4px 32px rgba(0,0,0,0.2);">' +
			'<button class="qr-trackr-close-modal" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:22px; cursor:pointer;">&times;</button>' +
			'<img src="' + imgSrc + '" alt="QR Code" style="display:block; margin:0 auto; width:300px; height:300px; object-fit:contain;">' +
			'<div style="height:20px;"></div>' +
			'<div style="text-align:center; margin-bottom:10px;">' +
			'<a href="' + imgSrc + '" download class="button" style="margin-right:10px;">Download JPG</a>' +
			'<a href="' + svgSrc + '" download class="button">Download SVG</a>' +
			'</div>' +
			'<div style="text-align:center; margin-top:18px; font-size:14px; color:#333; word-break:break-all;">' +
			'<span>' + (destinationUrl ? '<a href="' + destinationUrl + '" target="_blank" style="color:#0073aa; text-decoration:underline;">' + destinationUrl + '</a>' : '') + '</span>' +
			(linkId ? ' <button class="qr-trackr-modal-edit" data-id="' + linkId + '" title="Edit Destination" style="background:none; border:none; cursor:pointer; margin-left:8px; vertical-align:middle;"><svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.7 2.29a1 1 0 0 1 1.42 0l1.59 1.59a1 1 0 0 1 0 1.42l-9.3 9.3-3.3.71.71-3.3 9.3-9.3zM3 17h14a1 1 0 1 1 0 2H3a1 1 0 1 1 0-2z" fill="#888"/></svg></button>' : '') +
			'</div>' +
			'</div>' +
			'</div>');
		$('body').append($modal);
		$modal.fadeIn(200);
		// Close on click outside or close button
		$modal.on('click', function(e) {
			if ($(e.target).is('.qr-trackr-modal, .qr-trackr-close-modal')) {
				$modal.fadeOut(200, function() { $(this).remove(); });
			}
		});
		// Handle edit icon click
		$modal.on('click', '.qr-trackr-modal-edit', function(e) {
			e.preventDefault();
			$modal.fadeOut(200, function() { $(this).remove(); });
			// Trigger the edit modal for this QR code
			$('.edit-qr[data-id="' + linkId + '"]').trigger('click');
		});
	});

	// Enhance post/page search with autocomplete
	if ($('#post_search_input').length) {
		function fetchPosts(term, callback) {
			$.ajax({
				type: 'POST',
				url: qrTrackrAdmin.ajaxurl,
				dataType: 'json',
				data: {
					action: 'qr_trackr_search_posts',
					term: term,
					nonce: qrTrackrAdmin.nonce
				},
				success: function(data) {
					if (data.success && data.data) {
						callback(data.data.map(function(post) {
							return {
								label: post.title + ' (' + post.ID + ')',
								value: post.title,
								id: post.ID,
								permalink: post.permalink
							};
						}));
					} else {
						callback([]);
					}
				}
			});
		}
		$('#post_search_input').autocomplete({
			source: function(request, response) {
				fetchPosts(request.term, response);
			},
			minLength: 0,
			select: function(event, ui) {
				$('#post_id').val(ui.item.id);
				$('#post_id').data('url', ui.item.permalink);
			},
			open: function() {
				// Optionally style the dropdown
			}
		});
		$('#post_search_input').on('focus', function() {
			if (!$(this).val()) {
				$(this).autocomplete('search', '');
			}
		});
	}
}); 