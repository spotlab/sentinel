$(function () {

    // Badge Dashboard
    $('.content-graph').each(function(){
        var graph = {
            'container' : $(this),
            'json' : $(this).attr('data-json'),
            'lastPing' : {},
            'seriesOptions' : []
        }

        $.getJSON(graph.json, function(data_load) {
            _.each(data_load.config, function(serie, website){
                graph.seriesOptions.push({
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
                            graph.lastPing[website] = val.date;
                        });
                        return data;
                    })(),
                    turboThreshold: 0
                });
            });

            createChart(graph);
        });

        // create the chart when all data is loaded
        createChart = function (graph) {
            Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });

            graph.container.highcharts('StockChart', {
                chart : {
                    type: 'spline',
                    events: {
                        load: function () {
                            var data = {};
                            var series = this.series;

                            setInterval(function () {
                                $.getJSON(graph.json, function(data_load) {
                                    _.each(data_load.config, function(serie, website){
                                        _.each(serie, function(val, key){
                                            if(val.date > graph.lastPing[website]) {
                                                var x = moment(val.date, 'X').toDate().getTime();
                                                var y = val.total_time;
                                                _.find(series, {'name': website}).addPoint([x, y],true,true);
                                                graph.lastPing[website] = val.date;
                                            }
                                        });
                                    });
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

                credits: {
                    enabled: false
                },

                series: graph.seriesOptions
            });
        };
    });

    // Badge Dashboard
    $('.status-badge').each(function(){
        var badge = {
            'container' : $(this),
            'json' : $(this).attr('data-json'),
            'template' : $(this).attr('data-template'),
            'project' : $(this).attr('data-project'),
            'subproject' : $(this).attr('data-subproject'),
            'display' : [
                {
                    value: 100,
                    color: "#46BFBD",
                    highlight: "#5AD3D1",
                    label: "Success"
                },
                {
                    value: 0,
                    color:"#F7464A",
                    highlight: "#FF5A5E",
                    label: "Failed"
                },
                {
                    value: 0,
                    color:"#F8E654",
                    highlight: "#FFEC82",
                    label: "Too long"
                }
            ],
            'options' : {
                showTooltips: false,
                segmentShowStroke : true,
                segmentStrokeColor : "#252830",
                segmentStrokeWidth : 2,
                percentageInnerCutout : 80,
                animationSteps : 100,
                animationEasing : "easeOutBounce",
                animateRotate : true,
                animateScale : true,
                responsive: true,
                maintainAspectRatio: true
            }
        };

        $.get(badge.template, function(template) {
            getAverageData(badge, template);
            setInterval(function(){
                getAverageData(badge, template);
            }, 30000);
        });

        getAverageData = function(badge, template) {
            $.getJSON(badge.json, function(data) {
                if(data.quality_of_service.two_minutes && data.average.two_minutes) {
                    var percent = data.quality_of_service.two_minutes;
                    var average = data.average.two_minutes;
                }

                // Analysing average and percent
                if(!average && !percent) {
                    average = { 'human' : '!' };
                    badge.display[0].value = 0;
                    badge.display[1].value = 100;
                    badge.display[2].value = 0;
                } else if (average.raw >= 2) {
                    badge.display[0].value = 0;
                    badge.display[1].value = 0;
                    badge.display[2].value = 100;
                } else {
                    badge.display[0].value = percent;
                    badge.display[1].value = 100 - percent;
                    badge.display[2].value = 0;
                }

                // Append Template
                badge.container
                    .empty()
                    .append(template)
                    .find('.project').html(badge.project).end()
                    .find('.subproject').html(badge.subproject).end()
                    .find('.average').html(average.human).end()
                    .find('canvas').each(function(){
                        var canvas = $(this);
                        var name = canvas.attr('class');
                        new Chart(canvas.get(0).getContext("2d")).Doughnut(badge.display, badge.options);
                    });
            });
        }
    });

    $('.status-sound').each(function(){
        var sound = {
            'container' : $(this),
            'json' : $(this).attr('data-json'),
            'template' : $(this).attr('data-template')
        };

        $.get(sound.template, function(template) {
            getStatus(sound, template);
            setInterval(function(){
                getStatus(sound, template);
            }, 60000);
        });

        getStatus = function(sound, template) {
            $.getJSON(sound.json, function(data) {
                if(!data.status.quality_of_service) {
                    sound.container.html(template);
                } else {
                    sound.container.empty();
                }
            });
        };
    });
});
