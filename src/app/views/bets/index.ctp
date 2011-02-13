<div id='leftsection'>
	Date: <input type='text' class='dateselect' name='startdate' value='<?=date('n/j/Y', strtotime('-1 day'))?>' /> -
	<input type='text' class='dateselect' name='enddate' value='<?=date('n/j/Y', strtotime('+1 week'))?>' />
	       <h5>Click to see games</h5>
	<div id='accorselect'>&nbsp;</div>
</div>
<div id='rightsection'>
	<?= $session->flash() ?>
	<h5>Type in team names to find games</h5>
	<input type='text' name='superbar' id='superbar' />
	<div id='enterbets'></div>
</div>
<?php
echo $javascript->link('enterbets.js');
$html->css('enterbets', 'stylesheet', array('inline' => false));
