$(document).ready(function() {

    var M_jqplot_context = '';

    $('#stats-chart').bind('jqplotcustomDataMouseOver', function (e, data) {
        console.log(data);
        return "<b>" + data.series.title + "</b></br>" +
               "X = " + data.x + "</br>" +
               "Y = " + data.y + "</br>" +
               "Size = " + data.size;
    });

    var parse_hash = function() {
        var result = {year: '', week: '', day: '', hour: ''};
        var names = ['year', 'week', 'day', 'hour'];
        var hash = window.location.hash;
        var i, values;

        if (hash.match(/^#\d+(\.\d+){0,3}$/)) {
            values = hash.slice(1).split(".");
            for (i = 0; i < values.length; i++) {
                result[names[i]] = values[i];
            }
        }
        return result;
    }

    var init = function() {
        var params = parse_hash();
        update(params);
    }

    var update = function(params) {
        $('#stats').addClass('updating');
        $.ajax({
            url: 'ajax.php',
            data: params,
            dataType: 'json',
            type: 'POST',
            success: function(data){
                var hits, time, chart;
                update_select('stats-year', data.years, data.year);
                update_select('stats-week', data.weeks, data.week, true);
                update_select('stats-day', data.days, data.day, true);
                update_select('stats-hour', data.hours, data.hour, true);
                $('#stats-hits').html(format_hits(data.hits));
                $('#stats-time').html(format_time(data.time));
                update_chart(data);
                update_table('stats-courses', data.courses);
                update_table('stats-scripts', data.scripts);
                update_hash(data.year, data.week, data.day, data.hour);
            },
            error: function(jqXHR, textStatus){
                alert('Error!');
            }
        });
        $('#stats').removeClass('updating');
    }

    var exportcsv = function() {
        var params = [
            "csv=1",
            "year=" + $('#stats-year').val(),
            "week=" + $('#stats-week').val(),
            "day=" + $('#stats-day').val(),
            "hour=" + $('#stats-hour').val()
        ];
        window.location.href = 'ajax.php?' + params.join('&');
    };

    var format_hits = function(number) {
        var units = ['K', 'M', 'G', 'T'];
        var current_unit = '';

        if (number < 1000) {
            return number.toFixed(0);
        }

        $.each(units, function(index, unit) {
            if (number >= 1000) {
                number /= 1000;
                current_unit = unit;
            }
        });

        return number.toFixed(1) + current_unit;
    }

    var format_time = function(number) {
        if (typeof(number) === 'undefined') {
            number = format;
        }
        return number.toFixed(2) + "s";
    }

    var update_chart = function(data) {
        if (data.chart) {
            $('#stats-chart').empty();
            $('#stats-chart').show();

            M_jqplot_context = data.context;

            var hits = [], time = [], tickangle = {}, tooltips = {show:true, tooltipAxes: 'y', tooltipLocation: 'n'};
            $.each(data.chart, function (index, item) {
                hits.push([item.label, item.hits]);
                time.push([item.label, item.time]);
            });
            var formatter_hits = function (format, number) {
                return format_hits(number);
            }
            var formatter_time = function (format, number) {
                return format_time(number);
            }
            var formatter_data = function (format, number) {
                if (typeof(number) === 'number' &&  hits[number-1]){
                    return hits[number-1][0];
                }
            }
            if (M_jqplot_context == 'year'){
                tickangle.angle = -90;
                tooltips.yvalues = 1;
            }
            tickangle.formatter = formatter_data;
            tooltips.tooltipAxes='both';
            tooltips.formatString='%s <br /><strong>%s</strong>';
            $.jqplot('stats-chart', [hits, time],
                     {legend: {show: true},
                      series: [{label: data.string.hits },
                               {label: data.string.time, yaxis: "y2axis" }],
                      axesDefaults: {useSeriesColor: true},
                      axes: {xaxis: {renderer: $.jqplot.CategoryAxisRenderer,
                             tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
                             tickOptions: tickangle
                            },
                             yaxis: {autoscale: true,
                                     tickOptions: {formatter: formatter_hits}},
                             y2axis: {autoscale: true,
                                      tickOptions: {formatter: formatter_time}}},
                      highlighter: tooltips,
                      cursor: {show: false}});
        } else {
            $('#stats-chart').hide();
        }
    }

    var update_hash = function(year, week, day, hour) {
        var hash = "#" + year;
        if (week !== false) {
            hash += "." + week;
        }
        if (day !== false) {
            hash += "." + day;
        }
        if (hour !== false) {
            hash += "." + hour;
        }
        window.location.hash = hash
    }

    var update_select = function(id, values, selected, nulloption) {
        var $select = $("#" + id);
        $select.empty();
        if (values) {
            $select.removeAttr("disabled");
            if (nulloption) {
                $('<option>').attr("value", "").appendTo($select);
            }
            $.each(values, function(value, label) {
                $('<option>').attr("value", value).append(label).appendTo($select);
            });
            if (selected !== false) {
                $select.val(selected);
            }
        } else {
            $select.attr("disabled", "disabled");
            $('<option>').attr("value", "").append("&nbsp;").appendTo($select);
        }
    }

    var update_table = function(id, items) {
        $("#" + id + " tr:gt(0)").remove();
        $.each(items, function(index, item) {
            var $tr = $("<tr>").appendTo("#" + id);
            $("<td>").append(item.name).appendTo($tr);
            $("<td>").append(format_hits(item.hits)).appendTo($tr);
            $("<td>").append(format_time(item.time)).appendTo($tr);
        });
    };

    var change = function() {
        var params = {year: $('#stats-year').val(),
                      week: $('#stats-week').val(),
                      day: $('#stats-day').val(),
                      hour: $('#stats-hour').val()};
        update(params);
    }

    $('#stats-chart').bind('jqplotDataClick', function(ev, seriesIndex, pointIndex, data) {
        if (M_jqplot_context === 'year'){
            var value = $('#stats-week > option').eq(pointIndex+1).val();
            $('#stats-week').val(value);
        }else{
            if (M_jqplot_context === 'week'){
                var value = $('#stats-day > option').eq(pointIndex+1).val();
                $('#stats-day').val(value);
            }else{
                if (M_jqplot_context === 'day'){
                    var value = $('#stats-hour > option').eq(pointIndex+1).val();
                    $('#stats-hour').val(value);
                }
            }
        }
        change();
    });

    $('#stats-chart').bind('jqplotDataMouseOver', function(ev, seriesIndex, pointIndex, data) {
        $(this).css('cursor', 'pointer');
    });

    $('#stats-chart').bind('jqplotDataUnhighlight', function(ev, seriesIndex, pointIndex, data) {
        $(this).css('cursor', 'auto');
    });

    $('#stats-year').change(change);
    $('#stats-week').change(change);
    $('#stats-day').change(change);
    $('#stats-hour').change(change);
    $('#stats-refresh').click(change);
    $('#stats-csv').click(exportcsv);

    init();
});
