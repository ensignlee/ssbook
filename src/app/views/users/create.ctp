<h1>Register</h1>

<div style='width: 280px; float: left'>
<img src="/img/SypherV.png" />
</div>
<div style='width: 600px; float: left'>

<p>You're one step away from harnessing the power of information to increase your edge betting.</p>

<p>All you have to do is fill out this registration form and you'll be on your way to increasing your profits through analysis of your bets.</p>

<?= $session->flash(); ?>

<div class="registerBox clearfix">
<?php
$html->css('register', 'stylesheet', array('inline' => false));

echo $form->create('User', array('action' => 'create'));
echo $form->input('username', array('size' => 50));
echo $form->input('email', array('size' => 50));
echo $form->input('password', array('size' => 50));
echo $form->input('password2', array('label' => 'Confirm Password', 'type' => 'password', 'size' => 50));
echo "<div class='clear'><br />".$form->end('Create')."</div>";
?>
</div>

</div><br class='clear' />
