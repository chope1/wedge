<?php
/**
 * Wedge
 *
 * Displays the custom homepage. Hack away!
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

function template_main()
{
	global $context, $txt, $boardurl;

	echo '
	<we:cat>
		', $txt['wedge_home_title'], '
	</we:cat>';

	if (empty($context['skin_options']['mobile']))
		echo '
	<div class="home-intro">
		<img src="http://wedge.org/wedge.png" style="width: 130px; height: 135px; float: left; margin-top: 0" />
		<div class="windowbg2 wrc" style="margin: 16px 0 0 146px">', $txt['wedge_home_intro'], '
		</div>
	</div>';

	if (!$context['home_show']['topics'])
		return;

	$n = isset($_REQUEST['n']) ? (int) $_REQUEST['n'] : 5;
	$next = $n < 50 ? ($n < 20 ? ($n < 10 ? 10 : 20) : 50) : 100;

	echo '
	<we:cat style="margin-top: 16px">', $n == $next ? '' : '
		<span class="floatright"><a href="<URL>?action=boards">' . $txt['board_index'] . '</a></span>', '
		<a href="?n=' . $next . '" class="middle" style="display: inline-block; height: 16px"><div class="floatleft foldable"></div></a>', '
		', $txt['recent_posts'], '
	</we:cat>
	<we:block class="tborder" style="margin: 5px 0 15px; padding: 2px; border: 1px solid #dcc; border-radius: 5px">
		<table class="homeposts w100 cs0">';

	loadSource('../SSI');
	$naoboards = ssi_recentTopicTitles($n, we::$is_admin || ($boardurl != 'http://wedge.org') ? null : array(136), null, 'naos');

	$new_stuff = array();
	if (!we::$is_guest)
		foreach ($naoboards as $post)
			if ($post['is_new'])
				$new_stuff[] = $post['topic'];

	if (count($new_stuff) > 0)
	{
		$nb_new = array();
		$request = wesql::query('
			SELECT COUNT(DISTINCT m.id_msg) AS co, m.id_topic
			FROM {db_prefix}messages AS m
				LEFT JOIN {db_prefix}log_topics AS lt ON (lt.id_topic = m.id_topic AND lt.id_member = {int:id_member})
				LEFT JOIN {db_prefix}log_mark_read AS lmr ON (lmr.id_board = m.id_board AND lmr.id_member = {int:id_member})
			WHERE m.id_topic IN ({array_int:new_stuff})
					AND (m.id_msg > IFNULL(lt.id_msg, IFNULL(lmr.id_msg, 0)))
			GROUP BY m.id_topic',
			array(
				'id_member' => we::$id,
				'new_stuff' => $new_stuff
			)
		);
		while ($row = wesql::fetch_assoc($request))
			$nb_new[$row['id_topic']] = $row['co'];
		wesql::free_result($request);
	}
	unset($new_stuff, $row);

	$alt = '';
	foreach ($naoboards as $post)
	{
		$safe = strpos($post['board']['url'], '/pub') === false;
		$blo = strpos($post['board']['url'], '/blog') !== false;
		$alt = $alt ? '' : '2';
		echo '
			<tr class="windowbg', $alt, '">
				<td class="latestp1">
					<div>', strftime('%d/%m %H:%M', $post['timestamp']), '<br>', $post['poster']['link'], '</div>
				</td>
				<td class="latestp2">
					', $post['board']['name'], ' &gt; ';

		if ($post['is_new'] && !we::$is_guest)
			echo isset($nb_new[$post['topic']]) ? '<a href="' . $post['href'] . '" class="note">' . $nb_new[$post['topic']] . '</a> ' : '';

		echo '<a href="', $post['href'], $safe ? '" style="color: ' . ($blo ? '#a62' : 'green') : '', '">', $post['subject'], '</a>
				</td>
			</tr>';
		}

	echo '
		</table>
	</we:block>';
}

function template_thoughts()
{
	global $context, $txt, $theme;

	if (empty($context['thoughts']))
		return;

	echo '
		<we:cat style="margin-top: 16px">
			<span class="floatright"><a href="<URL>?action=thoughts">', $txt['all_pages'], '</a></span>
			<div class="thought_icon"></div>
			', $txt['thoughts'], '...
		</we:cat>
		<div class="tborder" style="margin: 5px 0 15px; padding: 2px; border: 1px solid #dcc; border-radius: 5px">
		<table class="w100 cp4 cs0 thought_list">';

	// @worg!!
	$privacy_icon = array(
		-3 => 'everyone',
		0 => 'members',
		5 => 'justme',
		20 => 'friends',
	);

	if (empty($context['skin_options']['mobile']))
	{
		if (!we::$is_guest)
			echo '
			<tr id="new_thought" class="windowbg">
				<td class="bc">%date%</td><td>%text%</td>
			</tr>';

		foreach ($context['thoughts'] as $id => $thought)
		{
			$col = empty($col) ? 2 : '';
			echo '
			<tr class="windowbg', $col, '">
				<td class="bc', $col, '"><a href="<URL>?action=thoughts;in=', $thought['id_master'] ? $thought['id_master'] : $id, '#t', $id, '"><img src="', $theme['images_url'], '/icons/last_post.gif" class="middle"></a> ', $thought['updated'], '</td>
				<td><div>', $thought['privacy'] != -3 ? '<div class="privacy_' . @$privacy_icon[$thought['privacy']] . '"></div>' : '', '<a href="<URL>?action=profile;u=', $thought['id_member'], '" id="t', $id, '">',
				$thought['owner_name'], '</a> &raquo; ', $thought['text'], '</div></td>
			</tr>';
		}
	}
	else
	{
		if (!we::$is_guest)
			echo '
			<tr id="new_thought" class="windowbg">
				<td>%date%<br>%text%</td>
			</tr>';

		foreach ($context['thoughts'] as $id => $thought)
		{
			$col = empty($col) ? 2 : '';
			echo '
			<tr class="windowbg', $col, '">
				<td><a href="<URL>?action=thoughts;in=', $thought['id_master'] ? $thought['id_master'] : $id, '#t', $id, '"><img src="', $theme['images_url'], '/icons/last_post.gif" class="middle"></a> ', $thought['updated'], '
				<br><div>', $thought['privacy'] != -3 ? '<div class="privacy_' . @$privacy_icon[$thought['privacy']] . '"></div>' : '', '<a href="<URL>?action=profile;u=', $thought['id_member'], '" id="t', $id, '">',
				$thought['owner_name'], '</a> &raquo; ', $thought['text'], '</div></td>
			</tr>';
		}
	}

	echo '
		</table>
		</div>';
}
