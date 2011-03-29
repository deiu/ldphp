<?php
/* manage.php
 * cloud manage page
 *
 * $Id$
 */

if (!$_user) {
    require_once('401.php');
    exit;
}

include_once('header.php');
?>

<div class="area-dashed" style="clear: left;">
<h3>new cloud</h3>
<?php $acls = array('public', 'known', 'private'); ?>
<form action="create" method="get" id="create">
    <p>1. pick a name: <sub class="right">at least 4 chars</sub></p>
    <div class="span-icon" style="float: left">
    <img src="/assets/images/check.gif" style="display: none" id="check_true" />
    <img src="/assets/images/cancel.gif" style="display: none" id="check_false" />
    </div>
    <input name="name" type="text" id="create_name" class="span-3 left" style="text-align: right; margin: 0" />
    <p><label for="create_name">.<?=BASE_DOMAIN?></label></p>
    <p style="text-align: right"><input id="create_check" type="button" value="check" /></p>
    <p>2. default read permissions:</p>
    <p>
    <?php $i = 0; foreach($acls as $acl) {?>
        <input type="radio" name="aclRead" value="<?=$acl?>" class="create_acl" id="aclRead_<?=$acl?>" <?=$i==0?'checked ':''?>/><label for="aclRead_<?=$acl?>"><?=$acl?></label>
    <?php $i++; } ?>
    </p>
    <p>3. default write permissions:</p>
    <p>
    <?php $i = 0; foreach($acls as $acl) { ?>
        <input type="radio" name="aclWrite" value="<?=$acl?>" class="create_acl" id="aclWrite_<?=$acl?>" <?=$i==0?'checked ':''?>/><label for="aclWrite_<?=$acl?>"><?=$acl?></label>
    <?php $i++; } ?>
    </p>
    <p style="text-align: right"><input id="create_submit" type="submit" value="create" disabled /></p>
</form>
</div>

<div class="area-dashed" style="min-width: 150px;">
<h3>your clouds</h3>
<?php
$d = sites\created_by($_user);
if (!count($d)) {
    ?><p>None found.</p><?php
} else {
    foreach ($d as $site) {
        $site = substr($site, 4);
        $link = strtok(REQUEST_BASE, ':').'://'.$site;
        echo "<a href=\"$link\">$site</a><a href=\"\"></a>";
        echo '<img class="right" src="/assets/images/cancel.gif" onclick="cloud.remove(\'', $site,'\')">';
        echo "<br />";
        foreach($sites->any("dns:$site") as $elt) {
            $p = basename($elt[1]['value']);
            if (substr($p, 0, 10) == 'schema#acl') {
                $p = substr($p, 7);
                $o = basename($elt[2]['value']);
                echo '<dd>', $p, ': ', $o, '</dd>';
            }
        }
        echo '<br />';
    }
}
?>
</div>

<div class="area-dashed">
<h3>your knowns' clouds</h3>
<?php
$d = profile\knows($_user);
if (!count($d)) {
    ?><p>No knowns (friends) were found in your profile.<br /><sub>we're not following sameAs/seeAlso yet</sub></p><?php
} else {
    ?><p>You know <?=count($d)?> others.</p><?php
    $n_visible = 0;
    foreach ($d as $known=>$stores) {
        if (count($stores)) {
            $n_visible += 1;
            echo "<div><a href='$known'>$known</a>";
            echo '<ul>';
            foreach ($stores as $store) {
                $store = substr($store, 4);
                $link = strtok(REQUEST_BASE, ':').'://'.$store;
                echo "<li><a href=\"", $link, "\">$store</a></li>";
            }
            echo '</ul></div>';
        }
    }
    if ($n_visible < 1) {
        echo "<p>None of their clouds are visible to you.</p>";
    }
}
$t = strftime('%c %Z', sess('knows_TS'));
echo "<p>updated: $t <a href='/s?reset=knows'><img src='/assets/images/redo.gif' /></a></p>";
?>
</div>

<script type="text/javascript" src="/assets/js/manage.js"></script>

<?php
TAG(__FILE__, __LINE__, '$Id$');
include_once('footer.php');
