(function ($, Drupal, once) {
  Drupal.behaviors.projectAppointment = {
    attach: function (context, settings) {
      console.log('projectAppointment behavior attached');

      const css = `
        .appointment-popup {
          display: none;
          position: absolute;
          z-index: 10;
          padding: 10px;
          background-color: #000;
          color: #fff;
          box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
          border-radius: 5px;
          width: 200px;
        }
        .appointment-details:hover .appointment-popup {
          display: block;
        }
      `;
      const head = document.head || document.getElementsByTagName('head')[0];
      const style = document.createElement('style');
      head.appendChild(style);
      style.type = 'text/css';
      if (style.styleSheet){
        // This is required for IE8 and below.
        style.styleSheet.cssText = css;
      } else {
        style.appendChild(document.createTextNode(css));
      }

      once('load-appointments', 'body', context).forEach(() => {
        loadAndAppendAppointments();
      });

      function loadAndAppendAppointments() {
        var url = Drupal.url('appointments/data');
        console.log('Loading appointments from:', url);

        $.getJSON(url, function (appointments) {
          console.log('Appointments loaded:', appointments);
          if (appointments.length > 0) {
            let appointmentsByDate = {};

            appointments.forEach(function(appointment) {
              if (!appointment.completed) { // Filter out completed appointments
                let appointmentDate = new Date(appointment.date).toISOString().split('T')[0];
                if (!appointmentsByDate[appointmentDate]) {
                  appointmentsByDate[appointmentDate] = [];
                }
                appointmentsByDate[appointmentDate].push(appointment);
              }
            });

            $('.calendar-view-day', context).each(function() {
              var dayTime = $(this).find('.calendar-view-day__number').attr('datetime');
              var calendarDate = new Date(dayTime.split('+')[0]).toISOString().split('T')[0];

              if (appointmentsByDate[calendarDate]) {
                let appointmentsList = appointmentsByDate[calendarDate].map(function(appointment) {
                  return `<li><span class="appointment-details">${appointment.name}<div class="appointment-popup">Duration: ${appointment.duration}<br>Timezone: ${appointment.timezone}<br>Platform: ${appointment.platform}<br>Project Details: ${appointment.project_details}<br>Participants: ${appointment.participants}</div></span></li>`;
                }).join('');

                $(this).find('.calendar-view-day__rows').html(`<ul>${appointmentsList}</ul>`);
                // Update the title with the number of appointments
                $(this).find('.calendar-view-day__number').attr('title', appointmentsByDate[calendarDate].length + ' appointments');
              }
            });
          } else {
            console.log('No appointments to process');
          }
        }).fail(function(jqXHR, textStatus, errorThrown) {
          console.error("Error fetching appointments: " + textStatus + ", " + errorThrown);
        });
      }

      $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url.includes('/appointment/editorial-calendar') || settings.url.includes('/editorial-calendar')) {
          console.log('AJAX call matched the editorial calendar paths. Reloading appointments.');
          once('load-appointments-post-ajax', 'body', context).forEach(() => {
            loadAndAppendAppointments();
          });
        }
      });
    }
  };
})(jQuery, Drupal, once);
