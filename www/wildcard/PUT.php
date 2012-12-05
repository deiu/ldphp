<?php
/* PUT.php
 * service HTTP PUT controller
 *
 * $Id$
 */

require_once('runtime.php');

// permissions
if (empty($_user)) {
    httpStatusExit(401, 'Unauthorized');
} elseif ($_wac->can('Write') == false) {
    // debug
    openlog('data.fm', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
    syslog(LOG_INFO, $_wac->getReason());
    closelog();
    httpStatusExit(403, 'Forbidden');
} else {
    openlog('data.fm', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
    syslog(LOG_INFO, $_wac->getReason());
    closelog();
}


// action
$d = dirname($_filename);
if (!file_exists($d))
    mkdir($d, 0777, true);

$_data = file_get_contents('php://input');

if ($_input == 'raw') {
    require_once('if-match.php');
    file_put_contents($_filename, $_data);
    exit;
}

$g = new Graph('', $_filename, '', $_base);
require_once('if-match.php');

$g->truncate();
if (!empty($_input) && $g->append($_input, $_data)) {
    librdf_php_last_log_level() && httpStatusExit(400, 'Bad Request', null, librdf_php_last_log_message());
    $g->save();
} else {
    httpStatusExit(406, 'Content-Type ('.$_content_type.') Not Acceptable');
}

@header('Triples: '.$g->size());
