<?php
$out = array();
foreach ($scores as $scoreid => $score) {
	ob_start();
?>

<?php 
$vextra = $hextra = '';
if ($score['isMLB']) { 
	$vextra = empty($score['visitExtra']) ? "" : " (".$score['visitExtra'].")";
	$hextra = empty($score['homeExtra']) ? "" : " (".$score['homeExtra'].")";
}
?>
<div><?= $score['visitor'].$vextra ?> @ <?= $score['home'].$hextra ?> <?= date('n/j/y g:i A', strtotime($score['game_date'])) ?></div>

<?php
	$thehtml = ob_get_contents();
	ob_end_clean();
	$out[] = array('html' => $thehtml, 'scoreid' => $scoreid);
}

echo json_encode($out);
