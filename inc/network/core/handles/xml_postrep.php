<?php
/*
+---------------------------------------------------------------------------
|   MyNetwork Post Reputation
|	|- Post Reputation XMLHTTP procedures
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

function postrep_xmlhttp_voters()
{
	global $charset, $db, $headerinclude, $lang, $mybb, $network, $templates, $theme;

	// Check important things first
	myn_lang("postrep");
	myn_lang("", true);

	// Quickly get the username of the poster
	if(is_moderator() === false)
	{
		// If this post is not visible, and they aren't a mod, s-mod or admin, then hide it
		$sql = ' AND visible = "1"';
	}

	$query = $db->simple_select("posts", "username, post_reputation", "pid = '".intval($mybb->input['pid'])."'{$sql}");
	$post = $db->fetch_array($query);

	$post_author = $post['username'];
	$post_reputation = $post['post_reputation'];

	$lang->voters_list = $lang->sprintf($lang->voters_list, htmlspecialchars_uni($post_author));

	if(!$post_author)
	{
		//echo $headerinclude; // For styling
		error($lang->failed_no_post);
		exit;
	}

	// Some extra SQL?
	if($mybb->settings['postrep_show_plus_votes'] == 1 || $mybb->settings['postrep_show_minus_votes'] == 1)
	{
		// We want a bit more info if we're showing a list of users...
		$query = $db->query("
			SELECT r.*, u.username, u.usergroup, u.displaygroup
			FROM ".TABLE_PREFIX."reputation r
			LEFT JOIN ".TABLE_PREFIX."users u ON (r.adduid = u.uid)
			WHERE r.pid = '".intval($mybb->input['pid'])."'
			ORDER BY r.reputation DESC
		");
	}
	else
	{
		// Just simple numbers needed...
		$options = array(
			"order_by" => "rep",
			"order_dir" => "DESC"
		);

		$query = $db->simple_select("reputation", "*", "pid = '".intval($mybb->input['pid'])."'");
	}

	while($rep_post = $db->fetch_array($query))
	{
		if($rep_post['viapr'] != 0)
		{
			// Post Rep doesn't affect MyBB Rep
			if($rep_post['viapr'] > 0)
			{
				$plus_rep[] = $rep_post;
				continue;
			}

			$minus_rep[] = $rep_post;
		}
		else
		{
			// We need to figure out who's voted positively for this
			if($rep_post['reputation'] > 0)
			{
				$plus_rep[] = $rep_post;
				continue;
			}

			$minus_rep[] = $rep_post;
		}
	}

	// Now, create a list for those people who were nice...
	if(is_array($plus_rep))
	{
		// Count, and create correct language
		$rep_count = 0;
		$like_users = count($plus_rep);
		if($like_users == 1)
		{
			// "1 person..."
			$lang->people_who_like = $lang->sprintf($lang->person_who_like, "1");
		}
		else
		{
			// "x people..."
			$lang->people_who_like = $lang->sprintf($lang->people_who_like, my_number_format($like_users));
		}

		if($mybb->settings['postrep_show_plus_votes'] == 1)
		{
			$count = 0;
			$user_list = '';
			foreach($plus_rep as $rep)
			{
				// Count Reputation
				if($rep['viapr'] != 0)
				{
					$rep_count = $rep_count + $rep['viapr'];
				}
				else
				{
					$rep_count = $rep_count + $rep['reputation'];
				}

				// Format user profile link
				$format_name = format_name(htmlspecialchars_uni($rep['username']), $rep['usergroup'], $rep['displaygroup']);
				$profile_link = get_profile_link($rep['adduid']);
				$user_list .= '<a href="'.$mybb->settings['bburl'].'/'.$profile_link.'" target="_blank" onclick="window.opener.location.href=this.href; return false;">'.$format_name.'</a>';

				if($count+1 != $like_users)
				{
					$user_list .= $lang->postrep_vote_sep." ";
					++$count;
				}
			}
		}

		$rep_class = "rep_none";
		if($rep_count > 0)
		{
			$rep_class = "rep_plus";
		}
		elseif($rep_count < 0)
		{
			$rep_class = "rep_minus";
		}

		$user_list = '<div class="rep_user_list">'.$user_list.'</div>';
		eval("\$plus_users = \"".$templates->myn_get("misc_voting_list_bit")."\";");
	}

	// Finally, create a list for those people who are nasty pasties...
	if(is_array($minus_rep))
	{
		$user_list = '';
		$rep_count = 0;
		$dislike_users = count($minus_rep);
		if($dislike_users == 1)
		{
			$lang->people_who_like = $lang->sprintf($lang->person_who_dislike, "1");
		}
		else
		{
			$lang->people_who_like = $lang->sprintf($lang->people_who_dislike, my_number_format($dislike_users));
		}

		// Are we showing the users that have minus voted?
		if($mybb->settings['postrep_show_minus_votes'] == 1)
		{
			$count = 0;
			$user_list = '';
			foreach($minus_rep as $rep)
			{
				// Count Reputation
				if($rep['viapr'] != 0)
				{
					$rep_count = $rep_count + $rep['viapr'];
				}
				else
				{
					$rep_count = $rep_count + $rep['reputation'];
				}

				// Format user profile link
				$format_name = format_name(htmlspecialchars_uni($rep['username']), $rep['usergroup'], $rep['displaygroup']);
				$profile_link = get_profile_link($rep['uid']);
				$user_list .= '<a href="'.$mybb->settings['bburl'].'/'.$profile_link.'" target="_blank" onclick="window.opener.location.href=this.href; return false;">'.$format_name.'</a>';

				if($count+1 != $dislike_users)
				{
					$user_list .= $lang->postrep_vote_sep." ";
					++$count;
				}
			}

			// Because we've enabled showing off users
			$user_list = '<div class="rep_user_list">'.$user_list.'</div>';
		}

		// Standard things for minus votes
		$rep_class = "rep_none";
		if($rep_count > 0)
		{
			$rep_class = "rep_plus";
		}
		elseif($rep_count < 0)
		{
			$rep_class = "rep_minus";
		}

		eval("\$minus_users = \"".$templates->myn_get("misc_voting_list_bit")."\";");
	}

	if(!$plus_users && !$minus_users)
	{
		eval("\$plus_users = \"".$templates->myn_get("misc_voting_list_none")."\";");
	}

	eval("\$voting_list = \"".$templates->myn_get("misc_voting_list")."\";");
	echo $voting_list;
	exit;
}

function postrep_xmlhttp()
{
	global $Alerts, $charset, $db, $lang, $mybb, $network, $templates, $theme, $stylesheets;

	// Check important things first
	myn_lang("postrep");
	myn_lang("", true);

	if(verify_post_check($mybb->input['my_post_key'], true) === false)
	{
		header("Content-type: text/xml; charset={$charset}");
		echo "<error>".$lang->failed_xmlhttprequest."</error>";
		exit;
	}

	$query = $db->query("
		SELECT p.pid, p.tid, p.fid, p.visible, p.post_reputation, p.uid AS author, p.username AS author_username, t.visible AS thread_visible, f.open, f.type, r.rid AS repped_post
		FROM ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."threads t ON (p.tid=t.tid)
		LEFT JOIN ".TABLE_PREFIX."forums f ON (p.fid=f.fid)
		LEFT JOIN ".TABLE_PREFIX."reputation r ON (r.pid=p.pid AND r.adduid='".intval($mybb->user['uid'])."')
		WHERE p.pid = '".intval($mybb->input['pid'])."'
	");

	if(!$db->num_rows($query))
	{
		header("Content-type: text/xml; charset={$charset}");
		echo "<error>".$lang->failed_post_not_found."</error>";
		exit;
	}

	$post = $db->fetch_array($query);
	$permissions = postrep_can_postrep(0, $post['fid']);
	$forumpermissions = forum_permissions($post['fid']);

	if($post['visible'] <= 0 || $post['thread_visible'] <= 0 || $permissions['postrep_giverep'] == 0)
	{
		// Don't allow an update on an invisible post/thread, or if they have no permission
		header("Content-type: text/xml; charset={$charset}");
		echo "<error>".$lang->failed_invalid_post."</error>";
		exit;
	}

	if($post['author'] == $mybb->user['uid'])
	{
		// User is trying to rate their own posts! :o
		header("Content-type: text/xml; charset={$charset}");
		echo "<error>".$lang->failed_own_post."</error>";
		exit;
	}

	if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0 || $post['open'] == 0 || $post['type'] != "f")
	{
		// User can't view threads or the forum, or the forum isn't open, so deny if trying to update
		header("Content-type: text/xml; charset={$charset}");
		echo "<error>".$lang->failed_invalid_forum."</error>";
		exit;
	}

	// Already voted?
	if($post['repped_post'])
	{
		header("Content-type: text/xml; charset={$charset}");
		echo "<error>".$lang->failed_already_voted."</error>";
		exit;
	}

	// Quickly check to see if this user has reached a postrep limit
	if($mybb->settings['maxreputationsday'] > 0)
	{
		$threshold = TIME_NOW - 86400;
		$query = $db->simple_select("reputation", "rid", "adduid = '".$mybb->user['uid']."' AND dateline >= '".$threshold."' AND pid > 0");

		if($db->num_rows($query) >= $mybb->settings['maxreputationsday'])
		{
			$lang->failed_limit_reached = $lang->sprintf($lang->failed_limit_reached, $mybb->settings['maxreputationsday']);
			header("Content-type: text/xml; charset={$charset}");
			echo "<error>".$lang->failed_limit_reached."</error>";
			exit;
		}
	}

	// Everything should be OK by now, so let's update the reputation!
	if($mybb->input['act'] == "minus")
	{
		if(!$permissions['postrep_givenegrep'])
		{
			header("Content-type: text/xml; charset={$charset}");
			echo "<error>".$lang->failed_no_negative."</error>";
			exit;
		}

		$action = -$permissions['postrep_ns_minreppower'];
		$post['post_reputation'] = $post['post_reputation'] - $permissions['postrep_ns_minreppower'];
	}
	elseif($mybb->input['act'] == "plus")
	{
		$action = $permissions['postrep_ns_minreppower'];
		$post['post_reputation'] = $post['post_reputation'] + $permissions['postrep_ns_minreppower'];
	}

	$viapr = 0;
	if(!$permissions['postrep_affectmybbrep'])
	{
		$viapr = $action;
		$action = 0;
	}

	// Insert into the MyBB reputation
	$insert_array = array(
		"uid" => intval($post['author']),
		"adduid" => intval($mybb->user['uid']),
		"pid" => intval($post['pid']),
		"reputation" => $db->escape_string($action),
		"dateline" => TIME_NOW,
		"comments" => '',
		"viapr" => $db->escape_string($viapr)
	);

	// Update the posts table
	$update_array = array(
		"post_reputation" => $db->escape_string($post['post_reputation'])
	);

	$db->update_query("posts", $update_array, "pid = '".intval($post['pid'])."'");
	$db->insert_query("reputation", $insert_array);

	// Now, sync the user reputation
	$user_reputation = "false";
	if($post['author'] > 0 && $permissions['postrep_affectmybbrep'])
	{
		$query = $db->simple_select("reputation", "SUM(reputation)+SUM(viapr) AS 'reputation'", "uid = '".$post['author']."'");
		$user_reputation = intval($db->fetch_field($query, "reputation"));

		$db->update_query("users", array("reputation" => $user_reputation), "uid = '".$post['author']."'");
	}

	// Dear Euan, your alerts system SUCKS. kthxbye. xoxo
	// Kidding. Let's see if MyAlerts is installed and, if it is, notify the user
	if(isset($Alerts))
	{
		$_user = get_user($post['author']);

		if($_user['myalerts_settings'])
		{
			$_user['myalerts_settings'] = json_decode($_user['myalerts_settings'], true);
		
			if($_user['myalerts_settings']['rep'])
			{
				$Alerts->addAlert($post['author'], 'rep', $post['pid'], $mybb->user['uid'], array());
			}
		}
	}

	// Return the post reputation area
	$pid = $post['pid'];
	$uid = $post['author'];

	$templatelist = "postbit_repremove,postbit_reparea"; // SOQ!
	$templates->cache($templatelist);

	$id_style = "none";
	if($post['post_reputation'] > 0)
	{
		$id_style = "plus";
	}
	elseif($post['post_reputation'] < 0)
	{
		$id_style = "minus";
	}

	$about_title = $lang->sprintf($lang->about_postrep_dyn, $post['username']);
	$reputation_link = "<a href=\"javascript:;\" onclick=\"MyBB.popupWindow('{$mybb->settings['bburl']}/misc.php?action=load_voters&amp;pid={$pid}', 'voters', 350, 350)\"><strong><span id=\"{$pid}_rep\">".$post['post_reputation']."</span></strong></a>";

	// Return the remove control
	eval("\$plus_link = \"".$templates->myn_get("postbit_repremove")."\";");
	eval("\$rep_area = \"".$templates->myn_get("postbit_reparea")."\";");

	$success_message = '';
	if($mybb->settings['postrep_display_success'] == 1)
	{
		$success_message = $lang->sprintf($lang->success_post_voted, $post['author_username']);
	}

	$js_send = array(
		"message" => $success_message,
		"rep_area" => $rep_area,
		"user_rep" => $user_reputation
	);

	header("Content-type: application/json; charset={$charset}");
	echo json_encode($js_send);
	exit;	
}

function postrep_xmlhttp_remove()
{
	global $Alerts, $charset, $db, $lang, $network, $mybb, $templates;

	myn_lang("postrep");
	myn_lang("", true);

	if(verify_post_check($mybb->input['my_post_key'], true) === false)
	{
		header("Content-type: text/xml; charset={$charset}");
		echo "<error>".$lang->failed_xmlhttprequest."</error>";
		exit;
	}

	// First, check that the post physically exists
	$query = $db->query("
		SELECT r.uid, r.adduid, r.pid, r.rid, r.reputation, p.post_reputation, p.username, p.fid, p.visible AS post_visible, t.visible as thread_visible, t.closed AS closed
		FROM ".TABLE_PREFIX."reputation r
		LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=r.pid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		WHERE r.pid = '".intval($mybb->input['pid'])."' AND r.adduid = '".$mybb->user['uid']."'
	");

	if(!$db->num_rows($query))
	{
		header("Content-type: text/xml; charset={$charset}");
		echo "<error>".$lang->failed_post_not_found."</error>";
		exit;
	}

	$post = $db->fetch_array($query);

	// Make sure we can do this
	if($post['adduid'] != $mybb->user['uid'])
	{
		// Not the reputation giver or a moderator
		echo "<error>".$lang->failed_post_not_yours."</error>";
		exit;
	}

	// Now that we're here, check this user's permissions for revoting
	$permissions = postrep_can_postrep(0, $post['fid']);

	// Update the post_reputation
	$db->delete_query("reputation", "rid = '".$post['rid']."'");
	$query = $db->simple_select("reputation", "SUM(reputation)+SUM(viapr) AS 'reputation'", "pid = '".$post['pid']."'");
	$post_rep = intval($db->fetch_field($query, "reputation"));

	$db->update_query("posts", array("post_reputation" => $post_rep), "pid = '".$post['pid']."'");

	// Now, sync the user reputation
	$reputation = "false";
	if($post['uid'] > 0 && $permissions['postrep_affectmybbrep'])
	{
		$query = $db->simple_select("reputation", "SUM(reputation) AS reputation", "uid = '".$post['uid']."'");
		$reputation = $db->fetch_field($query, "reputation");

		$db->update_query("users", array("reputation" => $reputation), "uid = '".$post['uid']."'");
	}

	// Have we got alerts?
	if(isset($Alerts))
	{
		$db->delete_query('alerts', "uid = '{$post['uid']}' AND from_id = '{$mybb->user['uid']}' AND tid = '{$post['pid']}' AND alert_type = 'rep'");
	}

	// OOoooooohhhhh who lives in a pineapple under the sea?...
	$templatelist = "postbit_minus,postbit_repplus,postbit_reparea";
	$templates->cache($templatelist);

	$pid = $post['pid'];
	$uid = $post['uid'];

	// Answer: An annoying frigging yellow sponge! >:-[
	// Because we removed the reputation return the controls based on our newfangled permissions
	$plus_link = $minus_link = '';
	if($post['thread_visible'] == 1 && !$post['closed'] || $post['closed'] == 1 && $permissions['postrep_inclosedthread'] == 1)
	{
		if($permissions['postrep_givenegrep'] == 1)
		{
			// Show the option to give minus rep if not disabled...
			eval("\$minus_link = \"".$templates->myn_get("postbit_repminus")."\";");
		}

		eval("\$plus_link = \"".$templates->myn_get("postbit_repplus")."\";");
	}

	$id_style = "none";
	if($post_rep > 0)
	{
		$id_style = "plus";
	}
	elseif($post_rep < 0)
	{
		$id_style = "minus";
	}

	$about_title = $lang->sprintf($lang->about_postrep_dyn, $post['username']);
	$reputation_link = "<a href=\"javascript:;\" onclick=\"MyBB.popupWindow('{$mybb->settings['bburl']}/misc.php?action=load_voters&amp;pid={$pid}', 'voters', 350, 350)\"><strong><span id=\"{$pid}_rep\">".$post_rep."</span></strong></a>";

	eval("\$rep_area = \"".$templates->myn_get("postbit_reparea")."\";");

	$success_message = '';
	if($mybb->settings['postrep_display_success'] == 1)
	{
		$success_message = $lang->success_post_updated;
	}

	$js_send = array(
		"message" => $success_message,
		"rep_area" => $rep_area,
		"user_rep" => $reputation
	);

	header("Content-type: application/json; charset={$charset}");
	echo json_encode($js_send);
	exit;
}

function rebuild_stylesheets()
{
	global $db, $mybb, $theme;
	
	$query = $db->simple_select("themes", "stylesheets", "tid = '".$theme['tid']."'");
	$theme['stylesheets'] = unserialize($db->fetch_field($query, "stylesheets"));
	$stylesheet_scripts = array("global", basename($_SERVER['PHP_SELF']));

	foreach($stylesheet_scripts as $stylesheet_script)
	{
		$stylesheet_actions = array("global");
		if($mybb->input['action'])
		{
			$stylesheet_actions[] = $mybb->input['action'];
		}

		// Load stylesheets for global actions and the current action
		foreach($stylesheet_actions as $stylesheet_action)
		{
			if(!$stylesheet_action)
			{
				continue;
			}

			if($theme['stylesheets'][$stylesheet_script][$stylesheet_action])
			{
				// Actually add the stylesheets to the list
				foreach($theme['stylesheets'][$stylesheet_script][$stylesheet_action] as $page_stylesheet)
				{
					if($already_loaded[$page_stylesheet])
					{
						continue;
					}

					$stylesheets .= "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$mybb->settings['bburl']}/{$page_stylesheet}\" />\n";
					$already_loaded[$page_stylesheet] = 1;
				}
			}
		}
	}

	return $stylesheets;
}
?>