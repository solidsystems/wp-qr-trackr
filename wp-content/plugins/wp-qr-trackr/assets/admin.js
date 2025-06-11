// QR Trackr Admin JS
jQuery(document).ready(function($) {
  var debug = window.qrTrackrDebugMode && window.qrTrackrDebugMode.debug;

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
    pollForForm();
  }

  $(document).on('submit', 'form.qr-trackr-create-form', function(e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('button[type="submit"]');
    var $list = $('.qr-trackr-list');
    var $msg = $('.qr-trackr-message');
    $btn.prop('disabled', true);
    $msg.remove();
    $list.before('<div class="qr-trackr-message">Creating QR code... <span class="spinner is-active"></span></div>');
    if (debug) console.log('[QR Trackr Debug] AJAX QR code creation started', new Date().toISOString());
    $.post(ajaxurl, $form.serialize() + '&action=qr_trackr_create_qr_ajax', function(response) {
      $('.qr-trackr-message').remove();
      if (debug) console.log('[QR Trackr Debug] AJAX response:', response);
      if (response.success) {
        $list.html(response.data.html);
        // Show QR image and download link if present
        if (response.data.qr_image_html) {
          $list.before('<div class="qr-trackr-message updated">QR code created successfully.<br>' + response.data.qr_image_html + '</div>');
        } else {
          $list.before('<div class="qr-trackr-message updated">QR code created successfully.</div>');
        }
      } else {
        $list.before('<div class="qr-trackr-message error">' + (response.data && response.data.message ? response.data.message : 'Error creating QR code') + '</div>');
      }
      $btn.prop('disabled', false);
      setTimeout(function() { $('.qr-trackr-message').fadeOut(400, function() { $(this).remove(); }); }, 3000);
    }).fail(function(xhr, status, error) {
      $('.qr-trackr-message').remove();
      $list.before('<div class="qr-trackr-message error">AJAX error: ' + error + '</div>');
      if (debug) console.log('[QR Trackr Debug] AJAX error:', error, xhr);
      $btn.prop('disabled', false);
      setTimeout(function() { $('.qr-trackr-message').fadeOut(400, function() { $(this).remove(); }); }, 3000);
    });
  });
}); 