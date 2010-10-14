<?php
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.templates.layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php __('SageStats'); ?> : 
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
			<h1><?php echo $this->Html->link($this->Html->image('logo.png', array('alt' => 'logo')), '/', array('escape' => false)); ?></h1>
		</div>
		<div id="content">
			<ul>
				<li><?= $html->link('View', '/bets/view') ?></li>
				<li><?= $html->link('Enter', '/bets') ?></li>
				<li><?= $html->link('Create', '/users/create') ?></li>
			</ul>

			<?php echo $content_for_layout; ?>

			<?php echo $this->element('sql_dump'); ?>
		</div>
		<div id="footer">
			&copy;2010 <a href="http://www.cameroncomputer.com">CameronComputer.com</a>
		</div>
	</div>
</body>
</html>
