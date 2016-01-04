$(function () {

    // Badge Dashboard
    $('.status-badge').each(function(){
        var badge = $(this);
        var json = badge.attr('data-json');
        var template = badge.attr('data-template');
        var data = [
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
            }
        ]
        var options = {
            segmentShowStroke : true,
            segmentStrokeColor : "#252830",
            segmentStrokeWidth : 2,
            percentageInnerCutout : 80,
            animationSteps : 100,
            animationEasing : "easeOutBounce",
            animateRotate : true,
            animateScale : true,
            responsive: true,
            maintainAspectRatio: true,
            legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>"

        }

        $.get(template, function(html) {
            $.getJSON(json, function(json) {
                // Append Template
                badge
                    .append(html)
                    .find('canvas').each(function(){
                        var canvas = $(this);
                        var name = canvas.attr('class');
                        data[0].value = 80;
                        data[1].value = 20;
                        new Chart(canvas.get(0).getContext("2d")).Doughnut(data, options);
                    });
            });
        });
    });
});
