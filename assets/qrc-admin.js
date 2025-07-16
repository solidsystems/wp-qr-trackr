/**
 * QR Code Admin JavaScript
 *
 * Handles AJAX interactions for the QR code admin interface.
 *
 * @package WP_QR_TRACKR
 * @since 1.2.8
 */

jQuery(document).ready(function($) {
	'use strict';

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
										<label><strong>${qrcAdmin.strings.destinationUrl}:</strong></label>
										<p><a id="qr-modal-destination-url" href="" target="_blank" style="word-break: break-all;"></a></p>
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
		</style>
	`;
	
	$('head').append(modalStyles);
	
	// Modal event handlers
	$(document).on('click', '.qr-code-modal-trigger', function(e) {
		e.preventDefault();
		const qrId = $(this).data('qr-id');
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
		.done(function(response) {
			if (response.success) {
				populateModal(response.data);
				$('.qr-modal-loading').hide();
				$('.qr-modal-content-inner').show();
			} else {
				showModalMessage(response.data || qrcAdmin.strings.errorLoadingDetails, 'error');
				$('.qr-modal-loading').hide();
			}
		})
		.fail(function() {
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
		$('#qr-modal-destination-url').attr('href', data.destination_url).text(data.destination_url);
		
		// Post title
		$('#qr-modal-post-title').text(data.post_title || qrcAdmin.strings.notLinkedToPost);
		
		// QR Image
		if (data.qr_code_url) {
			$('#qr-modal-image').attr('src', data.qr_code_url).show();
		} else {
			$('#qr-modal-image').hide();
		}
	}
	
	/**
	 * Save QR code details
	 */
	function saveQRDetails() {
		const qrId = $('#qr-modal-id').val();
		const commonName = $('#qr-modal-common-name').val().trim();
		const referralCode = $('#qr-modal-referral-code').val().trim();
		
		$('#qr-modal-save').prop('disabled', true).text(qrcAdmin.strings.saving);
		$('.qr-modal-message').hide();
		
		$.post(qrcAdmin.ajaxUrl, {
			action: 'qr_trackr_update_qr_details',
			qr_id: qrId,
			common_name: commonName,
			referral_code: referralCode,
			nonce: qrcAdmin.nonce
		})
		.done(function(response) {
			if (response.success) {
				showModalMessage(response.data.message, 'success');
				// Update the table row if visible
				updateTableRow(qrId, response.data);
				setTimeout(function() {
					closeQRModal();
				}, 1500);
			} else {
				showModalMessage(response.data || qrcAdmin.strings.errorSavingDetails, 'error');
			}
		})
		.fail(function() {
			showModalMessage(qrcAdmin.strings.errorSavingDetails, 'error');
		})
		.always(function() {
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
			.text(message)
			.show();
	}
	
	/**
	 * Update table row after successful save
	 */
	function updateTableRow(qrId, data) {
		const $row = $('tr').find('[data-qr-id="' + qrId + '"]').closest('tr');
		if ($row.length) {
			// Update common name column
			const $nameCell = $row.find('td').eq(2); // Assuming common_name is 3rd column
			if ($nameCell.length) {
				const nameText = data.common_name || qrcAdmin.strings.noNameSet;
				$nameCell.html(data.common_name ? nameText : '<em>' + nameText + '</em>');
			}
			
			// Update referral code column
			const $codeCell = $row.find('td').eq(5); // Assuming referral_code is 6th column
			if ($codeCell.length) {
				const codeText = data.referral_code || qrcAdmin.strings.none;
				$codeCell.html(data.referral_code ? '<code>' + data.referral_code + '</code>' : '<em>' + codeText + '</em>');
			}
		}
	}
}); 