<?php
require_once "pdo.php";
require_once "jpgraph/jpgraph.php";
require_once "jpgraph/jpgraph_line.php";
session_start();

if(isset($_GET['displayType']) && strlen($_GET['displayType']) > 0) {
    $displayType = $_GET['displayType'];
} else {
    $displayType = 'hour';
}

//Get device record
try {

} catch(Throwable $e) {
    header('Location: error.php');
    return;
}

// The callback that converts timestamp to minutes and seconds
function TimeCallback($aVal) {
    return Date('H:i:s',$aVal);
}

// Fake some suitable random data
$now = time();
switch($displayType) {
    case 'hour':
        $start = $now - (60*60);
        break;
    case 'day':
        $start = $now - (24*60*60);
        break;
    case 'week':
        $start = $now - (7*24*60*60);
        break;
    case 'month':
        $start = $now - (30*24*60*60);
        break;
    case 'year':
        $start = $now - (365*24*60*60);
        break;
}

$datax = array($now);
for( $i=0; $i < 360; $i += 10 ) {
    $datax[] = $now + $i;
}
$n = count($datax);
$datay=array();
for( $i=0; $i < $n; ++$i ) {
    $datay[] = rand(30,150);
}

$graph = new Graph(324,250);
$graph->SetMargin(40, 40, 30, 70);

switch($displayType) {
    case 'hour':
        $graph->title->Set(date('Y-m-d H-i-s', $now));
        break;
    case 'day':
        $graph->title->Set(date('Y-m-d', $now));
        break;
    case 'week':
    case 'month':
    case 'year':
        $graph->title->Set(date('Y-m-d', $start) . ' - ' . date('Y-m-d', $now));
        break;
}
// $graph->title->Set('Date: ' . date('Y-m-d', $now));
$graph->SetAlphaBlending();

$graph->SetScale("intlin",0,200,$now,$datax[$n-1]);
$graph->xaxis->SetLabelFormatCallback('TimeCallback');
$graph->xaxis->SetLabelAngle(90);
$p1 = new LinePlot($datay,$datax);
$p1->SetColor("blue");
$p1->SetFillColor("blue@0.4");
$graph->Add($p1);
$graph->Stroke();
?>