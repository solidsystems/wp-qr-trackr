/**
 * QR Code Admin JavaScript
 *
 * Handles AJAX interactions for the QR code admin interface.
 *
 * @package WP_QR_TRACKR
 * @since 1.2.8
 */

(function($) {
$(function() {
	'use strict';
	
	// Add CSS for updated cell highlighting
	$('<style>')
		.prop('type', 'text/css')
		.html(`
			.updated-cell {
				background-color: #fff3cd !important;
				border: 2px solid #ffc107 !important;
				transition: all 0.3s ease;
			}
		`)
		.appendTo('head');
	
	// Track deleted QR codes to prevent modal opening
	window.deletedQRCodes = window.deletedQRCodes || [];
	window.isDeletingQR = window.isDeletingQR || false;
	
	// Prevent multiple script loads
	if (window.qrTrackrInitialized) {
		console.log('QR Trackr already initialized, skipping duplicate load');
		return;
	}
	window.qrTrackrInitialized = true;
	
	// Handle QR code deletion via AJAX.
	$(document).off('click', '.qr-delete-btn').on('click', '.qr-delete-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
			// Debug logging
	console.log('Delete button clicked for QR ID:', $(this).data('qr-id'));
	console.log('Event handlers on button:', $._data(this, 'events'));
	
	// Prevent duplicate event handling
	if ($(this).data('deleting') === true || window.isDeletingQR === true) {
		console.log('Delete already in progress, ignoring click');
		return false;
	}
		
		const $button = $(this);
		const qrId = $button.data('qr-id');
		const nonce = $button.data('nonce');
		
		// Mark as deleting to prevent duplicate clicks
		$button.data('deleting', true);
		window.isDeletingQR = true;
		
		// Show confirmation dialog.
		if (!confirm('Are you sure you want to delete this QR code?')) {
			$button.data('deleting', false);
			window.isDeletingQR = false;
			return false;
		}
		
		// Disable button and show loading state.
		$button.prop('disabled', true).text('Deleting...');
		
		// Send AJAX request to delete QR code.
		$.ajax({
			url: qr_trackr_ajax.ajaxurl,
			type: 'POST',
			data: {
				action: 'qr_trackr_delete_qr_code',
				qr_id: qrId,
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					// Show success message.
					showAdminNotice(response.data.message, 'success');
					
					// Remove the table row and all related elements.
					const $row = $button.closest('tr');
					const qrId = $button.data('qr-id');
					
					// Track this QR code as deleted
					window.deletedQRCodes.push(qrId);
					
					// Remove ALL elements with this QR ID (images, text, buttons, etc.)
					$('[data-qr-id="' + qrId + '"]').each(function() {
						$(this).remove();
					});
					
					// Remove the table row.
					$row.fadeOut(300, function() {
						$(this).remove();
					});
					
					// Close any open modal for this QR code
					if ($('#qr-code-modal').is(':visible')) {
						closeQRModal();
					}
					
					// Disable any remaining modal triggers for this QR code
					$('[data-qr-id="' + qrId + '"]').off('click').css('pointer-events', 'none');
					
					// Refresh the page immediately to ensure all references are cleared.
					setTimeout(function() {
						location.reload();
					}, 500);
				} else {
					// Show error message.
					showAdminNotice(response.data || 'Delete failed.', 'error');
					$button.prop('disabled', false).text('Delete');
					$button.data('deleting', false);
					window.isDeletingQR = false;
				}
			},
			error: function() {
				// Show error message.
				showAdminNotice('Delete failed. Please try again.', 'error');
				$button.prop('disabled', false).text('Delete');
				$button.data('deleting', false);
				window.isDeletingQR = false;
			}
		});
	});

	// Function to show admin notices.
	function showAdminNotice(message, type) {
		const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
		const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
		
		// Remove any existing notices.
		$('.notice').remove();
		
		// Add new notice after the page title.
		$('.wp-heading-inline').after(notice);
		
		// Auto-dismiss after 5 seconds.
		setTimeout(function() {
			notice.fadeOut(300, function() {
				$(this).remove();
			});
		}, 5000);
	}

	// Post search functionality
	let searchTimeout;
	const $postSearch = $('#post_search');
	const $postResults = $('#post_search_results');
	const $postId = $('#post_id');
	const $selectedPostInfo = $('#selected_post_info');

	// Handle destination type change
	$('#destination_type').change(function() {
		const type = $(this).val();
		if (type === 'post') {
			$('#post-selector').show();
			$('#url-input').hide();
		} else {
			$('#post-selector').hide();
			$('#url-input').show();
			// Clear post selection when switching away
			clearPostSelection();
		}
	}).trigger('change');

	// Handle post search input
	$postSearch.on('input', function() {
		const searchTerm = $(this).val().trim();
		
		clearTimeout(searchTimeout);
		
		if (searchTerm.length < 2) {
			$postResults.hide().empty();
			return;
		}

		searchTimeout = setTimeout(function() {
			searchPosts(searchTerm);
		}, 300);
	});

	// Handle clicking outside search results to hide them
	$(document).on('click', function(e) {
		if (!$(e.target).closest('#post-selector').length) {
			$postResults.hide();
		}
	});

	// Focus search results when clicking on search input
	$postSearch.on('focus', function() {
		if ($postResults.children().length > 0) {
			$postResults.show();
		}
	});

	/**
	 * Search for posts via AJAX
	 * @param {string} searchTerm - The search term
	 */
	function searchPosts(searchTerm) {
		$postResults.html('<div style="padding: 10px; text-align: center;"><em>Searching...</em></div>').show();

		$.ajax({
			url: qrcAjax.ajaxurl,
			type: 'POST',
			data: {
				action: 'qrc_search_posts',
				search: searchTerm,
				nonce: qrcAjax.nonce
			},
			success: function(response) {
				if (response.success && response.data.posts) {
					displaySearchResults(response.data.posts);
				} else {
					$postResults.html('<div style="padding: 10px; color: #d63638;"><em>No posts found.</em></div>');
				}
			},
			error: function() {
				$postResults.html('<div style="padding: 10px; color: #d63638;"><em>Search failed. Please try again.</em></div>');
			}
		});
	}

	/**
	 * Display search results
	 * @param {Array} posts - Array of post objects
	 */
	function displaySearchResults(posts) {
		if (posts.length === 0) {
			$postResults.html('<div style="padding: 10px; color: #666;"><em>No posts found.</em></div>');
			return;
		}

		let html = '';
		posts.forEach(function(post) {
			html += `<div class="post-result-item" data-id="${post.id}" data-title="${post.title}" data-type="${post.type}" data-url="${post.url}" style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer; transition: background-color 0.2s;">
				<strong>${post.title}</strong> <span style="color: #666;">(${post.type})</span>
			</div>`;
		});

		$postResults.html(html).show();

		// Handle clicking on search results
		$postResults.off('click', '.post-result-item').on('click', '.post-result-item', function() {
			const $item = $(this);
			selectPost(
				$item.data('id'),
				$item.data('title'),
				$item.data('type'),
				$item.data('url')
			);
		});

		// Handle hover effects
		$postResults.off('mouseenter mouseleave', '.post-result-item')
			.on('mouseenter', '.post-result-item', function() {
				$(this).css('background-color', '#f6f7f7');
			})
			.on('mouseleave', '.post-result-item', function() {
				$(this).css('background-color', '');
			});
	}

	/**
	 * Select a post
	 * @param {number} id - Post ID
	 * @param {string} title - Post title
	 * @param {string} type - Post type
	 * @param {string} url - Post URL
	 */
	function selectPost(id, title, type, url) {
		$postId.val(id);
		$postSearch.val(title);
		$('#destination_url').val(url);
		$postResults.hide();
		
		$selectedPostInfo
			.html(`Selected: <strong>${title}</strong> (${type}) - <a href="${url}" target="_blank">View</a>`)
			.show();
	}

	/**
	 * Clear post selection
	 */
	function clearPostSelection() {
		$postId.val('');
		$postSearch.val('');
		$('#destination_url').val('');
		$postResults.hide().empty();
		$selectedPostInfo.hide();
	}

	// Add clear button functionality
	const $clearButton = $('<button type="button" style="margin-left: 5px;">Clear</button>');
	$clearButton.on('click', function(e) {
		e.preventDefault();
		clearPostSelection();
	});
	$postSearch.after($clearButton);

	/**
	 * QR Code Modal Functionality
	 */
	// Create modal HTML and append to body
	const modalHTML = `
		<div id="qr-code-modal" class="qr-modal" style="display: none;">
			<div class="qr-modal-content">
				<div class="qr-modal-header">
					<h2 id="qr-modal-title">QR Code Details</h2>
					<span class="qr-modal-close">&times;</span>
				</div>
				<div class="qr-modal-body">
					<div class="qr-modal-loading" style="text-align: center; padding: 20px;">
						<span class="spinner is-active"></span>
						<p>Loading...</p>
					</div>
					<div class="qr-modal-content-inner" style="display: none;">
						<div class="qr-modal-row">
							<div class="qr-modal-left">
								<div class="qr-image-container">
									<img id="qr-modal-image" src="" alt="QR Code" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />
								</div>
								<div class="qr-stats">
									<h4>Statistics</h4>
									<p><strong>Total Scans:</strong> <span id="qr-modal-scans">0</span></p>
									<p><strong>Recent Scans (30 days):</strong> <span id="qr-modal-recent-scans">0</span></p>
									<p><strong>Last Accessed:</strong> <span id="qr-modal-last-accessed">Never</span></p>
									<p><strong>Created:</strong> <span id="qr-modal-created"></span></p>
								</div>
							</div>
							<div class="qr-modal-right">
								<form id="qr-modal-form">
									<input type="hidden" id="qr-modal-id" value="" />
									
									<div class="qr-field-group">
										<label for="qr-modal-common-name"><strong>Common Name:</strong></label>
										<input type="text" id="qr-modal-common-name" name="common_name" class="regular-text" placeholder="Enter a friendly name" />
										<p class="description">A friendly name to help you identify this QR code.</p>
									</div>
									
									<div class="qr-field-group">
										<label for="qr-modal-referral-code"><strong>Referral Code:</strong></label>
										<input type="text" id="qr-modal-referral-code" name="referral_code" class="regular-text" placeholder="Enter a referral code" />
										<p class="description">A referral code for tracking and analytics.</p>
									</div>
									
									<div class="qr-field-group">
										<label><strong>QR Code:</strong></label>
										<p id="qr-modal-qr-code" style="font-family: monospace; background: #f9f9f9; padding: 8px; border-radius: 4px;"></p>
									</div>
									
									<div class="qr-field-group">
										<label><strong>QR URL:</strong></label>
										<p><a id="qr-modal-qr-url" href="" target="_blank" style="word-break: break-all;"></a></p>
									</div>
									
									<div class="qr-field-group">
										<label for="qr-modal-destination-url"><strong>Destination URL:</strong></label>
										<input type="url" id="qr-modal-destination-url" name="destination_url" class="regular-text" placeholder="https://example.com" />
										<p class="description">The URL that users will be redirected to when they scan this QR code.</p>
									</div>
									
									<div class="qr-field-group">
										<label><strong>Linked Post:</strong></label>
										<p id="qr-modal-post-title" style="font-style: italic;"></p>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
						<div class="qr-modal-footer">
			<button type="button" class="button button-secondary qr-modal-close">Close</button>
			<button type="button" class="button button-primary" id="qr-modal-save">Save Changes</button>
		</div>
			</div>
		</div>
	`;
	
	$('body').append(modalHTML);
	
	// Add modal styles
	const modalStyles = `
		<style>
		.qr-modal {
			position: fixed;
			z-index: 100000;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0,0,0,0.5);
		}
		.qr-modal-content {
			background-color: #fefefe;
			margin: 5% auto;
			border: 1px solid #888;
			border-radius: 6px;
			width: 90%;
			max-width: 800px;
			max-height: 90vh;
			overflow-y: auto;
			box-shadow: 0 4px 8px rgba(0,0,0,0.1);
		}
		.qr-modal-header {
			padding: 20px;
			background-color: #f1f1f1;
			border-bottom: 1px solid #ddd;
			border-radius: 6px 6px 0 0;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.qr-modal-header h2 {
			margin: 0;
			font-size: 1.3em;
		}
		.qr-modal-close {
			color: #aaa;
			font-size: 28px;
			font-weight: bold;
			cursor: pointer;
		}
		.qr-modal-close:hover {
			color: #000;
		}
		.qr-modal-body {
			padding: 20px;
		}
		.qr-modal-row {
			display: flex;
			gap: 30px;
		}
		.qr-modal-left {
			flex: 0 0 250px;
		}
		.qr-modal-right {
			flex: 1;
		}
		.qr-field-group {
			margin-bottom: 20px;
		}
		.qr-field-group label {
			display: block;
			margin-bottom: 5px;
		}
		.qr-field-group input {
			width: 100%;
		}
		.qr-field-group .description {
			color: #666;
			font-style: italic;
			margin-top: 5px;
		}
		.qr-stats {
			margin-top: 20px;
			padding: 15px;
			background: #f9f9f9;
			border-radius: 4px;
		}
		.qr-stats h4 {
			margin-top: 0;
			margin-bottom: 10px;
		}
		.qr-stats p {
			margin: 5px 0;
		}
		.qr-modal-footer {
			padding: 20px;
			background-color: #f1f1f1;
			border-top: 1px solid #ddd;
			border-radius: 0 0 6px 6px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		@media (max-width: 768px) {
			.qr-modal-content {
				width: 95%;
				margin: 2% auto;
			}
			.qr-modal-row {
				flex-direction: column;
			}
			.qr-modal-left {
				flex: none;
			}
		}
		
		/* URL validation styles */
		#qr-modal-destination-url.valid-url {
			border-color: #28a745;
			box-shadow: 0 0 0 1px #28a745;
		}
		
		#qr-modal-destination-url.invalid-url {
			border-color: #dc3545;
			box-shadow: 0 0 0 1px #dc3545;
		}
		
		/* Referral code validation styles */
		#qr-modal-referral-code.valid-code {
			border-color: #28a745;
			box-shadow: 0 0 0 1px #28a745;
		}
		
		#qr-modal-referral-code.invalid-code {
			border-color: #dc3545;
			box-shadow: 0 0 0 1px #dc3545;
		}
		
		/* Table row update highlight effect */
		.updated-cell {
			background-color: #d4edda !important;
			transition: background-color 0.5s ease;
		}
		
		/* Post unlinked indicator */
		.post-unlinked {
			color: #dc3545;
			font-style: italic;
		}
		</style>
	`;
	
	$('head').append(modalStyles);
	
	// Modal event handlers
	$(document).on('click', '.qr-code-modal-trigger', function(e) {
		e.preventDefault();
		const qrId = $(this).data('qr-id');
		
		// Check if this QR code has been deleted
		if (window.deletedQRCodes && window.deletedQRCodes.includes(qrId)) {
			showAdminNotice('This QR code has been deleted and is no longer available.', 'error');
			// Remove this element to prevent future clicks
			$(this).remove();
			return;
		}
		
		// Check if the element still exists in the DOM (basic validation)
		if (!$(this).is(':visible') || $(this).closest('tr').length === 0) {
			showAdminNotice('This QR code is no longer available.', 'error');
			return;
		}
		
		openQRModal(qrId);
	});
	
	$(document).on('click', '.qr-modal-close', function() {
		closeQRModal();
	});
	
	$(document).on('click', '#qr-code-modal', function(e) {
		if (e.target.id === 'qr-code-modal') {
			closeQRModal();
		}
	});
	
	$(document).on('click', '#qr-modal-save', function() {
		saveQRDetails();
	});
	
	// Real-time validation for destination URL field
	$(document).on('input', '#qr-modal-destination-url', function() {
		const url = $(this).val().trim();
		const $field = $(this);
		const $description = $field.siblings('.description');
		
		// Remove existing validation classes
		$field.removeClass('valid-url invalid-url');
		
		if (url === '') {
			// Empty field - no validation needed
			$description.text('The URL that users will be redirected to when they scan this QR code.').css('color', '#666');
			return;
		}
		
		if (isValidUrl(url)) {
			$field.addClass('valid-url');
			$description.text('✓ Valid URL format').css('color', '#28a745');
		} else {
			$field.addClass('invalid-url');
			$description.text('✗ Please enter a valid URL starting with http:// or https://').css('color', '#dc3545');
		}
	});
	
	// Real-time validation for referral code field
	$(document).on('input', '#qr-modal-referral-code', function() {
		const code = $(this).val().trim();
		const $field = $(this);
		const $description = $field.siblings('.description');
		
		// Remove existing validation classes
		$field.removeClass('valid-code invalid-code');
		
		if (code === '') {
			// Empty field - no validation needed
			$description.text('A referral code for tracking and analytics.').css('color', '#666');
			return;
		}
		
		if (/^[a-zA-Z0-9\-_]+$/.test(code)) {
			$field.addClass('valid-code');
			$description.text('✓ Valid referral code format').css('color', '#28a745');
		} else {
			$field.addClass('invalid-code');
			$description.text('✗ Referral code can only contain letters, numbers, hyphens, and underscores').css('color', '#dc3545');
		}
	});
	
	// ESC key to close modal
	$(document).on('keydown', function(e) {
		if (e.keyCode === 27 && $('#qr-code-modal').is(':visible')) {
			closeQRModal();
		}
	});
	
	/**
	 * Open QR code details modal
	 */
	function openQRModal(qrId) {
		// Check if this QR code has been deleted
		if (window.deletedQRCodes && window.deletedQRCodes.includes(qrId)) {
			showAdminNotice('This QR code has been deleted and is no longer available.', 'error');
			return;
		}
		
		$('#qr-code-modal').show();
		$('.qr-modal-loading').show();
		$('.qr-modal-content-inner').hide();
		$('.qr-modal-message').remove();
		
		// Fetch QR code details
		$.post(qr_trackr_ajax.ajaxurl, {
			action: 'qr_trackr_get_qr_details',
			qr_id: qrId,
			nonce: qr_trackr_ajax.nonce
		})
		.done(function(response) {
			if (response.success) {
				populateModal(response.data);
				$('.qr-modal-loading').hide();
				$('.qr-modal-content-inner').show();
				
				// If QR code image was generated, refresh the table row.
				if (response.data.qr_code_url) {
					updateQRImageInTable(qrId, response.data.qr_code_url);
				}
			} else {
				// Handle specific error cases
				let errorMessage = response.data || 'Error loading QR code details.';
				if (errorMessage.includes('QR code not found')) {
					errorMessage = 'This QR code has been deleted or is no longer available. Please refresh the page to see the updated list.';
					// Mark this QR code as deleted to prevent future attempts
					window.deletedQRCodes.push(qrId);
					// Remove any remaining elements with this QR ID
					$('[data-qr-id="' + qrId + '"]').remove();
				}
				showModalMessage(errorMessage, 'error');
				$('.qr-modal-loading').hide();
			}
		})
		.fail(function(xhr, status, error) {
			showModalMessage('Error loading QR code details.', 'error');
			$('.qr-modal-loading').hide();
		});
	}
	
	/**
	 * Close QR code modal
	 */
	function closeQRModal() {
		$('#qr-code-modal').hide();
	}
	
	/**
	 * Populate modal with QR code data
	 */
	function populateModal(data) {
		$('#qr-modal-id').val(data.id);
		$('#qr-modal-common-name').val(data.common_name);
		$('#qr-modal-referral-code').val(data.referral_code);
		$('#qr-modal-qr-code').text(data.qr_code);
		$('#qr-modal-scans').text(data.access_count);
		$('#qr-modal-recent-scans').text(data.recent_scans);
		$('#qr-modal-last-accessed').text(data.last_accessed);
		$('#qr-modal-created').text(data.created_at);
		
		// URLs
		$('#qr-modal-qr-url').attr('href', data.qr_url).text(data.qr_url);
		$('#qr-modal-destination-url').val(data.destination_url);
		
		// Post title
		$('#qr-modal-post-title').text(data.post_title || 'Not linked to a post');
		
		// QR Image
		if (data.qr_code_url) {
			$('#qr-modal-image').attr('src', data.qr_code_url).show();
		} else {
			$('#qr-modal-image').hide();
		}
		
		// Clear validation states and trigger validation for populated fields
		$('#qr-modal-destination-url, #qr-modal-referral-code').removeClass('valid-url invalid-url valid-code invalid-code');
		$('#qr-modal-destination-url').trigger('input');
		$('#qr-modal-referral-code').trigger('input');
	}
	
	/**
	 * Save QR code details
	 */
	function saveQRDetails() {
		const qrId = $('#qr-modal-id').val();
		const commonName = $('#qr-modal-common-name').val().trim();
		const referralCode = $('#qr-modal-referral-code').val().trim();
		const destinationUrl = $('#qr-modal-destination-url').val().trim();
		
		// Client-side validation
		const validationErrors = [];
		
		// Validate destination URL if provided
		if (destinationUrl && !isValidUrl(destinationUrl)) {
			validationErrors.push('Please enter a valid destination URL starting with http:// or https://');
		}
		
		// Validate referral code if provided
		if (referralCode && !/^[a-zA-Z0-9\-_]+$/.test(referralCode)) {
			validationErrors.push('Referral code can only contain letters, numbers, hyphens, and underscores.');
		}
		
		// Show validation errors if any
		if (validationErrors.length > 0) {
			showModalMessage(validationErrors.join('<br>'), 'error');
			$('#qr-modal-save').prop('disabled', false).text('Save Changes');
			return;
		}
		
		$('#qr-modal-save').prop('disabled', true).text('Saving...');
		$('.qr-modal-message').remove();
		
		$.post(qr_trackr_ajax.ajaxurl, {
			action: 'qr_trackr_update_qr_details',
			qr_id: qrId,
			common_name: commonName,
			referral_code: referralCode,
			destination_url: destinationUrl, // Include destination_url in the AJAX call
			nonce: qr_trackr_ajax.nonce
		})
		.done(function(response) {
			if (response.success) {
				console.log('AJAX response received:', response);
				showModalMessage(response.data.message, 'success');
				
				// If post was unlinked, update the modal to reflect this
				if (response.data.post_unlinked) {
					$('#qr-modal-post-title').text('Not linked to a post');
					// Add a visual indicator that the post was unlinked
					$('#qr-modal-post-title').addClass('post-unlinked').html('Not linked to a post <span style="color: #dc3545; font-size: 12px;">(unlinked due to URL change)</span>');
				}
				
				// If QR code was regenerated, update the modal image
				if (response.data.qr_code_url) {
					$('#qr-modal-image').attr('src', response.data.qr_code_url);
					// Add a visual indicator that the QR code was regenerated
					$('#qr-modal-image').addClass('regenerated').css('border', '2px solid #28a745');
					setTimeout(function() {
						$('#qr-modal-image').removeClass('regenerated').css('border', '1px solid #ddd');
					}, 3000);
				}
				
				// Update the table row if visible
				console.log('Calling updateTableRow for QR ID:', qrId);
				updateTableRow(qrId, response.data);
				setTimeout(function() {
					closeQRModal();
				}, 1500);
			} else {
				showModalMessage(response.data || 'Error saving QR code details.', 'error');
			}
		})
		.fail(function(xhr, status, error) {
			showModalMessage('Error saving QR code details.', 'error');
		})
		.always(function() {
			$('#qr-modal-save').prop('disabled', false).text('Save Changes');
		});
	}
	
	/**
	 * Show message in modal
	 */
	function showModalMessage(message, type) {
		// Remove any existing message element
		$('.qr-modal-message').remove();
		
		// Create new message element
		const $messageElement = $('<div class="qr-modal-message ' + type + '" style="margin-right: 10px; padding: 8px 12px; border-radius: 4px; display: block;"></div>');
		
		// Set styling based on type
		if (type === 'success') {
			$messageElement.css({
				'background-color': '#d4edda',
				'color': '#155724',
				'border': '1px solid #c3e6cb'
			});
		} else {
			$messageElement.css({
				'background-color': '#f8d7da',
				'color': '#721c24',
				'border': '1px solid #f5c6cb'
			});
		}
		
		// Use html() instead of text() to support line breaks
		$messageElement.html(message);
		
		// Insert before the buttons in the footer
		$('.qr-modal-footer').prepend($messageElement);
	}
	
	/**
	 * Update table row after successful save
	 */
	function updateTableRow(qrId, data) {
		console.log('Updating table row for QR ID:', qrId, 'with data:', data); // Debug log
		console.log('Data breakdown:', {
			common_name: data.common_name,
			destination_url: data.destination_url,
			referral_code: data.referral_code,
			qr_code_url: data.qr_code_url
		});
		
		// Find the table row by looking for the QR image or QR code text with the matching QR ID
		const $row = $('tr').has('[data-qr-id="' + qrId + '"]');
		console.log('Found rows:', $row.length); // Debug log
		
		// More specific row selection - look for the actual table row in the admin table
		const $adminTable = $('.wp-list-table');
		const $specificRow = $adminTable.find('tr').has('[data-qr-id="' + qrId + '"]');
		console.log('Found rows in admin table:', $specificRow.length);
		
		// Use the more specific row if found, otherwise fall back to the general search
		const $targetRow = $specificRow.length > 0 ? $specificRow : $row;
		
		// Debug: Check for multiple elements with the same QR ID
		const $allElementsWithQrId = $('[data-qr-id="' + qrId + '"]');
		console.log('All elements with QR ID ' + qrId + ':', $allElementsWithQrId.length);
		$allElementsWithQrId.each(function(index) {
			console.log('Element ' + index + ':', $(this).prop('tagName'), $(this).text().substring(0, 50));
		});
		
		if ($targetRow.length) {
			// Use more specific column selection based on CSS classes
			const $nameCell = $targetRow.find('td.column-common_name');
			if ($nameCell.length) {
				const nameText = data.common_name || 'No name set';
				$nameCell.html(data.common_name ? nameText : '<em>' + nameText + '</em>');
				// Add highlight effect
				$nameCell.addClass('updated-cell').delay(2000).queue(function() {
					$(this).removeClass('updated-cell');
					$(this).dequeue();
				});
			}
			
			// Update destination URL column
			const $urlCell = $targetRow.find('td.column-destination_url');
			if ($urlCell.length && data.destination_url) {
				const urlText = data.destination_url.length > 50 ? data.destination_url.substring(0, 50) + '...' : data.destination_url;
				$urlCell.html('<a href="' + data.destination_url + '" target="_blank">' + urlText + '</a>');
				// Add highlight effect
				$urlCell.addClass('updated-cell').delay(2000).queue(function() {
					$(this).removeClass('updated-cell');
					$(this).dequeue();
				});
			}
			
			// Update QR code column
			const $qrCodeCell = $targetRow.find('td.column-qr_code');
			if ($qrCodeCell.length && data.qr_code) {
				const trackingUrl = window.location.origin + '/redirect/' + data.qr_code;
				const qrCodeHtml = `<code style="font-size: 12px; padding: 2px 4px; background: #f1f1f1; border-radius: 3px;">${data.qr_code}</code><br><a href="${trackingUrl}" target="_blank" class="button button-small" style="margin-top: 4px;">Visit Link</a>`;
				$qrCodeCell.html(qrCodeHtml);
				// Add highlight effect
				$qrCodeCell.addClass('updated-cell').delay(2000).queue(function() {
					$(this).removeClass('updated-cell');
					$(this).dequeue();
				});
			}
			
			// Update referral code column
			const $codeCell = $targetRow.find('td.column-referral_code');
			if ($codeCell.length) {
				const codeText = data.referral_code || 'None';
				$codeCell.html(data.referral_code ? '<code>' + data.referral_code + '</code>' : '<em>' + codeText + '</em>');
				// Add highlight effect
				$codeCell.addClass('updated-cell').delay(2000).queue(function() {
					$(this).removeClass('updated-cell');
					$(this).dequeue();
				});
			}
			
			// Update scans column
			const $scansCell = $targetRow.find('td.column-scans');
			if ($scansCell.length && data.scans !== undefined) {
				$scansCell.html(data.scans.toString());
				// Add highlight effect
				$scansCell.addClass('updated-cell').delay(2000).queue(function() {
					$(this).removeClass('updated-cell');
					$(this).dequeue();
				});
			}
			
			// Update QR code image if regenerated
			if (data.qr_code_url) {
				const $imageCell = $targetRow.find('td.column-qr_image');
				if ($imageCell.length) {
					console.log('Updating QR image cell for QR ID:', qrId);
					console.log('Current cell content:', $imageCell.html());
					
					// Clear the cell first to prevent duplication
					$imageCell.empty();
					
					const newImageHtml = `<img src="${data.qr_code_url}" alt="QR Code" style="width: 60px; height: 60px; cursor: pointer; border: 2px solid #28a745; border-radius: 4px;" class="qr-code-modal-trigger" data-qr-id="${qrId}" title="Click to view details" />`;
					$imageCell.html(newImageHtml);
					
					console.log('Updated cell content:', $imageCell.html());
					
					// Add highlight effect
					$imageCell.addClass('updated-cell').delay(2000).queue(function() {
						$(this).removeClass('updated-cell');
						$(this).dequeue();
					});
					// Remove the green border after 3 seconds
					setTimeout(function() {
						$imageCell.find('img').css('border', '1px solid #ddd');
					}, 3000);
				}
			}
		}
	}
	
	/**
	 * Update QR image in table after generation
	 */
	function updateQRImageInTable(qrId, qrCodeUrl) {
		const $row = $('tr').find('[data-qr-id="' + qrId + '"]').closest('tr');
		if ($row.length) {
			// Find the QR image column and update it
			const $imageCell = $row.find('td').eq(1); // Assuming QR image is 2nd column
			if ($imageCell.length) {
				const newImageHtml = `<img src="${qrCodeUrl}" alt="QR Code" style="width: 60px; height: 60px; cursor: pointer; border: 1px solid #ddd; border-radius: 4px;" class="qr-code-modal-trigger" data-qr-id="${qrId}" title="Click to view details" />`;
				$imageCell.html(newImageHtml);
			}
		}
	}
	
	/**
	 * Validate URL format
	 */
	function isValidUrl(string) {
		// Trim the string
		const trimmedString = string.trim();
		
		// Use regex pattern for validation (same as backend)
		const urlRegex = /^https?:\/\/.+/;
		return urlRegex.test(trimmedString);
	}
});
})(window.jQuery); 