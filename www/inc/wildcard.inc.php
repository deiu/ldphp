<?php
/* wildcard.inc.php
 * site common includes
 *
 * $Id$
 */

$_domain = $_SERVER['SERVER_NAME'];
$_domain_data = $sites->SELECT_p_o("http://$_domain/");
$_user = $_SERVER['REMOTE_USER'];
$_filename = $_SERVER['REQUEST_FILENAME'];
$_base = $_SERVER['SCRIPT_URI'];

if (!strstr($_filename, '/')) {
    $_filename = '/home/'.BASE_DOMAIN."/data/{$_SERVER['SERVER_NAME']}/$_filename";
}

header("X-User: $_user");