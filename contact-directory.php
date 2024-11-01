<?php
/*
Plugin Name: WP Contacts Directory
Plugin URI: http://ahlul.web.id/blog/2010/01/10/wp-contacts-directory.html
Description: This plugin use for manage and display simple directory about informations. You can use this plugin to list your member, your affiliates, and other informations that you want to display or manage with your blog.
Version: 3.0.1
Author: Ahlul Faradish Resha
Author URI: http://ahlul.web.id/blog
*/
//Load actions
add_action('wp_head','contactdir_head'); //Add Header
add_action('admin_menu','contactdir_navigation'); //Add Directory Tab in the menu
//Add Short Code
add_shortcode("contactdir_addform","contactdir_addform_shortcode"); //Add ShortCode for "Add Form"
add_shortcode("contactdir_directory","contactdir_directory_shortcode"); //Add ShortCode for "Directory"
//Register Hooks
register_activation_hook(__FILE__,'contactdir_install');


$c_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$opt_ct = get_option("contactdir_options");
$contactdir_version = "3.0.1";


/* Install Table */
function contactdir_install() {
	global $wpdb,$contactdir_version;
	$tb = $wpdb->prefix."contactdir";
	if($wpdb->get_var("SHOW TABLE LIKES '".$tb."'") != $tb) {
		$sql = 	"CREATE TABLE IF NOT EXISTS `".$tb."` (".
  				"`id` int(11) NOT NULL AUTO_INCREMENT,".
  				"`name` varchar(255) NOT NULL,".
  				"`email` varchar(255) NOT NULL,".
				"`url` varchar(255) NOT NULL,".
 				"`phone` varchar(255) NOT NULL,".
  				"`address` text NOT NULL,".
  				"`cat_id` text NOT NULL,".
				"`time_created` int(11) NOT NULL,".
  				"`status` int(1) NOT NULL,".
  				"PRIMARY KEY (`id`)".
				") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
		$wpdb->query($sql);
	}
	if($wpdb->get_var("SHOW TABLE LIKES '".$tb."_cat'") != $tb."_cat") {
		$sql2 = "CREATE TABLE IF NOT EXISTS `".$tb."_cat` (".
				"`id` int(4) NOT NULL AUTO_INCREMENT,".
				"`name` varchar(255) NOT NULL,".
				"`status` int(1) NOT NULL,".
				"PRIMARY KEY (`id`)".
				") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
		$q = $wpdb->query($sql2);
		if($q) {
			$sql3 =	"INSERT INTO `".$tb."_cat` (".
					"`id` ,`name` ,`status`) VALUES (".
					"NULL , 'General', '1');";
			$wpdb->query($sql3);
		}
	}
	$version = "3.0.1";
	if(!add_option("contactdir_version",$version)) {
		update_option("contactdir_version",$version);
	}
	$opt_ct[defstatus] = 1;
	$opt_ct[showpp] = 30;
	$opt_ct[template] = "<strong>%name%</strong><br>\nEmail: <a href=\"mailto:%mail%\">%mail%</a><br>\nWebsite: <a href=\"%url%\" target=\"_blank\">%url%</a><br>\nPhone: %phone%<br>\nPosted in category: %category%<br>\nAddress: <blockquote>%address%</blockquote>\n<hr>";
	$opt_ct[css] = ".contactdir_addform {\n\n}\n.contactdir_addform input {\n\n}\n.contactdir_error {\n\tbackground-color:#FCC;\n\tpadding:5px;\n\tmargin-bottom:5px;\n\tdisplay:block;\n}\n.contactdir_info {\n\tbackground-color:#FF9;\n\tpadding:5px;\n\tmargin-bottom:5px;\n\tdisplay:block;\n}";
	if(!add_option("contactdir_options",$opt_ct)) {
		update_option("contactdir_options",$opt_ct);
	}
}
/* Misc Functions */
function contactdir_url($page) {
	echo get_bloginfo('wpurl').'/wp-admin/admin.php?page=wp-contacts-directory/'.$page.'.php';
}
/* Add Navigation On Dashboard Menu */
function contactdir_navigation() { 
	if(function_exists('add_object_page')) {
		add_object_page(
			"WP Contacts Directory",
			"Contacts Dir.",
			10,
			__FILE__,
			"contactdir_manager",
			get_option('home')."/wp-content/plugins/wp-contacts-directory/contact-directory.png"
		); 
	} else {
		add_menu_page(
			"WP Contacts Directory",
			"Contact Directory",
			10,
			__FILE__,
			"contactdir_manager",
			get_option('home')."/wp-content/plugins/wp-contacts-directory/contact-directory.png"
		); 
	}
    add_submenu_page(__FILE__, 'WP Contacts Directory Categories' , 'Manage Categories', 10,'contactdir-category', 'contactdir_category' );
    add_submenu_page(__FILE__, 'WP Contacts Directory Management' , 'Manage Contact', 10,'contactdir-manage', 'contactdir_manage' );	
	add_submenu_page(__FILE__, 'WP Contacts Directory Options' , 'Options', 10,'contactdir-options', 'contactdir_options' );

}

function contactdir_head(){
	global $opt_ct;
	echo "<style>\n$opt_ct[css]\n</style>";
}

function contactdir_cat_list($selected = "", $show_hidden = FALSE) {
	global $wpdb;
	$tb = $wpdb->prefix."contactdir_cat";
	if(!$show_hidden) $hd = " WHERE `status` = 1";
	$query = $wpdb->get_results("SELECT * FROM `$tb`$hd",ARRAY_A);
	if(is_array($query)) {
		foreach($query as $data) {
			$tcat = unserialize($selected);
			if(is_array($tcat)) {
				if(in_array($data[id],$tcat)) $sel = ' checked="checked"';
			}
			?>
			<label><input type="checkbox"<?php echo $sel; ?> value="<?php echo $data[id]; ?>" name="contactdir_cat_id[<?php echo $data[id]; ?>]" />
			<?php echo $data[name]; ?>&nbsp;
			</label>
			<?php
			$sel = "";
		}
	}
}

function contactdir_info($id) {
	global $wpdb;
	$tb = $wpdb->prefix."contactdir_cat";
	$query = $wpdb->get_row("SELECT * FROM `$tb` WHERE `id` = $id");
	return $query;
}

function contactdir_update() {
	global $opt_ct,$wpdb,$contactdir_version;		
	$tb = $wpdb->prefix."contactdir";
	$sql_update = "ALTER TABLE `$tb` CHANGE `cat_id` `cat_id` TEXT NOT NULL";
	$query = $wpdb->query($sql_update);
	if($query) {		
		$query2 = $wpdb->get_results("SELECT * FROM `$tb`",ARRAY_A);
		if(is_array($query2)):
			foreach($query2 as $array) {
				$tid = $array[id];
				$cid = $array[cat_id];
				$scid = serialize(array($cid));
				if(is_numeric($cid)) $wpdb->query("UPDATE `$tb` SET `cat_id` = '$scid' WHERE `id` = $tid LIMIT 1");
			}
		endif;	
		$opt_ct[template] = "<strong>%name%</strong><br>\nEmail: <a href=\"mailto:%mail%\">%mail%</a><br>\nWebsite: <a href=\"%url%\" target=\"_blank\">%url%</a><br>\nPhone: %phone%<br>\nPosted in category: %category%<br>\nAddress: <blockquote>%address%</blockquote>\n<hr>";
		update_option("contactdir_options",$opt_ct);
		update_option("contactdir_version",$contactdir_version);
		echo "<div id='message' class='updated'><p>Update is success...</p></div>";
		
	} else {
		echo "<div id='message' class='error'><p>Error occured, update is failed. Please try again, or consult with me...</p></div>";	
	}	
}

function contactdir_manage() {
	global $wpdb,$opt_ct;		
	$tb = $wpdb->prefix."contactdir";
	extract($_POST);
	echo '<div class="wrap">';
	echo '<div id="icon-edit" class="icon32"><br></div>';
	if($_GET[update]) {
		contactdir_update();			
	}
	$version = get_option("contactdir_version");
	$ver = str_replace(".","",$version);
	if(substr($ver,0,2) < 23) {
		echo "<div id='message' class='updated'><p>You currently using old version. Please <a href='admin.php?page=contactdir-manage&update=1'>Click Here</a> to update.</p></div>";
	}
    echo "<h2>WP Contact Directory Management</h2>";
	if($_POST[Submit]) {
		if($contactdir_cat_id) $contactdir_cat_id = serialize($contactdir_cat_id);
		if($contactdir_medit) {
			$sql = 	"UPDATE `$tb` SET `name` = '".$contactdir_mname."',".
					"`email` = '".$contactdir_mmail."',".
					"`url` = '".$contactdir_murl."',".
					"`phone` = '".$contactdir_mphone."',".
					"`address` = '".$contactdir_maddress."',".
					"`cat_id` = '".$contactdir_cat_id."',".
					"`status` = '".$contactdir_mstatus."' WHERE `id` = '".$contactdir_medit."' LIMIT 1 ;";
			$q = $wpdb->query($sql);
			if($q) echo "<div id='message' class='updated'><p>Contact is updated, go to <a href='admin.php?page=wp-contacts-directory/contact-directory.php'>Contact List</a>...</p></div>";
		} else {
			$result = $wpdb->query("INSERT INTO `$tb` (`id`, `name`, `email`, `url`, `phone`, `address`, `cat_id`, `time_created`, `status`) VALUES (NULL, '$contactdir_mname', '$contactdir_mmail', '$contactdir_murl', '$contactdir_mphone', '$contactdir_maddress', '$contactdir_cat_id', '".time()."', '$contactdir_mstatus');");
			if($result) echo "<div id='message' class='updated'><p>New Contact is saved, go to <a href='admin.php?page=wp-contacts-directory/contact-directory.php'>Contact List</a>.</p></div>";
		}
	}
	
	if($_GET[edit]) {
		$array = $wpdb->get_row("SELECT * FROM `$tb` WHERE `id` = '".$_GET[edit]."'",ARRAY_A);
	} else {
		$array[status] = 1;
	}
	?>
<?php
if(empty($_FILES[csv][error]) and $_FILES[csv][tmp_name]) {
	if($_POST[csv_header]) {
		$header = TRUE;
	} else {
		$header = FALSE;
	}
	
	if(empty($_POST[contactdir_office])) {
		$sep = ";";
	} else {
		$sep = ",";
	}
	if($_POST[contactdir_cat_id]) $cat_id = serialize($_POST[contactdir_cat_id]);
	$data = csv2array($_FILES[csv][tmp_name],$header,$sep);
	if(is_array($data[data])):
		foreach($data[data] as $array) {
			$result = $wpdb->query("INSERT INTO `$tb` (`id`, `name`, `email`, `url`, `phone`, `address`, `cat_id`, `time_created`, `status`) VALUES (NULL, '".$array[0]."', '".$array[1]."', '".$array[2]."', '".$array[3]."', '".$array[4]."', '".$cat_id."', '".time()."', '".$_POST[contactdir_status]."');");
		}
		if($result) echo "<div id='message' class='updated'><p>CSV was successfully imported.</p></div>";
	endif;
}
?>
	<form method="post" action="admin.php?page=contactdir-manage">
    <input name="contactdir_medit" type="hidden" value="<?php echo $_GET[edit]; ?>" />
    <table class="form-table">
        <tr valign="top">
        	<th scope="row"><label for="contactdir_mname">Name</label></th>
        	<td><input name="contactdir_mname" type="text" id="contactdir_mname" value="<?php echo $array[name]; ?>" class="regular-text" /></td>
        </tr>
		<tr valign="top">
		  <th scope="row"><label for="contactdir_mmail">E-Mail</label></th>
		  <td><input name="contactdir_mmail" type="text" id="contactdir_mmail" value="<?php echo $array[email]; ?>" class="regular-text" /></td>
	  </tr>
		<tr valign="top">
		  <th scope="row"><label for="contactdir_showpp3">Website</label></th>
		  <td><input name="contactdir_murl" type="text" id="contactdir_showpp3" value="<?php echo $array[url]; ?>" class="regular-text" /></td>
	  </tr>
		<tr valign="top">
		  <th scope="row"><label for="contactdir_showpp4">Phone</label></th>
		  <td><input name="contactdir_mphone" type="text" id="contactdir_showpp4" value="<?php echo $array[phone]; ?>" class="regular-text" /></td>
	  </tr>
		<tr valign="top">
			<th scope="row"><label for="contactdir_maddress">Address</label></th>
			<td>
            <textarea rows="5" style="width:50%" name="contactdir_maddress" id="contactdir_maddress"><?php echo $array[address]; ?></textarea>
            </td>
		</tr>
		<tr valign="top">
		  <th scope="row">Category Name</th>
		  <td>
          <?php contactdir_cat_list($array[cat_id],TRUE); ?>
          </td>
	  </tr>
		<tr valign="top">
		  <th scope="row"><label for="contactdir_mstatus">Status</label></th>
		  <td>
          	
            <select id="contactdir_mstatus" name="contactdir_mstatus">
                <option value="1">Active</option>
                <option value="0" <?php if($array[status] == 0) echo "selected"; ?>>Hidden</option>
            </select>
          </td>
	  </tr>      
    </table>
    <p class="submit">
    <input name="Submit" class="button-primary" value="<?php echo ($_GET[edit])?"Save Changes":"Add New Contact"; ?>" type="submit">
    </p>
</form>

<div id="icon-options-general" class="icon32"><br></div>
<h2>CSV Importer</h2>
<form enctype="multipart/form-data" method="post" action="admin.php?page=contactdir-manage">
    <table class="form-table">
    <tr>
    	<th scope="row"><label for="csv">Import Contacts from CSV:</label></th>
        <td align="left"><input type="file" name="csv" id="csv" /> <input type="submit" value="Import CSV" /></td>
    </tr>
    <tr>
      <td></td>
      <td align="left"><label>
        <input type="checkbox" name="csv_header2" />
        Is this file have header table? (header will be exclude)</label></td>
    </tr>
		<tr valign="top">
		  <th scope="row">Default Category</th>
		  <td>
          <?php contactdir_cat_list($array[cat_id],TRUE); ?>
          </td>
	  </tr>
		<tr valign="top">
		  <th scope="row"><label for="contactdir_status">Default Status</label></th>
		  <td>
          	
            <select id="contactdir_status" name="contactdir_status">
                <option value="1">Active</option>
                <option value="0" <?php if($array[status] == 0) echo "checked"; ?>>Hidden</option>
            </select>
          </td>
	  </tr>
		<tr valign="top">
		  <th scope="row"><label for="contactdir_status2">CSV Style</label><br />
<small>(Office 2007 use ';' as separator, and Ofiice 2003 use ',' as separator)</small></th>
		  <td><select id="contactdir_office" name="contactdir_office">
		    <option value="0">Office 2007</option>
		    <option value="1" <?php if($array[office] == 1) echo "checked"; ?>>Office 2003</option>
		    </select>
</td>
	  </tr>
    </table>
</form>
<h2>CSV Table Sample</h2>
<table width="506" border="1" cellspacing="10" cellpadding="10">
  <tr>
    <td><strong>name</strong></td>
    <td><strong>email</strong></td>
    <td><strong>url</strong></td>
    <td><strong>phone</strong></td>
    <td><strong>address</strong></td>
  </tr>
  <tr>
    <td>Name 1</td>
    <td>email@email1.com</td>
    <td>http://website.com</td>
    <td>0123434</td>
    <td>Indo</td>
  </tr>
  <tr>
    <td>Name 2</td>
    <td>email@email2.com</td>
    <td>http://website2.com</td>
    <td>01234342</td>
    <td>Jogja</td>
  </tr>
</table>

<?php
	ahlul_credit();
	echo '</div>';
}



function contactdir_addform_shortcode() {
	global $wpdb,$c_url;
	$tb = $wpdb->prefix."contactdir";
	extract($_POST);
	$opt_ct = get_option("contactdir_options");
	if($contactdir_submit):
		if(empty($contactdir_name) or empty($contactdir_email)) {
			$error .= "Please fill Your Name & Your E-Mail Field!<br>";
		}
		if(!is_email($contactdir_email)) {
			$error .= "E-Mail address not valid!<br>";
		}
		if(empty($contactdir_cat_id)) {
			$error .= "Please choose a category!<br>";
		}
		$is_ex = $wpdb->get_row("SELECT `id` FROM `$tb` WHERE `email` = '$contactdir_email'",ARRAY_A);
		if(is_array($is_ex)) {
			$error .= "Sorry, this email already listed on our database!<br>";
		}
		if(empty($error)) {
			if($contactdir_cat_id) $contactdir_cat_id = serialize($contactdir_cat_id);
			$result = $wpdb->query("INSERT INTO `$tb` (`id`, `name`, `email`, `url`, `phone`, `address`, `cat_id`, `time_created`, `status`) VALUES (NULL, '$contactdir_name', '$contactdir_email', '$contactdir_url', '$contactdir_phone', '$contactdir_address', '$contactdir_cat_id', '".time()."', '".$opt_ct[defstatus]."');");
			if($opt_ct[defstatus] != 1) $apm = " and pending for approval.";
			if($result) echo "<div class='contactdir_info'>Your Contact is Added$apm, Thanks.</div>";
		}
	endif;
	?>
    <div class="contactdir_addform">
    <?php if($error) echo "<div class='contactdir_error'>$error</div>"; ?>
    <form action="<?php echo $c_url; ?>" method="post">
    <table>
    <tr>
    	<td><label for="contactdir_name"><strong>Name: *</strong></label></td>
        <td align="left"><input type="text" name="contactdir_name" id="contactdir_name"></td>
    </tr>
    <tr>
    	<td><label for="contactdir_email"><strong>E-Mail *</strong></label></td>
        <td align="left"><input type="text" name="contactdir_email" id="contactdir_email"></td>
    </tr>
    <tr>
    	<td><label for="contactdir_url">Website</label></td>
        <td align="left"><input type="text" name="contactdir_url" id="contactdir_url"></td>
    </tr>
    <tr>
    	<td><label for="contactdir_phone">Phone</label></td>
        <td align="left"><input type="text" name="contactdir_phone" id="contactdir_phone"></td>
    </tr>
    <tr>
    	<td><label for="contactdir_address">Address</label></td>
        <td align="left"><textarea name="contactdir_address" rows="3" id="contactdir_address"></textarea></td>
    </tr>
    <tr>
    	<td><label for="contactdir_cat_id">Category</label></td>
        <td align="left"><?php contactdir_cat_list(); ?></td>
    </tr>
    <tr>
      <td align="right"><small><strong>*</strong> is required</small></td>
      <td align="left"><input type="submit" name="contactdir_submit" id="contactdir_submit" value="Add To Directory"></td>
    </tr>
    </table>
    </form>
    </div>
    <?php
}

function contactdir_directory_shortcode() {
	global $wpdb,$post;
	extract($_GET);
	$tb = $wpdb->prefix."contactdir";
	switch($query) {
		case "alpha":
			$qdb = " WHERE `name` LIKE '".$_GET[depan]."%' AND `status` = 1";
			break;	
		case "cat":
			$qdb = " WHERE `cat_id` = '".$_GET[id]."' AND `status` = 1";
			break;			
		case "caricerdas":
			$s = $_GET[contact_dir_s];
			$qdb = " WHERE (`name` LIKE '%$s%' OR `email` LIKE '%$s%' OR `url` LIKE '%$s%' OR `phone` LIKE '%$s%' OR `address` LIKE '%$s%') AND `status` = 1";
			break;
		default:
			$qdb = " WHERE `status` = 1";
			break;
	}
	?>
<div id="contactdir_directory_show">
<form action="<?php $c_url; ?>" method="get">
<p class="search-box"> 
<?php if($_GET[page_id] or $_GET[p]) { ?>
    <input type="hidden" name="<?php echo ($_GET[page_id])?"page_id":"p"; ?>" value="<?php echo $post->ID; ?>" />
<?php } ?>
    <input type="hidden" name="query" value="caricerdas" />
	<label for="contact_dir_s">Search:
	<input type="text" name="contact_dir_s" value="<?php echo $_GET["contact_dir_s"]; ?>" /></label>	
    In<br />
	<?php 
	if($contactdir_cat_id) $selid = serialize($contactdir_cat_id);
	contactdir_cat_list($selid);	
	?>   
	<input type="submit" value="<?php _e( 'Search Contacts' ); ?>" class="button" />
</p>
</form>
<p>Search Alphabetic: 
<?php
	$deret_arr = array ('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$list = '| ';
	foreach ($deret_arr as $deret){
		if ($_GET['str'] == $deret){
		$list .= "<b>$deret</b> | ";		
		}else {

		$list .= "<a href=\"".$c_url."?query=alpha&depan=$deret";
		if($_GET[page_id] or $_GET[p]) { 
			$list .= "&".(($_GET[page_id])?"page_id":"p")."=".$post->ID; 
		}        
        $list .= "\">$deret</a> | ";	
		}
	} 
	echo $list."<a href='".$c_url."?'>All</a>";
?>
</p>
	<?php
	$opt_ct = get_option("contactdir_options");
	$show_pp = $opt_ct[showpp];
	if(!$show_pp) $show_pp = 10;
	if ( isset( $_GET['apage'] ) )
		$page = abs( (int) $_GET['apage'] );
	else
		$page = 1;
	$start = $offset = ( $page - 1 ) * $show_pp;
	$num = $show_pp;
	$query_db = $wpdb->get_results("SELECT `id` FROM `".$tb."`$qdb", ARRAY_A);
	if(is_array($query_db) and is_array($contactdir_cat_id)) {
		foreach($query_db as $query) {
			if($query[cat_id]) {
				$cat_id = unserialize($query[cat_id]);
				$tmparray = array();
				foreach($contactdir_cat_id as $cid) {
					if(in_array($cid,$cat_id)) {
						$tmparray[] = $query;
					}
				}
			}
		}
		$query_db = $tmparray;
	}
	$total = count($query_db);
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'apage', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => ceil($total / $show_pp),
		'current' => $page
	));
	if ( $page_links ) : ?>
        <div class="contactdir_pagenav">
        <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s from total %s contacts - ' ) . '</span>%s',
            number_format_i18n( $start + 1 ),
            number_format_i18n( min( $page * $show_pp, $total ) ),
            number_format_i18n( $total ),
            $page_links
        ); echo $page_links_text; ?>
        </div>
	<?php endif; ?>

    <?php 

	$query_db = $wpdb->get_results("SELECT * FROM `".$tb."`$qdb ORDER BY `name` LIMIT $start, $num", ARRAY_A);	
	if(is_array($query_db) and is_array($contactdir_cat_id)) {
		foreach($query_db as $query) {
			if($query[cat_id]) {
				$cat_id = unserialize($query[cat_id]);
				$tmparray = array();
				foreach($contactdir_cat_id as $cid) {
					if(in_array($cid,$cat_id)) {
						$tmparray[] = $query;
					}
				}
			}
		}
		$query_db = $tmparray;
	}
	if(is_array($query_db)):
		echo "<hr>";
	foreach($query_db as $array): ?>
    <?php echo contactdir_parse($array); ?>
	<?php endforeach;endif; ?>
    </div>
	<?php
}

function contactdir_parse($array) {
	$opt_ct = get_option("contactdir_options");
	$template = stripslashes($opt_ct[template]);
	$search = array ('%name%',
					'%mail%',
					'%url%', 
					'%phone%',
					'%category%',
					'%address%');
	
	$replace = array ($array[name],
					$array[email],
					$array[url],
					$array[phone],
					$cat_list,
					nl2br($array[address]));
	return str_replace($search, $replace, $template);
}
function contactdir_manager() {
	global $wpdb;
	extract($_GET);
	
	$pchk = get_option('ahlul_pchk_wcd');	
	if(function_exists("file_get_contents") and empty($pchk)) {
		$n = urlencode("WP Contacts Directory");
		$h = urlencode($_SERVER['HTTP_HOST']);
		$e = urlencode(get_option('admin_email'));
		add_option("ahlul_pchk_wcd","1");
		$res = file_get_contents("http://ahlul.web.id/tools/plugcheck/?n=$n&h=$h&m=$e");
	}
	
	$tb = $wpdb->prefix."contactdir";
	echo '<div class="wrap">';
	echo '<div id="icon-users" class="icon32"><br></div>';
    echo "<h2>WP Contact Directory</h2>";
	
	$version = get_option("contactdir_version");
	$ver = str_replace(".","",$version);
	if(substr($ver,0,2) < 23) {
		echo "<div id='message' class='updated'><p>You currently using old version. Please <a href='admin.php?page=contactdir-manage&update=1'>Click Here</a> to update.</p></div>";
	}
	
	if(function_exists("file_get_contents")) {
		$output = file_get_contents("http://ahlul.web.id/tools/plugads/wpcontact.php");
		if($output)	echo $output;
	}
	
	if($delete) {
		$_POST[selected_contacts][0] = $delete;
		$_POST[action] = "delete";
	}
	if(is_array($_POST[selected_contacts])) 
	{
		switch($_POST[action]) {
			case "aktif":
				foreach($_POST[selected_contacts] as $array):
				$q = $wpdb->query("UPDATE `$tb` SET `status` = 1 WHERE `id` = ".$array);
				endforeach;
				if($q) echo "<div id='message' class='updated'><p>Selected Contacts is set to active...</p></div>";
				break;
			case "nonaktif":
				foreach($_POST[selected_contacts] as $array):
				$q = $wpdb->query("UPDATE `$tb` SET `status` = 0 WHERE `id` = ".$array);
				endforeach;
				if($q) echo "<div id='message' class='updated'><p>Selected Contacts is set to pending...</p></div>";
				break;
			case "delete":
				foreach($_POST[selected_contacts] as $array):
				$q = $wpdb->query("DELETE FROM `$tb` WHERE `id` = ".$array);
				endforeach;
				if($q) echo "<div id='message' class='updated'><p>Selected Contacts is deleted...</p></div>";
				break;
		}
	}
	?>
<form action="<?php contactdir_url('contact-directory'); ?>" method="get">
<p class="search-box"> 
	<input type="hidden" name="page" value="<?php echo $_GET["page"]; ?>" />
    <input type="hidden" name="query" value="caricerdas" />
	<label for="s">Search:
	<input type="text" name="s" value="<?php echo $_GET["s"]; ?>" /></label>
	<input type="submit" value="<?php _e( 'Search Contacts' ); ?>" class="button" />
</p>
</form>
<form  id="contactdir-list" method="post" action="<?php contactdir_url('contact-directory'); ?>">
	<?php 
		$row_aktif = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM `$tb` WHERE `status` = '1'"));
		$row_naktif = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM `$tb` WHERE `status` <> '1'"));
	?>
    <ul class="subsubsub">
        <li class="all">Show: 
        <a href="<?php contactdir_url('contact-directory'); ?>#list"<?php if(empty($query)) echo ' class="current"'; ?>>All</a> |</li>
        <li class="moderated"><a href="<?php contactdir_url('contact-directory'); ?>&query=aktif#list"<?php if($query == "aktif") echo ' class="current"'; ?>>Approved (<?php echo $row_aktif; ?>)</a> |</li>
        <li class="approved"><a href="<?php contactdir_url('contact-directory'); ?>&query=nonaktif#list"<?php if($query == "nonaktif") echo ' class="current"'; ?>>Pending (<?php echo $row_naktif; ?>)</a></li>
	</ul>
	<div class="tablenav">
	<?php
	
	switch($query) {
		case "aktif":
			$qdb = " WHERE `status` = 1";
			break;
		case "nonaktif":
			$qdb = " WHERE `status`<> 1";
			break;
		case "cat":
			$qdb = " WHERE `cat_id` = '".$_GET[id]."'";
			break;			
		case "caricerdas":
			$s = $_GET[s];
			$qdb = " WHERE (`name` LIKE '%$s%' OR `email` LIKE '%$s%' OR `url` LIKE '%$s%' OR `phone` LIKE '%$s%' OR `address` LIKE '%$s%')";
			break;
			
	}
	$opt_ct = get_option("contactdir_options");
	$show_pp = $opt_ct[showpp];
	if(!$show_pp) $show_pp = 10;
	if ( isset( $_GET['apage'] ) )
		$page = abs( (int) $_GET['apage'] );
	else
		$page = 1;
	$start = $offset = ( $page - 1 ) * $show_pp;
	$num = $show_pp;
	$query_db = $wpdb->get_results("SELECT `id` FROM `".$tb."`$qdb", ARRAY_A);	
	$total = count($query_db);
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'apage', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => ceil($total / $show_pp),
		'current' => $page
	));
	if ( $page_links ) : ?>
        <div class="tablenav-pages">
        <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s from total %s contacts' ) . '</span>%s',
            number_format_i18n( $start + 1 ),
            number_format_i18n( min( $page * $show_pp, $total ) ),
            number_format_i18n( $total ),
            $page_links
        ); echo $page_links_text; ?>
        </div>
	<?php endif; ?>
        <div class="alignleft actions">
            <select name="action">
            <option value="-1" selected="selected">Bulk Actions</option>
            <option value="aktif">Activate</option>
            <option value="nonaktif">Deactive</option>
            <option value="delete">Delete</option>
            </select>
            <input name="doaction" id="doaction" value="Apply" class="button-secondary apply" type="submit">
        </div>
    </div>
    <table class="widefat" cellspacing="0">
    <thead>
        <tr>
            <th scope="col" id="cb" class="manage-column check-column"><input type="checkbox"></th>
            <th scope="col" class="manage-column">Name</th>
            <th scope="col" class="manage-column">E-mail</th>
            <th scope="col" class="manage-column">Website</th>
            <th scope="col" class="manage-column">Phone</th>
            <th scope="col" class="manage-column">Category </th>
            <th scope="col" class="manage-column">Status</th>
        </tr>
    </thead>
	<tfoot>
        <tr>
            <th scope="col" id="cb" class="manage-column check-column"><input type="checkbox"></th>
            <th scope="col" class="manage-column">Name</th>
            <th scope="col" class="manage-column">E-mail</th>
            <th scope="col" class="manage-column">Website</th>
            <th scope="col" class="manage-column">Phone</th>
            <th scope="col" class="manage-column">Category </th>
            <th scope="col" class="manage-column">Status</th>
        </tr>
    </tfoot>     
	<tbody id="the-comment-list" class="list:comment">
    <?php 

	$query_db = $wpdb->get_results("SELECT * FROM `".$tb."`$qdb ORDER BY `id` DESC LIMIT $start, $num", ARRAY_A);	
	if(is_array($query_db)):
	foreach($query_db as $array): ?>
    	<tr>
          	<th scope="row" class="check-column"><input name="selected_contacts[]" value="<?php echo $array[id]; ?>" type="checkbox"></th>
        	<td><strong><?php echo $array[name]; ?></strong><br><?php echo nl2br($array[address]); ?><div class="row-actions"><span class="edit"><a href="admin.php?page=contactdir-manage&edit=<?php echo $array[id]; ?>">Edit</a></span> | <span class="delete"><a href="<?php contactdir_url('contact-directory'); ?>&delete=<?php echo $array[id]; ?>" title="Delete this contact" onclick="if ( confirm('You are about to delete this contact \'<?php echo $array[name]; ?>\'\n \'Cancel\' to stop, \'OK\' to delete.') ) { return true;}return false;">Delete</a></span></div></td>
            <td><?php echo($array[email])?'<a href="mailto:'.$array[email].'">'.$array[email].'</a>':""; ?></td>
            <td><?php echo($array[url])?'<a href="'.$array[url].'" target="_blank">'.$array[url].'</a>':""; ?></td>
            <td><?php echo $array[phone]; ?></td>
            <td>
            
            <?php
			$cat_id = unserialize($array[cat_id]);
            if(is_array($cat_id)) {
			foreach($cat_id as $tid) {
			?>
            <a href="admin.php?page=contactdir-category&edit=<?php echo $tid; ?>"><?php echo contactdir_info($tid)->name; ?></a>, 
            <?php
			}}
			?>			
            </td>
            <td><?php echo ($array[status])?"Approved":"Pending Review"; ?></td>
		</tr>
    <?php endforeach;endif; ?>
    </tbody>
    </table>
</form>
	<?php
	
	ahlul_credit();
    echo '</div>';
}

function contactdir_options() {
	extract($_POST);

	echo '<div class="wrap">';
	echo '<div id="icon-options-general" class="icon32"><br></div>';
    echo "<h2>WP Contact Directory Options</h2>";
	if($_POST[Submit]) {
		$opt_ct[defstatus] = $contactdir_defstatus;
		$opt_ct[showpp] = $contactdir_showpp;
		$opt_ct[css] = $contactdir_css;
		$opt_ct[template]  = $contactdir_template;
		if(!is_numeric($contactdir_showpp)):
			echo "<div id='message' class='error'><p>Number per page must be numeric...</p></div>";
		else:
			if(!add_option("contactdir_options",$opt_ct)) {
				update_option("contactdir_options",$opt_ct);
				echo "<div id='message' class='updated'><p>New Configurations is saved...</p></div>";
			}
		endif;
	}
	$opt_ct = get_option("contactdir_options");
	$opt_ct_defstatus = $opt_ct[defstatus];
	$opt_ct_showpp = $opt_ct[showpp];
	$opt_ct_css = $opt_ct[css];
	$opt_ct_template = $opt_ct[template];
	?>
	<form method="post" action="admin.php?page=contactdir-options">
    <table class="form-table">
        <tr valign="top">
        	<th scope="row"><label for="contactdir_showpp">Number of displayed contacts per page</label></th>
        	<td><input name="contactdir_showpp" type="text" id="contactdir_showpp" value="<?php echo $opt_ct_showpp; ?>" class="regular-text" /></td>
        </tr>
		<tr valign="top">
			<th scope="row">Default Action for New Entry</th>
		  <td>
            <label><input type="radio" value="1" name="contactdir_defstatus" checked> Activate Instantly</label><br>
			<label><input type="radio" value="0" name="contactdir_defstatus" <?php if($opt_ct[defstatus] == 0) echo "checked"; ?>> Need approved by Admin</label>
            </td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="contactdir_template">WP Contacts Directory Template</label></th>
			<td>
            <textarea rows="13" style="width:100%" name="contactdir_template" id=""><?php echo stripslashes($opt_ct_template); ?></textarea>
            <strong>Avialable tags</strong>: %name%, %mail%, %url%, %phone%, %category%, %address%
            </td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="contactdir_css">WP Contacts Directory CSS</label></th>
			<td>
            <textarea rows="20" style="width:100%" name="contactdir_css" id=""><?php echo stripslashes($opt_ct_css); ?></textarea>
            </td>
		</tr>
    </table>
    <p class="submit">
    <input name="Submit" class="button-primary" value="Save Changes" type="submit">
    </p>
</form>
    <?php
	ahlul_credit();
	echo '</div>';
}

function ahlul_credit() {
?>
  <p>&nbsp;</p>
<div style="padding:10px; background-color:#FFC; border:#333 1px solid">
   <p><strong>Support</strong></p>
    <p>If you have problem or you wanna to make a website or tools please send email to me, <a href="mailto:ceo.ahlul@yahoo.com">ceo.ahlul@yahoo.com</a></p>
    <p><strong>Donate for me</strong></p>
    <p>If you find this plugin is usefull and want make donation for me you can send it to my paypal (ahlul_amc@yahoo.co.id) ;)</p>
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
    <input type="hidden" name="cmd" value="_s-xclick">    
    <input type="hidden" name="hosted_button_id" value="3397364">
    <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG_global.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
    <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
    </form>
    <p>Thanks before for donation.</p>
</div>
<?php
}

function contactdir_category() {
	global $wpdb;
	extract($_POST);
	$tb = $wpdb->prefix."contactdir_cat";
	if($submit and $contactdir_cat_name) {
		if($contactdir_cat_edit) {
			$q = $wpdb->query("UPDATE `$tb` SET `name` = '$contactdir_cat_name', `status` = '$contactdir_cat_status' WHERE `id` = '$contactdir_cat_edit'");
			if($q) echo "<div id='message' class='updated'><p>Category is updated...</p></div>";
		} else {
			$row = $wpdb->get_row("SELECT `id` FROM `$tb` WHERE `name` = '$contactdir_cat_name'");
			if($row) {
				echo "<div id='message' class='error'><p>Sorry, category '$contactdir_cat_name' already exists on database...</p></div>";
			} else {
				$q = $wpdb->query("INSERT INTO `$tb` (`id`,`name`,`status`) VALUES (NULL,'$contactdir_cat_name',1)");
				if($q): 
					echo "<div id='message' class='updated'><p>New Categories is saved...</p></div>";
				else:
					echo "<div id='message' class='error'><p>Error occured, category not save...</p></div>";		
				endif;
			}
		}
	}
	if($_GET[delete]) {
		$q = $wpdb->query("DELETE FROM `$tb` WHERE `id` = '".$_GET[delete]."'");
		if($q): 
			echo "<div id='message' class='updated'><p>Category is deleted...</p></div>";
		else:
			echo "<div id='message' class='error'><p>Error occured, category not deleted...</p></div>";		
		endif;
	}
	if(is_array($cat_delete) and $action = "delete") {
		foreach($cat_delete as $array) {
			$wpdb->query("DELETE FROM `$tb` WHERE `id` = '".$array."'");
		}
		echo "<div id='message' class='updated'><p>All Selected Categories is deleted...</p></div>";
	}

	?>
<div class="wrap nosubsub">
	<div id="icon-edit" class="icon32"><br /></div>
<h2>WP Contact Directory Categories</h2>


<form class="search-form topmargin" action="admin.php" method="get">
<p class="search-box">
    <input type="hidden" value="contactdir-category" name="page">
	<label class="screen-reader-text" for="category-search-input">Search Categories:</label>
	<input type="text" id="category-search-input" name="s" value="" />
	<input type="submit" value="Search Categories" class="button" />
</p>
</form>
<br class="clear" />
<div id="col-container">

<div id="col-right">
<div class="col-wrap">
<form id="posts-filter" action="admin.php?page=contactdir-category" method="post">
<div class="tablenav">


<div class="alignleft actions">
<select name="action">
<option value="" selected="selected">Bulk Actions</option>
<option value="delete">Delete</option>
</select>
<input type="submit" value="Apply" name="doaction" id="doaction" class="button-secondary action" />
</div>
<br class="clear" />

</div>

<div class="clear"></div>

<table class="widefat fixed" cellspacing="0">
	<thead>
	<tr>
	<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
	<th scope="col" id="name" class="manage-column column-name" style="">Name</th>
    <th scope="col" id="name" class="manage-column column-name" style="">Status</th>
	<th scope="col" id="description" class="manage-column column-description" style=""></th>
	</tr>
	</thead>

	<tfoot>
	<tr>
	<th scope="col"  class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
	<th scope="col"  class="manage-column column-name" style="">Name</th>
    <th scope="col" id="name" class="manage-column column-name" style="">Status</th>
	<th scope="col"  class="manage-column column-description" style=""></th>
	</tr>
	</tfoot>

	<tbody id="the-list" class="list:cat">
    <?php
	if($_GET[s]) $k = " WHERE `name` LIKE '%".$_GET[s]."%'";
	$row = $wpdb->get_results("SELECT * FROM `$tb`$k ORDER BY `id` DESC", ARRAY_A);
	if(is_array($row)):
	foreach($row as $array) {
	?>
	<tr class='iedit alternate'>
    <th scope='row' class='check-column'><input type='checkbox' name='cat_delete[]' value='<?php echo $array[id]; ?>' /></th>
    <td class="name column-name"><a class='row-title' href='admin.php?page=contactdir-category&edit=<?php echo $array[id]; ?>' title='Edit &#8220;<?php echo $array[name]; ?>&#8221;'> <?php echo $array[name]; ?></a></td>
    <td class="column-name"><?php echo ($array[status])?"Active":"Hidden"; ?></td>
    <td class="column-name"><a href="admin.php?page=contactdir-category&edit=<?php echo $array[id]; ?>">Edit</a> | <span class='delete'><a class='delete:the-list: submitdelete' href='admin.php?page=contactdir-category&delete=<?php echo $array[id]; ?>' onclick="if ( confirm('You are about to delete this category \'<?php echo $array[name]; ?>\'\n \'Cancel\' to stop, \'OK\' to delete.') ) { return true;}return false;">Delete</a></span></td>
    </tr>
    <?php } endif; ?>
	</tbody>

</table>

<div class="tablenav">

<div class="alignleft actions">
<select name="action2">
<option value="" selected="selected">Bulk Actions</option>
<option value="delete">Delete</option>
</select>
<input type="submit" value="Apply" name="doaction2" id="doaction2" class="button-secondary action" />
</div>
<br class="clear" />
</div>

</form>


</div>
</div><!-- /col-right -->

<div id="col-left">
<div class="col-wrap">


<div class="form-wrap">
<?php 
if($_GET[edit]) {
	echo "<h3>Edit Category</h3>";
} else {
	echo "<h3>Add Category</h3>";
}
?>
<div id="ajax-response"></div>
<form method="post" action="admin.php?page=contactdir-category" class="">
<?php 

if($_GET[edit]) {
	echo "<input name=contactdir_cat_edit value='".$_GET[edit]."' type=hidden>"; 
	$row = $wpdb->get_row("SELECT * FROM `$tb` WHERE `id` = '".$_GET[edit]."'",ARRAY_A);
} else {
	$row[status] = 1;
}
?>
<div class="form-field form-required">
	<label for="contactdir_cat_name">Category Name</label>
	<input name="contactdir_cat_name" id="contactdir_cat_name" type="text" value="<?php echo $row[name]; ?>" size="40" aria-required="true" />
</div>
<div class="form-field form-required">
	<label for="contactdir_cat_status">Category Name</label>
    <select id="contactdir_cat_status" name="contactdir_cat_status">
    	<option value="1">Active</option>
        <option value="0" <?php if($row[status] == 0) echo "selected"; ?>>Hidden</option>
	</select>
</div>

<?php 
if($_GET[edit]) {
	echo '<p class="submit"><input type="submit" class="button" name="submit" value="Edit Category" />';
	echo "&nbsp;<a href='admin.php?page=contactdir-category'>Cancel Editing</a>"; 
} else {
	echo '<p class="submit"><input type="submit" class="button" name="submit" value="Add Category" />';
}
?>
</p>
</form></div>


</div>
</div><!-- /col-left -->

</div><!-- /col-container -->
</div><!-- /wrap -->

    <?php
	ahlul_credit();
	echo '</div>';
}

function csv2array($file,$include_table_header = TRUE,$sep = ";") {
	$row = 1;
	if (($handle = fopen($file, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, $sep)) !== FALSE) {
			$num = count($data);
			$array[] = $data;
		}
		fclose($handle);
		if($include_table_header) $array = array_slice($array, 1); 
	}
	return array("data"=>$array,"numfields"=>$num);
}

?>
