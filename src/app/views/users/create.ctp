<h1>Register</h1>
<p>You're one step away from harnessing the power of information to increase your edge betting.</p>

<p>All you have to do is fill out this registration form and you'll be on your way to increasing your profits through analysis of your bets.</p>

<?= $session->flash(); ?>

<div class="registerBox clearfix">
<?php
$html->css('register', 'stylesheet', array('inline' => false));

echo $form->create('User', array('action' => 'create'));
echo $form->input('username');
echo $form->input('email', array('size' => 50));
echo $form->input('password');
echo $form->input('password2', array('label' => 'Confirm Password', 'type' => 'password'));
echo "<div class='clear'><br />".$form->end('Create')."</div>";
?>
</div>
