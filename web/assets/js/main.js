$(function () {

    // Badge Dashboard
    $('.content-chart').each(function(){
        var graph = {
            'container' : $(this),
            'json' : $(this).attr('data-json'),
            'success' : {
                'lastPing' : {},
                'serie' : []
            },
            'failed' : {
                'lastPing' : {},
                'serie' : []
            },
        }

        $.getJSON(graph.json, function(json) {
            _.each(json, function(requests, serie){
                graph.success.serie.push({
                    name: serie,
                    dataGrouping: {
                        approximation: "high"
                    },
                    data: (function() {
                        var data = [];
                        _.each(requests, function(request, key){
                            if(request.error) request.ping_time = null;

                            data.push({
                                x: moment(request.ping_date, 'X').toDate().getTime(),
                                y: request.ping_time,
                            });

                            graph.success.lastPing[serie] = request.ping_date;
                        });
                        return data;
                    })(),
                    turboThreshold: 0
                });

                graph.failed.serie.push({
                    name: serie,
                    dataGrouping: {
                        approximation: "high"
                    },
                    data: (function() {
                        var data = [];
                        _.each(requests, function(request, key){
                            if(!request.error) request.http_status = null;

                            data.push({
                                x: moment(request.ping_date, 'X').toDate().getTime(),
                                y: request.http_status,
                            });
                            graph.failed.lastPing[serie] = request.ping_date;
                        });
                        return data;
                    })(),
                    turboThreshold: 0
                });
            });

            createStockChart(graph.success, true);
            createStockChart(graph.failed, false);
        });

        // create the chart when all data is loaded
        createStockChart = function (data, success) {
            Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });

            // Set chartType
            if(success) {
                var chartContainer = $('<div class="col-md-12 success"></div>');
                var chartType = 'spline';
                var chartLabel = 's';
                var chartDecimal = 2;
            } else {
                var chartContainer = $('<div class="col-md-12 failed"></div>');
                var chartType = 'column';
                var chartLabel = '';
                var chartDecimal = 0;
            }

            // Append Chart
            graph.container
                .append(chartContainer)
                .find(chartContainer)
                .highcharts('StockChart', {
                    chart : {
                        type: chartType,
                        events: {
                            load: function () {
                                var data = {};
                                var series = this.series;

                                setInterval(function () {
                                    $.getJSON(data.json, function(json) {
                                        _.each(json, function(requests, serie){
                                            _.each(requests, function(request, key){
                                                if(success) {
                                                    if(request.error) request.ping_time = null;

                                                    var x = moment(request.ping_date, 'X').toDate().getTime();
                                                    var y = request.ping_time;
                                                } else {
                                                    if(!request.error) request.http_status = null;

                                                    var x = moment(request.ping_date, 'X').toDate().getTime();
                                                    var y = request.http_status;
                                                }

                                                _.find(series, {'name': serie}).addPoint([x, y],true,true);
                                                data.lastPing[serie] = request.ping_date;
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
                                return this.value + chartLabel;
                            }
                        },
                        plotLines: [{
                            value: 0,
                            width: 2,
                            color: 'silver'
                        }]
                    },

                    tooltip: {
                        pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}' + chartLabel + '</b><br/>',
                        valueDecimals: chartDecimal
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

                    series: data.serie
                });
        };
    });

    // Badge Dashboard
    $('.status-badge').each(function(){
        var badge = {
            'container' : $(this),
            'group' : $(this).attr('data-group'),
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
                showTooltips: true,
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
                badge.container
                    .empty()
                    .append(template);

                if(badge.group == 'true') {
                    badge.container
                        .find('.average-badge').each(function(){
                            badge.project = $(this).attr('data-project');
                            badge.subproject = $(this).attr('data-subproject');
                            badge.range = $(this).attr('data-range');
                            updateDataBadge(data, badge, $(this));
                        });
                } else {
                    updateDataBadge(data, badge, badge.container);
                }
            });
        };

        updateDataBadge = function(data, badge, container) {
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
            } else {
                badge.display[0].value = percent.success;
                badge.display[1].value = percent.failed;
                badge.display[2].value = percent.toolong;
            }

            // Append Template
            container
                .find('.project').html(badge.project).end()
                .find('.subproject').html(badge.subproject).end()
                .find('.average').html(average.human).end()
                .find('canvas').each(function(){
                    var canvas = $(this);
                    new Chart(canvas.get(0).getContext("2d")).Doughnut(badge.display, badge.options);
                });
        };
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
