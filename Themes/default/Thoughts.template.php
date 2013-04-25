<?php
/**
 * Wedge
 *
 * Displays thoughts. Yup.
 *
 * @package wedge
 * @copyright 2010-2013 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

function template_showThoughts()
{
	global $context, $txt;

	echo '
		<we:cat>
			<div class="thought_icon"></div>
			', $txt['thoughts'], '
		</we:cat>
		<div class="list-thoughts">
		<table class="w100 cs0 thoughts" id="thought_thread" data-cx="thread ', $context['thought_context'], ' ', $_REQUEST['start'], '">',
			template_thoughts_thread(), '
		</table>
		</div>';
}

function template_showLatestThoughts()
{
	global $context, $txt;

	if (empty($context['thoughts']))
		return;

	// !! @todo: allow editing & replying to thoughts directly from within the Profile area...?
	// onclick="oThought.edit(', $thought['id'], !empty($thought['id_master']) && $thought['id'] != $thought['id_master'] ? ', ' . $thought['id_master'] : '0', ');"

	echo '
		<we:cat>
			<div class="thought_icon"></div>
			', $txt['thoughts'], ' (', $context['total_thoughts'], ')
		</we:cat>
		<div class="pagesection">
			', $context['page_index'], '
		</div>
		<div class="list-thoughts">
		<table class="w100 cs0 thoughts" data-cx="profile ', $context['thought_context'], ' ', $_REQUEST['start'], '">',
			template_thoughts_table(), '
		</table>
		</div>
		<div class="pagesection">
			', $context['page_index'], '
		</div>';
}

function template_thoughts()
{
	global $context, $txt, $theme;

	if (empty($context['thoughts']))
		return;

	if (!$context['action']) // Homepage..?
		echo '
		<we:cat style="margin-top: 16px">
			<span class="floatright"><a href="<URL>?action=thoughts">', $txt['all_pages'], '</a></span>
			<div class="thought_icon"></div>
			', $txt['thoughts'], '...';
	else
		echo '
		<we:cat>
			<img src="', $theme['images_url'], '/icons/profile_sm.gif">
			', $txt['thoughts'], empty($context['member']) ? '' : ' - ' . $context['member']['name'], ' (', $context['total_thoughts'], ')';

	echo '
		</we:cat>
		<div class="list-thoughts">
		<table class="w100 cs0 thoughts" data-cx="latest ', $context['thought_context'], ' 0">',
			template_thoughts_table(), '
		</table>
		</div>';
}

function template_thoughts_thread()
{
	global $context, $txt, $privacy_icon;

	$privacy_icon = array(
		-3 => 'everyone',
		0 => 'members',
		5 => 'justme',
		20 => 'friends',
	);

	$col = 2;
	foreach ($context['thoughts'] as $thought)
	{
		$col = empty($col) ? 2 : '';
		echo '
			<tr><td class="windowbg', $col, ' thought"><ul><li id="t', $thought['id'], '">
				<div>
					<a class="more_button thome" data-id="', $thought['id'], '">', $txt['actions_button'], '</a>',
					$thought['privacy'] != -3 ? '<div class="privacy_' . @$privacy_icon[$thought['privacy']] . '"></div>' : '', '
					<a href="<URL>?action=profile;u=', $thought['id_member'], '">', $thought['owner_name'], '</a>
					<span class="date">(', $thought['updated'], ')</span> &raquo; ', $thought['text'], '
				</div>';

		if (!empty($thought['sub']))
			template_sub_thoughts($thought);

		echo '
			</li></ul></td></tr>';
	}
}

function template_sub_thoughts(&$thought)
{
	global $txt, $privacy_icon;

	if (empty($thought['sub']))
		return;

	// !! @todo: see above...
	echo '<ul>';
	foreach ($thought['sub'] as $tho)
	{
		echo '<li id="t', $tho['id'], '"><div><a class="more_button thome" data-id="', $tho['id'], '">', $txt['actions_button'], '</a>',
			$tho['privacy'] != -3 ? '<div class="privacy_' . @$privacy_icon[$tho['privacy']] . '"></div>' : '',
			empty($tho['owner_name']) ? '' : '<a href="<URL>?action=profile;u=' . $tho['id_member'] . '">' .
			$tho['owner_name'] . '</a> <span class="date">(' . $tho['updated'] . ')</span> &raquo; ', parse_bbc($tho['text']), '</div>';

		if (!empty($tho['sub']))
			template_sub_thoughts($tho);

		echo '</li>';
	}
	echo '</ul>';
}

function template_thoughts_table()
{
	global $context, $txt;

	// @worg!!
	$privacy_icon = array(
		-3 => 'everyone',
		0 => 'members',
		5 => 'justme',
		20 => 'friends',
	);

	// This is where we'll show the Thought postbox.
	if (allowedTo('post_thought'))
	{
		echo '
			<tr class="windowbg2">', SKIN_MOBILE ? '' : '
				<td class="bc">' . $txt['date'] . '</td>', '
				<td><span class="my thought" id="thought0"><span></span></span></td>
			</tr>';

		add_js('
	oThought = new Thought([[-3, "everyone", "', $txt['privacy_public'], '"], [0, "members", "', $txt['privacy_members'], '"], ',
	// !! @worg This is temporary code for use on Wedge.org. Clean this up!!
	in_array(20, we::$user['groups']) ? '[20, "friends", "Friends"], ' : '', '[5, "justme", "', $txt['privacy_self'], '"]]);');
	}

	$col = 2;
	if (!SKIN_MOBILE)
	{
		foreach ($context['thoughts'] as $id => $thought)
		{
			$col = empty($col) ? 2 : '';
			echo '
			<tr class="windowbg', $col, '">
				<td class="bc">', $thought['updated'], '</td>
				<td><a class="more_button thome" data-id="', $id, '">', $txt['actions_button'], '</a>',
				$thought['privacy'] != -3 ? '<div class="privacy_' . @$privacy_icon[$thought['privacy']] . '"></div>' : '', '<a href="<URL>?action=profile;u=', $thought['id_member'], '" id="t', $id, '">',
				$thought['owner_name'], '</a> &raquo; ', $thought['text'], template_thought_likes($id), '</td>
			</tr>';
		}
	}
	else
	{
		foreach ($context['thoughts'] as $id => $thought)
		{
			$col = empty($col) ? 2 : '';
			echo '
			<tr class="windowbg', $col, '">
				<td>', $thought['updated'], '
				<div class="more_button thome" data-id="', $id, '">', $txt['actions_button'], '</div><br>',
				$thought['privacy'] != -3 ? '<div class="privacy_' . @$privacy_icon[$thought['privacy']] . '"></div>' : '', '<a href="<URL>?action=profile;u=', $thought['id_member'], '" id="t', $id, '">',
				$thought['owner_name'], '</a> &raquo; ', $thought['text'], template_thought_likes($id), '</td>
			</tr>';
		}
	}
}

function template_thought_likes($id_thought)
{
	global $context, $txt, $user_profile, $settings;

	if (empty($settings['likes_enabled']))
		return;

	$likes =& $context['liked_posts'][$id_thought];

	if (empty($likes))
		return;

	$you_like = !empty($likes['you']);

	// Simplest case, it's just you.
	if ($you_like && empty($likes['names']))
	{
		$string = $txt['you_like_this'];
		$num_likes = 1;
	}
	// So we have some names to display?
	elseif (!empty($likes['names']))
	{
		$base_id = $you_like ? 'you_' : '';
		if (!empty($likes['others']))
			$string = number_context($base_id . 'n_like_this', $likes['others']);
		else
			$string = $txt[$base_id . count($likes['names']) . '_like_this'];

		// OK so at this point we have the string with the number of 'others' added, and also 'You' if appropriate. Now to add other names.
		foreach ($likes['names'] as $k => $v)
			$string = str_replace('{name' . ($k + 1) . '}', '<a href="<URL>?action=profile;u=' . $v . '">' . $user_profile[$v]['real_name'] . '</a>', $string);
		$num_likes = ($you_like ? 1 : 0) + count($likes['names']) + (empty($likes['others']) ? 0 : $likes['others']);
	}

	echo ' <span class="like_button" title="', strip_tags($string), '"> <a href="<URL>?action=like;sa=view;type=think;cid=' . $id_thought . '" class="fadein" onclick="return reqWin(this);"><span class="note', $you_like ? 'nice' : '', '">', $num_likes, '</span></a></span>';
}
