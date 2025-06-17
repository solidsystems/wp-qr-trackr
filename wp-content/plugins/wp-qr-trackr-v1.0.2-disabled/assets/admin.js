// QR Trackr Admin JS
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

    // Handle QR code creation form submission
    var $form = $('.qr-trackr-create-form');
    var $button = $('#qr-trackr-create-button');
    var $container = $('.qr-trackr-container');
    var $list = $('.qr-trackr-list');
    var $msg = $('.qr-trackr-message');

    if ($form.length && $button.length) {
      $form.on('submit', function(e) {
        e.preventDefault();
        $button.prop('disabled', true);
        $msg.html('<div class="notice notice-info"><p>Creating QR code...</p></div>');

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'qr_trackr_create_qr_ajax',
            qr_trackr_admin_new_post_id: $('#qr_trackr_admin_new_post_id').val(),
            qr_trackr_admin_new_qr_nonce: $('#qr_trackr_admin_new_qr_nonce').val()
          },
          success: function(response) {
            if (response.success) {
              $msg.html('<div class="notice notice-success"><p>QR code created successfully! You can download the QR code image by clicking the "Download" button below the preview.</p></div>');
              $list.html(response.data.html);
              $form[0].reset();
            } else {
              $msg.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
            }
          },
          error: function() {
            $msg.html('<div class="notice notice-error"><p>Error creating QR code. Please try again.</p></div>');
          },
          complete: function() {
            $button.prop('disabled', false);
            setTimeout(function() {
              $msg.fadeOut();
            }, 20000);
          }
        });
      });
    }

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
            $link.closest('tr').find('.column-destination_url').text(newDestination);
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