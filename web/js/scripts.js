$(function () {
    var lastPing = {};
    var seriesOptions = [];
    var extreme = 1;
    var dataRaw;
    var setGeneralAverage;
    // create the chart when all data is loaded
    createChart = function () {
        $.getScript( "js/dark-unica-sentinel.js").done(function() {
            Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });

            $('#container').highcharts('StockChart', {
                chart : {
                    type: 'spline',
                    events: {
                        load: function () {
                            var data = {};
                            var series = this.series;

                            setInterval(function () {
                                $.getJSON("data/data.json", function(data_load) {
                                    dataRaw = data_load;
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
                                    setAverage();
                                    setGeneralAverage();
                                    alarmTest(data_load);
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

                xAxis: {
                    events: {
                        setExtremes: function(e) {
                            if(typeof(e.rangeSelectorButton)!== 'undefined')
                            {
                                extreme = e.rangeSelectorButton.count;
                                setGeneralAverage();
                            }
                        }
                    }
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
    alarmTest = function (data_load) {
        // Add Alarm
        if(data_load.status == 'false' || data_load.average > 2) {
            $('#alarm')
                .removeClass('good')
                .addClass('bad')
                .html('<audio autoplay><source src="sound/alarm.mp3"></audio>');
        } else {
            $('#alarm')
                .removeClass('bad')
                .addClass('good')
                .empty();
        }
    };

    // Instant Average
    setAverage = function () {
        replaceAverage('#average .time', dataRaw.average);
    };

    // General Average
    setGeneralAverage = function () {
        if (extreme == undefined) {
            extreme = 0;
        }
        replaceAverage('#averagePeriod .time', dataRaw.generalAverage[extreme]);
    };

    replaceAverage = function (target, data) {
        if (data < 1) {
            $(target).html((data * 1000) + 'ms');
        } else {
            $(target).html(data + 's');
        }
    };

    // Init call
    $.getJSON("data/data.json", function(data_load) {
        dataRaw = data_load;
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
                            y: val.total_time
                        });
                        lastPing[website] = val.date;
                    });
                    return data;
                })(),
                turboThreshold: 0
            });
        });
        createChart();
        setAverage();
        setGeneralAverage();
        alarmTest(data_load);
    });
});
