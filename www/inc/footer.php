<?php
/* footer.php
 * page footer
 *
 * $Id$
 */

define('FOOTER', 1);
TAG(__FILE__, __LINE__, '$Id$');
$time = $TAGS[count($TAGS)-1]['time']-$TAGS[0]['time'];
$caller = $TAGS[count($TAGS)-2];
$sparql_n = 0;
$sparql_t = 0;
if (isset($timings)) {
    $sparql_n = count($timings);
    foreach ($timings as $t) {
        $sparql_t += $t['time'];
    }
}
?>
<div>
<hr />
<?php

    $user_link = sess('u:link');
    $user_name = sess('u:name');
    if (is_null($user_name) || !strlen($user_name))
        $user_name = $_user;
?>
<span style="float: left; clear: left">
<?php

if ($user_link) { ?>
<a href="<?=$user_link?>" target="_blank"><?=$user_name?></a>
&nbsp;&nbsp;&nbsp;<a href="https://data.fm/logout" />logout</a>

<? } else { ?>
<a href="#login" onclick="$('login').toggle()">login</a>
<div id="login" style="display: none; position: absolute;" class="notice" align="center">
    <form action="//data.fm/login" style="float: left;">
    <input type="submit" name="auth" value="WebID" />
    <?php if (defined('GAPIKEY')) { ?>
    <input type="submit" name="provider" value="Gmail" />
    <input type="submit" name="provider" value="AOL" />
    <input type="submit" name="provider" value="Yahoo" />
    <?php } ?>
    </form>
</div>
&nbsp;&nbsp;&nbsp;<a id="create-webid" name="create[webid]" />create id</a>
<table id="webid-gen" style="display:none;">
    <form method="POST" action="">
        <tr><td>Your name: </td><td><input type="text" name="name" size="40" class="required"></td></tr>
        <tr><td>Preferred identifier: </td><td><input type="text" name="path" size="40" value="card#me" class="required"></td></tr>
        <tr><td>Email (recovery): </td><td><input type="text" name="email" size="40"></td></tr>
        <tr><td colspan="2"><keygen name="SPKAC" challenge="randomchars" keytype="rsa" hidden></td></tr>
        <tr><td colspan="2"><input type="submit" value="Generate" onclick="hideWebID()"> <input type="button" value="Cancel" onclick="hideWebID()"></td></tr>
    </form>
</table>
<script>
$('create-webid').observe('click', function(e) {
  $('webid-gen').setStyle({
    top: e.pageY,
    left: e.pageX
  });
  $('webid-gen').show();
});
function hideWebID() {
    $('webid-gen').hide();
}
</script>
<? }

?>
</span>
<?php

if ($_options->coderev) {
$src = explode('/', __FILE__);
$src = array_slice($src, array_search('www', $src));
$src = implode('/', $src);
$src = "https://github.com/linkeddata/data.fm/tree/master/$src";
?>
<span id="codeID" style="display:none;">
/ <?php echo implode(' / ', array(
    'librdf: '.array_shift(explode(' ',librdf_version_string_get())),
    'raptor: '.array_shift(explode(' ',raptor_version_string_get())),
    'rasqal: '.array_shift(explode(' ',rasqal_version_string_get()))
)); ?>
</span>
<span id="codeTime" onclick="$('codeID').toggle();"><?=substr($time, 0, 6)?>s
<?=$sparql_n<1?'':sprintf('with %d quer%s in %ss', $sparql_n, $sparql_n>1?'ies':'y', substr($sparql_t, 0, 6))?></span>
</div>
<?php
}

?>
<div class="clear"></div>
</div>
</body>
</html>
