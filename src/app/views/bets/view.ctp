<?php
$html->css('viewbets', 'stylesheet', array('inline' => false));
$html->css('jquery.contextmenu', 'stylesheet', array('inline' => false));

echo $javascript->link('jquery.flot');
echo $javascript->link('jquery.contextmenu');
echo $javascript->link('jquery.simplemodal-1.4.1');
echo $javascript->link('filtermenu');
echo $javascript->link('generic');
echo $javascript->link('viewbets');
?>
<script type="text/javascript">
$(function () {
	var dollarsWon = <?= json_encode($graphData[0]) ?>;
	$.plot($("#graph"), [{
			"label": "Dollars Won",
			"color": 'rgb(62,118,182)',
			"data": dollarsWon
		}],{
			"legend": {position: "nw"},
			"xaxis": { mode: "time"},
			"yaxis": { tickFormatter: function(val) {
				return formatCurrency(val);
			}
		}
	});

	<?php foreach (array_keys($filters) as $key) : ?>
	var m = new SS.FilterMenu('<?= $key ?>', '#hiddenForm', '#filter_<?= $key ?>');
	<?php $list = $filters[$key]; ?>
	m.init(<?= json_encode($list) ?>, <?= json_encode(isset($condAsMap[$key]) ? $condAsMap[$key] : '') ?>);
	<?php endforeach;// (array_keys($filters) as $key) : ?>
});
</script>
<form id="hiddenForm" method="get" action="">
	<?php foreach ($condAsMap as $key => $rows) {
		$values = array_unique(array_values($rows));
		// All values are true
		if (!empty($values) && count($values) == 1 && $values[0] === true) {
			$labels = array_keys($rows);
		} else {
			$labels = array_values($rows);
		}
		echo "<input type='hidden' name='$key' value=\"".htmlentities(implode(',', $labels))."\" />";
	} ?>
	<input type="hidden" name="sort" value="<?= "$sortKey,$sortDir" ?>" />
	       <?php
	       if ($isPublic) {
		       echo '<input type="hidden" name="share" value="'.$share.'" />';
	       }
	       ?>
</form>
<?= $session->flash() ?>
<div id="graph">Graph of Data</div>
<div id="record">
	<h1 style="text-align: center"><?= $viewingUser['User']['username'] ?></h1><br />
<div class='header-table'><h2><div>Record</div></h2>
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
</div></div>
<div id="groupStats" class="stat-style-table clear clearfix">
	<?php
	$first = true;
	echo "<div class='header-table'><h2><div>W-L-T Grouped By:</div></h2>";
	echo "<table class='buttons'><tr>";
	foreach ($groupStats as $label => $stats) {
		$perc = round(100/count($groupStats));
		echo "<td width='$perc%'>$label</td>";
	}
	echo "</tr></table>";
	foreach ($groupStats as $label => $stats) {

		if (!$first) {
			$hidden = 'hidden';
		} else {
			$hidden = '';
			$first = false;
		}

		$label = preg_replace('/[^a-z]+/', '_', strtolower($label));
		echo "<table class='spaced-table cell-left $hidden show_$label'>";
		foreach ($stats as $row) {
			$def = $row['CalcStat']->getDef();
			$record = $row['record'];

			echo "<tr><td width='160px'>";
			if (count($def) == 2) {
				if ($def[0] === false) {
					echo "less than {$def[1]}";
				} else if ($def[1] === false) {
					echo "greater than {$def[0]}";
				} else {
					echo "{$def[0]} to {$def[1]}";
				}
			} else {
				echo $def;
			}
			echo "</td>";

			echo "<td class='label'>{$record['win']} - {$record['loss']} - {$record['tie']}</td>";
			echo "<td class='number'>$".number_format($record['dollarsWon'], 2)."</td></tr>\n";
		}
		echo "</table>";
	}
	echo "</div>";
	?>
</div>
<div id="analysisStats" class="stat-style-table clearfix">
<?php
	$types = array('Spread', 'Moneyline', 'Total');
	$halfs = array('', 'half_', 'second_');
	$dirs = array('home','visitor','over','under');
	$favorites = array('', '_favorite', '_underdog');

	$first = true;
	echo "<div class='header-table'><h2><div>W-L-T Grouped by Bet Type:</div></h2>";
	echo "<table class='buttons'><tr>";
	foreach ($types as $label) {
		$perc = round(100/count($types));
		echo "<td width='$perc%'>$label</td>";
	}
	echo "</tr></table>";
	foreach ($types as $type) {

		if (!$first) {
			$hidden = 'hidden';
		} else {
			$hidden = '';
			$first = false;
		}

		$label = preg_replace('/[^a-z]+/', '_', strtolower($type));
		echo "<table class='spaced-table cell-left $hidden show_$label'>";
		foreach ($halfs as $half) {
			foreach ($dirs as $dir) {
				foreach ($favorites as $favorite) {
					$type = strtolower($type);
					$record = empty($analysisStats[$half.$type][$dir.$favorite]) ? array() : $analysisStats[$half.$type][$dir.$favorite]; 
					if (!empty($record)) {
						$disptype = $betTypes[$half.$type];
						$dirfav = Inflector::humanize($dir.$favorite);
						echo "<tr><td>$disptype $dirfav</td><td>{$record['win']} - {$record['loss']} - {$record['tie']}</td>";
						echo "<td class='number'>$".number_format($record['dollarsWon'], 2)."</td></tr>\n";	
					}
				}
			}
		}
		echo "</table>";
	}
	echo "</div>";
?>
</div>
<?php
$urlParams = $this->params['url'];
unset($urlParams['url']);
$urlParams['share'] = $share;
$fullurl = $html->url('/' . $this->params['url']['url'], true). '?'. http_build_query($urlParams);
?>
<?php if (!$isPublic): ?><div id="shareLink" class="clear"><a href="#" class="shortlink" data-url="<?= h($fullurl) ?>">Click to get link to share page</a></div><?php endif;// (!$isPublic): ?>
<div id="betTable" class="clear">
	<?php if (!$isPublic): ?>
	<form method='post' action='<?= $html->url('/bets/modify') ?>' id="betsForm">
	<label for='tagvalue'>Tag: </label>
	<input type='text' name='tagvalue' id='tagvalue' />
	<input type='submit' name="Tag" value='Tag'  />
	<input type="submit" name="Delete" id="deleteBets" value="Delete Bets" />
	<input type="button" name="Edit" id="editBets" value="Edit Bets" />
	<?php endif;// (!$isPublic): ?>

	<a name="Bets"><?= $html->link('Reset Filters', '/bets/view?reset=1') ?></a>

	<table>
	<tr>
		<?php if (!$isPublic): ?><th><input type="checkbox" class="check-all-parent" name="dummy" /></th><?php endif;// (!$isPublic): ?>
		<th>&nbsp;</th>
		<th class="clickable">Date <span class="extra-click" id="filter_date"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">League <span class="extra-click" id="filter_league"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Bet On <span class="extra-click" id="filter_beton"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Bet Type <span class="extra-click" id="filter_type"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Line <span class="extra-click" id="filter_spread"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Visitor <span class="extra-click" id="filter_visitor"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Home <span class="extra-click" id="filter_home"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Risk <span class="extra-click" id="filter_risk"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Odds <span class="extra-click" id="filter_odds"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Winnings <span class="extra-click" id="filter_winning"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Book <span class="extra-click" id="filter_book"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
		<th class="clickable">Tags <span class="extra-click" id="filter_tag"><img alt="\/" src="<?= $html->url('/img/icons/green_arrow_down.gif') ?>" /></span></th>
	</tr>

	<?php
	$i = 0;
	foreach ($bets as $bet) {
		$i++;
		dispBet($html, $i, $bet, $isPublic, $betTypes);
		if (!empty($bet['parlays'])) {
			foreach ($bet['parlays'] as $parlay) {
				dispBet($html, null, $parlay, $isPublic, $betTypes);
			}
		}
	}
	?>
	</table>
	<?php if (!$isPublic): ?></form><?php endif;// (!$isPublic): ?>
</div>
<?php
function parlayNull($winning, $active=1) {
	if (is_null($winning)) {
		return $active == 1 ? '' : 'cancelled';
	}
	if (empty($winning)) {
		return 'T';
	}
	return $winning >= 0 ? 'W' : 'L';
}
function dispBet($html, $i, $bet, $isPublic, &$betTypes) {
	$colorStyle = '';
	if (time() - strtotime($bet['created']) < 30) {
		$colorStyle = 'style="background-color: #FFF7D7"';
	}
?>
	<tr <?=$colorStyle?>>
		<?php if (!$isPublic): ?><td><?= empty($i) ? '' : "<input class='check-all' type='checkbox' name='tag[{$bet['betid']}]' />" ?></td><?php endif;// (!$isPublic): ?>
		<td><?= $i ?></td>
		<td class="date"><?= date("n/j/y", strtotime($bet['date'])) ?></td>
		<td><?= $bet['league'] ?></td>
		<td><?= $bet['beton'] ?></td>
		<td><?= $betTypes[$bet['type']] ?></td>
		<td class="number"><?= $bet['line'] ?></td>
		<td><?= $bet['visitor'] ?></td>
		<td><?= $bet['home'] ?></td>
		<td class="number"><?= empty($i) ? '' : nullMoney($bet['risk']) ?></td>
		<td class="number"><?= empty($i) ? '' : $bet['odds'] ?></td>
		<td class="number"><?= empty($i) ? parlayNull($bet['winning'], $bet['active']) : nullMoney($bet['winning'], $bet['active']) ?></td>
		<td><?= $bet['book'] ?></td>
		<td><?= $bet['tag'] ?></td>
	</tr>
<?php
}

function nullMoney($money, $active=1) {
	if (is_null($money)) {
		return $active == 1 ? '' : 'cancelled';
	}
	return money_format('%(n', $money);
}
