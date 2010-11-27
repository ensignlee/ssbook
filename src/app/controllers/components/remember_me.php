<?php

/**
 * Uses the cake session cookie, which is cached to disk. (You can set it in the
 * core config.
 *
 * Therefore this cookis which, reads from the disk only will be used once every 2 hours,
 * or whatever you set the default for the cake cookie to be.
 */

// http://lecterror.com/articles/view/rememberme-component-the-final-word
class RememberMeComponent extends Object
{
    var $components = array('Auth', 'Cookie');
    var $controller = null;

    /**
     * Cookie retention period.
     *
     * @var string
     */
    var $period = '+2 weeks';
    var $cookieName = 'User';

    function startup(&$controller)
    {
        $this->controller =& $controller;
    }

    function remember($username, $password)
    {
        $cookie = array();
        $cookie[$this->Auth->fields['username']] = $username;
        $cookie[$this->Auth->fields['password']] = $password;

        $this->Cookie->write(
            $this->cookieName,
            $cookie,
            true,
            $this->period
        );
    }

    function check()
    {
        $cookie = $this->Cookie->read($this->cookieName);

        if (!is_array($cookie) || $this->Auth->user())
            return;

        if ($this->Auth->login($cookie))
        {
            $this->Cookie->write(
                $this->cookieName,
                $cookie,
                true,
                $this->period
            );
        }
        else
        {
            $this->delete();
        }
    }

    function delete()
    {
        $this->Cookie->delete($this->cookieName);
    }
}