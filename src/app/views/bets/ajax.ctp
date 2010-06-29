<?php
$out = array();
foreach ($scores as $scoreid => $score) {
	ob_start();
?>
<div><?= $score['visitor'] ?> @ <?= $score['home'] ?> - <?= date('Y-m-d H:i:s', strtotime($score['game_date'])) ?></div>
<?php
	$html = ob_get_contents();
	ob_end_clean();
	$out[] = array('html' => $html, 'scoreid' => $scoreid);
}

echo json_encode($out);
