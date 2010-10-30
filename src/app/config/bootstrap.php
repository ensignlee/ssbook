<?php
/**
 * This file is loaded automatically by the app/webroot/index.php file after the core bootstrap.php
 *
 * This is an application wide file to load any function that is not used within a class
 * define. You can also use this to include or require any files in your application.
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
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * The settings below can be used to set additional paths to models, views and controllers.
 * This is related to Ticket #470 (https://trac.cakephp.org/ticket/470)
 *
 * App::build(array(
 *     'plugins' => array('/full/path/to/plugins/', '/next/full/path/to/plugins/'),
 *     'models' =>  array('/full/path/to/models/', '/next/full/path/to/models/'),
 *     'views' => array('/full/path/to/views/', '/next/full/path/to/views/'),
 *     'controllers' => array('/full/path/to/controllers/', '/next/full/path/to/controllers/'),
 *     'datasources' => array('/full/path/to/datasources/', '/next/full/path/to/datasources/'),
 *     'behaviors' => array('/full/path/to/behaviors/', '/next/full/path/to/behaviors/'),
 *     'components' => array('/full/path/to/components/', '/next/full/path/to/components/'),
 *     'helpers' => array('/full/path/to/helpers/', '/next/full/path/to/helpers/'),
 *     'vendors' => array('/full/path/to/vendors/', '/next/full/path/to/vendors/'),
 *     'shells' => array('/full/path/to/shells/', '/next/full/path/to/shells/'),
 *     'locales' => array('/full/path/to/locale/', '/next/full/path/to/locale/')
 * ));
 *
 */

/**
 * As of 1.3, additional rules for the inflector are added below
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */

if (!function_exists('curl_get_file_contents')) {
function curl_file_get_contents($url, $post = null) {
	$fp = curl_init();
	curl_setopt($fp, CURLOPT_URL, $url);
	curl_setopt($fp, CURLOPT_FAILONERROR, 1);
	curl_setopt($fp, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($fp, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($fp, CURLOPT_TIMEOUT, 120);
	curl_setopt($fp, CURLOPT_HTTPGET, true);
	$result = curl_exec($fp);
	curl_close($fp);

	if ($result) {
		return $result;
	} else {
		return false;
	}
}
}
if (!function_exists('mb_trim')) {
function mb_trim($string, $charlist='\\\\s', $ltrim=true, $rtrim=true) { 
        $both_ends = $ltrim && $rtrim; 

        $char_class_inner = preg_replace( 
            array( '/[\^\-\]\\\]/S', '/\\\{4}/S' ), 
            array( '\\\\\\0', '\\' ), 
            $charlist 
        ); 

        $work_horse = '[' . $char_class_inner . ']+'; 
        $ltrim && $left_pattern = '^' . $work_horse; 
        $rtrim && $right_pattern = $work_horse . '$'; 

        if($both_ends) 
        { 
            $pattern_middle = $left_pattern . '|' . $right_pattern; 
        } 
        elseif($ltrim) 
        { 
            $pattern_middle = $left_pattern; 
        } 
        else 
        { 
            $pattern_middle = $right_pattern; 
        } 

	return preg_replace("/$pattern_middle/usSD", '', $string); 
} 
}

if(!function_exists('safe_div')) {
function safe_div($left, $right) {
	if (is_null($left) || is_null($right)) {
		return null;
	}
	if ($right == 0) {
		return 0;
	}
	return $left / $right;
}
}

if(!function_exists('array_lookup')) {
/**
 * Caution, do not use inside a large array
 */
function array_lookup($key, &$arr, $default = false) {
	if (array_key_exists($key, $arr)) {
		return $arr[$key];
	}
	return $default;
}
}

if(!function_exists('numberSafeEmpty')) {
function numberSafeEmpty($str) {
	if (ctype_digit($str) && (int)$str === 0) {
		return false;
	}
	return empty($str);
}
}

setlocale(LC_MONETARY, 'en_US');