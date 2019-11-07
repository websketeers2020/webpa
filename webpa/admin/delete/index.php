<?php
/**
 * This area provide the delete location for the users held in the database
 *
 * @copyright Loughborough University
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL version 3
 *
 * @link https://github.com/webpa/webpa
 */

 //get the include file required
 require_once("../../includes/inc_global.php");
 require_once('../../includes/classes/class_assessment.php');

 if (!check_user($_user, APP__USER_TYPE_TUTOR)){
  header('Location:'. APP__WWW .'/logout.php?msg=denied');
  exit;
}

//get the passed user ID passed as u
$user = fetch_GET('u');
//get the passed module ID passed as m
$module = fetch_GET('m');

//set the page information
if (!$user) {
  $UI->page_title = APP__NAME . ' Delete module';
} else if ($user == 'unassigned') {
  $UI->page_title = APP__NAME . ' Delete all system users with no module';
}else {
  $UI->page_title = APP__NAME . ' Delete system user from module';
}
$UI->menu_selected = 'view data';
$UI->set_page_bar_button('View Student Data', '../../../images/buttons/button_student_user.png', '../review/student/index.php');
$UI->set_page_bar_button('View Staff Data', '../../../images/buttons/button_staff_user.png', '../review/staff/index.php');
if (check_user($_user, APP__USER_TYPE_ADMIN)) {
  $UI->set_page_bar_button('View Admin Data', '../../../images/buttons/button_admin_user.png', '../review/admin/index.php');
  $UI->set_page_bar_button('View Module Data', '../../../images/buttons/button_view_modules.png', '../review/module/index.php');
}
$UI->set_page_bar_button('Search for a user', '../../../images/buttons/button_search_user.png', '../search/index.php');
$UI->breadcrumbs = array ('home' => '../','review data'=>'../review/','edit'=>null, );
$UI->help_link = '?q=node/237';
$UI->head();
$UI->body();
$UI->content_start();

//build the content to be written to the screen

$page_intro = '';

//----------------------------------------------------------------------
//process request

  if (!$user) {
    if (intval($module) == $_module_id) {
      $sScreenMsg = "<p>You cannot delete the currently selected module!</p>";
    } else {
      $sScreenMsg = "<p>The module has been deleted.</p>";
      $delete_module = new Module();
      $delete_module->module_id = $module;
      $delete_module->set_dao_object($DB);
      $delete_module->delete();
    }
  } else if ($user == 'unassigned') {
    //increase the execution time to handle large numbers of deletions
    ini_set('max_execution_time', 120);
    $sScreenMsg = "<p>Users with no module have been deleted.</p>";
    $users = $DB->fetch_col("SELECT u.user_id FROM " . APP__DB_TABLE_PREFIX . "user u LEFT OUTER JOIN " . APP__DB_TABLE_PREFIX . "user_module um ON u.user_id = um.user_id WHERE (um.user_id IS NULL) AND (u.user_type = '" . APP__USER_TYPE_STUDENT . "')");
    $delete_user = new User();
    $delete_user->set_dao_object($DB);
    for ($i=0; $i<count($users); $i++) {
      $delete_user->id = $users[$i];
      $delete_user->delete();
    }
  } else if (intval($user) == $_SESSION['_user_id']) {
    $sScreenMsg = "<p>You cannot delete yourself!</p>";
  } else {
    $user_row = $CIS->get_user($user);
    $delete_user = new User();
    $delete_user->set_dao_object($DB);
    $delete_user->load_from_row($user_row);
    if (check_user($_user, APP__USER_TYPE_ADMIN) && $delete_user->is_admin()) {
      $sScreenMsg = "<p>The administrator has been deleted.</p>";
      $delete_user->delete();
    } else {
      $sScreenMsg = "<p>The user has been deleted from the module.</p>";
      $sql = 'DELETE FROM ' . APP__DB_TABLE_PREFIX . "user_module WHERE user_id = {$user} AND module_id = {$_SESSION['_module_id']}";
      $DB->execute($sql);
    }
  }


//-----------------------------------------------------------------------
//build the page and fill in the spaces

?>
<?php echo $page_intro; ?>


<div class="content_box">

<?php

  if(!empty($sScreenMsg)){
    echo "<div class=\"success_box\">{$sScreenMsg}</div>";
  }

?>

</div>
<?php

$UI->content_end();

?>
