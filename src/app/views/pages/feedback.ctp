<h1>Feedback</h1>
<?php

if (!$sent) {

$username = '';
if (!empty($user['username'])) {
	$username = $user['username'];
}
?>
<p>Something broken? Want to see a feature implemented? Tell us about it!</p>
<form action="" method="post">
	<div>Email : <input type="text" style="width:35em" name="email" /></div>
	<input type="hidden" name="username" value="<?= $username ?>" />
	<div><textarea name="feedback" style="width:700px;height:200px;margin: 10px;"></textarea></div>
	<div style="margin-left: 20px;"><input type="submit" name="submit" value="Send" /></div>
</form>

<?php

} else {
	echo "<p>Thank you for your feedback</p>";
}