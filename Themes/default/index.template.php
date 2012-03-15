<?php
/**
 * Wedge
 *
 * The core template that underpins the entire layout, including key configuration settings.
 *
 * @package wedge
 * @copyright 2010-2012 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

/*
	This template is probably the most important one in the theme.
	It defines the skeleton, i.e. the list of layers and blocks, starting
	with the html, body, main and sidebar layers among others.
	It also defines various blocks of interest like linktree (which displays
	the navigation hierarchy), menu (for the main menu), or button_strip.
*/

// Initialize the template... mainly little settings.
function template_init()
{
	global $context, $theme, $settings, $options, $txt;

	// Add the theme-specific JavaScript files to our priority cache list.
	if (!empty($context['javascript_files']))
	{
		$context['javascript_files'][] = 'scripts/theme.js';
		if ($context['user']['is_guest'] && empty($context['disable_login_hashing']) && !empty($context['show_login_bar']))
			$context['javascript_files'][] = 'scripts/sha1.js';
	}

	/* Use images from default theme when using templates from the default theme?
		- 'always': images from the default theme will be used.
		- 'defaults': images from the default theme will only be used with default templates.
		- 'never' or nothing: images from the default theme will not be used. */
	$theme['use_default_images'] = 'never';

	/* Use plain buttons - as opposed to text buttons? */
	$theme['use_buttons'] = true;

	/* Does this theme use post previews on the message index? */
	$theme['message_index_preview'] = false;

	/* Set the following variable to true if this theme requires the optional theme strings file to be loaded. */
	$theme['require_theme_strings'] = false;

	/* You can define macros for your theme, with default contents. Then, skins can override them through
		the skin.xml file (see the skins/Wine/Warm/skin.xml file for a sample implementation.)
		Macro names are case-sensitive, for performance reasons. */

	$theme['macros'] = array(

		// We start with the header bars. Nothing special about them...
		'title'		=> '<header class="title<if:class> {class}</if:class>"<if:style> style="{style}"</if:style><if:id> id="{id}"</if:id>>{body}</header>',
		'title2'	=> '<header class="title2<if:class> {class}</if:class>"<if:style> style="{style}"</if:style><if:id> id="{id}"</if:id>>{body}</header>',
		'cat'		=> '<header class="cat<if:class> {class}</if:class>"<if:style> style="{style}"</if:style><if:id> id="{id}"</if:id>>{body}</header>',

		// Now with a regular content macro. You may add a class, title and/or footer to it. If you don't specify a title,
		// everything between the <if:title> tags will be hidden. Same for the footer and class.
		'block'		=> '<section class="block<if:class> {class}</if:class>"<if:style> style="{style}"</if:style><if:id> id="{id}"</if:id>>'
						. '<if:header><header>{header}</header></if:header>'
						. '{body}'
						. '<if:footer><footer>{footer}</footer></if:footer></section>',

		// Our main content. Note that we can serve different content to different browsers by using an array
		// with browser names and a "else" fallback. This can also be done in skin.xml with the
		// <macro name="..." for="ie6,ie7"> keyword. Plus, you don't have to repeat identical strings.
		'offside'	=> array(
			'ie6'	=> '
	<table id="edge"><tr><td class="top">{body}</td>',
			'ie7'	=> '
	<table id="edge"><tr><td class="top">{body}</td>',
			'else'	=> '<div id="edge">{body}',
		),

		// Our sidebar. Now for a little trick -- since IE6 and IE7 need to be in a table,
		// we're closing here the table that was opened in the sidebar macro.
		'sidebar'	=> array(
			'ie6'	=> '
	<td id="sidebar" class="top"><div class="column">{body}</div></td></tr></table>',
			'ie7'	=> '
	<td id="sidebar" class="top"><div class="column">{body}</div></td></tr></table>',
			'else'	=> '
	<aside id="sidebar"><div class="column">{body}
	</div></aside>
	</div>',
		),

		// The main header of the website. Feel free to redefine it in your skins and themes.
		'header'	=> '
			<if:logo><h1>
				<a href="' . (!empty($settings['home_url']) && !empty($settings['home_link']) ? $settings['home_url']  : '<URL>') . '">{logo}</a>
			</h1></if:logo>
			{body}',

	);
}

/*
	The magical function where the layer/block layout is established.
	A layer is an array of blocks. Layers have '_before' and '_after' functions,
	but they're not mandatory. Blocks only have one function but can be overloaded.
	You can comment your skeleton with the usual <!-- HTML comment --> tags.
	Finally, you can redefine a skeleton through skin.xml (see the Warm skin for a sample.)
*/
function template_skeleton()
{
	global $context;

	$context['skeleton'] = '
		<html>
			<body>
				<wrapper>
					<header>
						<logo_toggler />
						<search_box />
						<language_selector />
						<random_news />
					</header>
					<menu />
					<linktree />
					<content_wrap>
						<offside_wrap>
							<main_wrap>
								<top></top>
								<default>
									<main />
								</default>
							</main_wrap>
						</offside_wrap>
						<sidebar_wrap>
							<sidebar></sidebar>
						</sidebar_wrap>
					</content_wrap>
					<footer />
				</wrapper>
			</body>
		</html>';
}

// The main block above the content.
function template_html_before()
{
	global $context, $theme, $options, $txt, $settings, $boardurl, $topic;

	if (!empty($context['current_action']))
		$id = $context['current_action'];
	elseif (!empty($context['current_topic']))
		$id = 'topic';
	elseif (!empty($context['current_board']))
		$id = 'board';

	// Declare our HTML5 doctype, and whether to show right to left.
	// The charset is already specified in the headers so it may be omitted,
	// but the specs recommend leaving them in, if the document is viewed offline.
	echo '<!DOCTYPE html>
<html', $context['right_to_left'] ? ' dir="rtl"' : '', !empty($txt['lang_dictionary']) ? ' lang="' . $txt['lang_dictionary'] . '"' : '', isset($id) ? ' id="' . $id . '"' : '', '>
<head>', empty($topic) ? '' : '
	<meta charset="utf-8">';

	// Our alltime favorites don't really like HTML5...
	if ($context['browser']['is_ie8down'])
		echo '
	<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>';

	echo theme_base_css(), '
	<!-- Powered by Wedge, © Wedgeward - http://wedge.org -->
	<title>', $context['page_title_html_safe'], !empty($context['page_indicator']) ? $context['page_indicator'] : '', '</title>';

	// If the forum is in a sub-folder, in which case it needs to explicitly set a favicon URL.
	if (strpos(trim(substr($boardurl, strpos($boardurl, '://') + 3), '/'), '/') !== false)
		echo '
	<link rel="shortcut icon" href="', $boardurl, '/favicon.ico" type="image/vnd.microsoft.icon">';

	// Present a canonical url for search engines to prevent duplicate content in their indices.
	if (!empty($context['canonical_url']))
		echo '
	<link rel="canonical" href="', $context['canonical_url'], '">';

	// Show all the relative links, such as search.
	echo '
	<link rel="search" href="<URL>?action=search">';

	// If feeds are enabled, advertise the presence of one.
	if (!empty($settings['xmlnews_enable']) && (!empty($settings['allow_guestAccess']) || $context['user']['is_logged']))
		echo '
	<link rel="alternate" type="application/atom+xml" title="', $context['forum_name_html_safe'], ' - ', $txt['feed'], '" href="<URL>?action=feed">';

	// If we're viewing a topic, we should link to the previous and next pages, respectively. Search engines like this.
	if (empty($context['robot_no_index']))
	{
		if (!empty($context['links']['prev']))
			echo '
	<link rel="prev" href="', $context['links']['prev'], '">';
		if (!empty($context['links']['next']))
			echo '
	<link rel="next" href="', $context['links']['next'], '">';
	}

	if ($context['browser']['is_iphone'])
		echo '
	<meta name="viewport" content="width=device-width; initial-scale=0.5; maximum-scale=2.0; minimum-scale=0.5; user-scalable=1;">';

	if (!empty($context['meta_description']))
		echo '
	<meta name="description" content="', $context['meta_description'], '">';

	// Please don't index these, Mr Robotto.
	if (!empty($context['robot_no_index']))
		echo '
	<meta name="robots" content="noindex">';

	echo '
	<meta name="generator" content="Wedge">
</head>';
}

function template_body_before()
{
	echo '
<body>';
}

// The main content should go here.
function template_wrapper_before()
{
	global $theme;

	echo '
<div id="wedge">', !empty($theme['forum_width']) ? '<div id="wrapper" style="width: ' . $theme['forum_width'] . '">' : '';
}

// Start the header layer.
function template_header_before()
{
	echo '
	<div id="header"><div class="frame">
		<div id="top_section"><div class="frame">';
}

// End the header layer.
function template_header_after()
{
	global $context, $options;

	echo '
		</div></div>
		<div id="upper_section"', empty($options['collapse_header']) ? '' : ' class="hide"', '><div class="frame"><we:header logo="',
		$context['header_logo_url_html_safe'], '">', $context['site_slogan'], '</we:header>
		</div></div>
	</div></div>';
}

function template_search_box()
{
	global $context, $txt;

	if (empty($context['allow_search']))
		return;

	echo '
			<form id="search_form" action="<URL>?action=search2" method="post" accept-charset="UTF-8">
				<input type="search" name="search" value="" class="search">
				<input type="submit" value="', $txt['search'], '">
				<input type="hidden" name="advanced" value="0">';

	// Search within current topic?
	if (!empty($context['current_topic']))
			echo '
				<input type="hidden" name="topic" value="', $context['current_topic'], '">';
	// Or within current board?
	elseif (!empty($context['current_board']))
		echo '
				<input type="hidden" name="brd[', $context['current_board'], ']" value="', $context['current_board'], '">';

	echo '
			</form>';
}

function template_language_selector()
{
	global $context, $user_info, $theme;

	if (empty($context['languages']) || count($context['languages']) < 2)
		return;

	$qmark = strpos($user_info['url'], '?');
	$lng = $qmark === false ? substr(strrchr($user_info['url'], '/'), 1) . '?' : substr($user_info['url'], strrpos(substr($user_info['url'], 0, $qmark), '/') + 1) . ';';
	if (strpos($lng, 'language=') !== false)
		$lng = preg_replace('~([;&?])language=[a-z]+[;&]~i', '$1', $lng);

	echo '
			<p>';

	foreach ($context['languages'] as $language)
		echo '
				<a href="' . $lng . 'language=' . $language['filename'] . '" class="flag_' . $language['filename'] . '" title="' . westr::htmlspecialchars($language['name']) . '"></a>';

	echo '
			</p>';
}

function template_random_news()
{
	global $txt, $context, $theme;

	// Show a random news item? (or you could pick one from news_lines...)
	if (empty($theme['enable_news']) || empty($context['random_news_line']))
		return;

	echo '
			<h2>', $txt['news'], '</h2>
			<p>', $context['random_news_line'], '</p>';
}

function template_logo_toggler()
{
	global $options, $txt, $context;

	echo '
			<div id="upshrink"', empty($options['collapse_header']) ? ' class="fold"' : '', ' title="', $txt['upshrink_description'], '"></div>';

	add_js('
	new weToggle({', empty($options['collapse_header']) ? '' : '
		bCurrentlyCollapsed: true,', '
		aSwappableContainers: [\'upper_section\'],
		aSwapImages: [{ sId: \'upshrink\', altExpanded: ', JavaScriptEscape($txt['upshrink_description']), ' }],', $context['user']['is_guest'] ? '
		sCookie: \'upshrink\'' : '
		sOptionName: \'collapse_header\'', '
	});');
}

function template_sidebar_wrap_before()
{
	echo '<we:sidebar>';
}

function template_sidebar_before()
{
	global $txt, $context, $settings, $user_info;

	echo '
	<section>
		<we:title>
			<span class="greeting">', sprintf($txt['hello_member_ndt'], $context['user']['name']), '</span>
		</we:title>
		<div id="userbox">';

	// If the user is logged in, display stuff like their name, new messages, etc.
	if ($context['user']['is_logged'])
	{
		echo empty($context['user']['avatar']) ? '
			<ul id="noava">' : '
			' . $context['user']['avatar']['image'] . '
			<ul>', '
				<li><a href="<URL>?action=unread">', $txt['show_unread'], '</a></li>
				<li><a href="<URL>?action=unreadreplies">', $txt['show_unread_replies'], '</a></li>';

		// Are there any members waiting for approval?
		if (!empty($context['unapproved_members']))
			echo '
				<li>', number_context('approve_members_waiting', $context['unapproved_members']), '</li>';

		echo '
			</ul>
			<p class="now">', $context['current_time'], '</p>
		</div>
	</section>';

		// Is the forum in maintenance mode?
		if ($context['in_maintenance'] && $context['user']['is_admin'])
			echo '
	<section>
		<p class="notice">', $txt['maintain_mode_on'], '</p>
	</section>';

		// This is where we'll show the Thought postbox.
		$thought_id = isset($context['user']['data']['id_thought']) ? $context['user']['data']['id_thought'] : 0;
		$thought_prv = isset($context['user']['data']['thought_privacy']) ? $context['user']['data']['thought_privacy'] : 1;

		echo '
	<section>
		<we:title>
			<div class="thought_icon"></div>
			', $txt['thought'], '
		</we:title>
		<div style="padding: 8px 0 0">
			<a href="#" onclick="oThought.edit(\'\', \'\', true); return false;">', $txt['add_thought'], '</a> |
			<a href="#" onclick="oThought.edit(\'\'); return false;">', $txt['edit_thought'], '</a>
		</div>
		<div class="my thought" id="thought_update" data-oid="', $thought_id, '" data-prv="', $thought_prv, '"><span>';

		echo empty($context['user']['data']['thought']) ? $txt['no_thought_yet'] : $context['user']['data']['thought'], '</span></div>
	</section>';

		add_js('
	oThought = new Thought({
		aPrivacy: { "_-3": "', $txt['privacy_public'], '", "_0": "', $txt['privacy_members'], '", ',
		// !! This is temporary code for use on Wedge.org. Clean this up!!
		// Also, the underscore is required for Chrome and Opera to respect the sort order... Silly.
		in_array(18, $user_info['groups']) ? '"_18": "Friends", ' : '', '"_-5": "', $txt['privacy_self'], '" },
		sSubmit: "', $txt['form_submit'], '", sCancel: "', $txt['form_cancel'], '", sEdit: "', $txt['edit_thought'], '", sReply: "', $txt['reply'], '", sDelete: "', $txt['delete'], '",
		sNoText: ', JavaScriptEscape($txt['no_thought_yet']), ',
		sLabelThought: ', JavaScriptEscape($txt['thought']), '
	});');
	}
	// Otherwise they're a guest - this time ask them to either register or login - lazy bums...
	elseif (!empty($context['show_login_bar']))
	{
		echo '
			<form id="guest_form" action="<URL>?action=login2" method="post" accept-charset="UTF-8" ', empty($context['disable_login_hashing']) ? ' onsubmit="hashLoginPassword(this, \'' . $context['session_id'] . '\');"' : '', '>
				<div class="info">', (empty($settings['registration_method']) || $settings['registration_method'] != 3) ? $txt['login_or_register'] : $txt['please_login'], '</div>
				<input type="text" name="user" size="10">
				<input type="password" name="passwrd" size="10">
				<select name="cookielength">
					<option value="60">', $txt['one_hour'], '</option>
					<option value="1440">', $txt['one_day'], '</option>
					<option value="10080">', $txt['one_week'], '</option>
					<option value="43200">', $txt['one_month'], '</option>
					<option value="-1" selected>', $txt['forever'], '</option>
				</select>
				<input type="submit" value="', $txt['login'], '" class="submit"><br>
				<div class="info">', $txt['quick_login_desc'], '</div>';

		echo '
				<input type="hidden" name="hash_passwrd" value="">
			</form>
		</div>
	</section>';
	}
	else
		echo '
		</div>
	</section>';
}

// Show the Quick Access (JumpTo) select box.
function template_sidebar_quick_access()
{
	global $context, $txt;

	add_js('
	new JumpTo(\'jump_to\'', !empty($context['current_board']) && empty($context['current_topic']) ? ', ' . $context['current_board'] : '', ');');

	echo '
	<section>
		<we:title>
			', $txt['jump_to'], '
		</we:title>
		<p id="jump_to">', $txt['select_destination'], '</p>
	</section>';
}

// This natty little function adds feed links to the sidebar. Mostly autonomous, it's lovely for that.
// This function is only added to the list if the feeds are available, so we don't even need to check anything.
function template_sidebar_feed()
{
	global $context, $txt, $topic, $board, $board_info;

	echo '
	<section>
		<we:title>
			<div class="feed_icon"></div>
			', $txt['feed'], '
		</we:title>
		<dl id="feed">';

	// Topic feed
	if (!empty($topic))
		echo '
			<dt>', $txt['feed_current_topic'], '</dt>
			<dd>', sprintf($txt['feed_posts'], '<URL>?topic=' . $topic . ';action=feed'), '</dd>';

	// Board level feed
	if (!empty($board))
	{
		$feed = '<URL>?board=' . $board_info['id'] . ';action=feed';
		echo '
			<dt>', $board_info['type'] == 'blog' ? $txt['feed_current_blog'] : $txt['feed_current_board'], '</dt>
			<dd>', sprintf($txt['feed_posts'], $feed), ' / ', sprintf($txt['feed_topics'], $feed . ';sa=news'), '</dd>';
	}

	// Forum-wide and end
	$feed = '<URL>?action=feed';
	echo '
			<dt>', $txt['feed_everywhere'], '</dt>
			<dd>', sprintf($txt['feed_posts'], $feed), ' / ', sprintf($txt['feed_topics'], $feed . ';sa=news'), '</dd>
		</dl>
	</section>';
}

function template_sidebar_wrap_after()
{
	echo '</we:sidebar>';
}

function template_offside_wrap_before()
{
	echo '<we:offside>';
}

function template_offside_wrap_after()
{
	echo '</we:offside>';
}

function template_content_wrap_before()
{
	echo '
	<div id="content"><div class="frame">';
}

function template_main_wrap_before()
{
	echo '
	<div id="main">';
}

function template_main_wrap_after()
{
	echo '
	</div>';
}

function template_content_wrap_after()
{
	echo '
	</div></div>';
}

function template_wrapper_after()
{
	global $theme;

	echo !empty($theme['forum_width']) ? '</div>' : '', '
</div>';
}

function template_body_after()
{
	global $context, $theme, $options, $txt, $settings, $user_info, $footer_coding;

	$no_resize = $context['browser']['is_ie6'] || $context['browser']['is_ie7'];
	echo '
', $no_resize ? '' : '
<script><!-- // --><![CDATA[
	function weres()
	{
		var d=document,b=d.body,m=d.getElementById("main"),w=m?m.clientWidth:0;
		b.id=w&&w<728?"responsive":w>=969?"":b.id;
	}
	weres();
// ]]></script>';

	// Include postponed inline JS, postponed HTML, and then kickstart the main
	// JavaScript section -- files to include, main vars and functions to start.
	// Don't modify the HTML comments, as they're placeholders for Wedge.

	echo $context['footer'], '
<!-- JavaScript area -->';

	// Code added here through add_js_inline() will execute before jQuery
	// and script.js are loaded. You may add time-critical events here.
	if (!empty($context['footer_js_inline']))
		echo '

<script><!-- // --><![CDATA[', $context['footer_js_inline'], '
// ]]></script>';

	// If user is logged in and has loaded a page in the last 10 minutes,
	// assume their script files are cached and run them in the header.
	// IE 6-8 should always put it below because they're too slow anyway.
	if (!$context['browser']['is_ie8down'] && (time() - $user_info['last_login'] < 600))
	{
		$context['header'] .= theme_base_js(1);
		echo '
<script><!-- // --><![CDATA[
	<!-- insert inline events here -->
	$("select").sb();';
	}
	else
		echo '
<script><!-- // --><![CDATA[
	<!-- insert inline events here -->
// ]]></script>', "\n", theme_base_js(), '<script><!-- // --><![CDATA[';

	echo '
	var
		we_script = "<URL>",
		we_default_theme_url = ', $theme['theme_url'] === $theme['theme_url'] ? 'we_theme_url = ' : '', '"', $theme['default_theme_url'], '", ', $theme['theme_url'] === $theme['theme_url'] ? '' : '
		we_theme_url = "' . $theme['theme_url'] . '",', '
		we_sessid = "', $context['session_id'], '",
		we_sessvar = "', $context['session_var'], '",', $context['server']['iso_case_folding'] && in_array('scripts/sha1.js', $context['javascript_files']) ? '
		we_iso_case_folding = true' : '', '
		we_loading = "', $txt['ajax_in_progress'], '",
		we_cancel = "', $txt['form_cancel'], '";

	', $no_resize ? '' : '$(window).resize(weres).resize();
	', '$("#main_menu").menu();', $context['show_pm_popup'] ? '

	if (confirm(' . JavaScriptEscape($txt['show_personal_messages']) . '))
		window.open(we_prepareScriptUrl() + "action=pm");' : '';

	// Output any postponed JavaScript added by templates
	// and mods, and close all outstanding tags. We're done!
	// $context['footer_js'] assumes the <script> tag is already output.
	echo $context['footer_js'], empty($footer_coding) ? '
<script><!-- // --><![CDATA[' : '', '
	bindEvents();
// ]]></script>
</body>';
}

function template_html_after()
{
	echo '</html>';
}

// Show a linktree - the thing that says "My Community > General Category > General Discussion"...
function template_linktree($force_show = false, $on_bottom = false)
{
	global $context, $theme, $options, $shown_linktree;

	// itemtype is provided for validation purposes.
 	echo '
	<div id="linktree', $on_bottom ? '_bt' : '', '" itemscope itemtype="http://schema.org/WebPage">';

	// If linktree is empty, just return - also allow an override.
	if (!empty($context['linktree']) && ($linksize = count($context['linktree'])) !== 1 && (empty($context['dont_default_linktree']) || $force_show))
	{
		echo '
		<ul itemprop="breadcrumb">';

		// Each tree item has a URL and name. Some may have extra_before and extra_after.
		$num = 0;
		foreach ($context['linktree'] as &$tree)
		{
			echo '
			<li', ++$num == $linksize ? ' class="last"' : '', '>';

			// Show something before the link?
			if (isset($tree['extra_before']))
				echo $tree['extra_before'];

			// Show the link, including a URL if it should have one.
			echo isset($tree['url']) ? '<a href="' . $tree['url'] . '">' . $tree['name'] . '</a>' : $tree['name'];

			// Show something after the link...?
			if (isset($tree['extra_after']))
				echo $tree['extra_after'];

			echo '</li>';
		}
		echo '
		</ul>';
	}

	echo '
	</div>';

	$shown_linktree = true;
}

// Show the menu up top. Something like [home] [profile] [logout]...
function template_menu()
{
	global $context, $theme, $options, $txt;

	echo '
	<div id="navi"><ul id="main_menu" class="css menu">';

	foreach ($context['menu_items'] as $act => $item)
	{
		$class = ($item['active_item'] ? ' chosen' : '') . (empty($item['sub_items']) ? ' nodrop' : '');

		echo '
		<li id="item_', $act, '"', $class ? ' class="' . ltrim($class) . '"' : '', '>
			<span class="m_' . $act . '"></span>
			<h4><a href="', $item['href'], '"', isset($item['target']) ? ' target="' . $item['target'] . '"' : '', '>',
			$item['title'], !empty($item['notice']) ? '<span class="note">' . $item['notice'] . '</span>' : '', '</a></h4>';

		if (!empty($item['sub_items']))
		{
			echo '
			<ul>';

			foreach ($item['sub_items'] as $sub_item)
			{
				if (empty($sub_item))
				{
					echo '
				<li class="separator"><a><hr></a></li>';
					continue;
				}
				echo '
				<li><a href="', $sub_item['href'], '"', isset($sub_item['target']) ? ' target="' . $sub_item['target'] . '"' : '', '>',
				$sub_item['title'], !empty($sub_item['notice']) ? '<span class="note">' . $sub_item['notice'] . '</span>' : '', '</a>';

				// 3rd-level menus
				if (!empty($sub_item['sub_items']))
				{
					echo '
					<ul>';

					foreach ($sub_item['sub_items'] as $subsub_item)
						echo '<li><a href="', $subsub_item['href'], '"', isset($subsub_item['target']) ? ' target="' . $subsub_item['target'] . '"' : '', '>', $subsub_item['title'], '</a></li>';

					echo '</ul>
				';
				}
				echo '</li>';
			}
			echo '
			</ul>';
		}
		echo '
		</li>';
	}
	echo '
	</ul></div>';
}

// The same footer area...
function template_footer()
{
	global $context, $txt, $user_info;

	if (!empty($context['bottom_linktree']))
		template_linktree(false, true);

	echo '
	<div id="footer"><div class="frame">
		<ul class="reset">';

	// Show the load time?
	if ($context['show_load_time'])
		echo '
			<li class="stats"><!-- insert stats here --></li>';

	// Show the credit page (forum admin/mod team and credits),
	// and a link to an HTML conformity checker, for geeks.
	echo '
			<li class="copyright">', $txt['copyright'], '</li>
			<li class="links">
				<a id="site_credits" href="<URL>?action=credits">', $txt['site_credits'], '</a> |
				<a id="button_html5" href="http://validator.nu/?doc=', $user_info['url'], '" target="_blank" class="new_win" title="', $txt['valid_html5'], '">', $txt['html5'], '</a>
			</li>
		</ul>
	</div></div>';
}

/**
 * This function is used to construct the page lists used throughout the application, e.g. 1 ... 6 7 [8] 9 10 ... 15.
 *
 * - The function accepts a start position, for calculating the page out of the list of possible pages, however if the value is not the start of an actual page, the function will sanitise the value so that it will be the actual start of the 'page' of content. It also will sanitise where the start is beyond the last item.
 * - Many URLs in the application are in the form of item=x.y format, e.g. index.php?topic=1.20 to denote topic 1, 20 items in. This can be achieved by specifying $flexible_start as true, and %1$d in the basic URL component, e.g. passing the base URL as index.php?topic=1.%1$d
 * - Only the first and last pages are linked to, and the display will consist of 5 contiguous items centered on the current page, so displaying the current page and 2 page-links either side)
 *
 * @param string $base_url The basic URL to be used for each link.
 * @param int &$start The start position, by reference. If this is not a multiple of the number of items per page, it is sanitized to be so and the value will persist upon the function's return.
 * @param int $max_value The total number of items you are paginating for.
 * @param int $num_per_page The number of items to be displayed on a given page. $start will be forced to be a multiple of this value.
 * @param bool $flexible_start Whether a ;start=x component should be introduced into the URL automatically (see above)
 * @param bool $show_prevnext Whether the Previous and Next links should be shown (should be on only when navigating the list)
 * @return string The complete HTML of the page index that was requested.
 */
function template_page_index($base_url, &$start, $max_value, $num_per_page, $flexible_start = false, $show_prevnext = true)
{
	global $settings, $theme, $topicinfo, $txt, $context;

	// Save whether $start was less than 0 or not.
	$start = (int) $start;
	$start_invalid = $start < 0;

	// Make sure $start is a proper variable - not less than 0.
	if ($start_invalid)
		$start = 0;
	// Not greater than the upper bound.
	elseif ($start >= $max_value)
		$start = max(0, (int) $max_value - (((int) $max_value % (int) $num_per_page) == 0 ? $num_per_page : ((int) $max_value % (int) $num_per_page)));
	// And it has to be a multiple of $num_per_page!
	else
		$start = max(0, (int) $start - ((int) $start % (int) $num_per_page));

	$base_link = '<a href="' . ($flexible_start ? $base_url : strtr($base_url, array('%' => '%%')) . ';start=%1$d') . '">%2$s</a> ';
	$pageindex = '';

	// The number of items to show on each side of the current page, e.g. "6 7 [8] 9 10" if $contiguous = 2. If you want to change it, here's the place.
	$contiguous = isset($settings['compactTopicPagesContiguous']) ? $settings['compactTopicPagesContiguous'] >> 1 : 2;

	// First of all, do we want a 'next' button to take us closer to the first (most interesting) page?
	if ($show_prevnext && $start >= $num_per_page)
		$pageindex .= sprintf($base_link, $start - $num_per_page, $txt['previous_next_back']);

	// Show the first page. (>1< ... 6 7 [8] 9 10 ... 15)
	if ($start > $num_per_page * $contiguous)
		$pageindex .= sprintf($base_link, 0, '1');

	// Show the ... after the first page. (1 >...< 6 7 [8] 9 10 ... 15)
	if ($start > $num_per_page * ($contiguous + 1))
	{
		$base_page = $flexible_start ? $base_url : strtr($base_url, array('%' => '%%')) . ';start=%1$d';
		$pageindex .= '<a data-href="' . $base_page . '" onclick="expandPages(this, ' . $num_per_page . ', ' . ($start - $num_per_page * $contiguous) . ', ' . $num_per_page . ');">&hellip;</a> ';
	}

	// Show the pages before the current one. (1 ... >6 7< [8] 9 10 ... 15)
	for ($nCont = $contiguous; $nCont >= 1; $nCont--)
		if ($start >= $num_per_page * $nCont)
		{
			$tmpStart = $start - $num_per_page * $nCont;
			$pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
		}

	// Show the current page. (1 ... 6 7 >[8]< 9 10 ... 15)
	if (!$start_invalid)
	{
		$page_num = ($start / $num_per_page + 1);
		$pageindex .= '[<strong>' . $page_num . '</strong>] ';
		if ($page_num > 1 && !isset($context['page_indicator']))
			$context['page_indicator'] = number_context('page_indicator', $page_num);
	}
	else
		$pageindex .= sprintf($base_link, $start, $start / $num_per_page + 1);

	// Show the pages after the current one... (1 ... 6 7 [8] >9 10< ... 15)
	$tmpMaxPages = (int) (($max_value - 1) / $num_per_page) * $num_per_page;
	for ($nCont = 1; $nCont <= $contiguous; $nCont++)
		if ($start + $num_per_page * $nCont <= $tmpMaxPages)
		{
			$tmpStart = $start + $num_per_page * $nCont;
			$pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
		}

	// Show the '...' part near the end. (1 ... 6 7 [8] 9 10 >...< 15)
	if ($start + $num_per_page * ($contiguous + 1) < $tmpMaxPages)
	{
		if (!isset($base_page))
			$base_page = $flexible_start ? $base_url : strtr($base_url, array('%' => '%%')) . ';start=%1$d';
		$pageindex .= '<a data-href="' . $base_page . '" onclick="expandPages(this, ' . ($start + $num_per_page * ($contiguous + 1)) . ', ' . $tmpMaxPages . ', ' . $num_per_page . ');">&hellip;</a> ';
	}

	// Show the last number in the list. (1 ... 6 7 [8] 9 10 ... >15<)
	if ($start + $num_per_page * $contiguous < $tmpMaxPages)
		$pageindex .= sprintf($base_link, $tmpMaxPages, $tmpMaxPages / $num_per_page + 1);

	// Finally, the next link.
	if ($show_prevnext && $start + $num_per_page < $max_value)
	{
		$pageindex .= sprintf($base_link, $start + $num_per_page, $txt['previous_next_forward']);

		// If we're in a topic page, and later pages have unread posts, show a New icon next to the following page number!
		if (!empty($topicinfo['new_from']) && $topicinfo['new_from'] <= $topicinfo['id_last_msg'])
			$pageindex .= ' <div class="note next_page">' . $txt['new_short'] . '</div>';
	}

	return $pageindex;
}

// Generate a strip of buttons.
function template_button_strip($button_strip, $direction = 'right', $strip_options = array())
{
	global $theme, $context, $txt;

	if (!is_array($strip_options))
		$strip_options = array();

	// List the buttons in reverse order for RTL languages.
	if ($context['right_to_left'])
		$button_strip = array_reverse($button_strip, true);

	// Create the buttons...
	$buttons = array();
	foreach ($button_strip as $key => $value)
		if (!isset($value['test']) || !empty($context[$value['test']]))
			$buttons[] = '
				<li><a' . (isset($value['id']) ? ' id="button_strip_' . $value['id'] . '"' : '') . ' class="' . $key . (isset($value['class']) ? ' ' . $value['class'] : '') . '" href="' . $value['url'] . '"' . (isset($value['custom']) ? ' ' . $value['custom'] : '') . '>' . $txt[$value['text']] . '</a></li>';

	// No buttons? No button strip either.
	if (empty($buttons))
		return;

	// Make the last one, as easy as possible.
	$buttons[count($buttons) - 1] = str_replace('<li>', '<li class="last">', $buttons[count($buttons) - 1]);

	echo '
			<ul class="buttonlist', !empty($direction) ? ' float' . $direction : '', empty($buttons) ? ' hide' : '', '"', !empty($strip_options['id']) ? ' id="' . $strip_options['id'] . '"': '', '>',
				implode('', $buttons), '
			</ul>';
}

?>