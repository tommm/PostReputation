<?php
/*
+---------------------------------------------------------------------------
|   MyNetwork Core
|	|- Post Reputation
|   =============================================
|   by Tom Moore (www.xekko.co.uk)
|   Copyright 2010 Mooseypx Design / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: postrep_install.php 44 2010-10-07 14:48:02Z Tomm $
|
|	Install data for Post Reputation
+---------------------------------------------------------------------------
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function postrep_do_install()
{
	global $cache, $db, $groupscache, $mybb, $network;

	// Any settings?
	$postrep_settings = array(
		"postrep_enable" => array(
			"title" => "Use Post Reputation?",
			"description" => "Do you want users to be able to 'vote' on individual posts?",
			"optionscode" => "onoff",
			"value" => "1",
			"isdefault" => "0"
		),
		"postrep_forums" => array(
			"title" => "Affected forums",
			"description" => "Select the forums you'd like Post Reputation to be used in.",
			"optionscode" => "text",
			"value" => "",
			"isdefault" => "0"
		),
		"postrep_impose_limit" => array(
			"title" => "Impose Post Reputation Limit?",
			"description" => "If you want to limit the amount of posts users can give reputation to in 24 hours, enter the limit below. 0 is unlimited.",
			"optionscode" => "text",
			"value" => "0",
			"isdefault" => "0"
		),
		"postrep_allow_minus" => array(
			"title" => "Allow Minus Reputation?",
			"description" => "Can users give minus reputation to a post?",
			"optionscode" => "yesno",
			"value" => "1",
			"isdefault" => "0"
		),
		"postrep_show_plus_votes" => array(
			"title" => "Show Plus Reputators?",
			"description" => "When viewing the reputation for a post, do you want to display the users that liked it?",
			"optionscode" => "yesno",
			"value" => "1",
			"isdefault" => "0"
		),
		"postrep_show_minus_votes" => array(
			"title" => "Show Minus Reputators?",
			"description" => "When viewing the reputation for a post, do you want to display the users that didn't like it?",
			"optionscode" => "yesno",
			"value" => "1",
			"isdefault" => "0"
		)
	);

	// Template groups?
	$template_groups = array(
		"misc" => "Misc",
		"postbit" => "Post Bit"
	);

// Templates to add
	$templates = array(
		"postbit_reparea" => '
<div class="post_rep" id="post_rep_area_{$pid}">
{$minus_link} 
<span class="rep_{$id_style}" id="{$pid}_rep_box" title="{$about_title}">{$reputation_link}</span> 
{$plus_link} |</div>',
		"postbit_repminus" => '<a href="javascript:;" title="{$lang->minus_post}" onclick="PostReputation.vote(\\\'{$pid}\\\', \\\'minus\\\', \\\'{$uid}\\\')" id="minus_link_{$pid}"><img src="{$network->img_path}postrep/minus_post.png" alt="" /></a>',
		"postbit_repplus" => '<a href="javascript:;" title="{$lang->plus_post}" onclick="PostReputation.vote(\\\'{$pid}\\\', \\\'plus\\\', \\\'{$uid}\\\')" id="plus_link_{$pid}"><img src="{$network->img_path}postrep/plus_post.png" alt="" /></a>',
		"postbit_repremove" => '<a href="javascript:;" title="{$lang->remove_post}" id="remove_link_{$pid}" onclick="PostReputation.remove(\\\'{$pid}\\\', \\\'{$uid}\\\'); return false;"><img src="{$network->img_path}postrep/remove_post.png" alt="" /></a>',
		"misc_voting_list" => '<html>
<head>
<title>{$lang->voters_list}</title>
{$headerinclude}
<style type="text/css">
<!--
body {
	margin: 0;
	padding: 4px;
}
.rep_padding {
	margin: 10px;
	font-size: 120%;
	text-align: center;
}

.rep_none {
	background-color: #CCC;
	border: 1px solid #666;
	vertical-align: middle;
	padding: 2px 4px 2px 4px;
}

.rep_minus {
	background-color: #FFCCCC;
	border: 1px solid #f30;
	vertical-align: middle;
	padding: 2px 4px 2px 4px;
}

.rep_plus {
	background-color: #c3d4c0;
	border: 1px solid #669966;
	vertical-align: middle;
	padding: 2px 4px 2px 4px;
}

.rep_user_list {
	margin: 10px;
}
-->
</style>
</head>
<body>
	<table cellspacing="{$theme[\\\'borderwidth\\\']}" cellpadding="{$theme[\\\'tablespace\\\']}" border="0" align="center" class="tborder">
	<tr>
		<td class="thead" colspan="2">
			<div class="float_right" style="margin-top: 3px;"><span class="smalltext"><a href="#" onclick="window.close();">{$lang->close}</a></span></div>
			<div><strong>{$lang->voters_list}</strong></div>
		</td>
	</tr>
	{$plus_users}
	{$minus_users}
	</table>
</body>
</html>',
		"misc_voting_list_bit" =>  '
<tr>
	<td class="trow2" width="20%"><div class="rep_padding {$rep_class}">{$rep_count}</div></td>
	<td class="trow2">{$lang->people_who_like}
		{$user_list}
	</td>
</tr>',
		"misc_voting_list_none" => '
<tr>
	<td class="trow2" colspan="2">{$lang->failed_no_rep}</td>
</tr>'
);

	$stylesheets = array(
		"showthread.css" => '
.post_rep {
	display: inline;
	font-size: 11px;
}

.post_rep img {
	vertical-align: middle;
}

.rep_none {
	background-color: #CCC;
	border: 1px solid #666;
	vertical-align: middle;
	padding: 2px 4px 2px 4px;
}

.rep_minus {
	background-color: #FFCCCC;
	border: 1px solid #f30;
	vertical-align: middle;
	padding: 2px 4px 2px 4px;
}

.rep_plus {
	background-color: #c3d4c0;
	border: 1px solid #669966;
	vertical-align: middle;
	padding: 2px 4px 2px 4px;
}');

	// Start with inserting our settings
	$query = $db->simple_select("settinggroups", "gid", "name = 'reputation'");
	$gid = $db->fetch_field($query, "gid");

	$disporder = 55000; // We need it to be high enough to be the >last< in the display order
	foreach($postrep_settings as $name => $setting)
	{
		$insert_array = array(
			"name" => $db->escape_string($name),
			"title" => $db->escape_string($setting['title']),
			"description" => $db->escape_string($setting['description']),
			"optionscode" => $db->escape_string($setting['optionscode']),
			"value" => $db->escape_string($setting['value']),
			"gid" => $gid,
			"disporder" => $disporder,
			"isdefault" => $setting['isdefault']
		);

		++$disporder;
		$db->insert_query("settings", $insert_array);
	}

	// Rebuild settings, setup existing template groups
	rebuild_settings();
	$existing_groups = array();

	$query = $db->simple_select("myn_templategroups", "prefix");
	while($group = $db->fetch_array($query))
	{
		$existing_groups[] = $group['prefix'];
	}

	// ... and then the template groups...
	foreach($template_groups as $prefix => $title)
	{
		// Check to make sure this isn't already installed previously
		if(in_array($prefix, $existing_groups))
		{
			continue;
		}

		// It doesn't exist, so install it...
		$db->insert_query("myn_templategroups", array("prefix" => $prefix, "title" => $title));
	}

	foreach($templates as $title => $template)
	{
		$insert_array = array(
			"title" => $title,
			"template" => $template,
			"sid" => "-2",
			"version" => $network->version_code,
			"status" => '',
			"dateline" => TIME_NOW
		);

		$db->insert_query("myn_templates", $insert_array);
	}

	require_once MYBB_ROOT.$mybb->config['admin_dir']."/inc/functions_themes.php";

	// Collect the themes
	$query = $db->simple_select("themes", "tid", "tid != '1'");
	while($theme_id = $db->fetch_array($query))
	{
		$themes[] = $theme_id['tid'];
	}

	foreach($stylesheets as $stylesheet => $content)
	{
		$query = $db->simple_select("themestylesheets", "*", "name = '".$stylesheet."'");
		if($db->num_rows($query) == 1)
		{
			// There is only one stylesheet, which will be the master (which we won't touch!)
			$orig_stylesheet = $db->fetch_array($query);
			
			// Make some quick changes to it...
			$orig_stylesheet['stylesheet'] .= ''.$content.'';
			$orig_stylesheet['lastmodified'] = TIME_NOW;

			foreach($themes as $theme_id)
			{
				// Copy the stylesheet to the themes
				$sid = copy_stylesheet_to_theme($orig_stylesheet, $theme_id);

				if(!cache_stylesheet($theme_id, $orig_stylesheet['name'], $orig_stylesheet['stylesheet']))
				{
					$db->update_query("themestylesheets", array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
				}
			}
		}
		else
		{
			// User has customized showthread.css stylesheets
			$count = 0;
			$style_count = $db->num_rows($query);
			while($orig_stylesheet = $db->fetch_array($query))
			{
				if($orig_stylesheet['tid'] == 1)
				{
					// Skips the master theme
					continue;
				}

				if(strpos($orig_stylesheet['stylesheet'], "post_rep") === false)
				{
					// Modify the stylesheet and update it in the database, as it doesn't exist here
					$orig_stylesheet['stylesheet'] .= $content;
					$orig_stylesheet['lastmodified'] = TIME_NOW;

					// Cache the stylesheet before we update it in the database...
					cache_stylesheet($orig_stylesheet['tid'], $orig_stylesheet['cachefile'], $orig_stylesheet['stylesheet']);
					$orig_stylesheet['stylesheet'] = $db->escape_string($orig_stylesheet['stylesheet']);
					$db->update_query("themestylesheets", $orig_stylesheet, "sid = '".$orig_stylesheet['sid']."'");

					++$count;
				}
			}
		}
	}
	
	foreach($themes as $theme_id)
	{
		update_theme_stylesheet_list($theme_id);
	}

	// Alter the users table to add in post_reputation
	if(!$db->field_exists("post_reputation", "posts"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."posts ADD post_reputation int(10) NOT NULL default 0");
	}

	// Alter the reputation table
	if(!$db->field_exists("viapr", "reputation"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."reputation ADD viapr int(1) NOT NULL default 0");
	}

	// Alter the forums table
	if(!$db->field_exists("postrep_cache", "forums"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."forums ADD postrep_cache text NOT NULL");

		$cache->update_forums();
	}

	// Almost there, setup the initial group permissions
	if(!$db->field_exists("postrep_giverep", "usergroups"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD postrep_giverep int(1) NOT NULL default 0");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD postrep_givenegrep int(1) NOT NULL default 0");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD postrep_inclosedthread int(1) NOT NULL default 0");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD postrep_affectmybbrep int(1) NOT NULL default 0");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD postrep_ns_minreppower int(1) NOT NULL default 1");

		// Go through each group and see if they can use reputation
		$groups = array();
		foreach($groupscache as $gid => $group)
		{
			if($group['usereputationsystem'] && $group['cangivereputations'])
			{
				$groups[] = "'".$group['gid']."'";
			}
		}

		if(!empty($groups))
		{
			$imp = implode(",", $groups);
			$db->write_query("UPDATE ".TABLE_PREFIX."usergroups SET postrep_giverep = '1' WHERE gid IN({$imp})");
		}

		$cache->update_usergroups();
	}

	// Finally, update the post_reputations for reputation posts
	$query = $db->simple_select("reputation", "pid, SUM(reputation) AS reputation", "pid > '0' GROUP BY pid");
	while($reputation = $db->fetch_array($query))
	{
		$db->write_query("UPDATE ".TABLE_PREFIX."posts SET post_reputation = '".intval($reputation['reputation'])."' WHERE pid = '".intval($reputation['pid'])."';");
	}
}

function postrep_do_uninstall()
{
	global $cache, $db, $mybb;

	if($db->field_exists("post_reputation", "posts"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."posts DROP post_reputation");
	}

	if($db->field_exists("postrep_cache", "forums"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."forums DROP postrep_cache");

		$cache->update_forums();
	}

	if($db->field_exists("viapr", "reputation"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."reputation DROP viapr");
	}

	if($db->field_exists("postrep_giverep", "usergroups"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP postrep_giverep");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP postrep_givenegrep");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP postrep_inclosedthread");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP postrep_affectmybbrep");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP postrep_ns_minreppower");

		$cache->update_usergroups();
	}

	// Remove templates
	$templates = array(
		"'misc_voting_list'",
		"'misc_voting_list_bit'",
		"'misc_voting_list_none'",
		"'postbit_reparea'",
		"'postbit_repplus'",
		"'postbit_repminus'",
		"'postbit_repremove'"
	);

	$exp = implode(",", $templates);
	$db->delete_query("myn_templates", "title IN (".$exp.")");

	// Remove templategroups if they are empty
	$query = $db->simple_select("myn_templates", "tid", "title LIKE 'postbit%'");
	if(!$db->num_rows($query))
	{
		$db->delete_query("myn_templategroups", "prefix = 'postbit'");
	}

	$query = $db->simple_select("myn_templates", "tid", "title LIKE 'misc%'");
	if(!$db->num_rows($query))
	{
		$db->delete_query("myn_templategroups", "prefix = 'misc'");
	}

	$db->delete_query("settings", "name LIKE 'postrep_%'");

	if($mybb->input['remove_file'] == 1)
	{
		// We're wanting to remove all files here...
		$file_list = array(
			"images/network/postrep/minus_post.png",
			"images/network/postrep/plus_post.png",
			"images/network/postrep/remove_post.png",
			"images/network/postrep/postbit_spinner.gif",
			"inc/network/languages/english/postrep.lang.php",
			"inc/network/core/javascript/postrep.js",
			"inc/network/core/m_postrep.php",
			"inc/network/core/handles/install_postrep.php",
			"inc/network/core/handles/upgrade_postrep.php",
			"inc/network/core/handles/admin_postrep.php",
			"inc/network/core/handles/xml_postrep.php"
		);

		foreach($file_list as $file)
		{
			if(is_file(MYBB_ROOT.$file))
			{
				@unlink(MYBB_ROOT.$file);
			}
		}
	}

	rebuild_settings();
}
?>