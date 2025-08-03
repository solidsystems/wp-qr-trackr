/**
 * QR Code Admin JavaScript
 *
 * Handles AJAX interactions for the QR code admin interface.
 *
 * @package WP_QR_TRACKR
 * @since 1.2.8
 */

jQuery(document).ready(function ($) {
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

	// Post search functionality
	let searchTimeout;
	const $postSearch = $('#post_search');
	const $postResults = $('#post_search_results');
	const $postId = $('#post_id');
	const $selectedPostInfo = $('#selected_post_info');

	// Handle destination type change
	$('#destination_type').change(function () {
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
	$postSearch.on('input', function () {
		const searchTerm = $(this).val().trim();

		clearTimeout(searchTimeout);

		if (searchTerm.length < 2) {
			$postResults.hide().empty();
			return;
		}

		searchTimeout = setTimeout(function () {
			searchPosts(searchTerm);
		}, 300);
	});

	// Handle clicking outside search results to hide them
	$(document).on('click', function (e) {
		if (!$(e.target).closest('#post-selector').length) {
			$postResults.hide();
		}
	});

	// Focus search results when clicking on search input
	$postSearch.on('focus', function () {
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
			success: function (response) {
				if (response.success && response.data.posts) {
					displaySearchResults(response.data.posts);
				} else {
					$postResults.html('<div style="padding: 10px; color: #d63638;"><em>No posts found.</em></div>');
				}
			},
			error: function () {
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
		posts.forEach(function (post) {
			html += `<div class="post-result-item" data-id="${post.id}" data-title="${post.title}" data-type="${post.type}" data-url="${post.url}" style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer; transition: background-color 0.2s;">
				<strong>${post.title}</strong> <span style="color: #666;">(${post.type})</span>
			</div>`;
		});

		$postResults.html(html).show();

		// Handle clicking on search results
		$postResults.off('click', '.post-result-item').on('click', '.post-result-item', function () {
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
			.on('mouseenter', '.post-result-item', function () {
				$(this).css('background-color', '#f6f7f7');
			})
			.on('mouseleave', '.post-result-item', function () {
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
		$('#destination_url').val(url); // Set the destination URL
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
		$('#destination_url').val(''); // Clear the destination URL
		$postResults.hide().empty();
		$selectedPostInfo.hide();
	}

	// Add clear button functionality
	const $clearButton = $('<button type="button" style="margin-left: 5px;">Clear</button>');
	$clearButton.on('click', function (e) {
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
					<h2 id="qr-modal-title">${qrcAdmin.strings.qrCodeDetails}</h2>
					<span class="qr-modal-close">&times;</span>
				</div>
				<div class="qr-modal-body">
					<div class="qr-modal-loading" style="text-align: center; padding: 20px;">
						<span class="spinner is-active"></span>
						<p>${qrcAdmin.strings.loading}</p>
					</div>
					<div class="qr-modal-content-inner" style="display: none;">
						<div class="qr-modal-row">
							<div class="qr-modal-left">
								<div class="qr-image-container">
									<img id="qr-modal-image" src="" alt="QR Code" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px;" />
								</div>
								<div class="qr-stats">
									<h4>${qrcAdmin.strings.statistics}</h4>
									<p><strong>${qrcAdmin.strings.totalScans}:</strong> <span id="qr-modal-scans">0</span></p>
									<p><strong>${qrcAdmin.strings.recentScans}:</strong> <span id="qr-modal-recent-scans">0</span></p>
									<p><strong>${qrcAdmin.strings.lastAccessed}:</strong> <span id="qr-modal-last-accessed">Never</span></p>
									<p><strong>${qrcAdmin.strings.created}:</strong> <span id="qr-modal-created"></span></p>
								</div>
							</div>
							<div class="qr-modal-right">
								<form id="qr-modal-form">
									<input type="hidden" id="qr-modal-id" value="" />

									<div class="qr-field-group">
										<label for="qr-modal-common-name"><strong>${qrcAdmin.strings.commonName}:</strong></label>
										<input type="text" id="qr-modal-common-name" name="common_name" class="regular-text" placeholder="${qrcAdmin.strings.enterFriendlyName}" />
										<p class="description">${qrcAdmin.strings.commonNameDesc}</p>
									</div>

									<div class="qr-field-group">
										<label for="qr-modal-referral-code"><strong>${qrcAdmin.strings.referralCode}:</strong></label>
										<input type="text" id="qr-modal-referral-code" name="referral_code" class="regular-text" placeholder="${qrcAdmin.strings.enterReferralCode}" />
										<p class="description">${qrcAdmin.strings.referralCodeDesc}</p>
									</div>

									<div class="qr-field-group">
										<label><strong>${qrcAdmin.strings.qrCode}:</strong></label>
										<p id="qr-modal-qr-code" style="font-family: monospace; background: #f9f9f9; padding: 8px; border-radius: 4px;"></p>
									</div>

									<div class="qr-field-group">
										<label><strong>${qrcAdmin.strings.qrUrl}:</strong></label>
										<p><a id="qr-modal-qr-url" href="" target="_blank" style="word-break: break-all;"></a></p>
									</div>

									<div class="qr-field-group">
										<label for="qr-modal-destination-url"><strong>${qrcAdmin.strings.destinationUrl}:</strong></label>
										<input type="url" id="qr-modal-destination-url" name="destination_url" class="regular-text" placeholder="https://example.com" />
										<p class="description">The URL that users will be redirected to when they scan this QR code.</p>
									</div>

									<div class="qr-field-group">
										<label><strong>${qrcAdmin.strings.linkedPost}:</strong></label>
										<p id="qr-modal-post-title" style="font-style: italic;"></p>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
				<div class="qr-modal-footer">
					<div class="qr-modal-message" style="display: none;"></div>
					<button type="button" class="button button-secondary qr-modal-close">${qrcAdmin.strings.close}</button>
					<button type="button" class="button button-primary" id="qr-modal-save">${qrcAdmin.strings.saveChanges}</button>
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
		.qr-modal-message {
			padding: 8px 12px;
			border-radius: 4px;
			margin-right: 10px;
		}
		.qr-modal-message.success {
			background-color: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}
		.qr-modal-message.error {
			background-color: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
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
	$(document).on('click', '.qr-code-modal-trigger', function (e) {
		e.preventDefault();
		const qrId = $(this).data('qr-id');
		openQRModal(qrId);
	});

	$(document).on('click', '.qr-modal-close', function () {
		closeQRModal();
	});

	$(document).on('click', '#qr-code-modal', function (e) {
		if (e.target.id === 'qr-code-modal') {
			closeQRModal();
		}
	});

	$(document).on('click', '#qr-modal-save', function () {
		saveQRDetails();
	});

	// Real-time validation for destination URL field
	$(document).on('input', '#qr-modal-destination-url', function () {
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
	$(document).on('input', '#qr-modal-referral-code', function () {
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
	$(document).on('keydown', function (e) {
		if (e.keyCode === 27 && $('#qr-code-modal').is(':visible')) {
			closeQRModal();
		}
	});

	/**
	 * Open QR code details modal
	 */
	function openQRModal(qrId) {
		$('#qr-code-modal').show();
		$('.qr-modal-loading').show();
		$('.qr-modal-content-inner').hide();
		$('.qr-modal-message').hide();

		// Fetch QR code details
		$.post(qrcAdmin.ajaxUrl, {
			action: 'qr_trackr_get_qr_details',
			qr_id: qrId,
			nonce: qrcAdmin.nonce
		})
			.done(function (response) {
				if (response.success) {
					populateModal(response.data);
					$('.qr-modal-loading').hide();
					$('.qr-modal-content-inner').show();
				} else {
					// Handle specific error cases
					let errorMessage = response.data || qrcAdmin.strings.errorLoadingDetails;
					if (errorMessage.includes('QR code not found')) {
						errorMessage = 'This QR code has been deleted or is no longer available. Please refresh the page to see the updated list.';
					}
					showModalMessage(errorMessage, 'error');
					$('.qr-modal-loading').hide();
				}
			})
			.fail(function () {
				showModalMessage(qrcAdmin.strings.errorLoadingDetails, 'error');
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
		$('#qr-modal-post-title').text(data.post_title || qrcAdmin.strings.notLinkedToPost);

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
			$('#qr-modal-save').prop('disabled', false).text(qrcAdmin.strings.saveChanges);
			return;
		}

		$('#qr-modal-save').prop('disabled', true).text(qrcAdmin.strings.saving);
		$('.qr-modal-message').hide();

		$.post(qrcAdmin.ajaxUrl, {
			action: 'qr_trackr_update_qr_details',
			qr_id: qrId,
			common_name: commonName,
			referral_code: referralCode,
			destination_url: destinationUrl,
			nonce: qrcAdmin.nonce
		})
			.done(function (response) {
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
						setTimeout(function () {
							$('#qr-modal-image').removeClass('regenerated').css('border', '1px solid #ddd');
						}, 3000);
					}

					// Update the table row if visible
					console.log('Calling updateTableRow for QR ID:', qrId);
					updateTableRow(qrId, response.data);
					setTimeout(function () {
						closeQRModal();
					}, 1500);
				} else {
					showModalMessage(response.data || qrcAdmin.strings.errorSavingDetails, 'error');
				}
			})
			.fail(function () {
				showModalMessage(qrcAdmin.strings.errorSavingDetails, 'error');
			})
			.always(function () {
				$('#qr-modal-save').prop('disabled', false).text(qrcAdmin.strings.saveChanges);
			});
	}

	/**
	 * Show message in modal
	 */
	function showModalMessage(message, type) {
		$('.qr-modal-message')
			.removeClass('success error')
			.addClass(type)
			.html(message)
			.show();
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
		$allElementsWithQrId.each(function (index) {
			console.log('Element ' + index + ':', $(this).prop('tagName'), $(this).text().substring(0, 50));
		});

		if ($targetRow.length) {
			// Use more specific column selection based on CSS classes
			const $nameCell = $targetRow.find('td.column-common_name');
			if ($nameCell.length) {
				const nameText = data.common_name || qrcAdmin.strings.noNameSet;
				$nameCell.html(data.common_name ? nameText : '<em>' + nameText + '</em>');
				// Add highlight effect
				$nameCell.addClass('updated-cell').delay(2000).queue(function () {
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
				$urlCell.addClass('updated-cell').delay(2000).queue(function () {
					$(this).removeClass('updated-cell');
					$(this).dequeue();
				});
			}

			// Update QR code column
			const $qrCodeCell = $targetRow.find('td.column-qr_code');
			if ($qrCodeCell.length && data.qr_code) {
				// Generate clean rewrite URL
				const trackingUrl = window.location.origin + '/qr/' + data.qr_code + '/';
				const qrCodeHtml = `<code style="font-size: 12px; padding: 2px 4px; background: #f1f1f1; border-radius: 3px;">${data.qr_code}</code><br><a href="${trackingUrl}" target="_blank" class="button button-small" style="margin-top: 4px;">Visit Link</a>`;
				$qrCodeCell.html(qrCodeHtml);
				// Add highlight effect
				$qrCodeCell.addClass('updated-cell').delay(2000).queue(function () {
					$(this).removeClass('updated-cell');
					$(this).dequeue();
				});
			}

			// Update referral code column
			const $codeCell = $targetRow.find('td.column-referral_code');
			if ($codeCell.length) {
				const codeText = data.referral_code || qrcAdmin.strings.none;
				$codeCell.html(data.referral_code ? '<code>' + data.referral_code + '</code>' : '<em>' + codeText + '</em>');
				// Add highlight effect
				$codeCell.addClass('updated-cell').delay(2000).queue(function () {
					$(this).removeClass('updated-cell');
					$(this).dequeue();
				});
			}

			// Update scans column
			const $scansCell = $targetRow.find('td.column-scans');
			if ($scansCell.length && data.scans !== undefined) {
				$scansCell.html(data.scans.toString());
				// Add highlight effect
				$scansCell.addClass('updated-cell').delay(2000).queue(function () {
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
					$imageCell.addClass('updated-cell').delay(2000).queue(function () {
						$(this).removeClass('updated-cell');
						$(this).dequeue();
					});
					// Remove the green border after 3 seconds
					setTimeout(function () {
						$imageCell.find('img').css('border', '1px solid #ddd');
					}, 3000);
				}
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
