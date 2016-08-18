<?php

session_start();

ob_start();

header('Content-Type: text/html;charset=utf8');

if (!defined('BASEPATH')) {
    die();
}

if (BASEPATH == "Staff") {
    define('PATH', '../');
    define('PATH_A', '../applications/');
    define('TEMPLATE', '../');
    define('APPLICATION', '');
} elseif (BASEPATH == "Extension") {
    define('PATH', '../../../');
    define('PATH_A', '../../../applications/');
    define('TEMPLATE', '../../../');
    define('APPLICATION', '../../../applications/');
} elseif (BASEPATH == "Install") {
    define('PATH', '../../');
    define('PATH_A', '../../applications/');
    define('TEMPLATE', '../../');
    define('APPLICATION', '../../applications/');
} else {
    define('PATH', '');
    define('PATH_A', 'applications/');
    define('TEMPLATE', '');
    define('APPLICATION', 'applications/');
}

require_once(PATH . 'applications/config.php');

//Directional paths.
define('LIB', 'libraries/');
define('CLA', 'classes/');

//PDO Library
require_once(PATH_A . LIB . 'pdo.php');
$MYSQL = new db();

/* Databse CRUDs */
require_once(PATH_A . 'dependencies/easyCRUD.class.php');
require_once(PATH_A . 'dependencies/db_crud/generic.php');


$USERLINKS = array();

if (!defined('Install')) {


    //PermGET Library
    require_once(PATH_A . LIB . 'permget.php');
    $PGET = new Library_PermGET();

    // password_compat library for legacy PHP versions before PHP 5.5
    if (!function_exists('password_hash')) {
        require_once(PATH_A . LIB . 'password.php');
    }

    require_once(PATH_A . 'functions.php');
    require_once(PATH_A . 'pagination.php');

    //Using the language package.
    if (!defined('Install')) {
        $generic = new Crud_Generic();
        $generic->Find(1);
        switch (BASEPATH) {
            case "Staff":
                $package = '../applications/languages/' . $generic->site_language . '.php';
                $default = '../applications/languages/english.php';
                break;
            case "Extension";
                $package = '../../../applications/languages/' . $generic->site_language . '.php';
                $default = '../../../applications/languages/english.php';
                break;
            default:
                $package = 'applications/languages/' . $generic->site_language . '.php';
                $default = 'applications/languages/english.php';
                break;
        }
        if (file_exists($package)) {
            require_once($package);
        } else {
            require_once($default);
        }
    }

    //Smiles for the posts
    require_once(PATH_A . 'smilies/emoteicons.php');
    require_once(PATH_A . 'smilies/synonymes.php');

    //Classes to run IkoBB
    require_once(PATH_A . CLA . 'core.php');
    $IKO = new Iko_Core();

    //Captcha
    require_once(PATH_A . LIB . 'captcha.php');
    $IKO->captcha = new Iko_Captcha();

    require_once(PATH_A . CLA . 'user.php');
    $IKO->user = new Iko_User();

    require_once(PATH_A . CLA . 'session.php');
    $IKO->sess = new Iko_Session();

    require_once(PATH_A . CLA . 'template.php');
    $IKO->tpl = new Iko_Template();
    $IKO->tpl->setTheme($IKO->data['site_theme']);

    require_once(PATH_A . CLA . 'forum.php');
    $IKO->bb = new Iko_Forum();

    require_once(PATH_A . CLA . 'node.php');
    $IKO->node = new Iko_Node();

    //Permissions Library
    require_once(PATH_A . LIB . 'permissions.php');
    $IKO->perm = new Library_Permissions();

    require_once(PATH_A . LIB . 'parse.php');
    $IKO->lib_parse = new Library_Parse();

    //Mail Library
    require_once(PATH_A . LIB . 'mail.php');
    $MAIL = new Library_Mail();

    require_once(PATH_A . LIB . 'nocsrf.php');

    //Terminal Library
    require_once(PATH_A . LIB . 'terminal.php');
    $TERMINAL = new Library_Terminal();

    //Form Builder Library
    require_once(PATH_A . LIB . 'form.php');
    $FORM = new Library_FormBuilder();

    //Form Validator
    require_once(PATH_A . 'dependencies/error_handler.php');
    require_once(PATH_A . CLA . 'validator.php');

    //Timezone
    require_once(PATH_A . 'timezone.php');

    //Admin Class
    if ($IKO->perm->check('access_administration')) {
        require_once(PATH_A . CLA . 'admin.php');
        $ADMIN = new Iko_Admin();
    }

    $FB_USER = false;
    if ($IKO->data['facebook_authenticate'] == "1") {
        require_once('facebook.php');
    } else {
        if ($IKO->sess->isLogged) {
            $IKO->user->addUserLink(array(
                'Log Out' => SITE_URL . '/members.php/cmd/logout'
            ));
        }
    }

}

if (!defined('Install')) {
    //Including installed extensions.
    include_extensions();
    //Check if authenticated via Facebook
    if ($IKO->data['facebook_authenticate'] == "1") {
        if (isset($_GET['code']) && isset($_GET['state'])) {
            redirect(SITE_URL . '/forum.php');
        }
    }
}

?>
