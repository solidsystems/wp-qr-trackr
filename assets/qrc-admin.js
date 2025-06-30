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
}); 