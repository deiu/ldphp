<?php
/* index.rdf.php
 * service RDF index page
 */
require_once('runtime.php');

$g = new Graph('memory', '', '', $_base);

// page length (number of items on a page)
$pl = 10;

$listing = array();
if (is_dir($_filename))
    $listing = scandir($_filename);

$contents = array();

foreach($listing as $item) {
    $len = strlen($item);
    if (!$len) continue;
    // don't report .. for the root
    if ($item == '..')
        continue;
    $is_dir = is_dir("$_filename/$item");
    $item_ext = strrpos($item, '.');
    $item_ext = $item_ext ? substr($item, 1+$item_ext) : '';
    $item_elt = $item;
    if (in_array($item_ext, array('sqlite')))
        $item_elt = substr($item_elt, 0, -strlen($item_ext)-1);
    if ($is_dir)
        $item_elt = "$item_elt/";
    if ($is_dir)
        $item_type = 'p:Directory';
    elseif (in_array($item_ext, $_RAW_EXT))
        $item_type = 'p:File';
    else
        $item_type = '<http://www.w3.org/2000/01/rdf-schema#Resource>';
    $mtime = filemtime("$_filename/$item");
    $size = filesize("$_filename/$item");
    
    $properties = array( 'resource' => $item_elt,
    					 'type' => $item_type,
    					 'mtime' => $mtime,
    					 'size' => $size);
    $contents[] = $properties;
}

// serve LDP by default and beging with the first page
$p = 1;
$complement = '?p=1';
if (isset($_GET['p'])) {
	$p = (int) $_GET['p'];
	$complement = '?p='. (string) $p;
}

if ($p > 0) { 
	$contents_chunks = array_chunk($contents, $pl);
	$contents = $contents_chunks[$p-1];
	if($p < count($contents_chunks)) {
    	$g->append('turtle', "@prefix ldp: <http://www.w3.org/ns/ldp#> . <". $_request_path . $complement ."> ldp:nextPage <". $_request_path . "?p=". (string) ($p+1) ."> ." );
	} 
	else {
		$g->append('turtle', "@prefix ldp: <http://www.w3.org/ns/ldp#> . @prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> . <". $_request_path . $complement ."> ldp:nextPage rdf:nil ." );
	}
	$g->append('turtle', "@prefix ldp: <http://www.w3.org/ns/ldp#> . <". $_request_path . $complement ."> a ldp:Page . <". $_request_path . $complement ."> ldp:pageOf <". $_request_path ."> ." );
}


$ldprs = array();
foreach ($contents as $item) {
    if ($item['resource'] != './')
        $ldprs[] = '<'.$item['resource'].'>';
}

foreach($contents as $properties) {
    // filesystem resources
	$g->append('turtle', "@prefix p: <http://www.w3.org/ns/posix/stat#> . <".
        $properties['resource']."> a ".
        $properties['type'] ." ; p:mtime ".
        $properties['mtime'] ." ; p:size ".
        $properties['size'] ." .");

    // LDP resoures
    if ($properties['resource'] == "./") {
        $g->append('turtle', "@prefix ldp: <http://www.w3.org/ns/ldp#> . @prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>. ".
            "<".$properties['resource']."> a ldp:Container ; " .
            "ldp:membershipSubject <> ; ".
            "ldp:membershipPredicate rdfs:member ; ".
            "ldp:membershipObject ldp:MemberSubject ; ".
            "rdfs:member ".implode(",", $ldprs)." .");
    }

}



