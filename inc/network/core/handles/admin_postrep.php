<?php
/*
+---------------------------------------------------------------------------
|   MyNetwork Core
|	|- Post Reputation ACP functions
|   =============================================
|   by Tom Moore (www.xekko.co.uk)
|   Copyright 2011 Mooseypx Design / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: $
+---------------------------------------------------------------------------
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function postrep_settings_save()
{
	global $mybb, $page;

	if($mybb->request_method == "post" && $mybb->input['upsetting'] && ($page->active_action == "settings" && $mybb->input['gid'] == 15 || $page->active_action == "modules" && $mybb->input['action'] == "edit"))
	{
		// Convert our postrep forums
		if(!isset($mybb->input['upsetting']['postrep_forums']) && !is_array($mybb->input['postrep_forums_select']))
		{
			$mybb->input['upsetting']['postrep_forums'] = '';
		}
		else if(is_array($mybb->input['postrep_forums_select']))
		{
			$mybb->input['upsetting']['postrep_forums'] = implode(",", $mybb->input['postrep_forums_select']);
		}
	}
}

function postrep_settings_remover($args)
{
	global $form, $lang, $mybb, $page;

	if($page->active_action != "settings" && $mybb->input['action'] != "change" && $page->active_action != "modules" && $mybb->input['action'] != "edit")
	{
		return false;
	}

	if(!$lang->postrep_post_reputation)
	{
		myn_lang("postrep");
		myn_lang("", true);
	}

	if($args['row_options']['id'] == "row_setting_postrep_enable" && $page->active_action == "settings")
	{
		// First of our row options. Swines!
		$args['this']->end();
		$args['this']->_title = $lang->postrep_post_reputation;
	}

	if($args['row_options']['id'] == "row_setting_postrep_forums")
	{
		// Here we cunningly replace the default 'text' version with a select box. Boom!
		preg_match("/value=\"[^A-Za-z]{1,}\"/", $args['content'], $values);
		$values = explode(",", str_replace(array("value", "\"", "="), "", $values[0]));

		// Now that we have our array of forums we've selected, generate the nice forum select box
		$args['content'] = $form->generate_forum_select("postrep_forums_select[]", $values, array("multiple" => true, "size" => "5\" style=\"width: 305px;"));
	}
}

function postrep_group_end()
{
	global $cache, $mybb, $updated_group, $usergroup;

	foreach(array('postrep_givenegrep', 'postrep_inclosedthread', 'postrep_affectmybbrep', 'postrep_ns_minreppower') as $setting)
	{
		$updated_group[$setting] = intval($mybb->input[$setting]);
	}

	// Some custom items
	$updated_group['postrep_giverep'] = $updated_group['cangivereputations'];

	// Have we selected to update forums with these settings?
	if($mybb->input['update_postrep_settings'])
	{
		$forum_cache = $cache->read("forums");

		$update = false;
		foreach($forum_cache as $forum)
		{
			if(!$forum['postrep_cache'])
			{
				// We can skip forums without cache, as they go off group settings anyway
				continue;
			}

			$update = true;
			$postrep_cache = unserialize($forum['postrep_cache']);
			$postrep_cache[$usergroup['gid']] = array(
				"postrep_giverep" => $updated_group['postrep_giverep'],
				"postrep_givenegrep" => $updated_group['postrep_givenegrep'],
				"postrep_inclosedthread" => $updated_group['postrep_inclosedthread'],
				"postrep_affectmybbrep" => $updated_group['postrep_affectmybbrep'],
				"postrep_ns_minreppower" => $updated_group['postrep_ns_minreppower']
			);

			$db->update_query("forums", array("postrep_cache" => serialize($postrep_cache)), "fid = '".$forum['fid']."'");
		}

		if($update == true)
		{
			$cache->update("forums");
		}
	}
}

function postrep_group_start($args)
{
	global $lang, $form, $form_container, $mybb, $page;

	if($args['title'] == $lang->warning_system && $page->active_action == "groups")
	{
		myn_lang("postrep");
		myn_lang("", true);

		// Add our container after the reputation system info
		$post_reputation_options = array(
			$form->generate_check_box("postrep_givenegrep", 1, $lang->postrep_can_givenegrep, array("checked" => $mybb->input['postrep_givenegrep'])),
			$form->generate_check_box("postrep_inclosedthread", 1, $lang->postrep_can_inclosedthread, array("checked" => $mybb->input['postrep_inclosedthread'])),
			$form->generate_check_box("postrep_affectmybbrep", 1, $lang->postrep_can_affectmybbrep, array("checked" => $mybb->input['postrep_affectmybbrep'])),
			"{$lang->points_to_award_take}<br /><small class=\"input\">{$lang->postrep_points_to_award_take_desc}</small><br />".$form->generate_text_box('postrep_ns_minreppower', $mybb->input['postrep_ns_minreppower'], array('id' => 'postrep_ns_minreppower', 'class' => 'field50'))."<br /><br />",
			$form->generate_check_box("update_postrep_settings", 1, $lang->postrep_update_settings, array("checked" => $mybb->input['update_postrep_settings']))
		);

		$form_container->output_row($lang->postrep_post_reputation_system, $lang->postrep_post_reputation_system_desc, "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $post_reputation_options)."</div>");
	}
}

function postrep_do_permissions()
{
	global $cache, $db, $lang, $mybb;

	if($mybb->request_method != "post" || $mybb->input['update'] != "post_reputation")
	{
		return false;
	}

	myn_lang("postrep");
	myn_lang("", true);

	$fid = intval($mybb->input['fid']);

	if($fid)
	{
		$forum = get_forum($fid);
	}

	// Are we saving custom permissions or are we setting default ones?
	if(isset($mybb->input['default']) && $mybb->input['default'] == 1)
	{
		// Setting default ones
		$db->update_query("forums", array("postrep_cache" => ''), "fid = '".$fid."'");
		$cache->update_forums();

		log_admin_action('quickpermissions', $fid, $forum['name']); // Act like quick permissions

		flash_message($lang->postrep_success_permissions_updated, 'success');
		admin_redirect("index.php?module=forum-management&fid={$fid}#tab_postrep_permissions");

		return true;
	}

	// Now that we've got the info, let's start saving our post reputation settings
	$perms = array();
	foreach($mybb->input as $id => $permission)
	{
		if(strpos($id, 'fields_') === false || (strpos($id, 'fields_default_') !== false || strpos($id, 'fields_inherit_') !== false))
		{
			continue;
		}

		list(, $gid) = explode('fields_postrep_', $id);

		if(strpos($permission, "_dragfield") !== false)
		{
			$permission = str_replace("_dragfield", "", $permission);
		}

		if($permission && !is_array($permission))
		{
			$permission = explode(',', $permission);
			$permission = array_flip($permission);

			foreach($permission as $name => $value)
			{
				$permission[$name] = 1;
			}
		}

		// Store our custom permissions
		if(is_array($permission) && $mybb->input["fields_inherit_postrep_{$gid}"] != 1)
		{
			$perms[$gid] = $permission;

			// Now store out reputation power for this forum
			if(isset($mybb->input['postrep_ns_minreppower_'.$gid]))
			{
				$perms[$gid]['postrep_ns_minreppower'] = intval($mybb->input['postrep_ns_minreppower_'.$gid]);
			}
		}
	}

	// Save our new permissions in the database
	$db->update_query("forums", array("postrep_cache" => serialize($perms)), "fid = '".$fid."'");
	$cache->update_forums();

	log_admin_action('quickpermissions', $fid, $forum['name']); // Act like quick permissions

	flash_message($lang->postrep_success_permissions_updated, 'success');
	admin_redirect("index.php?module=forum-management&fid={$fid}#tab_postrep_permissions");
}

function postrep_forum_form_end_container()
{
	global $cache, $db, $fid, $forum_cache, $groupscache, $lang, $mybb, $page;

	if($mybb->input['reset'])
	{
		$reset_gid = intval($mybb->input['reset']);

		if(!is_array($groupscache[$reset_gid]) || !$forum_cache[$fid]['postrep_cache'])
		{
			flash_message($lang->postrep_error_incorrect_reset, "error");
			admin_redirect("index.php?module=forum-management&fid={$fid}#tab_postrep_permissions");
		}

		$postrep_cache = unserialize($forum_cache[$fid]['postrep_cache']);

		if($postrep_cache[$reset_gid])
		{
			unset($postrep_cache[$reset_gid]);
			$db->update_query("forums", array("postrep_cache" => serialize($postrep_cache)), "fid = '".$fid."'");
			$cache->update_forums();

			admin_redirect("index.php?module=forum-management&fid={$fid}#tab_postrep_permissions");
		}
	}

	// Start Custom Settings - draggable fields? Ohhh yes.
	$default_settings = '';
	foreach($groupscache as $group)
	{
		if($group['gid'] == 1)
		{
			continue;
		}

		if(!is_array($default_settings))
		{
			// Build default permissions array
			foreach($group as $g_setting => $g_value)
			{
				if(strpos($g_setting, "postrep_") !== false && strpos($g_setting, "postrep_ns_") === false)
				{
					$default_settings[$g_setting] = $lang->$g_setting;

					$drag_string = $g_setting."_drag";
					$default_settings_drag[$g_setting] = $lang->$drag_string;
				}
			}

			$d_settings = array_keys($default_settings);
		}

		$usergroups[$group['gid']] = array(
			"gid" => $group['gid'],
			"title" => $group['title'],
			"postrep_ns_minreppower" => $group['postrep_ns_minreppower']
		);

		foreach($d_settings as $setting)
		{
			$usergroups[$group['gid']][$setting] = $group[$setting];
		}
	}

	// See if we have any existing permissions for this group and this forum
	$default_check = true;
	$permissions_cache = '';
	if(is_array($forum_cache[$fid]) && $forum_cache[$fid]['postrep_cache'] != '')
	{
		$default_check = false;
		$permissions_cache = unserialize($forum_cache[$fid]['postrep_cache']);
	}

	echo "<div id=\"tab_postrep_permissions\">\n";
	$form = new Form("index.php?module=forum-management", "post", "management");

	$form_container = new FormContainer($lang->postrep_permissions_for_postrep);
	$form_container->output_row($lang->postrep_use_default_settings, $lang->postrep_use_default_settings_desc,  $form->generate_yes_no_radio('default', (int)$default_check, true, array("class" => "settings_default", "id" => "settings_default_on"), array("class" => "settings_default", "id" => "settings_default_off")), 'default', array('colspan' => 3));

	$form_container->output_row_header($lang->permissions_group, array("class" => "align_center", 'style' => 'width: 30%'));
	$form_container->output_row_header($lang->overview_allowed_actions, array("class" => "align_center"));
	$form_container->output_row_header($lang->overview_disallowed_actions, array("class" => "align_center"));

	// For guests, show the error #36
	$form_container->output_cell($lang->postrep_guests_cant_vote, array("colspan" => 3));
	$form_container->construct_row(array('id' => 'row_postrep_1'));

	$peeker_string = '';	
	foreach($usergroups as $usergroup)
	{
		$perms = array();
		if(is_array($permissions_cache) && $permissions_cache[$usergroup['gid']])
		{
			$perms = $permissions_cache[$usergroup['gid']];
			$default_checked = false;
		}
		else
		{
			// We need to revert to the default settings
			$perms = $usergroup;
			$default_checked = true;
		}

		foreach($default_settings as $permission => $title)
		{
			$perms_checked[$permission] = 0;
			if($perms[$permission] == 1)
			{
				$perms_checked[$permission] = 1;
			}
		}

		$rep_power = '
<div style="margin: 5px 15px;">
'.$lang->postrep_reputation_power.' '.$form->generate_text_box("postrep_ns_minreppower_{$usergroup['gid']}", $perms['postrep_ns_minreppower'], array("id" => "name", "style" => "width: 25px;")).'
</div>';

		$default_string = $lang->postrep_group_default;
		if($default_checked === false)
		{
			$default_string = $lang->postrep_group_custom." - <a href=\"index.php?module=forum-management&amp;fid={$fid}&amp;reset={$usergroup['gid']}#tab_postrep_permissions\">{$lang->postrep_reset}</a>";
		}

		$form_container->output_cell("<strong>{$usergroup['title']}</strong> <small style=\"vertical-align: middle;\">(GID #{$usergroup['gid']})</small>
		<br /><small>".$default_string."</small>".$rep_power);

		$field_select = "<div class=\"quick_perm_fields\">\n";
		$field_select .= "<div class=\"enabled\"><div class=\"fields_title\">{$lang->enabled}</div><ul id=\"fields_enabled_postrep_{$usergroup['gid']}\">\n";

		foreach($perms_checked as $perm => $value)
		{
			if($value == 1)
			{
				$field_select .= "<li id=\"field-{$perm}_dragfield\">{$default_settings_drag[$perm]}</li>";
			}
		}

		$field_select .= "</ul></div>\n";
		$field_select .= "<div class=\"disabled\"><div class=\"fields_title\">{$lang->disabled}</div><ul id=\"fields_disabled_postrep_{$usergroup['gid']}\">\n";
		
		foreach($perms_checked as $perm => $value)
		{
			if($value == 0)
			{
				$field_select .= "<li id=\"field-{$perm}_dragfield\">{$default_settings_drag[$perm]}</li>";
			}
		}

		$field_select .= "</ul></div></div>\n";
		$field_select .= $form->generate_hidden_field("fields_postrep_".$usergroup['gid'], @implode(",", @array_keys($perms_checked, '1')), array('id' => 'fields_postrep_'.$usergroup['gid']));
		$field_select .= $form->generate_hidden_field("fields_inherit_postrep_".$usergroup['gid'], (int)$default_checked, array('id' => 'fields_inherit_postrep_'.$usergroup['gid']));
		$field_select .= $form->generate_hidden_field("fields_default_postrep_".$usergroup['gid'], @implode(",", @array_keys($perms_checked, '1')), array('id' => 'fields_default_postrep_'.$usergroup['gid']));
		$field_select = str_replace("'", "\\'", $field_select);
		$field_select = str_replace("\n", "", $field_select);

		$field_select = "<script type=\"text/javascript\">
//<![CDATA[
document.write('".str_replace("/", "\/", $field_select)."');
//]]>
</script>\n";

		$field_selected = array();
		foreach($default_settings as $forum_permission => $permission_title)
		{
			$field_options[$forum_permission] = $permission_title;
			if($perms_checked[$forum_permission])
			{
				$field_selected[] = $forum_permission;
			}
		}

		$field_select .= "<noscript>".$form->generate_select_box('postrepfields_'.$usergroup['gid'].'[]', $field_options, $field_selected, array('id' => 'postrepfields_'.$usergroup['gid'].'[]', 'multiple' => true))."</noscript>\n";
		$form_container->output_cell($field_select, array('colspan' => 2));

		$form_container->construct_row(array('id' => 'row_postrep_'.$usergroup['gid']));
		$ids[] = "postrep_".$usergroup['gid'];

		// Make a peeker string for this group
		$peeker_string .= '
		new Peeker($$(".settings_default"), $("row_postrep_'.$usergroup['gid'].'"), /0/, true);';
	}

	$form_container->end();

	$buttons = array();
	$buttons[] = $form->generate_submit_button($lang->postrep_update_permissions);
	$form->output_submit_wrapper($buttons);

	echo $form->generate_hidden_field("fid", $fid);
	echo $form->generate_hidden_field("update", "post_reputation");
	$form->end();
	echo "</div>";

	echo "<script type=\"text/javascript\">\n<!--\n";
	foreach($ids as $id)
	{
		echo "Event.observe(window, 'load', function(){ QuickPermEditor.init('".$id."') });\n";
	}

	echo "// -->\n</script>\n";

	// Some nice 'peek' effects
	echo '<script type="text/javascript" src="./jscripts/peeker.js"></script>
<script type="text/javascript">
<!--
	Event.observe(window, "load", function() {
		loadPeekers();		
	});

	function loadPeekers()
	{
		new Peeker($$(".settings_default"), $("row_postrep_1"), /0/, true);
		'.$peeker_string.'

		return;
	}
//-->
</script>';
}

function postrep_forum_tabs_start($tabs)
{
	global $fid, $lang, $page, $plugins;

	if($page->active_action != "management" || !$fid || $mybb->input['action'])
	{
		return false;
	}

	if(!isset($lang->postrep_tab_permission))
	{
		myn_lang("postrep");
		myn_lang("", true);
	}

	$sub_tabs = array_slice($tabs, 0, 2, true);
	$sub_tabs['postrep_permissions'] = $lang->postrep_tab_permissions;

	$tabs = array_merge($sub_tabs, $tabs);

	// Add our hook for later
	$plugins->add_hook("admin_page_output_footer", "postrep_forum_form_end_container", "postrep");
}
?>