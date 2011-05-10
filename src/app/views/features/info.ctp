<?php
$html->css('features', 'stylesheet', array('inline' => false));

//$active
$fid = $feature['Feature']['id'];
$title = $feature['Feature']['title'];
$descr = $feature['Feature']['description'];

?>
<h1><?= $title ?></h1>
<p><?= $descr ?></p>
<br />
<?php
foreach ($comments as $comment) {
	$username = empty($comment['User']['username']) ? 'anonymous' : $comment['User']['username'];
	$created = date('n/j/Y g:i A', strtotime($comment['FeatureComment']['created']));
	$commentString = $comment['FeatureComment']['comment'];
	echo "<div class='comment'><div class='title'>$username wrote at $created</div><div class='text'>$commentString</div></div>";
}

if (!$active) {
	echo "<div>No Longer Active</div>";
} else {
	if (empty($userid)) {
		echo '<div>Please log in to post a comment</div>';
	} else {
		?>
<div class='inputtext'>
<form action="<?= $html->url('/features/comment/'.$fid) ?>" method="post">
	<div><textarea name="data[FeatureComment][comment]" style="width: 600px; height: 40px;"></textarea></div>
	<input type="submit" value="Comment" />
</form>
</div>
		<?php
	}
}