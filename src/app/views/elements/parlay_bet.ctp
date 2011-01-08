<?php

$betid = $bet['UserBet']['id'];
$tags = array();
foreach ($bet['Tag'] as $tag) {
	$tags[] = $tag['name'];
}

if ($betType == 'parlay') {
	echo "<h3>Parlay</h3>";
} else {
	echo "<h3>Teaser</h3>";
}
echo "<input type='hidden' name='type[$betid]' value='{$bet['UserBet']['type']}' />";

?>
<table>
	<tr>
		<th>Risk</th>
		<th>Odds</th>
		<th>Book</th>
		<th>Tag</th>
	</tr>
	<tr>		
		<td><input type="text" size="7" name="risk[<?= $betid?>]" value="<?= h($bet['UserBet']['risk']) ?>" /></td>
		<td><input type="text" size="7" name="odds[<?= $betid?>]" value="<?= h($bet['UserBet']['odds']) ?>" /></td>
		<td><input type="text" size="12" name="book[<?= $betid?>]" value="<?= h($bet['UserBet']['source']) ?>" /></td>
		<td><input type="text" size="20" name="tag[<?= $betid?>]" value="<?= h(implode($tags,',')) ?>" /></td>
	</tr>
	<tr>
		<td colspan="4" style="padding-left: 15px">
			<?php 
			foreach ($bet['UserBet']['Parlay'] as $parlay) {
				echo $editBets->renderBet($parlay);
			}
			?>
		</td>
	</tr>
</table>