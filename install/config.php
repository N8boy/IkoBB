<?php

if (!defined('BASEPATH')) {
    die();
}

/*
 * IkoBB Configuration File.
 * IkoBB (http://tangobb.net)
 */
define('MYSQL_HOST', '%mysql_host%');  // Your SQL host
define('MYSQL_USERNAME', '%mysql_username%'); // Your SQL username
define('MYSQL_PASSWORD', '%mysql_password%'); // Your SQL password
define('MYSQL_DATABASE', '%mysql_database%'); // Your SQL Database for IkoBB
define('MYSQL_PREFIX', '%mysql_prefix%'); // The prefix for this forum
define('MYSQL_PORT', 3306); // The port to your SQL

/*
 * Iko Local Details
 */
define('SITE_URL', '%site_url%'); // Without the ending "/"
define('IKOBB_VERSION', '0.1.0'); // Do not change if you want to get updates
define('IKO_SESSION_TIMEOUT', 31536000); // In seconds. Default: 31536000 (one year)
define('USER_PASSWORD_HASH_COST', 10);  // Not used? ToDo: Check if somewhere used, if not delete

/*
 * Usergroup Details.
 * DO NOT CHANGE IF YOU DON'T KNOW WHAT THIS WILL DO.
 */
define('ADMIN_ID', '4');
define('MOD_ID', '3');
define('BAN_ID', '2');

/*
 * Forum Configuration.
 */
define('THREAD_RESULTS_PER_PAGE', 12);
define('POST_RESULTS_PER_PAGE', 9);

?>
