<?php ob_start(); ?>

<h1>Edit Bets</h1>
<div>
	
<?php
if (empty($bets)) {
	echo "Please select a bet to edit";
} else {
?>
	<form id="modalForm">
		<?php
		foreach ($bets as $bet) {
			echo '<div class="singleBet">';
			echo $editBets->renderBet($bet);
			echo '</div>';
		}
		?>
	</form>
<?php } ?>
	<div class="confirmation">
		<button id="editOkay">OK</button> <a id="editCancel" href="#">Cancel</a>
	</div>
</div>

<?php
$json['html'] = ob_get_contents();
if (!empty($this->params['isAjax'])) {
	ob_end_clean();
	echo json_encode($json);
} else {
	ob_end_flush();
}
