<?php
$html->css('viewbets', 'stylesheet', array('inline' => false));

echo $javascript->link('jquery.flot.min.js');
?>
<script type="text/javascript">
$(function () {
	var data = <?= json_encode($graphData) ?>;
	$.plot($("#graph"), data);
});
</script>
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
		<th>Home</th>
		<th>Visitor</th>
		<th>League</th>
		<th>Bet Direction</th>
		<th>Bet Type</th>
		<th>Bet</th>
		<th>Risk</th>
		<th>Winnings</th>
		<th>Book</th>
		<th>Delete</th>
	</tr>

	<?php
	$startSS = strtotime('1990-01-01');
	foreach ($bets as $bet) :
		$score = $bet['Score'];
		$bet = $bet['UserBet'];
		$game_date = strtotime($bet['game_date']);
		if ($game_date < $startSS) {
			$game_date = strtotime($score['game_date']);
		}

	?>

	<tr>
		<td><?= date("m/d/y", $game_date) ?></td>
		<td><?= $score['home'] ?></td>
		<td><?= $score['visitor'] ?></td>
		<td><?= $score['league'] ?></td>
		<td><?= $bet['direction'] ?></td>
		<td><?= $bet['type'] ?></td>
		<td><?= $bet['bet'] ?></td>
		<td><?= $bet['risk'] ?></td>
		<td><?= $bet['winning'] ?></td>
		<td><?= $bet['source'] ?></td>
		<td><?= $html->link('X', '/bets/delete/'.$bet['id']) ?></td>
	</tr>
	<?php endforeach;// ($bets as $bet) : ?>

	</table>
</div>