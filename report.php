<?php

define('BASEPATH', 'Forum');
require_once('applications/wrapper.php');

if (!$IKO->sess->isLogged) {
    redirect(SITE_URL);
}//Checks if user has permission to create a thread.
$IKO->tpl->getTpl('page');

if ($PGET->g('post')) {

    $post = clean($PGET->g('post'));
    $MYSQL->bind('id', $post);
    $query = $MYSQL->query('SELECT * FROM {prefix}forum_posts WHERE id = :id');

    if (!empty($query)) {

        $notice = '';
        $content = '';

        if (isset($_POST['report'])) {
            try {

                foreach ($_POST as $parent => $child) {
                    $_POST[$parent] = clean($child);
                }

                NoCSRF::check('csrf_token', $_POST);
                $reason = $_POST['reason'];

                if (!$reason) {
                    throw new Exception ($LANG['global_form_process']['all_fields_required']);
                } else {

                    $time = time();
                    $MYSQL->bindMore(
                        array(
                            'report_reason' => $reason,
                            'reported_by' => $IKO->sess->data['id'],
                            'reported_post' => $post,
                            'reported_time' => $time
                        )
                    );

                    if ($MYSQL->query("INSERT INTO {prefix}reports (report_reason, reported_by, reported_post, reported_time) VALUES (:report_reason, :reported_by, :reported_post, :reported_time)") > 0) {
                        $notice .= $IKO->tpl->entity(
                            'success_notice',
                            'content',
                            $LANG['global_form_process']['report_create_success']
                        );
                    } else {
                        throw new Exception ($LANG['global_form_process']['error_submitting_report']);
                    }

                }

            } catch (Exception $e) {
                $notice .= $IKO->tpl->entity(
                    'danger_notice',
                    'content',
                    $e->getMessage()
                );
            }
        }

        define('CSRF_TOKEN', NoCSRF::generate('csrf_token'));
        $content .= '<form action="" id="tango_form" method="POST">
                         ' . $FORM->build('hidden', '', 'csrf_token', array('value' => CSRF_TOKEN)) . '
                         ' . $FORM->build('textarea', $LANG['bb']['form']['report_reason'], 'reason', array('style' => 'height:150px;width:100%;min-width:100%;max-width:100%;')) . '
                         <br /><br />
                         ' . $FORM->build('submit', '', 'report', array('value' => $LANG['bb']['form']['report'])) . '
                       </form>';

        //Breadcrumbs
        $IKO->tpl->addBreadcrumb(
            $LANG['bb']['forum'],
            SITE_URL . '/forum.php'
        );
        $IKO->tpl->addBreadcrumb(
            $LANG['bb']['new_report'],
            '#',
            true
        );
        $bc = $IKO->tpl->breadcrumbs();

        $IKO->tpl->addParam(
            array(
                'page_title',
                'content'
            ),
            array(
                $LANG['bb']['new_report'],
                $bc . $notice . $content
            )
        );

    } else {
        redirect(SITE_URL);
    }

} elseif ($PGET->g('user')) {
    /* Feature coming soon. */
    redirect(SITE_URL);
} else {
    redirect(SITE_URL);
}

echo $IKO->tpl->output();

?>