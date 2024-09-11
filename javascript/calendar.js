(function($) {
    $(document).ready(function() {

        console.log('calendar.js######');

        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          dateClick: function(info) {
            console.log('Clicked on: ' + info.dateStr);
            console.log('Coordinates: ' + info.jsEvent.pageX + ',' + info.jsEvent.pageY);
            console.log('Current view: ' + info.view.type);
            // change the day's background color just for fun

            // remove all background colors
            var days = document.getElementsByClassName('fc-day');
            for (var i = 0; i < days.length; i++) {
              days[i].style.backgroundColor = '';
            }
            
            info.dayEl.style.backgroundColor = 'red';
            console.log(info);

            $('#selected-date').html(info.dateStr);
            const eventid = $('.eventid') ? $('.eventid').val() : 0;
            let cmid = $('input[name="cmid"]').val();
            let typeid = $('input[name="typeid"]').val();
            $.ajax(M.cfg.wwwroot + '/mod/coach/ajax/ajax.php', {
                method: 'post',
                dataType: 'json',
                data: {
                  action: 'getEvents',
                  date: info.dateStr,
                  typeid: typeid,
                  eventid : eventid,
                  cmid: cmid,
                },
                success: function (data) {
                  if (data.status === 'ok') {
                    $('.calendar-hours').html(data.content);
                  }
                  if (data.status === 'invaliddate') {

                    const minDay = new Date(data.content.minDay * 1000);
                    const maxDay = new Date(data.content.maxDay * 1000);

                    const message = 'Please select date between ' + minDay.toLocaleDateString() + ' and ' + maxDay.toLocaleDateString()
                    $('.calendar-hours').html(message);
                  }
                }
            });


          }
        });
        calendar.render();
        console.log(calendar);
        if($('.eventdate')) {
          const eventdate = $('.eventdate').val();
          const eventid = $('.eventid').val();
          const cmid = $('input[name="cmid"]').val();
          const typeid = $('input[name="typeid"]').val();
          $(`td[data-date="${eventdate}"]`).css("background-color", "red");
          $.ajax(M.cfg.wwwroot + '/mod/coach/ajax/ajax.php', {
            method: 'post',
            dataType: 'json',
            data: {
              action: 'getEvents',
              date: eventdate,
              typeid: typeid,
              eventid: eventid,
              cmid: cmid,
            },
            success: function (data) {
              if (data.status === 'ok') {
                $('.calendar-hours').html(data.content);
              }
            }
          });
        }


    });
  })(jQuery);
