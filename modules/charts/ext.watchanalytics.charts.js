
var canvas = $("#page-reviews-chart");
canvas.attr( "width", canvas.parent().width() - 100 );
canvas.attr( "height", parseInt(canvas.width() * .75) );

var rawData = JSON.parse( $('#ext-watchanalytics-page-stats-data').text() );

var labels = [];
var reviewState = [];
var datePrevious = null;
var dateCurrent = null;
for ( var timestamp in rawData ) {

    // timestamp like YYYY-MM-DD HH:II:SS, want just the first 10
    dateCurrent = timestamp.slice( 0, 10 );

    if ( dateCurrent !== datePrevious ) {
        labels[ labels.length ] = timestamp;
    }
    else {
        labels[ labels.length ] = '';
    }

    datePrevious = dateCurrent;
    reviewState[ reviewState.length ] = parseInt( rawData[ timestamp ] );
}

var ctx = canvas.get(0).getContext( "2d" );

var data = {
    labels: labels, //["January", "February", "March", "April", "May", "June", "July"],
    datasets: [
        {
            label: "first dataset",
            fillColor: "rgba(255,92,92,0.2)",
            strokeColor: "rgba(255,92,92,1)",
            pointColor: "rgba(255,92,92,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(151,187,205,1)",
            data: reviewState
        }
        // ,{
        //     label: "My Second dataset",
        //     fillColor: "rgba(151,187,205,0.2)",
        //     strokeColor: "rgba(151,187,205,1)",
        //     pointColor: "rgba(151,187,205,1)",
        //     pointStrokeColor: "#fff",
        //     pointHighlightFill: "#fff",
        //     pointHighlightStroke: "rgba(151,187,205,1)",
        //     data: getMovingAverage( hits, 7 )
        // }
    ]
};

var options = {

    ///Boolean - Whether grid lines are shown across the chart
    scaleShowGridLines : false,

    //String - Colour of the grid lines
    scaleGridLineColor : "rgba(0,0,0,.05)",

    //Number - Width of the grid lines
    scaleGridLineWidth : 1,

    //Boolean - Whether the line is curved between points
    bezierCurve : true,

    //Number - Tension of the bezier curve between points
    bezierCurveTension : 0.4,

    //Boolean - Whether to show a dot for each point
    pointDot : true,

    //Number - Radius of each point dot in pixels
    pointDotRadius : 4,

    //Number - Pixel width of point dot stroke
    pointDotStrokeWidth : 1,

    //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
    pointHitDetectionRadius : 0,

    //Boolean - Whether to show a stroke for datasets
    datasetStroke : false,

    //Number - Pixel width of dataset stroke
    datasetStrokeWidth : 1,

    //Boolean - Whether to fill the dataset with a colour
    datasetFill : true,

    //String - A legend template
    legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>"

};

var myLineChart = new Chart( ctx ).Line( data, options );

