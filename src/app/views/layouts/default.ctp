<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php __('Sharp Bet Tracker'); ?> : 
		<?php echo $title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('reset');
		echo $this->Html->css('generic');

		echo $this->Javascript->link('http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js');
		echo $this->Javascript->link('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js');
		echo $this->Javascript->link('date');
?>
<!--[if IE]>
<?= $this->Html->css('ie_all') ?>
<![endif]-->
<script type='text/javascript'>
var SS = {};
SS.Cake = {
	base : '<?= $this->base ?>',
	here : '<?= $this->here ?>'
};
</script>
	<?php
		echo $scripts_for_layout;
	?>
</head>
<body>
	<div id="container">
		<div id="header">
			<?= $this->Html->link($this->Html->image('logo.png', array('height' => '55px', 'width' => '360px', 'alt' => 'logo')), '/', array('escape' => false)); ?>

		</div>
		<div id="navbar">
			<div class='nav'>
			<ul class='clearfix'>
<?php if (empty($user)) { ?>
				<li><?= $html->link('Enter Bets', '/pages/enter') ?></li>
				<li><?= $html->link('View Bets', '/pages/view') ?></li>
				<li class="far-right"><?= $html->link('Register', '/users/create') ?></li>
<?php } else { ?>
				<li><?= $html->link('Enter Bets', '/bets') ?></li>
				<li><?= $html->link('View Bets', '/bets/view') ?></li>
<?php } ?>				
			</ul>
			</div>
		</div>
		<div id="banner">
			<?= $this->Html->image('girls_header.jpg') ?>
<div id="loginBox">
<?php
if (empty($user)) {
	echo $form->create('User', array('action' => 'login'));
	echo $form->input('username');
	echo $form->input('password');
	echo "Remember: ".$form->checkbox('remember', array('checked' => true));
	echo $form->submit('Login', array('div' => false));
	echo $form->end();
} else {
	echo $user['username'].' '.$html->link('logout', '/users/logout');
}
?>
</div>
		</div>
		<div id="content">

			<?php echo $content_for_layout; ?>

			<!-- 
			<?php echo $this->element('sql_dump'); ?>
			--> 
		<br style='clear: both'/>
		</div>
		<div id="footer">
			<div class='nav'>
			<ul>
				<li><?= $html->link('Home', '/') ?></li>
				<li class='sep'>|</li>
				<li><?= $html->link('View', '/bets/view') ?></li>
				<li class='sep'>|</li>
				<li><?= $html->link('Enter', '/bets') ?></li>
				<li class='sep'>|</li>
				<li><?= $html->link('Create', '/users/create') ?></li>
			</ul>
			</div>
			<div id='copy'>&copy;2010 <a href="http://www.cameroncomputer.com">CameronComputer.com</a></div>
		</div>
	</div>
</body>
</html>
