<html>
<head>
<!-- Styles -->
<style>
#chartdiv {
  width: 40%;
  height:2000px;
}								
</style>
</head>
<body onload="GetData();">
<div id="MyData"></div>
</body>
<script src="amstockchart_3.21.12.free/amcharts/amcharts.js"></script>
<script src="amstockchart_3.21.12.free/amcharts/serial.js"></script>
<script src="amstockchart_3.21.12.free/amcharts/plugins/export/export.min.js"></script>
<script src="jquery-2.2.3.min.js"></script>
<link rel="stylesheet" href="amstockchart_3.21.12.free/amcharts/plugins/export/export.css" type="text/css" media="all">
<!-- Chart code -->
<script>
/*
var responseData = [{
    "age": "85+",
    "male": -0.1,
    "female": 0.3
  }, {
    "age": "80-54",
    "male": -0.2,
    "female": 0.3
  }, {
    "age": "75-79",
    "male": -0.3,
    "female": 0.6
  }, {
    "age": "70-74",
    "male": -0.5,
    "female": 0.8
  }, {
    "age": "0-4",
    "male": -5.0,
    "female": 4.8
  }];
*/


function GetData(){
    $.ajax({
      url: "response.php",
      success:function(responseData){
            console.log(responseData);

                var chart = AmCharts.makeChart("chartdiv", {
                  "type": "serial",
                  "theme": "light",
                  "rotate": true,
                  "marginBottom": 50,
                  "dataProvider": JSON.parse(responseData),
                  "valueAxes": [ {
                "gridColor": "#FFFFFF",
                "gridAlpha": 0.2,
                "dashLength": 0
              } ],
              "gridAboveGraphs": true,
              "startDuration": 1,
              "graphs": [ {
                "balloonText": "[[category]]: <b>[[value]]</b>",
                "fillAlphas": 0.8,
                "lineAlpha": 0.2,
                "type": "column",
                "valueField": "Count"
              } ],
              "chartCursor": {
                "categoryBalloonEnabled": false,
                "cursorAlpha": 0,
                "zoomable": false
              },
              "categoryField": "Equipment",
              "categoryAxis": {
                "gridPosition": "start",
                "gridAlpha": 0,
                "tickPosition": "start",
                "tickLength": 20
              },
              "export": {
                "enabled": true
              }

                });




      }
    });
}
</script>

<!-- HTML -->
<div id="chartdiv"></div>
</html>
