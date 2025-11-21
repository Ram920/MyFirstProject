/**
 * PHP Email Form Validation - v3.0
 * URL: https://bootstrapmade.com/php-email-form/
 * Author: BootstrapMade.com
 */
!(function($) {
  "use strict";

  $('form.php-email-form').submit(function(e) {
    e.preventDefault();

    var this_form = $(this);
    var action = this_form.attr('action');
    var recaptcha = this_form.attr('data-recaptcha-site-key');

    if (!action) {
      displayError(this_form, 'The form action property is not set!')
      return;
    }

    this_form.find('.loading').slideDown();
    this_form.find('.error-message').slideUp();
    this_form.find('.sent-message').slideUp();

    var formData = new FormData(this_form.get(0));

    if (recaptcha) {
      grecaptcha.ready(function() {
        grecaptcha.execute(recaptcha, {
          action: 'php_email_form_submit'
        }).then(function(token) {
          formData.append('recaptcha-response', token);
          send_form(this_form, action, formData);
        });
      });
    } else {
      send_form(this_form, action, formData);
    }
  });

  function send_form(this_form, action, formData) {
    $.ajax({
      type: "POST",
      url: action,
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'text',
      success: function(response) {
        if (response.trim() === 'OK') {
          displaySuccess(this_form);
        } else {
          displaySuccess(this_form, response);
        }
      },
      error: function(xhr) {
        displayError(this_form, xhr.responseText);
      }
    });
  }

  function displaySuccess(this_form, message) {
    this_form.find('.loading').slideUp('fast');
    if (message) {
      this_form.find('.sent-message').html(message).slideDown('fast');
    } else {
      this_form.find('.sent-message').slideDown('fast');
    }

    // If on the cart page, hide the cart contents after successful submission
    if (this_form.closest('.inner-page').length) { // A simple way to detect if we are on cart.php
      $('#cart-contents').slideUp();
    }

    this_form.find('input:not([type=submit]), textarea').val('');
  }

  function displayError(this_form, error) {
    this_form.find('.loading').slideUp('fast');
    this_form.find('.error-message').html(error).slideDown('fast');
  }

})(jQuery);
