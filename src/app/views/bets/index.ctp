<div id='leftsection'>
	Date: <input type='text' class='dateselect' name='startdate' value='<?=date('Y-m-d')?>' /> -
	<input type='text' class='dateselect' name='enddate' value='<?=date('Y-m-d', strtotime('+1 day'))?>' />
	<div id='accorselect'>&nbsp;</div>
</div>
<div id='rightsection'>
	<input type='text' name='superbar' id='superbar' />
	<div id='enterbets'></div>
</div>
<?php
echo $javascript->link('enterbets.js');
$html->css('enterbets', 'stylesheet', array('inline' => false));
