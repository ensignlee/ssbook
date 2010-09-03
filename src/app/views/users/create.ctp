<h1>Create User</h1>
<?php

$session->flash('auth');
echo $form->create('User', array('action' => 'create'));
echo $form->input('username');
echo $form->input('password');
echo $form->input('password2', array('label' => 'Confirm', 'type' => 'password'));
echo $form->end('Create');

?>
