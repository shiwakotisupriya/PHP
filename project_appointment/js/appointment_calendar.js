(function ($, Drupal) {
  // Diagnostic check for jQuery and jQuery Once
  $(document).ready(function () {
    if (typeof $ === 'undefined') {
      console.error('jQuery is not loaded');
    } else {
      console.log('jQuery is loaded');
      if (typeof $.fn.once === 'undefined') {
        console.error('jQuery Once is not loaded');
      } else {
        console.log('jQuery Once is loaded');
      }
    }
  });

  Drupal.behaviors.appointmentDatePicker = {
    attach: function (context, settings) {
      $(context).find('.datepicker').once('appointmentDatePicker').each(function () {
        var $datepicker = $(this);
        $.ajax({
          url: '/disabled-dates', // Adjust this URL to your endpoint.
          method: 'GET',
          success: function (data) {
            // Assuming data is an array of dates to disable.
            $datepicker.datepicker({
              beforeShowDay: function(date) {
                var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                return [data.indexOf(string) === -1];
              }
            });
          }
        });
      });
    }
  };
})(jQuery, Drupal);
