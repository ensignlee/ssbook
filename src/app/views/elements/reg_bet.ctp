<?php
$betid = $bet['UserBet']['id'];
$tags = array();
foreach ($bet['Tag'] as $tag) {
	$tags[] = $tag['name'];
}

$isParlay = false;

echo "<input type='hidden' name='scoreid[$betid]' value='{$bet['Score']['id']}' />";
echo "<input type='hidden' name='date_std[$betid]' value='{$bet['Score']['game_date']}' />";
if (!is_null($bet['UserBet']['parlayid'])) {
	$isParlay = true;
	echo "<input type='hidden' name='parlayid[$betid]' value='{$bet['UserBet']['parlayid']}' />";
	echo "<input type='hidden' name='pt[$betid]' value='{$bet['UserBet']['pt']}' />";
}
if ($betTypeParent == 'moneyline') {
	echo "<input type='hidden' name='spread[$betid]' value=0 />";
}

?>
<h3><?= h($bet['Score']['visitor']) ?> @ <?= h($bet['Score']['home']) ?> - <?= date('n/j/Y g:i A', strtotime($bet['Score']['game_date'])) ?></h3>
<table>
	<tr>
		<th>Type</th>
		<th>Direction</th>
		<?php if ($betTypeParent == 'spread') { ?>
			<th>Spread</th>
		<?php } else if ($betTypeParent == 'total') { ?>
			<th>Total</th>
		<?php } ?>
		<?php if (!$isParlay) { ?>
		<th>Risk</th>
		<th>Odds</th>
		<th>Book</th>
		<th>Tag</th>
		<?php } ?>
	</tr>
	<tr>
		<td>	<select name="type[<?= $betid?>]">
				<?php
				foreach ($types as $key => $value) {
					if (strpos($key, $betType) === false) {
						continue;
					}
					if ($bet['UserBet']['type'] == $key) {
						echo "<option value=\"$key\" selected='selected'>$value</option>";
					} else {
						echo "<option value=\"$key\">$value</option>";
					}
				}
				?>
			</select>
		</td>
		<td>
			<select name="direction[<?= $betid ?>]">
				<?php
				if ($betTypeParent == 'total') {
					$dirs = array('over' => 'Over', 'under' => 'Under');
				} else {
					$dirs = array('visitor' => $bet['Score']['visitor'], 'home' => $bet['Score']['home']);
				}
				foreach ($dirs as $key => $value) {
					if ($bet['UserBet']['direction'] == $key) {
						echo "<option value=\"$key\" selected='selected'>$value</option>";
					} else {
						echo "<option value=\"$key\">$value</option>";
					}
				}
				?>
			</select>
		</td>
		<?php if ($betTypeParent != 'moneyline') { ?>
			<td><input type="text" size="7" name="spread[<?= $betid?>]" value="<?= h($bet['UserBet']['spread']) ?>" /></td>
		<?php } ?>
		<?php if (!$isParlay) { ?>
		<td><input type="text" size="7" name="risk[<?= $betid?>]" value="<?= h($bet['UserBet']['risk']) ?>" /></td>
		<td><input type="text" size="7" name="odds[<?= $betid?>]" value="<?= h($bet['UserBet']['odds']) ?>" /></td>
		<td><input type="text" size="12" name="book[<?= $betid?>]" value="<?= h($bet['UserBet']['source']) ?>" /></td>
		<td><input type="text" size="20" name="tag[<?= $betid?>]" value="<?= h(implode($tags,',')) ?>" /></td>
		<?php } ?>
	</tr>
</table>