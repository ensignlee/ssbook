<?php
if (!isset($cacheTimeout)) {
	$cacheTimeout = 30 * 60; // 30 minutes
} else {
	$cacheTimeout = (int)$cacheTimeout;
}

header("Cache-Control: private, max-age=10800, pre-check=10800");
header("Pragma: private");
header("Expires: " . date(DATE_RFC822, strtotime($cacheTimeout." minutes")));

echo $content_for_layout;