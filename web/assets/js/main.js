$(function () {

    // Badge Dashboard
    $('.content-chart').each(function(){
        var graph = {
            'container' : $(this),
            'json' : $(this).attr('data-json'),
            'error' : $(this).attr('data-error'),
            'lastPing' : {},
            'seriesOptions' : []
        }

        $.getJSON(graph.json, function(json) {
            _.each(json, function(requests, serie){
                graph.seriesOptions.push({
                    name: serie,
                    dataGrouping: {
                        approximation: "high"
                    },
                    data: (function() {
                        var data = [];
                        _.each(requests, function(request, key){
                            if((graph.error == 'false' && request.error) || (graph.error == 'true' && !request.error)) {
                                request.ping_time = null;
                            }

                            data.push({
                                x: moment(request.ping_date, 'X').toDate().getTime(),
                                y: request.ping_time,
                            });
                            graph.lastPing[serie] = request.ping_date;
                        });
                        return data;
                    })(),
                    turboThreshold: 0
                });
            });

            createStockChart(graph);
        });

        // create the chart when all data is loaded
        createStockChart = function (graph) {
            Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });

            // Set chartType
            if(graph.error == 'false') {
                var chartType = 'spline';
            } else {
                var chartType = 'column';
            }

            graph.container.highcharts('StockChart', {
                chart : {
                    type: chartType,
                    events: {
                        load: function () {
                            var data = {};
                            var series = this.series;

                            setInterval(function () {
                                $.getJSON(graph.json, function(json) {
                                    _.each(json, function(requests, serie){
                                        _.each(requests, function(request, key){
                                            if(request.ping_date > graph.lastPing[serie]) {
                                                if((graph.error == 'false' && request.error) || (graph.error == 'true' && !request.error)) {
                                                    request.ping_time = null;
                                                }

                                                var x = moment(request.ping_date, 'X').toDate().getTime();
                                                var y = request.ping_time;
                                                _.find(series, {'name': serie}).addPoint([x, y],true,true);
                                                graph.lastPing[serie] = request.ping_date;
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
            'range' : $(this).attr('data-range'),
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
                if(data.quality_of_service[badge.range] && data.average[badge.range]) {
                    var percent = data.quality_of_service[badge.range];
                    var average = data.average[badge.range];
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
