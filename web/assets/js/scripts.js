$(function () {

    // Sound
    $('body')
        .append('<div id="sound"></div>')
        .find('.graphs').each(function(){
            var container = $(this);
            var json = container.attr('data-json');

            var lastPing = {};
            var seriesOptions = [];

            $.getJSON(json, function(data_load) {
                _.each(data_load.config, function(serie, website){
                    seriesOptions.push({
                        name: website,
                        dataGrouping: {
                            approximation: "high"
                        },
                        data: (function() {
                            var data = [];
                            _.each(serie, function(val, key){
                                data.push({
                                    x: moment(val.date, 'X').toDate().getTime(),
                                    y: val.total_time,
                                });
                                lastPing[website] = val.date;
                            });
                            return data;
                        })(),
                        turboThreshold: 0
                    });
                });

                createAlarm(container, data_load);
                createChart(container, json, lastPing, seriesOptions);
            });
        });

    // create the chart when all data is loaded
    createChart = function (container, json, lastPing, seriesOptions) {
        $.getScript( "js/dark-unica-sentinel.js").done(function() {
            Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });

            container.find('.highstock').highcharts('StockChart', {
                chart : {
                    type: 'spline',
                    events: {
                        load: function () {
                            var data = {};
                            var series = this.series;

                            setInterval(function () {
                                $.getJSON(json, function(data_load) {
                                    _.each(data_load.config, function(serie, website){
                                        _.each(serie, function(val, key){
                                            if(val.date > lastPing[website]) {
                                                var x = moment(val.date, 'X').toDate().getTime();
                                                var y = val.total_time;
                                                _.find(series, {'name': website}).addPoint([x, y],true,true);
                                                lastPing[website] = val.date;
                                            }
                                        });
                                    });

                                    updateAlarm(container, data_load);
                                });
                            }, 30000);
                        }
                    }
                },

                yAxis: {
                    labels: {
                        formatter: function () {
                            return this.value + 's';
                        }
                    },
                    plotLines: [{
                        value: 0,
                        width: 2,
                        color: 'silver'
                    }]
                },

                tooltip: {
                    pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y} sec</b><br/>',
                    valueDecimals: 2
                },

                rangeSelector: {
                    buttons: [{
                        count: 1,
                        type: 'hour',
                        text: '1H'
                    }, {
                        count: 3,
                        type: 'hour',
                        text: '3H'
                    }, {
                        count: 24,
                        type: 'hour',
                        text: '24H'
                    }, {
                        type: 'all',
                        text: 'All'
                    }],
                    inputEnabled: false,
                    selected: 0
                },

                legend: {
                    enabled: true
                },

                series: seriesOptions
            });
        });
    };

    // Alarm test
    createAlarm = function (container, data_load) {
        var alarm = container.find('.alarm');
        var icon = $('<div class="icon"></div>');
        var legend = $('<div class="legend"><div class="title"></div><div class="data"></div></div>');

        // Append HTML
        alarm.append(icon).append(legend);

        // Added data
        updateAlarm(container, data_load);
    };

    // Alarm test
    updateAlarm = function (container, data_load) {
        var alarm = container.find('.alarm');
        var icon = container.find('.icon');
        var legend = container.find('.legend');

        // Add Alarm
        if(data_load.status != 200) {
            icon.removeClass().addClass('icon status_error');
            if(data_load.status == 999) data_load.status = '---';
            legend.find('.title').html('Erreur').end().find('.data').html(data_load.status);
            updateSound(true);
        } else if(data_load.average > 2) {
            icon.removeClass().addClass('icon average_error');
            legend.find('.title').html('Temps moyen').end().find('.data').html(data_load.average + 's');
            updateSound(true);
        } else {
            if(data_load.average < 1) {
                average = (data_load.average*1000) + 'ms';
            } else {
                average = data_load.average + 's';
            }

            icon.removeClass().addClass('icon good');
            legend.find('.title').html('Temps moyen').end().find('.data').html(average);
            updateSound(false);
        }
    };

    updateSound = function (flag) {
        if (flag) {
            $('#sound').html('<audio autoplay><source src="sound/alarm.mp3"></audio>');
        } else {
            $('#sound').empty();
        }
    };

});
