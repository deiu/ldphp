<?php
/* GET.php
 * service HTTP GET/HEAD controller
 *
 * $Id$
 */

if (basename($_filename) == 'favicon.ico') {
    header('Location: http'.(isHTTPS()?'s':'').'://'.BASE_DOMAIN.'/favicon.ico');
    exit;
} 

if (!in_array($_method, array('GET', 'HEAD')) && !isset($i_query))
    httpStatusExit(501, 'Not Implemented');

if (!file_exists($_filename) && in_array($_filename_ext, array('turtle','n3','json','rdf','nt','json-ld'))) {
    $_filename = substr($_filename, 0, -strlen($_filename_ext)-1);
    $_base = substr($_base, 0, -strlen($_filename_ext)-1);
    if ($_filename_ext == 'turtle' || $ext == 'n3') {
        $_output = 'turtle';
        $_output_type = 'text/turtle';
    } elseif ($_filename_ext == 'json') {
        $_output = 'json';
        $_output_type = 'application/json';
    } elseif ($_filename_ext == 'rdf') {
        $_output = 'rdfxml-abbrev';
        $_output_type = 'application/rdf+xml';
    } elseif ($_filename_ext == 'nt') {
        $_output = 'ntriples';
        $_output_type = 'text/plain';
    } elseif ($_filename_ext == 'json-ld') {
        $_output = 'json-ld';
        $_output_type = 'application/json';
    }
}

// permissions
if (!\sites\is_public($_domain)) {
    if (empty($_user))
        httpStatusExit(401, 'Unauthorized', '401.php');
    elseif (!\sites\is_owner($_domain, $_user) && !wac('Read'))
        httpStatusExit(403, 'Forbidden', '403-404.php');
}

// directory indexing
if (is_dir($_filename)) {
    if (substr($_filename, -1) != '/') {
        header("Location: $_base/");
        exit;
    } elseif (!isset($_output) || empty($_output)) {
        include_once('index.html.php');
        exit;
    } else {
        include_once('index.rdf.php');
    }
}

// set default output
if (empty($_output)) {
    $_output = 'turtle';
    $_output_type = 'text/turtle';
}

// output raw
if ($_output == 'raw') {
    if ($_output_type)
        header("Content-Type: $_output_type");
    if (!file_exists($_filename))
        httpStatusExit(404, 'Not Found', '403-404.php');
    if ($_method == 'GET')
        readfile($_filename);
    exit;
}

// output RDF
if (!isset($g))
    $g = new \RDF\Graph('', $_filename, '', $_base);

// .ALL: directory accumulator
if (!$g->exists() && substr($_filename, -4) == '.ALL') {
    $_basebase = dirname($_base);
    $_filedir = dirname($_filename);
    $contents = scandir($_filedir);
    foreach($contents as $item) {
        if (strrchr($item, '.') == '.sqlite') continue;
        $g->append_file('turtle', "file://$_filedir/$item", "$_basebase/$item");
    }
} elseif (!empty($_filename) && !$g->exists() && !$g->size())
    header('HTTP/1.1 404 Not Found');

header('X-Triples: '.$g->size());
if (isset($i_query))
    header('X-Query: '.str_replace(array("\r","\n"), '', $i_query));

if (isset($i_callback)) {
    header('Content-Type: text/javascript');
    if ($_method == 'GET') {
        if ($_output == 'json' || isset($i_query)) {
            echo $i_callback, '(';
            register_shutdown_function(function() { echo ');'; });
        } else {
            echo $i_callback, '("';
            register_shutdown_function(function() { echo '");'; });
        }
    }
} elseif (isset($i_query)) {
    header('Content-Type: application/json');
} else {
    header("Content-Type: $_output_type");
}

if (in_array($_method, array('GET', 'POST')))
    if (isset($i_query)) {
        echo $g->query($i_query);
    } else {
        echo $g->to_string($_output);
    }
