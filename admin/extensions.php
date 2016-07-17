<?php

define('BASEPATH', 'Staff');
require_once('../applications/wrapper.php');

if (!$IKO->perm->check('access_administration')) {
    redirect(SITE_URL);
}//Checks if user has permission to create a thread.
//require_once('template/top.php');
echo $ADMIN->template('top');
$notice = '';

/*
 * Install Extension.
 */
if ($PGET->g('install')) {
    if (file_exists('../applications/extensions/' . $PGET->g('install') . '/')) {

        $ext = clean($PGET->g('install'));
        /*$MYSQL->where('extension_folder', $ext);
        $query = $MYSQL->get('{prefix}extensions');*/
        $MYSQL->bind('extension_folder', $ext);
        $query = $MYSQL->query('SELECT * FROM {prefix}extensions WHERE extension_folder = :extension_folder');

        if (empty($query)) {

            $loc = '../applications/extensions/' . $ext . '/';
            require_once('../applications/dependencies/extension_implementation.php');
            require_once($loc . 'setup.php');

            if (!class_exists('Extension_Setup')) {
                $notice .= $ADMIN->alert(
                    'Setup does not exist for that extension.',
                    'danger'
                );
            }
            $setup = new Extension_Setup();

            /*$data  = array(
              'extension_name' => $setup->extension_name,
              'extension_folder' => $ext
            );*/
            $MYSQL->bindMore(array(
                    'extension_name' => $setup->extension_name,
                    'extension_folder' => $ext
                )
            );

            try {
                //$MYSQL->insert('{prefix}extensions', $data);
                $MYSQL->query('INSERT INTO {prefix}extensions (extension_name, extension_folder) VALUES (:extension_name, :extension_folder)');
            } catch (mysqli_sql_exception $e) {
                $notice .= $ADMIN->alert(
                    'Error installing extension.',
                    'danger'
                );
            }

            if ($setup->install()) {
                $notice .= $ADMIN->alert(
                    'Extension successfully installed!',
                    'success'
                );
            } else {
                $notice .= $ADMIN->alert(
                    'Error installing extension.',
                    'danger'
                );
            }

        } else {
            $notice .= $ADMIN->alert(
                'Extension is already installed.',
                'danger'
            );
        }

    } else {
        $notice .= $ADMIN->alert(
            'Extension does not exist.',
            'danger'
        );
    }
}

/*
 * Uninstall Extension.
 */
if ($PGET->g('uninstall')) {
    /*$MYSQL->where('extension_folder', $PGET->g('uninstall'));
    $query = $MYSQL->get('{prefix}extensions');*/
    $MYSQL->bind('extension_folder', $PGET->g('uninstall'));
    $query = $MYSQL->query('SELECT * FROM {prefix}extensions WHERE extension_folder = :extension_folder');
    if (!empty($query)) {

        $ext = clean($PGET->g('uninstall'));
        $loc = '../applications/extensions/' . $ext . '/';
        require_once('../applications/dependencies/extension_implementation.php');
        require_once($loc . 'setup.php');

        if (!class_exists('Extension_Setup')) {
            $notice .= $ADMIN->alert(
                'Setup does not exist for that extension.',
                'danger'
            );
        }

        $setup = new Extension_Setup();

        //$MYSQL->where('extension_folder', $ext);
        $MYSQL->bind('extension_folder', $ext);
        try {
            //$MYSQL->delete('{prefix}extensions');
            $MYSQL->query('DELETE FROM {prefix}extensions WHERE extension_folder = :extension_folder');
        } catch (mysqli_sql_exception $e) {
            $notice .= $ADMIN->alert(
                'Error installing extension.',
                'danger'
            );
        }

        if ($setup->uninstall()) {
            $notice .= $ADMIN->alert(
                'Extension successfully uninstalled!',
                'success'
            );
        } else {
            $notice .= $ADMIN->alert(
                'Error uninstalling extension.',
                'danger'
            );
        }

    } else {
        $notice .= $ADMIN->alert(
            'Extension does not exist.',
            'danger'
        );
    }
}

$extensions = '';
foreach (glob('../applications/extensions/*', GLOB_ONLYDIR) as $dir) {
    $dir = str_replace('../applications/extensions/', '', $dir);

    /*$MYSQL->where('extension_folder', $dir);
    $query       = $MYSQL->get('{prefix}extensions');*/
    $MYSQL->bind('extension_folder', $dir);
    $query = $MYSQL->query('SELECT * FROM {prefix}extensions WHERE extension_folder = :extension_folder');

    $install = (empty($query)) ? '<a href="' . SITE_URL . '/admin/extensions.php/install/' . $dir . '">Install</a>' : '<a href="' . SITE_URL . '/admin/extensions.php/uninstall/' . $dir . '">Uninstall</a>';
    $inst_helper = (empty($query)) ? '' : ' class="success"';

    $extensions .= '<tr' . $inst_helper . '>
                        <td>
                          <strong>' . $dir . '</strong><br />
                        </td>
                        <td>
                          <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                              Options <span class="caret"></span>
                            </button>
                            <span class="dropdown-arrow dropdown-arrow-inverse"></span>
                            <ul class="dropdown-menu dropdown-inverse" role="menu">
                              <li>' . $install . '</li>
                            </ul>
                          </div>
                        </td>
                      </tr>';
}

echo $ADMIN->box(
    'Extensions',
    $notice .
    'You can manage the extensions that improves the functionality of the forum here.',
    '<table class="table table-hover">
         <thead>
           <tr>
              <th style="width:80%">Extension</th>
              <th style="width:20%">Controls</th>
            </tr>
         </thead>
         <tbody>
           ' . $extensions . '
        </tbody>
       </table>',
    '12'
);

//require_once('template/bot.php');
echo $ADMIN->template('bot');
?>
