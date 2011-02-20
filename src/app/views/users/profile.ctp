<h1>Profile</h1>

<p>Use the profile to change your email and password</p>

<?= $session->flash(); ?>

<div class="registerBox clearfix">
<?php
$html->css('register', 'stylesheet', array('inline' => false));

echo $form->create('User', array('action' => 'profile'));
echo "<label for='username'>Username</label><div style='height:24px'>$username</div>";
echo $form->input('email', array('size' => 50));
echo $form->input('password', array('label' => 'New Password', 'size' => 50));
echo $form->input('password2', array('label' => 'Confirm Password', 'type' => 'password', 'size' => 50));
echo "<div class='clear'><br />".$form->end('Update')."</div>";
?>
</div>