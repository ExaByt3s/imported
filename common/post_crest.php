<?php

/**
 * $Date$
 * $Revision$
 * $HeadURL$
 * @package EDK
 */
$page = new Page('Post killmail');

if (isset($_POST['undelete']) && isset($_POST['kll_id']) && $page->isAdmin()) {
	$kll_id = intval($_POST['kll_id']);
	$qry = DBFactory::getDBQuery();
	$qry->execute("DELETE FROM kb3_mails WHERE kll_id = ".$kll_id);
	if (isset($_POST['killmail'])) {
		$html = post();
	} else {
		$html = "Mail lock has been removed.";
	}
} else if (isset($_POST['crest_url'])) {
	$html = post();
}
if (isset($html)) {
	$smarty->assign('error', $html);
}
$smarty->assign('isadmin', $page->isAdmin());
$smarty->assign('post_forbid', config::get('post_forbid'));
$smarty->assign('post_oog_forbid', config::get('post_oog_forbid'));

$page->setContent($smarty->fetch(get_tpl('post_crest')));
$page->generate();

function post()
{
    global $page;
    if (config::get("post_password") == ''
        || crypt($_POST['password'], config::get("post_password")) == config::get("post_password")
        || $page->isAdmin()) {

        $CrestParser = new CrestParser($_POST['crest_url']);

        if($CrestParser->getError()) {
            $errors = $CrestParser->getError();
            foreach ($errors as $error) {
                $html .= 'Error: '.$error[0];
            }
            return $html;
        }

        // Filtering

        try
        {
            $killid = $CrestParser->parse(true);
        }
        catch(Exception $e) {
            if($e->getCode() == -4 && $page->isAdmin()) {
                $html .= '<br />
                    <form id="postform" name="postform" class="f_killmail" method="post" action="'.KB_HOST.'/?a=post_crest">
                            <input type="hidden" name="crest_url" id="crest_url" value = "'.htmlentities($_POST['crest_url']).'"/>
                            <input type="hidden" name="kll_id" id="kill_id" value = "'.$CrestParser->getDupeID().'"/>
                            <input type="hidden" name="undelete" id="undelete" value = "1"/>
                    <input id="submit" name="submit" type="submit" value="Undelete" />
                    </form>';
            }

            else {
                $html .= $e->getMessage();
                return $html;
            }
        }

        logger::logKill($killid);
        header("Location: ".html_entity_decode(edkURI::page('kill_detail',
                                        $killid, 'kll_id')));
        exit();
    } 

    else {
        $html = "Invalid password.";
    }
    return $html;
}