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

	<?php foreach (array_keys($filters) as $key) : ?>
	var m = new SS.FilterMenu('<?= $key ?>', '#hiddenForm', '#filter_<?= $key ?>');
	<?php $list = $filters[$key]; sort($list); ?>
	m.init(<?= json_encode($list) ?>, <?= json_encode(isset($condAsMap[$key]) ? $condAsMap[$key] : '') ?>);
	<?php endforeach;// (array_keys($filters) as $key) : ?>
});
</script>
<form id="hiddenForm" method="get" action="">
	<?php foreach ($condAsMap as $key => $rows) {
		echo "<input type='hidden' name='$key' value=\"".htmlentities(implode(',', array_keys($rows)))."\" />";
	} ?>
	<input type="hidden" name="sort" value="<?= "$sortKey,$sortDir" ?>" />
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
		<th>&nbsp;</th>
		<th>Date</th>
		<th>League <span class="clickable extra-click" id="filter_league"><img src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th>Bet On <span class="clickable extra-click" id="filter_beton"><img src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th>Bet Type <span class="clickable extra-click" id="filter_type"><img src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th>Line</th>
		<th>Home <span class="clickable extra-click" id="filter_home"><img src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th>Visitor <span class="clickable extra-click" id="filter_visitor"><img src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th>Risk</th>
		<th>Odds</th>
		<th>Winnings</th>
		<th>Book <span class="clickable extra-click" id="filter_book"><img src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th>Delete</th>
	</tr>

	<?php
	$i = 0;
	foreach ($bets as $bet) :
		$i++;
	?>
	<tr>
		<td><?= $i ?></td>
		<td class="date"><?= date("n/j/y", strtotime($bet['date'])) ?></td>
		<td><?= $bet['league'] ?></td>
		<td><?= $bet['beton'] ?></td>
		<td><?= $bet['type'] ?></td>
		<td class="number"><?= $bet['line'] ?></td>
		<td><?= $bet['home'] ?></td>
		<td><?= $bet['visitor'] ?></td>
		<td class="number"><?= money_format('%n', $bet['risk']) ?></td>
		<td class="number"><?= $bet['odds'] ?></td>
		<td class="number"><?= money_format('%(n', $bet['winning']) ?></td>
		<td><?= $bet['book'] ?></td>
		<td style="text-align: center"><?= $html->link('X', '/bets/delete/'.$bet['betid']) ?></td>
	</tr>
	<?php endforeach;// ($bets as $bet) : ?>
	</table>
</div>