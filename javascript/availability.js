(function($) {
    $(document).ready(function() {

       //Add more criterias
       $('.addmorefields').on('click', () => {
        const Lastindex = $('.specificdateparent') ? $('.specificdateparent').last().data('id') : 1;
        $.ajax(M.cfg.wwwroot + '/mod/coach/ajax/ajax.php', {
            method: 'post',
            dataType: 'json',
            data: {
              action: 'getSpecificFields',
              Lastindex: Lastindex,
            },
            success: function (response) {
              if (response.status === 'ok') {
                if (Lastindex > 1){
                  $('.specificdateparent').last().after(response.content);
                } else {
                  $('#fitem_id_addmorecriterias').before(response.content);
                }
              } else {
                console.log(response.error);
              }
            }
        });
      });
      //Remove criteria
      $('body').on('click', '.removeitem', (e) => {
        const inputid = $(e.currentTarget).data('id');
        $(`.specificdateparent[data-id="${inputid}"]`).remove();
      });

      //See which button was clicked
      $('input[type=submit]').on('click', (e) => {
        $('input[type=submit]').removeAttr('clicked');
        $(e.currentTarget).attr('clicked', 'true');
      });

      //SAVE specific dates
      $('#id_submitbutton').closest('form').on('submit', (e) => {
        const pressed = $('input[type=submit][clicked=true]');
        if (pressed.val() != 'Cancel') {
          e.preventDefault();
          const dates = [];
          $('.specificdateparent').each((i, e) => {
            const parentid = $(e).data('id');
            const date = $(`#date[data-id="${parentid}"]`).val();
            const available = $(`input[name="availability${parentid}"]:checked`).val();
            const starttime = $(`.from_hour[data-id="${parentid}"]`).val() + ':' + $(`.from_minutes[data-id="${parentid}"]`).val();
            const endtime = $(`.to_hour[data-id="${parentid}"]`).val() + ':' + $(`.to_minutes[data-id="${parentid}"]`).val();

            const specificDate = {date, available, starttime, endtime};
            dates.push(specificDate);
          });

          $.when(
            $.ajax(M.cfg.wwwroot + '/mod/coach/ajax/ajax.php', {
              method: 'post',
              dataType: 'json',
              data: {
                action: 'saveSpecificDates',
                dates: JSON.stringify(dates),
              },
              success: function (response) {
                if (response.status != 'ok') {
                  console.log(response.error);
                }
              }
            })
          ).done(function(){
              $(e.currentTarget).unbind('submit').submit();
          });
        }
      });

    });
  })(jQuery);