<div id='leftsection'>
	Date: <input type='text' class='dateselect' name='startdate' value='<?=date('n/j/Y', strtotime('-1 day'))?>' /> -
	<input type='text' class='dateselect' name='enddate' value='<?=date('n/j/Y', strtotime('+1 week'))?>' />
	       <h4>Click to see games</h4>
	<div id='accorselect'>&nbsp;</div>
</div>
<div id='rightsection'>
	<?= $session->flash() ?>
	<input type='text' name='superbar' id='superbar' />
	<div id='enterbets'></div>
</div>
<?php
echo $javascript->link('enterbets.js');
$html->css('enterbets', 'stylesheet', array('inline' => false));
