<?php

/**
 * A graph page
 */

App::import('vendor', 'jpgraph', array('file'=>'jpgraph/jpgraph.php'));
App::import('vendor', 'jpgraph_line', array('file'=>'jpgraph/jpgraph_line.php'));
App::import('vendor', 'jpgraph_date', array('file'=>'jpgraph/jpgraph_date.php'));
App::import('vendor', 'jpgraph_utils', array('file'=>'jpgraph/jpgraph_utils.inc.php'));

// Get the points the way that this graph is happy
$x = array();
$y = array();
foreach ($graphData[0] as $point) {
	$x[] = $point[0] / 1000;
	$y[] = $point[1];
}

$graph = new Graph(400,150,'auto');
$graph->SetScale("datlin");

// Set theme
$theme_class=new UniversalTheme();
$graph->SetTheme($theme_class);

$graph->SetMargin(80,50,10,30);

$wltRecord = "{$record['win']} - {$record['loss']}";
if ($record['tie'] != 0) {
	$wltRecord .= " - {$record['tie']}";	// Only display ties if non-zero
}
$graph->title->Set("{$user['username']} ($wltRecord)");
$graph->SetBox(false);

// Y
$graph->yaxis->HideLine(false);
$graph->yaxis->HideTicks(false,false);
$graph->yaxis->SetLabelFormatCallback('labelCallback');
function labelCallback($label) {
	return money_format('%(.0n', $label);
}

$graph->ygrid->SetFill(true,'#FFFFFF','#FFFFFF');

// X
list($tickPositions,$minTickPositions) = DateScaleUtils::GetTicks($x,2,true);
$graph->xaxis->SetTickPositions($tickPositions,$minTickPositions);
$graph->xaxis->scale->SetDateFormat('M j');
$graph->xaxis->SetPos('min');
$graph->xaxis->SetFont(FF_VERDANA,FS_NORMAL,9);

$graph->xgrid->Show();
$graph->xgrid->SetColor('#E3E3E3'); // light gray

// Create line
$p1 = new LinePlot($y, $x);
$graph->Add($p1);
$p1->SetColor("#6495ED"); // blue

// Output line
$graph->Stroke();
