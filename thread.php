<?php

define('BASEPATH', 'Forum');
require_once('applications/wrapper.php');

$IKO->tpl->getTpl('page');

if ($PGET->s(true)) {
    $get = $PGET->s(true);

    //Node
    $node_id = $get['id'];
    $node_name = $get['value'];
    $MYSQL->bindMore(
        array(
            'id' => $node_id,
            'title_friendly' => $node_name
        )
    );
    $query = $MYSQL->query("SELECT * FROM {prefix}forum_posts WHERE id = :id and title_friendly = :title_friendly AND post_type = 1");
    if (!empty($query)) {

        $MYSQL->bind('id', $query['0']['origin_node']);
        $p_query = $MYSQL->query("SELECT * FROM {prefix}forum_node WHERE id = :id");
        $allowed = explode(',', $p_query['0']['allowed_usergroups']);
        if (!in_array($IKO->sess->data['user_group'], $allowed)) {
            redirect(SITE_URL . '/404.php');
        }

        $user = $IKO->user($query['0']['post_user']);
        $node = node($query['0']['origin_node']);
        $time_post = simplify_time($query['0']['post_time'], @$IKO->sess->data['location']);
        $user_joined = simplify_time($user['date_joined'], @$IKO->sess->data['location']);

        // Poll
        $MYSQL->bind('thread_id', $node_id);
        $qry_poll = $MYSQL->query("SELECT * FROM {prefix}poll WHERE thread_id = :thread_id LIMIT 1");
        $poll_question = $qry_poll['0']['question'];
        $poll_id = $qry_poll['0']['id'];
        $MYSQL->bind('poll_id', $poll_id);
        $user_voted = $MYSQL->query("SELECT user_id FROM {prefix}poll_votes WHERE poll_id = :poll_id");
        $users      = array();
        foreach ($user_voted as $u) {
            $users[] = $u['user_id'];
        }
        if (isset($_POST['submit_form']) && isset($_POST['vote']) && !in_array($IKO->sess->data['id'], $users)) {
            $MYSQL->bind('user_id', $IKO->sess->data['id']);
            $MYSQL->bind('answer_id', $_POST['vote']);
            $MYSQL->bind('poll_id', $poll_id);
            $MYSQL->query("INSERT INTO {prefix}poll_votes (user_id, answer_id, poll_id) VALUES (:user_id, :answer_id, :poll_id)");
        }
        $answers = array();
        $poll_list = '';

        $MYSQL->bind('poll_id', $poll_id);
        $qry_answers = $MYSQL->query("SELECT * FROM {prefix}poll_answers WHERE poll_id = :poll_id");
        foreach ($qry_answers as $answer) {
            $answers[$answer['id']] = $answer['answer'];
            $MYSQL->bind('answer_id', $answer['id']);
            $qry_quan = $MYSQL->query("SELECT COUNT(answer_id) AS quan FROM {prefix}poll_votes WHERE answer_id = :answer_id");
            $number[$answer['id']] = $qry_quan['0']['quan'];
        }

        if ($IKO->sess->isLogged && !in_array($IKO->sess->data['id'], $users)) {
            foreach ($answers as $id => $answer) {
                $form_answers .= '<input type="radio" name="vote" value="' . $id . '" id="a_' . $id . '" /> <label style="font-weight: normal;" for="a_' . $id . '">' . $answer . '</label><br />';
            }
            $poll_list = $IKO->tpl->entity(
                'poll_form',
                array(
                    'answers'
                ),
                array(
                    $form_answers
                )
            );
        } else {
            $MYSQL->bind('poll_id', $poll_id);
            $total_qnt = $MYSQL->query("SELECT COUNT(poll_id) AS qnt FROM {prefix}poll_votes WHERE poll_id = :poll_id");
            $total_qnt = $total_qnt['0']['qnt'];
            foreach ($answers as $id => $answer) {

                $percentage = $number[$id] / $total_qnt * 100;
                $poll_list .= $IKO->tpl->entity(
                    'poll_list',
                    array(
                        'answer',
                        'now',
                        'total',
                        'percentage'
                    ),
                    array(
                        $answer,
                        $number[$id],
                        $total_qnt,
                        $percentage
                    )
                );

            }
        }
        $poll = '';
        if (!empty($poll_list) && !empty($poll_question)) {
            $poll = $IKO->tpl->entity(
                'poll_overview',
                array(
                    'question',
                    'poll_list'
                ),
                array(
                    $poll_question,
                    $poll_list
                )
            );
        }


        // Breadcrumbs
        $IKO->tpl->addBreadcrumb(
            $LANG['bb']['forum'],
            SITE_URL . '/forum.php'
        );
        if ($node['node_type'] == 2) {

            $parent_node = node($node['parent_node']);
            $ori_cat = category($parent_node['in_category']);

            $IKO->tpl->addBreadcrumb(
                $ori_cat['category_title'],
                '#'
            );

            $IKO->tpl->addBreadcrumb(
                $parent_node['node_name'],
                SITE_URL . '/node.php/' . $parent_node['name_friendly'] . '.' . $parent_node['id']
            );

            $IKO->tpl->addBreadcrumb(
                $node['node_name'],
                SITE_URL . '/node.php/' . $node['name_friendly'] . '.' . $node['id']
            );

        } elseif ($node['node_type'] == 1) {
            $ori_cat = category($node['in_category']);

            $IKO->tpl->addBreadcrumb(
                $ori_cat['category_title'],
                '#'
            );

            $IKO->tpl->addBreadcrumb(
                $node['node_name'],
                SITE_URL . '/node.php/' . $node['name_friendly'] . '.' . $node['id']
            );
        }

        $IKO->tpl->addBreadcrumb(
            $query['0']['post_title'],
            '#',
            true
        );
        $breadcrumb = $IKO->tpl->breadcrumbs();

        $IKO->node->thread_mark_read($node_id);

        $reply_button = '';
        $quote_thread = '';
        $edit_thread = '';
        $report_thread = '';
        if ($IKO->perm->check('reply_thread') && ($query['0']['post_locked'] == "0")) {
            $reply_button .= $IKO->tpl->entity(
                'reply_thread',
                'link',
                SITE_URL . '/reply.php/' . $node_name . '.' . $node_id,
                'buttons'
            );
            $report_thread .= $IKO->tpl->entity(
                'report_post',
                'url',
                SITE_URL . '/report.php/post/' . $node_id,
                'buttons'
            );
            $quote_thread .= $IKO->tpl->entity(
                'quote_post',
                'url',
                'javascript:quote(\'' . $query['0']['id'] . '\');',
                'buttons'
            );
            if ($query['0']['post_user'] == $IKO->sess->data['id']) {
                $edit_thread .= $IKO->tpl->entity(
                    'edit_post',
                    'url',
                    SITE_URL . '/edit.php/post/' . $node_id,
                    'buttons'
                );
            }
        }

        $thread_mod_tools = '';
        if ($IKO->perm->check('access_moderation')) {
            $stick_thread = ($query['0']['post_sticky'] == "0") ? 'Stick Thread' : 'Unstick Thread';
            $stick_thread_url = ($query['0']['post_sticky'] == "0") ? SITE_URL . '/mod/stick.php/thread/' . $query['0']['id'] : SITE_URL . '/mod/unstick.php/thread/' . $query['0']['id'];
            $close_thread = ($query['0']['post_locked'] == "0") ? 'Close Thread' : 'Open Thread';
            $close_thread_url = ($query['0']['post_locked'] == "0") ? SITE_URL . '/mod/close.php/thread/' . $query['0']['id'] : SITE_URL . '/mod/open.php/thread/' . $query['0']['id'];

            $move_thread = '<form action="' . SITE_URL . '/mod/move.php/thread/' . $query['0']['id'] . '"  method="POST"><select name="move_to" onchange="this.form.submit()">';
            $move_thread .= '<option value="' . $query['0']['origin_node'] . '">--' . $LANG['mod']['move']['move'] . '--</option>';
            foreach (list_forums() as $list_f) {
                if ($list_f['id'] == $query['0']['id']) {
                    $name         = ( $list_f['parent_node'] == 0 )? $list_f['name'] : '> ' . $list_f['name'];
                    $move_thread .= '<option value="' . $list_f['id'] . '" SELECTED>' . $name . '</option>';
                } else {
                    $name         = ( $list_f['parent_node'] == 0 )? $list_f['name'] : '> ' . $list_f['name'];
                    $move_thread .= '<option value="' . $list_f['id'] . '">' . $name . '</option>';
                }
            }
            $move_thread .= '</select></form>';

            $thread_mod_tools .= $IKO->tpl->entity(
                'mod_tools',
                array(
                    'stick_thread',
                    'stick_thread_url',
                    'close_thread',
                    'close_thread_url',
                    'edit_post_url',
                    'delete_post_url',
                    'move_thread_form'
                ),
                array(
                    $stick_thread,
                    $stick_thread_url,
                    $close_thread,
                    $close_thread_url,
                    SITE_URL . '/edit.php/post/' . $query['0']['id'],
                    SITE_URL . '/mod/delete.php/post/' . $query['0']['id'],
                    $move_thread
                ),
                'buttons'
            );
        }

        /*
         * Watch Thread
         */
        $watchers = explode(',', $query['0']['watchers']);
        //Watch process.
        $thread_notice = '';
        if ($PGET->g('watch')) {
            switch ($PGET->g('watch')) {
                case "1":
                    if (!in_array($IKO->sess->data['id'], $watchers)) {
                        $watcher = $watchers;
                        $watcher[] = $IKO->sess->data['id'];
                        $watcher = implode(',', $watcher);

                        $MYSQL->bind('watchers', $watcher);
                        $MYSQL->bind('id', $node_id);
                        if ($MYSQL->query('UPDATE {prefix}forum_posts SET watchers = :watchers WHERE id = :id')) {
                            $thread_notice = $IKO->tpl->entity(
                                'success_notice',
                                array(
                                    'content'
                                ),
                                array(
                                    $LANG['bb']['watch_thread']
                                )
                            );
                        } else {
                            $thread_notice = $IKO->tpl->entity(
                                'danger_notice',
                                array(
                                    'content'
                                ),
                                array(
                                    $LANG['bb']['error_watching']
                                )
                            );
                        }
                    } else {
                        $thread_notice = $IKO->tpl->entity(
                            'danger_notice',
                            array(
                                'content'
                            ),
                            array(
                                $LANG['bb']['already_watched_thread']
                            )
                        );
                    }
                    break;
                case "2":
                    if (in_array($IKO->sess->data['id'], $watchers)) {
                        $watcher = array_diff($watchers, array($IKO->sess->data['id']));
                        $watcher = implode(',', $watcher);
                        $MYSQL->bind('watchers', $watcher);
                        $MYSQL->bind('id', $node_id);
                        if ($MYSQL->query('UPDATE {prefix}forum_posts SET watchers = :watchers WHERE id = :id')) {
                            $thread_notice = $IKO->tpl->entity(
                                'success_notice',
                                array(
                                    'content'
                                ),
                                array(
                                    $LANG['bb']['unwatch_thread']
                                )
                            );
                        } else {
                            $thread_notice = $IKO->tpl->entity(
                                'danger_notice',
                                array(
                                    'content'
                                ),
                                array(
                                    $LANG['bb']['error_unwatching']
                                )
                            );
                        }
                    } else {
                        $thread_notice = $IKO->tpl->entity(
                            'danger_notice',
                            array(
                                'content'
                            ),
                            array(
                                $LANG['bb']['already_unwatched_thread']
                            )
                        );
                    }
                    break;
            }
        }

        //Watch link.
        if ($IKO->sess->isLogged) {
            $page = ($PGET->g('page')) ? '/page/' . clean($PGET->g('page')) : '';
            if (in_array($IKO->sess->data['id'], $watchers)) {
                $watch_link = $IKO->tpl->entity(
                    'unwatch_thread',
                    array(
                        'url'
                    ),
                    array(
                        SITE_URL . '/thread.php/' . $node_name . '.' . $node_id . $page . '/watch/2'
                    ),
                    'buttons'
                );
            } else {
                $watch_link = $IKO->tpl->entity(
                    'watch_thread',
                    array(
                        'url'
                    ),
                    array(
                        SITE_URL . '/thread.php/' . $node_name . '.' . $node_id . $page . '/watch/1'
                    ),
                    'buttons'
                );
            }
        } else {
            $watch_link = '';
        }

        if (!$PGET->g('page') or $PGET->g('page') == 1) {
            $starter = $IKO->tpl->entity(
                'thread_starter',
                array(
                    'breadcrumbs',
                    'reply_button',
                    'quote_post',
                    'edit_post',
                    'report_post',
                    'user_avatar',
                    'profile_url',
                    'username',
                    'date_joined',
                    'postcount',
                    'poll',
                    'thread_content',
                    'user_signature',
                    'post_time',
                    'mod_tools',
                    'watch_link',
                    'thread_notice',
                    'id',
                    'user_id',
                    'flag'
                ),
                array(
                    $breadcrumb,
                    $reply_button,
                    $quote_thread,
                    $edit_thread,
                    $report_thread,
                    $user['user_avatar'],
                    SITE_URL . '/members.php/cmd/user/id/' . $user['id'],
                    $user['username_style'],
                    $user_joined['time'],
                    $user['post_count'],
                    $poll,
                    $IKO->lib_parse->parse($query['0']['post_content']),
                    $IKO->lib_parse->parse($user['user_signature']),
                    $time_post['time'],
                    $thread_mod_tools,
                    $watch_link,
                    $thread_notice,
                    $query['0']['id'],
                    $user['id'],
                    '<span class="flag-icon flag-icon-' . strtolower($user['location']) . '"></span>'
                )
            );
        } else {
            $starter = $IKO->tpl->entity(
                'thread_top',
                array(
                    'breadcrumbs',
                    'reply_button',
                    'watch_link',
                    'thread_notice'
                ),
                array(
                    $breadcrumb,
                    $reply_button,
                    $watch_link,
                    $thread_notice
                )
            );
        }

        $content = $starter . '';

        $page = ($PGET->g('page')) ? clean($PGET->g('page')) : '1';
        foreach (getPosts($node_id, $page) as $post) {
            $ur = $IKO->user($post['post_user']);
            $quote_p = '';
            $edit_p = '';
            $report_p = '';
            $time_reply = simplify_time($post['post_time'], @$IKO->sess->data['location']);
            $user_joined = simplify_time($ur['date_joined'], @$IKO->sess->data['location']);
            if ($IKO->perm->check('reply_thread') && ($query['0']['post_locked'] == "0")) {
                $quote_p .= $IKO->tpl->entity(
                    'quote_post',
                    'url',
                    'javascript:quote(\'' . $post['id'] . '\');',
                    'buttons'
                );
                $report_p .= $IKO->tpl->entity(
                    'report_post',
                    'url',
                    SITE_URL . '/report.php/post/' . $post['id'],
                    'buttons'
                );
                if ($post['post_user'] == $IKO->sess->data['id']) {
                    $edit_p .= $IKO->tpl->entity(
                        'edit_post',
                        'url',
                        SITE_URL . '/edit.php/post/' . $post['id'],
                        'buttons'
                    );
                }
            }
            $post_mod_tools = '';
            if ($IKO->perm->check('access_moderation')) {
                $post_mod_tools = $IKO->tpl->entity(
                    'mod_tools_posts',
                    array(
                        'edit_post_url',
                        'delete_post_url'
                    ),
                    array(
                        SITE_URL . '/edit.php/post/' . $post['id'],
                        SITE_URL . '/mod/delete.php/post/' . $post['id']
                    ),
                    'buttons'
                );
            }

            $content .= $IKO->tpl->entity(
                'thread_reply',
                array(
                    'post_id',
                    'quote_post',
                    'edit_post',
                    'report_post',
                    'user_avatar',
                    'profile_url',
                    'username',
                    'date_joined',
                    'postcount',
                    'reply_content',
                    'user_signature',
                    'post_time',
                    'mod_tools',
                    'id',
                    'user_id',
                    'flag'
                ),
                array(
                    'post-' . $post['id'],
                    $quote_p,
                    $edit_p,
                    $report_p,
                    $ur['user_avatar'],
                    SITE_URL . '/members.php/cmd/user/id/' . $ur['id'],
                    $ur['username_style'],
                    $user_joined['time'],
                    $ur['post_count'],
                    $IKO->lib_parse->parse($post['post_content']),
                    $IKO->lib_parse->parse($ur['user_signature']),
                    $time_reply['time'],
                    $post_mod_tools,
                    $post['id'],
                    $ur['id'],
                    '<span class="flag-icon flag-icon-' . strtolower($ur['location']) . '"></span>'
                )
            );
        }

        $total_pages = ceil(fetchTotalPost($node_id) / POST_RESULTS_PER_PAGE);
        if ($page != 1 && $total_pages > 1) {
            $IKO->tpl->addPagination(
                '<<',
                SITE_URL . '/thread.php/' . $node_name . '.' . $node_id . '/page/' . intval($page - 1)
            );
        }
        if ($total_pages > 1) {
            $i = '';
            for ($i = 1; $i <= $total_pages; ++$i) {
                if ($i <= 2 || ($i == ($page - 1) && $page > 1) || $i == $page || $i == ($page + 1) || $i >= ($total_pages - 1)) {
                    if ($i == $page) {
                        $IKO->tpl->addPagination(
                            $i,
                            '#',
                            true
                        );
                    } else {
                        $IKO->tpl->addPagination(
                            $i,
                            SITE_URL . '/thread.php/' . $node_name . '.' . $node_id . '/page/' . $i
                        );
                    }
                } elseif (($i == 3 && $page != 1) || ($i == ($total_pages - 2) && $page != $total_pages)) {
                    $IKO->tpl->addPagination(
                        '...',
                        '#'
                    );
                }
            }
        }
        if ($page != $total_pages && $total_pages > 1) {
            $IKO->tpl->addPagination(
                '>>',
                SITE_URL . '/thread.php/' . $node_name . '.' . $node_id . '/page/' . intval($page + 1)
            );
        }
        define('CSRF_TOKEN', NoCSRF::generate('csrf_token'));
        define('CSRF_INPUT', '<input type="hidden" name="csrf_token" value="' . CSRF_TOKEN . '">');
        //Reply textarea.
        if ($IKO->sess->isLogged && $IKO->perm->check('reply_thread') && ($query['0']['post_locked'] == "0")) {
            $content .= $IKO->tpl->entity(
                'reply_thread',
                array(
                    'form_thread',
                    'form_id',
                    'csrf_input',
                    'textarea_name',
                    'reply_form_action',
                    'editor_id',
                    'submit_name'
                ),
                array(
                    'tango_form',
                    'tango_form',
                    CSRF_INPUT,
                    'content',
                    SITE_URL . '/reply.php/' . $node_name . '.' . $node_id,
                    'editor',
                    'reply'
                )
            );

            foreach ($ICONS as $category => $icons_cat) {
                $icon_package[$category] = '';
                foreach ($icons_cat as $code => $html) {
                    $icon_package[$category] .= '<a href="javascript:add_emoji(\'' . $code . '\');"><span style="font-size: 30px;" title="' . $code . '">' . $html . '</span></a> ';
                }
            }
            $content .= $IKO->tpl->entity(
                'smiley_list',
                array(
                    'smilies',
                    'misc',
                    'food',
                    'animals',
                    'plants'
                ),
                array(
                    $icon_package['smilies'],
                    $icon_package['misc'],
                    $icon_package['food'],
                    $icon_package['animals'],
                    $icon_package['plants']
                )
            );
        }
        $content .= $IKO->tpl->pagination();

        $IKO->tpl->addParam(
            array(
                'page_title',
                'content',
                'description'
            ),
            array(
                $query['0']['post_title'],
                $content,
                htmlentities(substr($query['0']['post_content'], 0, 200), ENT_QUOTES) . '...'
            )
        );
    } else {
        redirect(SITE_URL . '/404.php');
    }

} else {
    redirect(SITE_URL);
}

echo $IKO->tpl->output();

?>
