<?php
/**
 * Class :  UI
 *
 * @copyright Loughborough University
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL version 3
 *
 * @link https://github.com/webpa/webpa
 */

//include main global file so that the session can be used
function rel7($struc, &$file) {
  return file_exists( ( $file = ( dirname($struc).'/'.$file ) ) );
}

function relativetome7($structure, $filetoget){
  return rel7($structure,$filetoget) ? require_once($filetoget) : null;
}

relativetome7(__FILE__, 'inc_global.php');

class UI {
  // Public Vars
  public $page_title = '';
  public $menu_selected = '';
  public $breadcrumbs = null;
  public $help_link = '';

  // Private Vars
  private $_user = null;
  private $_menu = null;
  private $_page_bar_buttons = null;

  /**
  * CONSTRUCTOR for the UI
  * @param string $_user
  */
  function __construct( $_user = null) {

    global $CIS, $INSTALLED_MODS, $_source_id;

    $this->_user =& $_user;

    $helper_link = APP__HELP_LINK;

    // Initialise the menu - sets either staff or student menu items
    if ( $this->_user ) {

      if ($this->_user->is_staff()) {
        // Staff menu
        $this->set_menu('<img src="../images/icons/tutors.svg" alt="Tutors logo" class="mainMenuIcons"> Tutors', array ('<img src="../images/icons/home.svg" alt="Home logo"> Home' => APP__WWW . '/tutors/index.php',
                         '<img src="../images/icons/form.svg" alt="Froms logo"> My Forms'     => APP__WWW . '/tutors/forms/',
                         '<img src="../images/icons/group.svg" alt="My Groups"> My Groups'    => APP__WWW . '/tutors/groups/',
                         '<img src="../images/icons/assessment.svg" alt="Assessments"> My Assessments' => APP__WWW . '/tutors/assessments/'));// /$this->set_menu()

      } else if ($this->_user->is_student()) {
        // Student menu
        $this->set_menu('Students', array ('<img src="../images/icons/home.svg" alt="Home"> Home'     => APP__WWW . '/students/index.php' ,
        '<img src="../images/icons/group.svg" alt="My Groups"> My Groups'    => APP__WWW . '/students/groups/' ,
                           '<img src="../images/icons/assesment.svg" alt="Assessments"> My Assessments' => APP__WWW . '/students/assessments/' ) );// /$this->set_menu()
      }

      //Admin menu
      if ($this->_user->is_staff()) {
        $menu = array('<img src="../images/icons/home.svg" alt="Admin Home logo"> Admin Home'   => APP__WWW .'/admin/index.php',
        '<img src="../images/icons/upload-data.svg" alt="Upload Data"> Upload Data'  =>  APP__WWW . '/admin/load/index.php',
                      '<img src="../images/icons/view-data.svg" alt="View Data"> View Data'    =>  APP__WWW . '/admin/review/index.php');
        if ($this->_user->is_admin()) {
          $menu['<img src="../images/icons/metrics.svg" alt="Metrics"> Metrics'] = APP__WWW . '/admin/metrics/index.php';
        }
        $this->set_menu('Admin', $menu);
      }

      // Check for module menu items
      foreach ($INSTALLED_MODS as $mod) {
        $mod = strtolower($mod);
        $menu_file = DOC__ROOT . "mod/$mod/menu.php";
        if (file_exists($menu_file)) {
          require_once($menu_file);
        }
      }

    }

    $this->set_menu('<img src="../images/icons/support.svg" alt="Support"> Support', array ('<img src="../images/icons/help.svg" alt="Help"> Help'    =>  $helper_link, //this is a link set in each page / area to link to the approriate help
    '<img src="../images/icons/phone.svg" alt="Contact"> Contact'   => APP__WWW . '/contact/') );// /$this->set_menu();

    if ( $this->_user ) {
      if ($_user->is_admin()) {
        $modules = $CIS->get_user_modules(NULL, NULL, 'name');
      } else {
        $modules = $CIS->get_user_modules($_user->id, NULL, 'name');
      }
      if ((($_source_id == '') || $this->_user->is_admin()) && (count($modules) > 1)) {
        $this->set_menu('  ', array ('change module'  => APP__WWW .'/module.php') );
      }
    }

    $menu = $this->get_menu(' ');
    if (count($menu) > 0) {
      unset($this->_menu[' ']);
    }
    if (!array_key_exists('logout', $menu) && !isset($_SESSION['logout_url'])) {
      $menu['Logout'] = APP__WWW . '/logout.php';
    }
    $this->set_menu(' ', $menu);// /$this->set_menu();

  }// /->UI()

  // --------------------------------------------------------------------------------
  // Public Methods

  /**
  * Send the expiry headers.
  * Leave $expiry_date empty to force the browser to page refresh
  * @param string $expire_date
  * @param string $modified_date
  */
  function headers_expire($expire_date = null, $modified_date = null) {
    // If no expiry date, expire at 00:00:01 today
    if (!$expire_date) { $expire_date = mktime(0,0,1,date('m'),date('d'),date('Y')); }

    // If no modified date, modified today
    if (!$modified_date) { $modified_date = time(); }

    header('Expires: '. gmdate('D, d M Y H:i:s', $expire_date ) .' GMT');
    header('Last-Modified: '. gmdate('D, d M Y H:i:s', $modified_date) .' GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');   // HTTP/1.1
    header('Cache-Control: post-check=0, pre-check=0', false);    // HTTP/1.1
    header("Cache-control: private", false);
    header('Pragma: no-cache');   // HTTP/1.0
  } // /-headers_expire()


  /**
   * Function to generate the header
  */
  function head () {

    global $_user, $BRANDING;
    /*
    Commented out until the day IE can show a full XHTML page without entering quirks mode
    echo('<?xml version="1.0" encoding="UTF-8"?>'."\n");
    */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="content-language" content="EN" />
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo(APP__NAME ) ?></title>
  <link href="<?php echo(APP__WWW) ?>/css/webpa.css" media="screen" rel="stylesheet" type="text/css" />
  <link href="<?php echo(APP__WWW) ?>/css/style.css" media="screen" rel="stylesheet" type="text/css" />
  <link href="<?php echo(APP__WWW) ?>/css/webpa_print.css" media="print" rel="stylesheet" type="text/css" />
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" media="print" rel="stylesheet" type="text/css" />
  <style type="text/css">
<?php
  if (!isset($_SESSION['_no_header'])) {
?>
    #app_bar {
      height: <?php echo $BRANDING['logo.margin'];?>px;
    }
    #app_bar #inst_logo {
      width: <?php echo $BRANDING['logo.width'];?>px;
    }
    #main {
      top: <?php echo $BRANDING['logo.height']; ?>px;
    }
<?php
  } else {
?>
  #side_bar {
    padding-top: 20px;
    top: 20px;
  }
  #content {
    padding-top: 10px;
  }
  #main {
    margin-top: 20px;
  }
<?php
  }
?>
  </style>
<?php
    if (isset($BRANDING['css']) && !empty($BRANDING['css'])) {
?>
  <link href="<?php echo $BRANDING['css']; ?>" rel="stylesheet" type="text/css" />
<?php
    }
  } // /->head()


  /**
   * function to close the body area of the page
   * @param string $extra_attributes
  */
  function body($extra_attributes = '') {
    echo("\n</head>\n<body $extra_attributes>\n\n");

  } // /->body()


  /**
  * render page header
  */
  function header() {

    global $_module, $BRANDING;

    ?>
  <div id="header">
<?php
    if (!isset($_SESSION['_no_header'])) {
?>
    <div id="app_bar">
    <a href="<?php echo APP__WWW; ?>/logout.php" class="test">Logout<a>
      <div id="title_logo"><a href=""><img src="<?php echo APP__WWW; ?>/images/hud-logo-desktop.png" alt="<?php echo APP__NAME; ?>" /></a></div>
<?php
      if (isset($_module)) {
        echo "{$_module['module_title']} [{$_module['module_code']}]";
      } else {
        echo('&nbsp;');
      }
      if (isset($BRANDING['logo']) && !empty($BRANDING['logo'])) {
        echo '<div id="inst_logo"><img src="' . $BRANDING['logo'] . '"';
        if (isset($BRANDING['name']) && !empty($BRANDING['name'])) {
          echo ' alt="' . htmlentities($BRANDING['name']) . '"';
          echo ' title="' . htmlentities($BRANDING['name']) . '"';
        }
        echo ' /></div>';
      } else {
        echo '&nbsp;';
      }
?>
    </div>
    <div id="module_bar">
<?php
      if ($this->_user) {
        echo("User: {$this->_user->forename} {$this->_user->lastname}");
      } else {
        echo('&nbsp;');
      }
?>
    </div>
<?php
    }
?>
    <div id="breadcrumb_bar">
      You are in:
<?php
    if (is_array($this->breadcrumbs)) {
      $num_crumbs = count($this->breadcrumbs);
      foreach( $this->breadcrumbs as $k => $v ) {
        --$num_crumbs;
        if (!is_null($v)) {
          echo("<a class=\"breadcrumb\" href=\"$v\">$k</a>");
          if ($num_crumbs>0) { echo(' &gt; '); }
        } else { echo($k); }
      }
    }
?>
    </div>
  </div>
<?php
  }// /->header()

  /**
  * Set the given section name to the given assoc-array of links
  * Does NO checking of $section_array
  * @param string $section_name
  * @param array $section_array
  */
  function set_menu($section_name, $section_array) {
    $this->_menu[$section_name] = $section_array;
  }

  function get_menu($section_name) {
    if (isset($this->_menu[$section_name])) {
      return $this->_menu[$section_name];
    } else {
      return array();
    }
  }

  /**
  * Draw the menu
  */
  function menu() {
    // If there's a menu, draw it
    if ($this->_menu) {
      $menu_html = '<div id="menu">';

      foreach($this->_menu as $menu_section => $menu_links) {
        $menu_html .= ($menu_section==' ') ? '<div class="menu_section"><ul class="menu_list">' : '<div class="menu_section"><div class="menu_title">'. $menu_section .'</div><ul class="menu_list">';

        foreach($menu_links as $menu_name => $menu_link ) {
          if ($menu_name == 'help'){
            $link_class = ($this->menu_selected == $menu_name) ? 'menu_selected' : 'menu';
            $menu_html .= '<li><a class="'. $link_class .'" href="'. $menu_link . $this->help_link . '" target="_blank">'. $menu_name .'</a></li>';
          }else{
            $link_class = ($this->menu_selected == $menu_name) ? 'menu_selected' : 'menu';
            $menu_html .= '<li><a class="'. $link_class .'" href="'. $menu_link .'">'. $menu_name .'</a></li>';
          }
        }// /for

        $menu_html .= '</ul></div>';
      }// /for

      $menu_html .= '</div>';
      echo($menu_html);
    }
  }// /->menu()

  /**
  * Set a page bar button
  * @param string $text
  * @param string $img
  * @param string  $link
  * @param string $side
  */
  function set_page_bar_button($text, $img, $link, $side = 'left') {
    $this->_page_bar_buttons[$side][$text] = array ('img' => "../images/buttons/$img", 'link' => $link);

  }// /->set_page_bar_button()

  /**
  * Draw the page toolbar
  */
  function page_bar() {
    if (is_array($this->_page_bar_buttons)) {
      ?>
      <div id="page_bar">
        <table cellpadding="0" cellspacing="0">
        <tr>
          <?php
            if (array_key_exists('left',$this->_page_bar_buttons)) {
              foreach($this->_page_bar_buttons['left'] as $text => $button) {
                echo("<td><a class=\"page_bar_link\" href=\"{$button['link']}\" title=\"$text\"><img src=\"{$button['img']}\" alt=\"$text\" height=\"50\" /></a></td>");
              }
            }
          ?>
          <td width="100%">&nbsp;</td>
          <?php
            // right-hand buttons are automatically set to target="_blank"
            if (array_key_exists('right',$this->_page_bar_buttons)) {
              foreach($this->_page_bar_buttons['right'] as $text => $button) {
                echo("<td><a class=\"page_bar_link\" href=\"{$button['link']}\" target=\"$text\" title=\"$text\"><img src=\"{$button['img']}\" alt=\"$text\" height=\"50\" /></a></td>");
              }
            }
          ?>
        </tr>
        </table>
      </div>
      <?php
    }
  }// /->page_bar()

  /**
  * Footer
  */
  function footer() {
    global $_user, $_source_id, $DB, $INSTALLED_MODS;
?>
  <div id="footer">
    <div style="margin-top: 50px">
      &copy; Loughborough University and University of Hull, 2005 -  <?php echo date('Y');?>&nbsp;&nbsp;&nbsp;
      <span style="font-size: small;">Version: <?php
      echo APP__VERSION;
      if (count($INSTALLED_MODS) > 0) echo ' [' . implode(",", $INSTALLED_MODS) . ']'; ?></span>
<?php
    if (isset($_user) && $_user->is_admin() && $_source_id) {
      echo "<br />\n";
      echo '      <span style="font-size: small;">Source:&nbsp;';
      echo ($_source_id) ? $_source_id : '&lt;' . APP__NAME . '&gt;';
      echo "</span>\n";
    }
?>
    </div>
    <iframe src="<?php echo APP__WWW; ?>/keep_alive.php" height="1" width="1" style="display: none;">keep alive</iframe>
  </div>
<?php
  }// /->footer()

  /**
  * Start main page content
  */
  function content_start() {
    echo('<div id="container">');
    echo('<div id="main">');
    $this->page_bar();
    echo('<div id="content">');
    if ($this->page_title) { echo("<h1>{$this->page_title}</h1>\n\n"); }
  }// /content_start()

  /**
  * End main page content
  * @param boolean $render_menu
  * @param boolean $render_header
  * @param boolean $renders_footer
  */
  function content_end($render_menu = true, $render_header = true, $render_footer = true) {
    global $BRANDING;
?>
  </div>
</div>

<div id="side_bar">
<?php
    if ($render_menu) {
      $this->menu();
?>
    <div class="alert_box" style="margin: 40px 8px 8px 8px; font-size: 0.7em;">
      <p><strong>Technical Problem?</strong></p>
      <p>If you have a problem, find a bug or discover a technical problem in the system, <a href="<?php echo APP__WWW ?>/contact/index.php?q=bug">contact us</a> to report it!</p>
    </div>
<?php
    } else {
?>
    <div class="alert_box" style="margin: 40px 8px 8px 8px; font-size: 0.7em;">
      <p><strong>Technical Problem?</strong></p>
      <p>If you have a problem, find a bug or discover a technical problem in the system, please <a href="mailto:<?php echo $BRANDING['email.help']; ?>" title="(email: <?php echo $BRANDING['email.help']; ?>)">email us</a> to report it!</p>
    </div>
<?php
    }
?>
</div>
<?php
    if ($render_header) {
      $this->header();
    }
    if ($render_footer) {
      $this->footer();
    }
?>
<div class="clear"></div>
</div> <!-- id="container" -->
</body>
</html>
<?php
  }// /content_end()

  /**
  * function to draw the boxed list
  * @param string $list
  * @param string $box_class
  * @param string $header_text
  * @param string $footer_text
  */
  function draw_boxed_list($list, $box_class, $header_text, $footer_text) {
    if (is_array($list)) {
      echo("<div class=\"$box_class\"><p style=\"font-weight: bold;\">$header_text</p><ul class=\"spaced\">");
      foreach($list as $item) { echo("<li>$item</li>"); }
      echo("</ul><p>$footer_text</p></div>");
    }
  }// ->draw_boxed_list()

  // --------------------------------------------------------------------------------
  // Private Methods

}// /class: UI

?>
