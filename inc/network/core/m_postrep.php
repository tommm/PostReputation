<?php
/*
+---------------------------------------------------------------------------
|   MyNetwork Post Reputation
|   =============================================
|   by Tom Moore (www.xekko.co.uk)
|   Copyright 2011 Mooseypx Design / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > Version 1.1.0
+---------------------------------------------------------------------------
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if(!in_array(THIS_SCRIPT, array("misc.php", "newreply.php", "reputation.php", "showthread.php", "xmlhttp.php")) && !defined("IN_ADMINCP"))
{
	// We're not interested in this feature
	return false;
}

if(defined("IN_ADMINCP"))
{
	// Strangely faster here? Check it out for MyBB Core #26
	// Hooks for the Forum settings
	$plugins->add_hook("admin_forum_management_start", "postrep_do_permissions", "postrep", MYBB_ROOT."inc/network/core/handles/admin_postrep.php");
	$plugins->add_hook("admin_page_output_tab_control_start", "postrep_forum_tabs_start", "postrep", MYBB_ROOT."inc/network/core/handles/admin_postrep.php");

	// Group Settings
	$plugins->add_hook("admin_formcontainer_output_row", "postrep_group_start", "postrep", MYBB_ROOT."inc/network/core/handles/admin_postrep.php");
	$plugins->add_hook("admin_user_groups_edit_commit", "postrep_group_end", "postrep", MYBB_ROOT."inc/network/core/handles/admin_postrep.php");

	// Setting Settings
	$plugins->add_hook("myn_global", "postrep_settings_save", "postrep", MYBB_ROOT."inc/network/core/handles/admin_postrep.php");
	$plugins->add_hook("admin_config_settings_change", "postrep_settings_save", "postrep", MYBB_ROOT."inc/network/core/handles/admin_postrep.php");
	$plugins->add_hook("admin_formcontainer_output_row", "postrep_settings_remover", "postrep", MYBB_ROOT."inc/network/core/handles/admin_postrep.php");
}

if($mybb->settings['enablereputation'])
{
	switch(THIS_SCRIPT)
	{
		case "misc.php":
			if($mybb->input['action'] == "load_voters")
			{
				$plugins->add_hook("misc_start", "postrep_xmlhttp_voters", "postrep", MYBB_ROOT."inc/network/core/handles/xml_postrep.php");
			}
			break;
		case "newreply.php":
			if($mybb->input['ajax'])
			{
				$plugins->add_hook("postbit", "postrep_quick_reply", "postrep");
			}
			break;
		case "reputation.php":
			$plugins->add_hook("reputation_start", "postrep_reputation_start", "postrep");
			$plugins->add_hook("reputation_end", "postrep_reputation_end", "postrep");
			$plugins->add_hook("reputation_do_add_process", "postrep_process_mybb", "postrep");
			$plugins->add_hook("reputation_do_add_end", "postrep_process_mybb_end", "postrep");
			break;
		case "xmlhttp.php":		
			switch($mybb->input['action'])
			{
				case "post_reputation":
					$plugins->add_hook("myn_global", "postrep_xmlhttp", "postrep", MYBB_ROOT."inc/network/core/handles/xml_postrep.php");
					break;
				case "remove_reputation":
					$plugins->add_hook("myn_global", "postrep_xmlhttp_remove", "postrep", MYBB_ROOT."inc/network/core/handles/xml_postrep.php");
					break;
			}
			break;
		default:
			global $network;
			$network->storage['postrep']['forums'] = array();
			$template_cache = "postbit_reparea,postbit_repminus,postbit_repplus,postbit_repremove";

			if($mybb->settings['postrep_forums'])
			{
				$network->storage['postrep']['forums'] = explode(",", $mybb->settings['postrep_forums']);
			}

			$plugins->add_hook("showthread_start", "postrep_start", "postrep");
			$plugins->add_hook("showthread_end", "postrep_end", "postrep");
			$plugins->add_hook("postbit", "postrep_reply", "postrep");
			break;
	}

	if($template_cache)
	{
		global $db, $templates;
		$templates->myn_cache($db->escape_string($template_cache));
	}
}

function postrep_info()
{
	return array(
		"name"			=> "Post Reputation",
		"description"	=> "This feature enables a Post Reputation system that allows voting on a post. This is similar to IPB3's post voting system.",
		"website"		=> "http://xekko.co.uk",
		"author"		=> "Tomm",
		"version"		=> "1.1.3"
	);
}

function postrep_install()
{
	require_once MYBB_ROOT."inc/network/core/handles/install_postrep.php";

	postrep_do_install();
}

function postrep_uninstall()
{
	require_once MYBB_ROOT."inc/network/core/handles/install_postrep.php";

	postrep_do_uninstall();
}

function postrep_is_installed()
{
	global $db;

	if($db->field_exists("viapr", "reputation"))
	{
		return true;
	}

	return false;
}

function postrep_start()
{
	global $db, $fid, $headerinclude, $lang, $mybb, $network, $templates;

	if(!in_array($fid, $network->storage['postrep']['forums']))
	{
		return false;
	}

	// We need to add a custom language variable into the headerinclude for failed AJAX requests
	myn_lang("postrep");
	myn_lang("", true);
	$headerinclude = str_replace("var imagepath", "var failed_xmlhttprequest = '".$lang->failed_xmlhttprequest."';\n\tvar imagepath", $headerinclude);

	// Here, we need to include our custom javascript
	$headerinclude .= '<script type="text/javascript" src="'.$mybb->settings['bburl'].'/inc/network/core/javascript/postrep.js?ver=1200"></script>';

	$network->storage['postrep']['repped_posts'] = array();
	$query = $db->simple_select("reputation", "pid, adduid");

	while($post = $db->fetch_array($query))
	{
		$network->storage['postrep']['posts'][$post['pid']][] = $post['adduid'];
	}

	// Bring ignored users back to life
	$GLOBALS['ignore_string'] = $lang->postbit_currently_ignoring_user;

	// Fancy inline reputation updating. Boo-yah.
	$templates->cache['postbit_reputation'] = str_replace("{\$post['userreputation']}", "<span class=\"user_{\$post['uid']}_rep\">{\$post['userreputation']}</span>", $templates->cache['postbit_reputation']);
}

function postrep_end()
{
	global $db, $fid, $lang, $mybb, $network, $pids, $posts, $templates, $thread;

	// Make sure this forum can even use post reputation
	if(!in_array($fid, $network->storage['postrep']['forums']))
	{
		return false;
	}

	$clean_pids = explode(",", str_replace(array("pid IN(", "'", ")"), "", $pids));

	// Grab the postrep data
	$postrep_cache = array();
	$query = $db->simple_select("posts", "pid, uid, username, post_reputation, visible", $pids);
	while($post = $db->fetch_array($query))
	{
		if(!$post['post_reputation'])
		{
			$post['post_reputation'] = 0;
		}

		$postrep_cache[$post['pid']] = array(
			"pid" => $post['pid'],
			"uid" => $post['uid'],
			"username" => $post['username'],
			"visible" => $post['visible'],
			"rep" => $post['post_reputation']
		);
	}

	$permissions = postrep_can_postrep(0, $fid);

	$vote = true;
	if(!$mybb->user['uid'] || $thread['closed'] == 1 && $permissions['postrep_inclosedthread'] != 1)
	{
		$vote = false;
	}

	$search = $replace = array();
	foreach($clean_pids as $pid)
	{
		$about_title = $lang->sprintf($lang->about_postrep_dyn, $postrep_cache[$pid]['username']);
		$rep_area = $plus_link = $minus_link = '';
		$uid = $postrep_cache[$pid]['uid'];

		// An array of users that have repped this post
		$storage = $network->storage['postrep']['posts'][$pid];
		if(!$storage)
		{
			$storage = array();
		}

		$visibility = true;
		if($postrep_cache[$pid]['visible'] == 0)
		{
			// We shouldn't rate invisible posts
			$visibility = false;
		}

		// Has this user already voted on this post?
		if($vote == true && $visibility == true && $mybb->user['uid'] && $mybb->user['uid'] != $postrep_cache[$pid]['uid'] && $permissions['postrep_giverep'] == 1 && in_array($mybb->user['uid'], $storage))
		{
			// That's a yes then - show a remove link
			eval("\$plus_link = \"".$templates->myn_get("postbit_repremove")."\";");
		}
		else if($vote == true && $visibility == true && $mybb->user['uid'] && $mybb->user['uid'] != $postrep_cache[$pid]['uid'] && $permissions['postrep_giverep'] == 1 && !in_array($mybb->user['uid'], $storage))
		{
			// User can vote, just hasn't bothered yet - but can they negative rep?
			if($permissions['postrep_givenegrep'])
			{
				eval("\$minus_link = \"".$templates->myn_get("postbit_repminus")."\";");
			}

			eval("\$plus_link = \"".$templates->myn_get("postbit_repplus")."\";");
		}

		// Now for the box
		$id_style = "none";
		if($postrep_cache[$pid]['rep'] > 0)
		{
			$id_style = "plus";
		}
		elseif($postrep_cache[$pid]['rep'] < 0)
		{
			$id_style = "minus";
		}

		if($mybb->user['uid'])
		{
			$reputation_link = "<a href=\"javascript:;\" onclick=\"MyBB.popupWindow('{$mybb->settings['bburl']}/misc.php?action=load_voters&amp;pid={$pid}', 'voters', 350, 350)\"><strong><span id=\"{$pid}_rep\">".$postrep_cache[$pid]['rep']."</span></strong></a>";
		}
		else
		{
			$reputation_link = "<strong>".$postrep_cache[$pid]['rep']."</strong>";
		}

		eval("\$rep_area = \"".$templates->myn_get("postbit_reparea")."\";");
		$search_array[] = "<!--REP_AREA_".$pid."-->";
		$replace_array[] = $rep_area;
	}

	// Switch arrays...
	$posts = str_replace($search_array, $replace_array, $posts);

	// Do we have xThreads installed?
	if(isset($GLOBALS['first_post']))
	{
		$GLOBALS['first_post'] = str_replace($search_array, $replace_array, $GLOBALS['first_post']);
	}
}

function postrep_reputation_start()
{
	global $mybb, $templates;

	if($mybb->input['action'])
	{
		return false;
	}

	$templates->cache['reputation_vote'] = str_replace("<tr>", "<tr id=\"reputation_{\$reputation_vote['rid']}\">", $templates->cache['reputation_vote']);
}

function postrep_reputation_end()
{
	// Because our non-mybb affecting users' info is stored in the neutral
	// comments, we need to change lots of things here
	global $conditions, $db, $lang, $multipage, $mybb, $neutral_count, $neutral_week, $neutral_month, $neutral_6months, $order, $parser, $start, $reputation_count, $reputation_votes, $templates, $total_reputation, $rep_post_count, $user;

	myn_lang("postrep");
	myn_lang("", true);

	$total_reps = $rep_post_count + ($total_reputation - $rep_post_count);
	$query = $db->query("
		SELECT r.*, r.uid AS rated_uid, u.username, u.reputation AS user_reputation, u.usergroup AS user_usergroup, u.displaygroup AS user_displaygroup
		FROM ".TABLE_PREFIX."reputation r
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=r.adduid)
		WHERE r.uid='{$user['uid']}' AND r.viapr != 0 $conditions
		ORDER BY $order
		LIMIT $start, {$mybb->settings['repsperpage']}
	");

	if(!$db->num_rows($query))
	{
		return false;
	}

	$last_week = TIME_NOW-604800;
	$last_month = TIME_NOW-2678400;
	$last_6months = TIME_NOW-16070400;

	$reputation_parser = array(
		"allow_html" => 0,
		"allow_mycode" => 0,
		"allow_smilies" => 1,
		"allow_imgcode" => 0
	);

	$find = array();
	$n_rep = array("total" => 0, "week" => 0, "month" => 0, "6months" => 0);
	$string = $lang->sprintf($lang->postrep_unaffected_reputation, $user['username']);
	while($reputation_vote = $db->fetch_array($query))
	{
		++$n_rep['total'];

		// First, figure out how long ago this was
		if($reputation_vote['dateline'] >= $last_week)
		{
			++$n_rep['week'];
		}
		if($reputation_vote['dateline'] >= $last_month)
		{
			++$n_rep['month'];
		}
		if($reputation_vote['dateline'] >= $last_6months)
		{
			++$n_rep['6months'];
		}

		$find[] = $reputation_vote['rid'];

		if($reputation_vote['adduid'] == 0)
		{
			$reputation_vote['user_reputation'] = 0;
		}

		$reputation_vote['user_reputation'] = get_reputation($reputation_vote['user_reputation'], $reputation_vote['adduid']);

		// Format the username of this poster
		if(!$reputation_vote['username'])
		{
			$reputation_vote['username'] = $lang->na;
			$reputation_vote['user_reputation'] = '';
		}
		else
		{
			$reputation_vote['username'] = format_name($reputation_vote['username'], $reputation_vote['user_usergroup'], $reputation_vote['user_displaygroup']);
			$reputation_vote['username'] = build_profile_link($reputation_vote['username'], $reputation_vote['uid']);
			$reputation_vote['user_reputation'] = "({$reputation_vote['user_reputation']})";
		}

		// Now, create a template for our replacement
		$vote_reputation = intval($reputation_vote['viapr']);

		// This is a negative reputation
		if($vote_reputation > 0)
		{
			$vote_reputation = "+{$vote_reputation}";
			$status_class = "trow_reputation_positive";
			$vote_type_class = "reputation_positive";
			$vote_type = $lang->positive;
		}
		if($vote_reputation < 0)
		{
			$status_class = "trow_reputation_negative";
			$vote_type_class = "reputation_negative";
			$vote_type = $lang->negative;
		}
		// This is a neutral reputation
		else if($vote_reputation == 0)
		{
			$status_class = "trow_reputation_neutral";
			$vote_type_class = "reputation_neutral";
			$vote_type = $lang->neutral;
		}

		$vote_reputation = "({$vote_reputation})";

		// Format the date this reputation was last modified
		$last_updated_date = my_date($mybb->settings['dateformat'], $reputation_vote['dateline']);
		$last_updated_time = my_date($mybb->settings['timeformat'], $reputation_vote['dateline']);
		$last_updated = $lang->sprintf($lang->last_updated, $last_updated_date, $last_updated_time);

		$link = "<a href=\"".get_post_link($reputation_vote['pid'])."#pid{$reputation_vote['pid']}\">{$lang->postrep_post}".$reputation_vote['pid']."</a>";
		$postrep_given = $lang->sprintf($lang->postrep_given, $link).$string;

		// Does the current user have permission to delete this reputation? Show delete link
		$delete_link = '';
		if($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['issupermod'] == 1 || ($mybb->usergroup['cangivereputations'] == 1 && $reputation_vote['adduid'] == $mybb->user['uid'] && $mybb->user['uid'] != 0))
		{
			$delete_link = "[<a href=\"reputation.php?action=delete&amp;uid={$reputation_vote['rated_uid']}&amp;rid={$reputation_vote['rid']}\" onclick=\"MyBB.deleteReputation({$reputation_vote['rated_uid']}, {$reputation_vote['rid']}); return false;\">{$lang->delete_vote}</a>]";
		}

		if($reputation_vote['comments'] == '')
		{
			$reputation_vote['comments'] = $lang->no_comment;
		}
		else
		{
			$reputation_vote['comments'] = $parser->parse_message($reputation_vote['comments'], $reputation_parser);
		}

		eval("\$replace[{$reputation_vote['rid']}] = \"".$templates->get("reputation_vote")."\";");
	}

	if(is_array($find))
	{
		// We have post reputations on our page!
		$neutral_count = $neutral_count - $n_rep['total'];
		$neutral_week = $neutral_week - $n_rep['week'];
		$neutral_month = $neutral_month - $n_rep['month'];
		$neutral_6months = $neutral_6months - $n_rep['6months'];

		foreach($find as $rid)
		{
			$reputation_votes = str_replace("<tr id=\"reputation_{$rid}\">", $replace[$rid]."\n<tr id=\"reputation_orig_{$rid}\" style=\"display: none;\">", $reputation_votes);
		}
	}
}

function postrep_quick_reply(&$post)
{
	global $mybb, $templates;

	// Forum has rep posts?
	if(!in_array($post['fid'], explode(",", $mybb->settings['postrep_forums'])))
	{
		return false;
	}

	// Because we're in quick reply, this is our only chance to add in the reputation
	$id_style = "none";

	// Figure out the rep link?
	if($mybb->user['uid'])
	{
		$reputation_link = "<a href=\"javascript:;\" onclick=\"MyBB.popupWindow('".$mybb->settings['bburl']."/inc/network/misc.php?action=load_voters&amp;pid=".$post['pid']."', 'voters', 350, 350)\"><strong><span id=\"".$post['pid']."_rep\">0</span></strong></a>";
	}
	else
	{
		$reputation_link = "<strong>0</strong>";
	}

	eval("\$rep_area = \"".$templates->myn_get("postbit_reparea")."\";");

	// Alter the templates to input our rep_area - sadly, we have no idea where it will be... :(
	foreach($post as $piece => $cake)
	{
		$post[$piece] = str_replace("<!--REP_AREA_".$post['pid']."-->", $rep_area, $cake);
	}

	// Perhaps it's in the postbit template?
	$templates->cache['postbit'] = str_replace("<!--REP_AREA_{\$post['pid']}-->", $rep_area, $templates->cache['postbit']);
}

function postrep_reply(&$post)
{
	global $ignored_users, $lang, $mybb, $network, $templates;
	static $postrep_ignored;

	// Forum has rep posts?
	if(!in_array($post['fid'], $network->storage['postrep']['forums']))
	{
		return false;
	}

	if(!$ignored_users[$post['uid']] && !isset($postrep_ignored[$post['uid']]))
	{
		// This user is being ignored by me...
		$postrep_ignored[$post['uid']] = 1;
	}

	// Hide this post?
	if($mybb->settings['postrep_impose_limit'] != 0 && $mybb->settings['postrep_impose_limit'] < 0 && $post['post_reputation'] < $mybb->settings['postrep_impose_limit'])
	{
		// This is like, unbelievabley cheating...
		$ignored_users[$post['uid']] = 1;
		if(!$lang->postbit_currently_ignoring_post)
		{
			$lang->postbit_currently_ignoring_post = "The contents of this message are hidden because of its low reputation.";
		}

		$lang->postbit_currently_ignoring_user = $lang->postbit_currently_ignoring_post;
	}
	else
	{
		if($ignored_users[$post['uid']] && $postrep_ignored[$post['uid']])
		{
			// This user isn't ignored, but has had a post hit the minus limit
			$ignored_users[$post['uid']] = 0;
		}

		$lang->postbit_currently_ignoring_user = $GLOBALS['ignore_string'];
	}
}

function postrep_process_mybb_end()
{
	global $db, $reputation;

	$query = $db->simple_select("reputation", "SUM(reputation)+SUM(viapr) AS 'reputation'", "pid = '".$reputation['pid']."'");
	$post_rep = intval($db->fetch_field($query, "reputation"));

	$db->update_query("posts", array("post_reputation" => $post_rep), "pid = '".$reputation['pid']."'");
}

function postrep_process_mybb()
{
	global $db, $headerinclude, $existing_reputation, $existing_post_reputation, $lang, $mybb, $reputation, $templates;

	if(!$existing_post_reputation)
	{
		// We're only interested in post reputations
		return false;
	}

	myn_lang("postrep");
	myn_lang("", true);

	$query = $db->simple_select("posts", "fid", "pid = '".$existing_post_reputation['pid']."'");
	$fid = $db->fetch_field($query, "fid");

	$permissions = postrep_can_postrep(0, $fid);

	// Let's make sure this user can do what they're doing
	if($reputation['reputation'] < 0 && !$permissions['postrep_givenegrep'])
	{
		$show_back = 1;
		$message = $lang->postrep_no_negative_comment;

		eval("\$error = \"".$templates->get("reputation_add_error")."\";");
		output_page($error);
		exit;
	}

	if(!$permissions['postrep_affectmybbrep'])
	{
		// This user can't affect MyBB reputation here
		$reputation['viapr'] = $reputation['reputation'];
		$reputation['reputation'] = 0;
	}
	else
	{
		// Are we going for neutral reputation? If so, don't count it as a post rep
		if($reputation['reputation'] == 0 && $existing_post_reputation['reputation'] != 0)
		{
			$reputation['viapr'] = 0;
		}
	}
}

function postrep_can_postrep($user=0, $fid)
{
	global $cache, $db, $groupscache, $mybb;

	if(!$user && $mybb->user['uid'])
	{
		$user = $mybb->user; // We should only ever really be getting this...
	}
	else if($user > 0)
	{
		// Retrieve info from the database
		$query = $db->simple_select("users", "usergroup, additionalgroups", "uid = '".intval($user)."'");
		$user = $db->fetch_array($query);
	}

	if(!$user || $user == 0)
	{
		// Guests can't use this
		return array();
	}

	$f_cache = $cache->read("forums");

	// Does this user have additional groups?
	$groups[] = $user['usergroup'];
	if($user['additionalgroups'])
	{
		$additional_groups = explode(",", $user['additionalgroups']);

		foreach($additional_groups as $group)
		{
			if($group != $groups[0])
			{
				$groups[] = $group;
			}
		}
	}

	// Attempt to gather a list of custom permissions
	$postrep_permcache = array();
	foreach($f_cache as $forum)
	{
		if(!$forum['postrep_cache'])
		{
			continue;
		}

		$p_cache = unserialize($forum['postrep_cache']);
		foreach($p_cache as $group => $permission)
		{
			$postrep_permcache[$forum['fid']][$group] = $permission;
		}
	}

	// Now go through each group and find their post reputation permissions
	// Classic MyBB permissions tree
	foreach($groups as $group)
	{
		if($groupscache[$group])
		{
			$level_permissions = $postrep_permcache[$fid][$group];

			if(empty($level_permissions))
			{
				$parents = explode(',', $f_cache[$fid]['parentlist']);
				rsort($parents);

				if(!empty($parents))
				{
					foreach($parents as $parent_id)
					{
						if(!empty($postrep_permcache[$parent_id][$group]))
						{
							$level_permissions = $postrep_permcache[$parent_id][$group];
							break;
						}
					}

					if(empty($level_permissions))
					{
						$level_permissions = $groupscache[$group];
					}
				}
			}

			foreach($level_permissions as $permission => $access)
			{
				if(strpos($permission, "postrep_") === false)
				{
					continue;
				}

				if($access >= $current_permissions[$permission] || !$current_permissions[$permission])
				{
					$current_permissions[$permission] = $access;
				}
			}
		}
	}

	return $current_permissions;
}
?>