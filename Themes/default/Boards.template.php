<?php
/**
 * Wedge
 *
 * Displays the main listing of boards.
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

function template_boards()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings, $language;

	echo '
	<table class="table_list" id="board_list">';

	/* Each category in categories is made up of:
	id, href, link, name, is_collapsed (is it collapsed?), can_collapse (is it okay if it is?),
	new (is it new?), collapse_href (href to collapse/expand), collapse_image (up/down image),
	and boards. (see below.) */
	$alt = false;
	foreach ($context['categories'] as $category)
	{
		// If there are no parent boards we can see, avoid showing an empty category (unless it's collapsed)
		if (empty($category['boards']) && !$category['is_collapsed'])
			continue;

		echo '
		<tbody id="category_', $category['id'], '">
			<tr>
				<td colspan="4">
					<we:cat>';

		// If this category even can collapse, show a link to collapse it.
		if ($category['can_collapse'])
			echo '
						<a class="collapse" href="', $category['collapse_href'], '">', $category['collapse_image'], '</a>';

		if (!$context['user']['is_guest'] && !empty($category['show_unread']))
			echo '
						<a class="unreadlink" href="', $scripturl, '?action=unread;c=', $category['id'], '">', $txt['view_unread_category'], '</a>';

		echo '
						<a class="catfeed" href="', $scripturl, '?action=feed;c=', $category['id'], '"><div class="feed_icon"></div></a>
						', $category['link'], '
					</we:cat>
				</td>
			</tr>
		</tbody>';

		// Assuming the category hasn't been collapsed...
		if (!$category['is_collapsed'])
		{
			/* Each board in each category's boards has:
			new (is it new?), id, name, description, moderators (see below), link_moderators (just a list.),
			children (see below.), link_children (easier to use.), children_new (are they new?),
			topics (# of), posts (# of), link, href, and last_post. (see below.) */

			echo '
		<tbody id="category_', $category['id'], '_boards">';

			foreach ($category['boards'] as $board)
			{
				$alt = !$alt;

				echo '
			<tr id="board_', $board['id'], '" class="windowbg', $alt ? '2' : '', '">
				<td class="icon"', !empty($board['children']) ? ' rowspan="2"' : '', '>
					<a', $board['redirect_newtab'] ? ' target="_blank"' : '', ' href="', ($board['is_redirect'] || $context['user']['is_guest'] ? $board['href'] : $scripturl . '?action=unread;board=' . $board['id'] . '.0;children'), '">';

				// If the board or children is new, show an indicator.
				if ($board['new'] || $board['children_new'])
					echo '
						<div class="boardstate_', $board['new'] ? 'new' : 'on', '" title="', $txt['new_posts'], '"></div>';
				// Is it a redirection board?
				elseif ($board['is_redirect'])
					echo '
						<div class="boardstate_redirect"></div>';
				// No new posts at all! The agony!!
				else
					echo '
						<div class="boardstate_off" title="', $txt['old_posts'], '"></div>';

				echo '
					</a>
				</td>
				<td class="info">
					', $modSettings['display_flags'] == 'all' || ($modSettings['display_flags'] == 'specified' && !empty($board['language'])) ? '<img src="' . $settings['default_theme_url'] . '/languages/Flag.' . (empty($board['language']) ? $language : $board['language']) . '.png"> ': '', '<a', $board['redirect_newtab'] ? ' target="_blank"' : '', ' class="subject" href="', $board['href'], '" id="b', $board['id'], '">', $board['name'], '</a>';

				// Has it outstanding posts for approval?
				if ($board['can_approve_posts'] && ($board['unapproved_posts'] || $board['unapproved_topics']))
					echo '
					<a href="', $scripturl, '?action=moderate;area=postmod;sa=', ($board['unapproved_topics'] > 0 ? 'topics' : 'posts'), ';brd=', $board['id'], ';', $context['session_query'], '" title="', sprintf($txt['unapproved_posts'], $board['unapproved_topics'], $board['unapproved_posts']), '" class="moderation_link">(!)</a>';

				if (!empty($board['description']))
					echo '
					<p>', $board['description'], '</p>';

				// Show the "Moderators: ". Each has name, href, link, and id. (but we're gonna use link_moderators.)
				if (!empty($board['moderators']))
					echo '
					<p class="moderators">', count($board['moderators']) == 1 ? $txt['moderator'] : $txt['moderators'], ': ', implode(', ', $board['link_moderators']), '</p>';

				// Show some basic information about the number of posts, etc.
				echo '
				</td>
				<td class="stats">
					<p>', number_context($board['is_redirect'] ? 'redirects' : 'posts', $board['posts']),
					$board['is_redirect'] ? '' : '<br>' . number_context('topics', $board['topics']), '</p>
				</td>
				<td class="lastpost">';

				/* The board's and children's 'last_post's have:
				time, timestamp (a number that represents the time.), id (of the post), topic (topic id.),
				link, href, subject, start (where they should go for the first unread post.),
				and member. (which has id, name, link, href, username in it.) */
				if (!empty($board['last_post']['id']))
					echo '
					<p>
						<strong>', $txt['last_post'], '</strong> ', $txt['by'], ' ', $board['last_post']['member']['link'], '<br>
						', $txt['in'], ' ', $board['last_post']['link'], '<br>
						', $txt['on'], ' ', $board['last_post']['time'], '
					</p>';

				echo '
				</td>
			</tr>';

				// Show the "Child Boards: ". (there's a link_children but we're going to bold the new ones...)
				if (!empty($board['children']))
				{
					// Sort the links into an array with new boards bold so it can be imploded.
					$children = array();
					/* Each child in each board's children has:
							id, name, description, new (is it new?), topics (#), posts (#), href, link, and last_post. */
					foreach ($board['children'] as $child)
					{
						if (!$child['is_redirect'])
						{
							$child_title = ($child['new'] ? $txt['new_posts'] : $txt['old_posts']) . ' (' . number_context('topics', $child['topics']) . ', ' . number_context('posts', $child['posts']) . ')';
							$child['link'] = '<a href="' . $child['href'] . '"' . ($child['new'] ? ' class="new_posts"' : '') . ' title="' . $child_title . '">' . $child['name'] . ($child['new'] ? '</a> <a href="' . $scripturl . '?action=unread;board=' . $child['id'] . '" title="' . $child_title . '"><div class="new_icon new_posts"></div>' : '') . '</a>';
						}
						else
							$child['link'] = '<a href="' . $child['href'] . '" title="' . number_context('redirects', $child['posts']) . '">' . $child['name'] . '</a>';

						// Has it posts awaiting approval?
						if ($child['can_approve_posts'] && ($child['unapproved_posts'] || $child['unapproved_topics']))
							$child['link'] .= ' <a href="' . $scripturl . '?action=moderate;area=postmod;sa=' . ($child['unapproved_topics'] > 0 ? 'topics' : 'posts') . ';brd=' . $child['id'] . ';' . $context['session_query'] . '" title="' . sprintf($txt['unapproved_posts'], $child['unapproved_topics'], $child['unapproved_posts']) . '" class="moderation_link">(!)</a>';

						$children[] = $child['new'] ? '<strong>' . $child['link'] . '</strong>' : $child['link'];
					}
					echo '
				<tr id="board_', $board['id'], '_children">
					<td colspan="3" class="children windowbg', $alt ? '2' : '', '">
						<strong>', $txt['sub_boards'], '</strong>: ', implode(', ', $children), '
					</td>
				</tr>';
				}
			}

			echo '
		</tbody>';
		}
		echo '
		<tbody class="divider">
			<tr>
				<td colspan="4"></td>
			</tr>
		</tbody>';
	}
	echo '
	</table>';
}

function template_boards_ministats()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	// Show some statistics if stat info is off.
	if (!$settings['show_stats_index'])
		echo '
	<div id="index_common_stats">
		', $txt['members'], ': ', $context['common_stats']['total_members'], ' &nbsp;&#8226;&nbsp; ', $txt['posts_made'], ': ', $context['common_stats']['total_posts'], ' &nbsp;&#8226;&nbsp; ', $txt['topics'], ': ', $context['common_stats']['total_topics'], '
		', $settings['show_latest_member'] ? ' ' . $txt['welcome_member'] . ' <strong>' . $context['common_stats']['latest_member']['link'] . '</strong>' . $txt['newest_member'] : '', '
	</div>';
}

function template_boards_newsfader()
{
	// Show the news fader?  (assuming there are things to show...)
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	if ($settings['show_newsfader'] && !empty($context['fader_news_lines']))
	{
		echo '
	<div id="newsfader">
		<we:cat>
			<div id="newsupshrink" title="', $txt['upshrink_description'], '"', empty($options['collapse_news_fader']) ? ' class="fold"' : '', '></div>
			', $txt['news'], '
		</we:cat>
		<ul class="reset" id="fadeScroller">';

			foreach ($context['news_lines'] as $news)
				echo '
			<li>', $news, '</li>';

		echo '
		</ul>
	</div>';

		add_js_file('scripts/fader.js');

		// Create a news fader object and toggle.
		add_js('
	var oNewsFader = new wedge_NewsFader({
		sSelf: \'oNewsFader\',
		sFaderControlId: \'fadeScroller\',
		sItemTemplate: ', JavaScriptEscape('<strong>%1$s</strong>'), ',
		iFadeDelay: ', empty($settings['newsfader_time']) ? 5000 : $settings['newsfader_time'], '
	});

	var weNewsFadeToggle = new weToggle({
		bCurrentlyCollapsed: ', empty($options['collapse_news_fader']) ? 'false' : 'true', ',
		aSwappableContainers: [\'fadeScroller\'],
		aSwapImages: [{ sId: \'newsupshrink\', altExpanded: ', JavaScriptEscape($txt['upshrink_description']), ' }],
		oThemeOptions: { bUseThemeSettings: ', $context['user']['is_guest'] ? 'false' : 'true', ', sOptionName: \'collapse_news_fader\' },
		oCookieOptions: { bUseCookie: ', $context['user']['is_guest'] ? 'true' : 'false', ', sCookieName: \'newsupshrink\' }
	});');
	}
}

function template_boards_below()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	if ($context['user']['is_logged'])
	{
		echo '
	<div id="posting_icons" class="floatleft">';

		// Mark read button.
		$mark_read_button = array(
			'markread' => array('text' => 'mark_as_read', 'url' => $scripturl . '?action=markasread;sa=all;' . $context['session_query']),
		);

		echo '
		<ul class="reset">
			<li class="floatleft"><div class="mini_boardstate_on"></div> ', $txt['new_posts'], '</li>
			<li class="floatleft"><div class="mini_boardstate_off"></div> ', $txt['old_posts'], '</li>
			<li class="floatleft"><div class="mini_boardstate_redirect"></div> ', $txt['redirect_board'], '</li>
		</ul>
	</div>';

		// Show the mark all as read button?
		if (!empty($context['categories']))
			echo '<div class="mark_read">', template_button_strip($mark_read_button), '</div>';
	}
	else
	{
		echo '
	<div id="posting_icons" class="flow_hidden">
		<ul class="reset">
			<li class="floatleft"><div class="mini_boardstate_off"></div> ', $txt['old_posts'], '</li>
			<li class="floatleft"><div class="mini_boardstate_redirect"></div> ', $txt['redirect_board'], '</li>
		</ul>
	</div>';
	}
}

?>