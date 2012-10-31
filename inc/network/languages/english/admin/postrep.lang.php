<?php
/*
+---------------------------------------------------------------------------
|   MyNetwork Core
|	|- Post Reputation ACP
|   =============================================
|   by Tom Moore (www.xekko.co.uk)
|   Copyright 2011 Mooseypx Design / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: $
+---------------------------------------------------------------------------
*/

$l['postrep_post_reputation'] = "Post Reputation";
$l['postrep_tab_permissions'] = "Reputation Permissions";
$l['postrep_post_reputation_system'] = "Post Reputation System";
$l['postrep_post_reputation_system_desc'] = "Along with the settings above, you can this group's global settings for Post Reputation.";
$l['postrep_permissions_for_postrep'] = "Permissions for MyNetwork Post Reputation";
$l['postrep_use_default_settings'] = "Use Default Settings?";
$l['postrep_use_default_settings_desc'] = "Use the default settings (chosen in the Groups settings) for Post Reputation in this forum? Select 'No' to choose each individual group's settings.
<br /><br />
For Reputation Power, the level you set works both ways. For example - if you have set the level at '2' and a user of that group votes negatively against a post, that post will get a -2 rating. Alternatively, if they vote positive, the post will get a +2 rating. The user does not choose how much power to give the post.";
$l['postrep_reputation_power'] = "Reputation Power:";
$l['postrep_update_permissions'] = "Update Post Reputation Permissions";
$l['postrep_guests_cant_vote'] = "<strong>Guests</strong>
<br />
<div style=\"margin: 5px 15px;\">
	Sorry, but in this version of Post Reputation, Guests do not have the facility to vote.
</div>";

$l['postrep_giverep'] = "Give Reputation";
$l['postrep_giverep_drag'] = "&#149; Give Reputation";
$l['postrep_givenegrep'] = "Give Negative Reputation";
$l['postrep_givenegrep_drag'] = "&#149; Give Negative Reputation";
$l['postrep_can_givenegrep'] = "Can give negative reputation?";
$l['postrep_inclosedthread'] = "Give Reputation in closed threads";
$l['postrep_inclosedthread_drag'] = "&#149; Give Reputation in closed threads";
$l['postrep_can_inclosedthread'] = "Can give reputation in closed threads?";
$l['postrep_affectmybbrep'] = "Reputation affects MyBB Reputation";
$l['postrep_affectmybbrep_drag'] = "&#149; Reputation affects MyBB Reputation";
$l['postrep_can_affectmybbrep'] = "Reputation votes affect MyBB reputation?";
$l['postrep_update_settings'] = "Update all forums with these Post Reputation settings for this group?";
$l['postrep_points_to_award_take_desc'] = "Enter the number of points to give or take away each time a user votes on a post.";
$l['postrep_group_default'] = "Inherited from Group";
$l['postrep_group_custom'] = "Custom Permissions";
$l['postrep_reset'] = "Reset";

$l['postrep_success_permissions_updated'] = "Post Reputation settings successfully updated!";
$l['postrep_error_incorrect_reset'] = "Couldn't find the group you specified to reset or they have no custom set permissions.";
?>