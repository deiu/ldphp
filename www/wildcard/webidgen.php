<?php

// Prepare the request
$name = (isset($_POST['name']))?$_POST['name']:'Anonymous';
if (isset($_POST['path'])) {
    $path = $_POST['path'];
    // Exit if we don't have a #
    if (strpos($path, '#') === false) // missing # 
        die("You must at least provide a # fragment. For example: #me or #public.");

    // remove the # fragment so we get the profile document path
    $path_frag = explode('#', $path);
    $profile = $path_frag[0];
    $hash = $path_frag[1];

    if (file_exists($_filename.'/'.$profile) === true)
        die('You must pick a different identifier. <strong>'.
           $path.'</strong> already exists in the current directory!');
} else {
    die('You need to provide a preferred identifier.');
}
$email = $_POST['email'];
$spkac = str_replace(str_split("\n\r"), '', $_POST['SPKAC']);
$webid = $_base.$path;

$cert_cmd = 'python ../../py/pki.py '.
                " -s '$spkac'" .
                " -n '$name'" .
                " -w '$webid'";

// Send the certificate back to the user
header('Content-Type: application/x-x509-user-cert');
echo trim(shell_exec($cert_cmd));

/* --- Profile --- */

// Write the new profile to disk
$document = new Graph('', $_filename.$profile, '', $_base.$profile);
if (!$document) { return false; }

// add a PrimaryTopic
$document->append_objects($_base.'/'.$profile,
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
         array(array('type'=>'uri', 'value'=>'http://xmlns.com/foaf/0.1/PersonalProfileDocument')));
$document->append_objects($_base.'/'.$profile,
        'http://xmlns.com/foaf/0.1/primaryTopic',
         array(array('type'=>'uri', 'value'=>$_base.'/'.$path)));
 
// add a foaf:Person
$document->append_objects($_base.'/'.$path,
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        array(array('type'=>'uri', 'value'=>'http://xmlns.com/foaf/0.1/Person')));
// add name
$document->append_objects($_base.'/'.$path,
        'http://xmlns.com/foaf/0.1/name',
        array(array('type'=>'literal', 'value'=>$name)));
// add mbox if we have one
if (strlen($email) > 0) {
    $document->append_objects($_base.'/'.$path,
            'http://xmlns.com/foaf/0.1/mbox',
            array(array('type'=>'uri', 'value'=>'mailto:'.$email)));
}

// add modulus and exponent as bnode
$document->append_objects($_base.'/'.$path,
        'http://www.w3.org/ns/auth/cert#key',
        array(array('type'=>'bnode', 'value'=>'_:bnode1')));
$document->append_objects('_:bnode1',
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        array(array('type'=>'uri', 'value'=>'http://www.w3.org/ns/auth/cert#RSAPublicKey'))); 

if (isset($modulus))
$document->append_objects('_:bnode1',
        'http://www.w3.org/ns/auth/cert#modulus',
        array(array('type'=>'literal', 'value'=>$modulus, 'datatype'=>'http://www.w3.org/2001/XMLSchema#hexBinary')));

$document->append_objects('_:bnode1',
        'http://www.w3.org/ns/auth/cert#exponent',
        array(array('type'=>'literal', 'value'=>'65537', 'datatype'=>'http://www.w3.org/2001/XMLSchema#int')));

$document->save();

