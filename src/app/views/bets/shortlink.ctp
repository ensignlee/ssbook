<h1>Share Bet Record</h1>
<div id="shareLinkDialog">

<div>
<label>Short Link:</label>
<input type="text" size="50" value="<?php echo $shorturl; ?>">
</div>

<div>
<label>Embed Image (HTML):</label>
<input type="text" size="50" value="<?php echo h('<img src="'.$imgurl.'" />'); ?>">
</div>

<div>
<label>Embed Image (BBCode):</label>
<input type="text" size="50" value="<?php echo h('[img]'.$imgurl.'[/img]'); ?>">
</div>

<a href="#" class="simplemodal-close">Close</a>

</div>
