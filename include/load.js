$(function() {
  window.wt = window.wt == null ? {} : window.wt;

  window.wt.lpad = function(s, p, l) {
    s = s.toString();
    while (s.length < l) {
      s = p + s;
    }
    return s;
  }

  window.wt.getlist = function() {
    $.getJSON('?a=list', function(data) {
      if (data.status === false) {
        window.wt.timeouts.list = window.setTimeout(window.wt.getlist, 5000);
        return false;
      }

      for (let i=0; i < data.data.length; i++) {
        for (j=0; j < window.wt.actions.length; j++) {
          if (window.wt.actions[j].id === data.data[i].id_webtriggers) {
            data.data[i].actionname = window.wt.actions[j].name;
            break;
          }
        }

        if (data.data[i].actionname == null) {
          data.data[i].actionname = '';
        }

        let statustext =
          window.wt.t(window.wt.statuses[data.data[i].status]);

        // error
        if (data.data[i].status < 0) {
          statustext +=
            '<br>' + window.wt.t('Return code') + ': ' + data.data[i].returncode +
            '<br>' + window.wt.t('Output') + ': ' + data.data[i].output;
        // 1 = started
        } else if (data.data[i].status === 1) {

          // source: https://stackoverflow.com/questions/13903897/javascript-return-number-of-days-hours-minutes-seconds-between-two-dates
          let date_future = new Date(data.data[i].started).getTime(),
              date_now = (new Date()).getTime();

          // get total seconds between the times
          let delta = Math.abs(date_future - date_now) / 1000;

          // calculate (and subtract) whole days
          let days = Math.floor(delta / 86400);
          delta -= days * 86400;

          // calculate (and subtract) whole hours
          let hours = Math.floor(delta / 3600) % 24;
          delta -= hours * 3600;

          // calculate (and subtract) whole minutes
          let minutes = Math.floor(delta / 60) % 60;
          delta -= minutes * 60;

          // what's left is seconds
          let seconds = Math.round(delta % 60),  // in theory the modulus is not required
            phours = window.wt.lpad(hours, '0', 2),
            pminutes = window.wt.lpad(minutes, '0', 2),
            pseconds = window.wt.lpad(seconds, '0', 2);

          if (days > 0) {
            statustext += ' ... ' + [days, pminutes, pseconds].join(':')
          } else if (hours > 0) {
            statustext += ' ... ' + [phours, pminutes, pseconds].join(':')
          } else if (minutes > 0) {
            statustext += ' ... ' + [pminutes, pseconds].join(':')
          } else if (seconds > 0) {
            statustext += ' ... ' + [pseconds].join(':')
          }
        }

        let queuerow = $('#queuerow' + data.data[i].id);



        if (!queuerow.length) {
          while ($('#queue tbody tr').length > 9) {
            $('#queue tbody tr').last().remove();
          }

          $('#queue tbody').prepend(

            $('<tr>')
              .attr('id', 'queuerow' + data.data[i].id)
              .append(
                $('<td>')
                  .addClass('actionname')
                  .text(data.data[i].actionname)
              )
              .append(
                $('<td>')
                  .addClass('status')
                  .text(statustext)
              )
              .append(
                $('<td>')
                  .addClass('created')
                  .addClass('extra')
                  .text(data.data[i].created)
              )
              .append(
                $('<td>')
                  .addClass('started')
                  .addClass('extra')
                  .text(data.data[i].started)
              )
              .append(
                $('<td>')
                  .addClass('ended')
                  .addClass('extra')
                  .text(data.data[i].ended)
              )
              .append(
                $('<td>')
                  .append(
                    data.data[i].status === 0 &&
                    data.data[i].id >= 0
                    ?
                      $('<button>')
                        .text('Abort')
                        .bind('click', function() {
                          let id = $(this).parents('tr:first').attr('id').replace('queuerow', '');
                          $.getJSON('?action=abort&id_orders=' + id + '&format=json');
                          $(this).attr('disabled', true);
                        })
                    :
                    ''
                  )
              )
          )

        } else {

          let tdstatus = queuerow.find('.status').first();
          if (tdstatus.html() !== statustext) {
            tdstatus.html(statustext);
          }
          for (let col of ['actionname', 'created', 'started', 'ended']) {
            let td = queuerow.find('.' + col).first();
            if (td.html() !== data.data[i][col]) {
              td.html(data.data[i][col]);
            }
          }
          if (data.data[i].status !== 0 && $(queuerow[0]).find('button').length) {
            $(queuerow[0]).find('button').remove();
            console.log($(queuerow[0]).find('button'));
          }
        }
      }
      window.wt.timeouts.list = window.setTimeout(window.wt.getlist, 2000);
    });
  }

  window.wt.t = (s) => {
    let found = false;
    if (typeof window.wt.msg !== "object") {
      return s;
    }
    Object.keys(window.wt.msg).forEach((a) => {
      if (
        found === false &&
        window.wt != null &&
        window.wt.msg != null &&
        window.wt.msg[a] !== undefined &&
        window.wt.msg[a][0] !== undefined &&
        window.wt.msg[a][1] !== undefined &&
        window.wt.msg[a][0] === s
      ) {
        found = window.wt.msg[a][1];
      }
    });
    if (found) {
      return found;
    }
    return s;
  };

  $('form').on('submit', function(e) {
      e.preventDefault();

    let buttons = $(e.target).find('button');

    buttons.attr('disabled', 'disabled');

    let id = $(e.target).find('input[name="id"]').val(),
      action = $(e.target).find('input[name="action"]').val();
      $.getJSON('?action=' + action + '&id=' + id + '&format=json', function() {
        window.clearTimeout(window.wt.timeouts.list);
        window.wt.getlist();
        buttons.removeAttr('disabled');
      });

      return false;
  });

  window.wt.getlist();
});
