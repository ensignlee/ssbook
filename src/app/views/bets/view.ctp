<?php
$html->css('viewbets', 'stylesheet', array('inline' => false));
$html->css('jquery.contextmenu', 'stylesheet', array('inline' => false));

echo $javascript->link('jquery.flot.min.js');
echo $javascript->link('jquery.contextmenu.js');
echo $javascript->link('filtermenu.js');
?>
<script type="text/javascript">
$(function () {
	var data = <?= json_encode($graphData) ?>;
	$.plot($("#graph"), data);

	var m = new SS.FilterMenu('home', '#hiddenForm', '#filter_home_team');
	m.init(<?= json_encode($filters['home']) ?>, <?= json_encode(isset($condAsMap['home']) ? $condAsMap['home'] : '') ?>);
});
</script>
<form id="hiddenForm" method="get" action="">
	<?php foreach ($condAsMap as $key => $rows) {
		echo "<input type='hidden' name='$key' value=\"".htmlentities(implode(',', array_keys($rows)))."\" />";
	} ?>
</form>
<div id="graph">Graph of Data</div>
<div id="record">
	<table class="spaced-table cell-centered">
		<tr>
			<th>Record</th>
			<th>Winning Percentage</th>
			<th>Dollars Won</th>
		</tr>
		<tr>
			<td><?= "{$record['win']} - {$record['loss']} - {$record['tie']}" ?></td>
			<td><?= round($record['winningPercentage']*100, 2) ?>%</td>
			<td>$<?= number_format($record['dollarsWon'], 2) ?></td>
		</tr>
	</table>
</div>
<div id="allStats">
	<table class="spaced-table cell-left">
		<tr><td>Average Amount Earned Per Bet</td><td class="number">$<?= round($allStats['avgEarned'], 2) ?></td></tr>
		<tr><td>Average Amount Bet Per Bet</td><td class="number">$<?= round($allStats['avgBet'], 2) ?></td></tr>
		<tr><td>Average Return on Investment</td><td class="number"><?= round($allStats['roi']*100) ?>%</td></tr>
		<tr><td>Average Odds Per Bet</td><td class="number"><?= round($allStats['avgOdds']) ?></td></tr>
		<tr><td>Average Breakeven Winning Percentage</td><td class="number"><?= round($allStats['breakEven']*100) ?>%</td></tr>
	</table>
</div>

<div id="betTable">
	<table>
	<tr>
		<th>Date</th>
		<th>League</th>
		<th>Bet On</th>
		<th>Bet Type</th>
		<th>Line</th>
		<th>Home  <span class="clickable extra-click" id="filter_home_team"><img src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th>Visitor</th>
		<th>Risk</th>
		<th>Odds</th>
		<th>Winnings</th>
		<th>Book</th>
		<th>Delete</th>
	</tr>

	<?php	
	foreach ($bets as $bet) :
	?>
	<tr>
		<td><?= date("m/d/y", strtotime($bet['date'])) ?></td>
		<td><?= $bet['league'] ?></td>
		<td><?= $bet['beton'] ?></td>
		<td><?= $bet['type'] ?></td>
		<td><?= $bet['line'] ?></td>
		<td><?= $bet['home'] ?></td>
		<td><?= $bet['visitor'] ?></td>
		<td><?= $bet['risk'] ?></td>
		<td><?= $bet['odds'] ?></td>
		<td><?= $bet['winning'] ?></td>
		<td><?= $bet['book'] ?></td>
		<td><?= $html->link('X', '/bets/delete/'.$bet['betid']) ?></td>
	</tr>
	<?php endforeach;// ($bets as $bet) : ?>
	</table>
</div>