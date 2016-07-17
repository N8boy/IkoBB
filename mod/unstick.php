<?php

define('BASEPATH', 'Staff');
require_once('../applications/wrapper.php');

if (!$IKO->perm->check('access_moderation')) {
    redirect(SITE_URL);
}//Checks if user has permission to create a thread.
$IKO->tpl->getTpl('page');

$content = '';

if ($PGET->g('thread')) {

    $MYSQL->bind('id', $PGET->g('thread'));
    $query = $MYSQL->query("SELECT * FROM {prefix}forum_posts WHERE id = :id");

    if (!empty($query)) {

        if ($query['0']['post_sticky'] == "1") {
            $MYSQL->bind('id', $PGET->g('thread'));

            if ($MYSQL->query("UPDATE {prefix}forum_posts SET post_sticky = 0 WHERE id = :id") > 0) {
                $content .= $IKO->tpl->entity(
                    'success_notice',
                    'content',
                    str_replace(
                        '%url%',
                        SITE_URL . '/thread.php/' . $query['0']['title_friendly'] . '.' . $query['0']['id'],
                        $LANG['mod']['stick']['unstick_success']
                    )
                );
            } else {
                $content .= $IKO->tpl->entity(
                    'danger_notice',
                    'content',
                    $LANG['mod']['stick']['unstick_error']
                );
            }

        } else {
            $content .= $IKO->tpl->entity(
                'danger_notice',
                'content',
                $LANG['mod']['stick']['already_unstuck']
            );
        }

    } else {
        redirect(SITE_URL);
    }

} else {
    redirect(SITE_URL);
}

$IKO->tpl->addParam(
    array(
        'page_title',
        'content'
    ),
    array(
        $LANG['mod']['stick']['unstick'],
        $content
    )
);

echo $IKO->tpl->output();

?>