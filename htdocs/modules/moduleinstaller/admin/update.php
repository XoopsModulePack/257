<?php
/**
 * See the enclosed file license.txt for licensing information.
 * If you did not receive this file, get it at http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package     installer
 * @since       2.3.0
 * @author      Haruki Setoyama  <haruki@planewave.org>
 * @author      Kazumi Ono <webmaster@myweb.ne.jp>
 * @author      Skalpa Keo <skalpa@xoops.org>
 * @author      Taiwen Jiang <phppp@users.sourceforge.net>
 * @author      DuGris (aka L. JEN) <dugris@frxoops.org>
 * @version     $Id: uninstall.php 8107 2011-11-06 13:26:25Z beckmi $
**/
include_once __DIR__ . '/admin_header.php';
xoops_cp_header();
$xoopsOption['checkadmin'] = true;
$xoopsOption['hascommon'] = true;
require_once './../include/common.inc.php';
require_once XOOPS_ROOT_PATH . "/modules/system/admin/modulesadmin/modulesadmin.php";
defined('XOOPS_INSTALL') || die('XOOPS Installation wizard die');

if (!@include_once XOOPS_ROOT_PATH . "/language/{$wizard->language}/global.php") {
    include_once XOOPS_ROOT_PATH . '/language/english/global.php';
}
if (!@include_once XOOPS_ROOT_PATH . "/modules/system/language/{$wizard->language}/admin/modulesadmin.php") {
    include_once XOOPS_ROOT_PATH . '/modules/system/language/english/admin/modulesadmin.php';
}
require_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
require_once XOOPS_ROOT_PATH . '/class/xoopslists.php';

//$xoTheme->addStylesheet( XOOPS_URL . "/modules/" . $xoopsModule->getVar("dirname") . "/assets/css/style.css" );

$pageHasForm = true;
$pageHasHelp = false;

/**
 * @param $dirname
 */

/*
function xoops_module_update($dirname) {

//http://localhost/257final/modules/system/admin.php?fct=modulesadmin&op=update&module=mypoints

$url = XOOPS_URL . "/modules/system/admin.php?fct=modulesadmin&op=update&module=".$dirname;
//$ch = curl_init();
//curl_setopt($ch, CURLOPT_URL,$url);
//curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
//curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//$result = curl_exec($ch);
//curl_close($ch);


$crl = curl_init();
curl_setopt($crl, CURLOPT_URL, $url);
@curl_setopt($crl, CURLOPT_HEADER, 0);
@curl_setopt($crl, CURLOPT_NOBODY, 1);
@curl_setopt($crl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
$res = curl_exec($crl);
curl_close($crl);
return $res;

}

*/

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include_once XOOPS_ROOT_PATH . '/class/xoopsblock.php';
    include_once XOOPS_ROOT_PATH . '/kernel/module.php';
    include_once XOOPS_ROOT_PATH . '/include/cp_functions.php';
    include_once XOOPS_ROOT_PATH . '/include/version.php';
//    include_once './../include/modulesadmin.php';

    $config_handler =& xoops_getHandler('config');
    $xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);

    $msgs = array();
    foreach ($_REQUEST['modules'] as $dirname => $updateModule) {
        if ($updateModule) {
            $msgs[] =& xoops_module_update($dirname);
        }
    }

    $pageHasForm = false;

    if (count($msgs) > 0) {
        $content = "<div class='x2-note successMsg'>" . UPDATED_MODULES . "</div><ul class='log'>";
        foreach ($msgs as $msg) {
            $content .= "<dt>{$msg}</dt>";
        }
        $content .= "</ul>";
    } else {
        $content = "<div class='x2-note confirmMsg'>" . NO_INSTALLED_MODULES . "</div>";
    }

    // Flush cache files for cpanel GUIs
    xoops_load("cpanel", "system");
    XoopsSystemCpanel::flush();

    //Set active modules in cache folder
    xoops_setActiveModules();
} else {
    if (!isset($GLOBALS['xoopsConfig']['language'])) {
        $GLOBALS['xoopsConfig']['language'] = $_COOKIE['xo_install_lang'];
    }

    // Get installed modules
    $module_handler =& xoops_getHandler('module');
    $installed_mods =& $module_handler->getObjects();
    $listed_mods = array();
    foreach ($installed_mods as $module) {
        $listed_mods[] = $module->getVar('dirname');
    }

    include_once XOOPS_ROOT_PATH . '/class/xoopslists.php';
    $dirlist = XoopsLists::getModulesList();
    $toinstal = 0;

    $javascript = "";
    $content = "<ul class='log'><li>";
    $content .= "<table class='module'>\n";
    //remove System module and itself from the list of modules that can be uninstalled
//    $dirlist = array_diff($dirlist, array('system', 'moduleinstaller'));
    foreach ($dirlist as $file) {
        clearstatcache();
        if (in_array($file, $listed_mods)) {
            $value = 0;
            $style = "";
            if (in_array($file, $wizard->configs['modules'])) {
                $value = 1;
                $style = " style='background-color:#E6EFC2;'";
            }

            $file = trim($file);
            $module =& $module_handler->create();
            if (!$module->loadInfo($file, false)) {
                continue;
            }

            $form = new XoopsThemeForm('', 'modules', 'index.php', 'post');
            $moduleYN = new XoopsFormRadioYN('', 'modules['. $module->getInfo('dirname') . ']', $value, _YES, _NO);
            $moduleYN->setExtra("onclick='selectModule(\"" . $file . "\", this)'");
            $form->addElement($moduleYN);

            $content .= "<tr id='" . $file . "'" . $style . ">\n";
            $content .= "    <td class='img' ><img src='" . XOOPS_URL . "/modules/" . $module->getInfo('dirname') . "/" . $module->getInfo('image') . "' alt='" . $module->getInfo('name') . "'/></td>\n";

            $moduleHandlerInDB =& xoops_getHandler('module');
            $moduleInDB         =& $module_handler->getByDirname($module->getInfo('dirname'));
            // Save current version for use in the update function
            $prevVersion = round($moduleInDB->getVar('version') / 100, 2);

            $content = round($module->getInfo('version'), 2) != $prevVersion ? $content . "    <td ><span style='color: #FF0000; font-weight: bold;'>" : $content . "    <td><span>";
            $content .= "        " . $module->getInfo('name') . "&nbsp;" . number_format(round($module->getInfo('version'), 2), 2) . "&nbsp;" . $module->getInfo('module_status') . "&nbsp;(folder: /" . $module->getInfo('dirname') . ")";
            $content .= "        <br />" .  $module->getInfo('description');
            $content .= "    </span></td>\n";
            $content .= "    <td class='yesno'>";
            $content .= $moduleYN->render() ;
            $content .= "    </td></tr>\n";
            ++$toinstal;
        }
    }
    $content .= "</table>";
    $content .= "</li></ul><script type='text/javascript'>" . $javascript . "</script>";
    if ($toinstal == 0) {
        $pageHasForm = false;
        $content = "<div class='x2-note confirmMsg'>" . NO_MODULES_FOUND . "</div>";
    }
}
$indexAdmin = new ModuleAdmin();
echo $indexAdmin->addNavigation('update.php');

$indexAdmin->addItemButton(
    _AM_INSTALLER_SELECT_ALL,
    "javascript:selectAll();",
    'button_ok'
);

$indexAdmin->addItemButton(
    _AM_INSTALLER_SELECT_NONE,
    "javascript:unselectAll();",
    'prune'
);

echo $indexAdmin->renderButton('left', '');

include_once dirname(__DIR__) . '/include/install_tpl.php';
include_once __DIR__ . '/admin_footer.php';
