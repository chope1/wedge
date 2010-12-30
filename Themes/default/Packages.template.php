<?php
// Version: 2.0 RC4; Packages

function template_main()
{
	global $context, $settings, $options;
}

function template_view_package()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>', $txt[($context['uninstalling'] ? 'un' : '') . 'install_mod'], '</h3>
		</div>
		<div class="information">';

	if ($context['is_installed'])
		echo '
			<strong>', $txt['package_installed_warning1'], '</strong><br />
			<br />
			', $txt['package_installed_warning2'], '<br />
			<br />';

	echo $txt['package_installed_warning3'], '
		</div>';

	// Do errors exist in the install? If so light them up like a christmas tree.
	if ($context['has_failure'])
	{
		echo '
		<div class="errorbox">
			<strong>', $txt['package_will_fail_title'], '</strong><br />
			', $txt['package_will_fail_warning'], '
		</div>';
	}

	if (isset($context['package_readme']))
	{
		echo '
		<div class="cat_bar">
			<h3>', $txt['package_' . ($context['uninstalling'] ? 'un' : '') . 'install_readme'], '</h3>
		</div>
		<div class="windowbg2 wrc">
			', $context['package_readme'], '
			<span class="floatright">', $txt['package_available_readme_language'], '
				<select name="readme_language" id="readme_language" onchange="if (this.options[this.selectedIndex].value) window.location.href = smf_prepareScriptUrl(smf_scripturl + \'', '?action=admin;area=packages;sa=', $context['uninstalling'] ? 'uninstall' : 'install', ';package=', $context['filename'], ';readme=\' + this.options[this.selectedIndex].value);">';

		foreach ($context['readmes'] as $a => $b)
			echo '
					<option value="', $b, '"', $a === 'selected' ? ' selected="selected"' : '', '>', $b == 'default' ? $txt['package_readme_default'] : ucfirst($b), '</option>';

		echo '
				</select>
			</span>
		</div>
		<br />';
	}

	echo '
		<form action="', $scripturl, '?action=admin;area=packages;sa=', $context['uninstalling'] ? 'uninstall' : 'install', $context['ftp_needed'] ? '' : '2', ';package=', $context['filename'], ';pid=', $context['install_id'], '" onsubmit="submitonce();" method="post" accept-charset="UTF-8">
			<div class="cat_bar">
				<h3>
					', $context['uninstalling'] ? $txt['package_uninstall_actions'] : $txt['package_install_actions'], ' &quot;', $context['package_name'], '&quot;
				</h3>
			</div>';

	// Are there data changes to be removed?
	if ($context['uninstalling'] && !empty($context['database_changes']))
	{
		echo '
			<div class="windowbg2 wrc">
				<label for="do_db_changes"><input type="checkbox" name="do_db_changes" id="do_db_changes" class="input_check" />', $txt['package_db_uninstall'], '</label> [<a href="#" onclick="return swap_database_changes();">', $txt['package_db_uninstall_details'], '</a>]
				<div id="db_changes_div">
					', $txt['package_db_uninstall_actions'], ':
					<ul>';

		foreach ($context['database_changes'] as $change)
			echo '
						<li>', $change, '</li>';
		echo '
					</ul>
				</div>
			</div>';
	}

	echo '
			<div class="information">';

	if (empty($context['actions']) && empty($context['database_changes']))
		echo '
				<strong>', $txt['corrupt_compatible'], '</strong>
			</div>';
	else
	{
		echo '
				', $txt['perform_actions'], '
			</div>
			<table class="table_grid w100 cs0">
			<thead>
				<tr class="catbg">
					<th scope="col" style="width: 20px"></th>
					<th scope="col" style="width: 30px"></th>
					<th scope="col" class="left">', $txt['package_install_type'], '</th>
					<th scope="col" class="left w50">', $txt['package_install_action'], '</th>
					<th scope="col" class="left" style="width: 20%">', $txt['package_install_desc'], '</th>
				</tr>
			</thead>
			<tbody>';

		$alternate = true;
		$i = 1;
		$action_num = 1;
		$js_operations = array();
		foreach ($context['actions'] as $packageaction)
		{
			// Did we pass or fail?  Need to now for later on.
			$js_operations[$action_num] = isset($packageaction['failed']) ? $packageaction['failed'] : 0;

			echo '
				<tr class="windowbg', $alternate ? '' : '2', '">
					<td>', isset($packageaction['operations']) ? '<img id="operation_img_' . $action_num . '" src="' . $settings['images_url'] . '/sort_down.gif" alt="*" style="display: none;" />' : '', '</td>
					<td>', $i++, '.</td>
					<td>', $packageaction['type'], '</td>
					<td>', $packageaction['action'], '</td>
					<td>', $packageaction['description'], '</td>
				</tr>';

			// Is there water on the knee? Operation!
			if (isset($packageaction['operations']))
			{
				echo '
				<tr id="operation_', $action_num, '">
					<td colspan="5" class="windowbg3">
						<table class="w100 cp4 cs0">';

				// Show the operations.
				$alternate2 = true;
				$operation_num = 1;
				foreach ($packageaction['operations'] as $operation)
				{
					// Determine the position text.
					$operation_text = $operation['position'] == 'replace' ? 'operation_replace' : ($operation['position'] == 'before' ? 'operation_after' : 'operation_before');

					echo '
							<tr class="windowbg', $alternate2 ? '' : '2', '">
								<td style="width: 0"></td>
								<td style="width: 30px" class="smalltext"><a href="' . $scripturl . '?action=admin;area=packages;sa=showoperations;operation_key=', $operation['operation_key'], ';package=', $_REQUEST['package'], ';filename=', $operation['filename'], (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'uninstall' ? ';reverse' : ''), '" onclick="return reqWin(this, 680, 400, false);"><img src="', $settings['default_images_url'], '/admin/package_ops.gif" /></a></td>
								<td style="width: 30px" class="smalltext">', $operation_num, '.</td>
								<td style="width: 23%" class="smalltext">', $txt[$operation_text], '</td>
								<td class="w50 smalltext">', $operation['action'], '</td>
								<td style="width: 20%" class="smalltext">', $operation['description'], !empty($operation['ignore_failure']) ? ' (' . $txt['operation_ignore'] . ')' : '', '</td>
							</tr>';

					$operation_num++;
					$alternate2 = !$alternate2;
				}

				echo '
						</table>
					</td>
				</tr>';

				// Increase it.
				$action_num++;
			}
			$alternate = !$alternate;
		}
		echo '
			</tbody>
			</table>';

		// What if we have custom themes we can install into? List them too!
		if (!empty($context['theme_actions']))
		{
			echo '
			<br />
			<div class="cat_bar">
				<h3>
					', $context['uninstalling'] ? $txt['package_other_themes_uninstall'] : $txt['package_other_themes'], '
				</h3>
			</div>
			<div id="custom_changes">
				<div class="information">
					', $txt['package_other_themes_desc'], '
				</div>
				<table class="table_grid w100 cs0">';

			$failure = JavaScriptEscape($txt['package_theme_failure_warning']);

			// Loop through each theme and display it's name, and then it's details.
			foreach ($context['theme_actions'] as $id => $theme)
			{
				// Pass?
				$js_operations[$action_num] = !empty($theme['has_failure']);

				echo '
					<tr class="catbg">
						<td></td>
						<td class="center">';
				if (!empty($context['themes_locked']))
					echo '
							<input type="hidden" name="custom_theme[]" value="', $id, '" />';
				echo '
							<input type="checkbox" name="custom_theme[]" id="custom_theme_', $id, '" value="', $id, '" class="input_check" onclick="', (!empty($theme['has_failure']) ? 'if (this.form.custom_theme_' . $id . '.checked && !confirm(' . $failure . ')) return false;' : ''), 'invertAll(this, this.form, \'dummy_theme_', $id, '\', true);" ', !empty($context['themes_locked']) ? 'disabled="disabled" checked="checked"' : '', '/>
						</td>
						<td colspan="3">
							', $theme['name'], '
						</td>
					</tr>';

				foreach ($theme['actions'] as $action)
				{
					echo '
					<tr class="windowbg', $alternate ? '' : '2', '">
						<td>', isset($packageaction['operations']) ? '<img id="operation_img_' . $action_num . '" src="' . $settings['images_url'] . '/sort_down.gif" alt="*" style="display: none;" />' : '', '</td>
						<td style="width: 30px" class="center">
							<input type="checkbox" name="theme_changes[]" value="', !empty($action['value']) ? $action['value'] : '', '" id="dummy_theme_', $id, '" class="input_check" ', (!empty($action['not_mod']) ? '' : 'disabled="disabled"'), ' ', !empty($context['themes_locked']) ? 'checked="checked"' : '', '/>
						</td>
						<td>', $action['type'], '</td>
						<td class="w50">', $action['action'], '</td>
						<td style="width: 20%"><strong>', $action['description'], '</strong></td>
					</tr>';

					// Is there water on the knee? Operation!
					if (isset($action['operations']))
					{
						echo '
					<tr id="operation_', $action_num, '">
						<td colspan="5" class="windowbg3">
							<table class="w100 cp4 cs0">';

						$alternate2 = true;
						$operation_num = 1;
						foreach ($action['operations'] as $operation)
						{
							// Determine the possition text.
							$operation_text = $operation['position'] == 'replace' ? 'operation_replace' : ($operation['position'] == 'before' ? 'operation_after' : 'operation_before');

							echo '
								<tr class="windowbg', $alternate2 ? '' : '2', '">
									<td style="width: 0"></td>
									<td style="width: 30px" class="smalltext"><a href="' . $scripturl . '?action=admin;area=packages;sa=showoperations;operation_key=', $operation['operation_key'], ';package=', $_REQUEST['package'], ';filename=', $operation['filename'], (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'uninstall' ? ';reverse' : ''), '" onclick="return reqWin(this, 600, 400, false);"><img src="', $settings['default_images_url'], '/admin/package_ops.gif" /></a></td>
									<td style="width: 30px" class="smalltext">', $operation_num, '.</td>
									<td style="width: 23%" class="smalltext">', $txt[$operation_text], '</td>
									<td class="w50 smalltext">', $operation['action'], '</td>
									<td style="width: 20%" class="smalltext">', $operation['description'], !empty($operation['ignore_failure']) ? ' (' . $txt['operation_ignore'] . ')' : '', '</td>
								</tr>';
							$operation_num++;
							$alternate2 = !$alternate2;
						}

						echo '
							</table>
						</td>
					</tr>';

						// Increase it.
						$action_num++;
					}
				}

				$alternate = !$alternate;
			}

			echo '
				</table>
			</div>';
		}
	}

	// Are we effectively ready to install?
	if (!$context['ftp_needed'] && (!empty($context['actions']) || !empty($context['database_changes'])))
		echo '
			<div class="righttext padding">
				<input type="submit" value="', $context['uninstalling'] ? $txt['package_uninstall_now'] : $txt['package_install_now'], '" onclick="return ', !empty($context['has_failure']) ? '(submitThisOnce(this) && confirm(' . JavaScriptEscape($context['uninstalling'] ? $txt['package_will_fail_popup_uninstall'] : $txt['package_will_fail_popup']) . '))' : 'submitThisOnce(this)', ';" class="button_submit" />
			</div>';

	// If we need ftp information then demand it!
	elseif ($context['ftp_needed'])
	{
		echo '
			<div class="cat_bar">
				<h3>', $txt['package_ftp_necessary'], '</h3>
			</div>
			<div>
				', template_control_chmod(), '
			</div>';
	}

	echo '
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />', (isset($context['form_sequence_number']) && !$context['ftp_needed']) ? '
			<input type="hidden" name="seqnum" value="' . $context['form_sequence_number'] . '" />' : '', '
		</form>
	</div>
	<br class="clear" />';

	// Toggle options.
	add_js('
	var aOperationElements = [];');

	// Operations.
	if (!empty($js_operations))
		foreach ($js_operations as $key => $operation)
			add_js('
	aOperationElements[', $key, '] = new smc_Toggle({
		bToggleEnabled: true,
		bCurrentlyCollapsed: ', $operation ? 'false' : 'true', ',
		aSwappableContainers: [
			\'operation_', $key, '\'
		],
		aSwapImages: [
			{
				sId: \'operation_img_', $key, '\',
				srcExpanded: smf_images_url + \'/sort_down.gif\',
				altExpanded: \'*\',
				srcCollapsed: smf_images_url + \'/selected.gif\',
				altCollapsed: \'*\'
			}
		]
	});');

	// And a bit more for database changes.
	if (!empty($context['database_changes']))
		add_js_inline('
	var database_changes_area = document.getElementById(\'db_changes_div\');
	var db_vis = false;
	database_changes_area.style.display = "none";
	function swap_database_changes()
	{
		db_vis = !db_vis;
		database_changes_area.style.display = db_vis ? "" : "none";
		return false;
	}');
}

function template_extract_package()
{
	global $context, $settings, $options, $txt, $scripturl;

	if (!empty($context['redirect_url']))
		add_js_inline('
	setTimeout("doRedirect();", ', empty($context['redirect_timeout']) ? '5000' : $context['redirect_timeout'], ');

	function doRedirect()
	{
		window.location = "', $context['redirect_url'], '";
	}');

	echo '
	<div id="admincenter">';

	if (empty($context['redirect_url']))
		echo '
			<div class="cat_bar">
				<h3>', $context['uninstalling'] ? $txt['uninstall'] : $txt['extracting'], '</h3>
			</div>
			<div class="information">', $txt['package_installed_extract'], '</div>';

	else
		echo '
			<div class="cat_bar">
				<h3>', $txt['package_installed_redirecting'], '</h3>
			</div>';

	echo '
		<div class="windowbg wrc">';

	// If we are going to redirect we have a slightly different agenda.
	if (!empty($context['redirect_url']))
		echo '
			', $context['redirect_text'], '<br /><br />
			<a href="', $context['redirect_url'], '">', $txt['package_installed_redirect_go_now'], '</a> | <a href="', $scripturl, '?action=admin;area=packages;sa=browse">', $txt['package_installed_redirect_cancel'], '</a>';

	elseif ($context['uninstalling'])
		echo '
			', $txt['package_uninstall_done'];

	elseif ($context['install_finished'])
	{
		if ($context['extract_type'] == 'avatar')
			echo '
			', $txt['avatars_extracted'];
		elseif ($context['extract_type'] == 'language')
			echo '
			', $txt['language_extracted'];
		else
			echo '
			', $txt['package_installed_done'];
	}
	else
		echo '
			', $txt['corrupt_compatible'];

	echo '
		</div>';

	// Show the "restore permissions" screen?
	if (function_exists('template_show_list') && !empty($context['restore_file_permissions']['rows']))
	{
		echo '<br />';
		template_show_list('restore_file_permissions');
	}

	echo '
	</div>
	<br class="clear" />';
}

function template_list()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>', $txt['list_file'], '</h3>
		</div>
		<div class="title_bar">
			<h3>', $txt['files_archive'], ' ', $context['filename'], ':</h3>
		</div>
		<div class="windowbg wrc">
			<ol>';

	foreach ($context['files'] as $fileinfo)
		echo '
				<li><a href="', $scripturl, '?action=admin;area=packages;sa=examine;package=', $context['filename'], ';file=', $fileinfo['filename'], '" title="', $txt['view'], '">', $fileinfo['filename'], '</a> (', $fileinfo['size'], ' ', $txt['package_bytes'], ')</li>';

	echo '
			</ol>
			<br />
			<a href="', $scripturl, '?action=admin;area=packages">[ ', $txt['back'], ' ]</a>
		</div>
	</div>
	<br class="clear" />';
}

function template_examine()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>', $txt['package_examine_file'], '</h3>
		</div>
		<div class="title_bar">
			<h3>', $txt['package_file_contents'], ' ', $context['filename'], ':</h3>
		</div>
		<div class="windowbg wrc">
			<pre class="file_content">', $context['filedata'], '</pre>
			<a href="', $scripturl, '?action=admin;area=packages;sa=list;package=', $context['package'], '">[ ', $txt['list_files'], ' ]</a>
		</div>
	</div>
	<br class="clear" />';
}

function template_browse()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings, $forum_version;

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>
				<a href="', $scripturl, '?action=helpadmin;help=latest_packages" onclick="return reqWin(this);" class="help"><img src="', $settings['images_url'], '/helptopics.gif" alt="', $txt['help'], '" class="top" /></a>', $txt['packages_latest'], '
			</h3>
		</div>
		<div class="windowbg2 wrc">
			<div id="packagesLatest">', $txt['packages_latest_fetch'], '</div>
			<div class="clear_right"></div>
		</div>';

	// Make a list of already installed mods so nothing is listed twice ;)
	add_js('
	window.smfForum_scripturl = "', $scripturl, '";
	window.smfForum_sessionid = "', $context['session_id'], '";
	window.smfForum_sessionvar = "', $context['session_var'], '";
	window.smfInstalledPackages = ["', implode('", "', $context['installed_mods']), '"];
	window.smfVersion = "', $context['forum_version'], '";');

	if (empty($modSettings['disable_smf_js']))
		add_js_file($scripturl . '?action=viewsmfile;filename=latest-packages.js', true);

	add_js('
	if (typeof(window.smfLatestPackages) != "undefined")
		$("#packagesLatest").html(window.smfLatestPackages);');

	echo '
		<br />
		<div class="cat_bar">
			<h3>', $txt['browse_packages'], '</h3>
		</div>';

	if (!empty($context['available_mods']))
		template_sublist($context['available_mods'], $txt['modification_package']);

	if (!empty($context['available_avatars']))
		template_sublist($context['available_avatars'], $txt['avatar_package']);

	if (!empty($context['available_languages']))
		template_sublist($context['available_languages'], $txt['language_package']);

	if (!empty($context['available_other']))
		template_sublist($context['available_other'], $txt['unknown_package']);

	if (empty($context['displayed_mod_listing']))
		echo '
		<div class="information">', $txt['no_packages'], '</div>';

	echo '
		<div class="flow_auto">
			<div class="padding smalltext floatleft">
				', $txt['package_installed_key'], '
				<img src="', $settings['images_url'], '/icons/package_installed.gif" class="middle" style="margin-left: 1ex" /> ', $txt['package_installed_current'], '
				<img src="', $settings['images_url'], '/icons/package_old.gif" class="middle" style="margin-left: 2ex" /> ', $txt['package_installed_old'], '
			</div>
			<div class="padding smalltext floatright">
				<a href="#" onclick="$(\'#advanced_box\').toggle(); return false;">', $txt['package_advanced_button'], '</a>
			</div>
		</div>
		<form action="', $scripturl, '?action=admin;area=packages;sa=browse" method="get">
			<div id="advanced_box" style="display: none;">
				<div class="cat_bar">
					<h3>', $txt['package_advanced_options'], '</h3>
				</div>
				<div class="windowbg wrc">
					<p>
						', $txt['package_emulate_desc'], '
					</p>
					<dl class="settings">
						<dt>
							<strong>', $txt['package_emulate'], ':</strong>
							<dfn><a href="#" onclick="$(\'#ve\').val(\'', $forum_version, '\'); return false">', $txt['package_emulate_revert'], '</a></dfn>
						</dt>
						<dd>
							<input type="text" name="version_emulate" id="ve" value="', $context['forum_version'], '" size="25" class="input_text" />
						</dd>
					</dl>
					<div class="righttext padding">
						<input type="submit" value="', $txt['package_apply'], '" class="button_submit" />
					</div>
				</div>
			</div>
			<input type="hidden" name="action" value="admin" />
			<input type="hidden" name="area" value="packages" />
			<input type="hidden" name="sa" value="browse" />
		</form>
	</div>
	<br class="clear" />';
}

function template_sublist(&$mod_list, $mod_heading)
{
	global $context, $txt, $scripturl, $settings;
	static $bad = null;

	if ($bad === null)
		$bad = JavaScriptEscape($txt['package_delete_bad']);

	$context['displayed_mod_listing'] = true;

	echo '
		<br />
		<div class="title_bar">
			<h3>', $mod_heading, '</h3>
		</div>

		<table class="table_grid w100 cs0">
		<thead>
			<tr class="catbg">
				<th class="first_th" style="width: 32px"></th>
				<th class="left w25">', $txt['mod_name'], '</th>
				<th class="left w25">', $txt['mod_version'], '</th>
				<th class="last_th" style="width: 49%"></th>
			</tr>
		</thead>
		<tbody>';

	$alt = false;
	foreach ($mod_list as $i => $package)
	{
		echo '
			<tr class="', $alt ? 'windowbg2' : 'windowbg', '">
				<td>', ++$i, '.</td>
				<td>', $package['name'], '</td>
				<td>
					', $package['version'];

		if ($package['is_installed'] && !$package['is_newer'])
			echo '
					<img src="', $settings['images_url'], '/icons/package_', $package['is_current'] ? 'installed' : 'old', '.gif" class="middle" style="margin-left: 2ex" />';

		echo '
				</td>
				<td class="right">';

		if ($package['can_uninstall'])
			echo '
					<a href="', $scripturl, '?action=admin;area=packages;sa=uninstall;package=', $package['filename'], ';pid=', $package['installed_id'], '">[ ', $txt['uninstall'], ' ]</a>';
		elseif ($package['can_upgrade'])
			echo '
					<a href="', $scripturl, '?action=admin;area=packages;sa=install;package=', $package['filename'], '">[ ', $txt['package_upgrade'], ' ]</a>';
		elseif ($package['can_install'])
			echo '
					<a href="', $scripturl, '?action=admin;area=packages;sa=install;package=', $package['filename'], '">[ ', $txt['install_mod'], ' ]</a>';

		echo '
					<a href="', $scripturl, '?action=admin;area=packages;sa=list;package=', $package['filename'], '">[ ', $txt['list_files'], ' ]</a>
					<a href="', $scripturl, '?action=admin;area=packages;sa=remove;package=', $package['filename'], ';', $context['session_var'], '=', $context['session_id'], '"', $package['is_installed'] && $package['is_current'] ? ' onclick="return confirm(' . $bad . ');"' : '', '>[ ', $txt['package_delete'], ' ]</a>
				</td>
			</tr>';
		$alt = !$alt;
	}

	echo '
		</tbody>
		</table>';
}

function template_servers()
{
	global $context, $settings, $options, $txt, $scripturl;

	if (!empty($context['package_ftp']['error']))
			echo '
					<div class="errorbox">
						<tt>', $context['package_ftp']['error'], '</tt>
					</div>';

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>', $txt['download_new_package'], '</h3>
		</div>';

	if ($context['package_download_broken'])
	{
		echo '
		<div class="title_bar">
			<h3>', $txt['package_ftp_necessary'], '</h3>
		</div>
		<div class="windowbg wrc">
			<p>
				', $txt['package_ftp_why_download'], '
			</p>
			<form action="', $scripturl, '?action=admin;area=packages;get" method="post" accept-charset="UTF-8">
				<dl class="settings">
					<dt>
						<label for="ftp_server">', $txt['package_ftp_server'], ':</label>
					</dt>
					<dd>
						<input type="text" size="30" name="ftp_server" id="ftp_server" value="', $context['package_ftp']['server'], '" class="input_text" />
						<label for="ftp_port">', $txt['package_ftp_port'], ':&nbsp;</label> <input type="text" size="3" name="ftp_port" id="ftp_port" value="', $context['package_ftp']['port'], '" class="input_text" />
					</dd>
					<dt>
						<label for="ftp_username">', $txt['package_ftp_username'], ':</label>
					</dt>
					<dd>
						<input type="text" size="50" name="ftp_username" id="ftp_username" value="', $context['package_ftp']['username'], '" style="width: 99%;" class="input_text" />
					</dd>
					<dt>
						<label for="ftp_password">', $txt['package_ftp_password'], ':</label>
					</dt>
					<dd>
						<input type="password" size="50" name="ftp_password" id="ftp_password" style="width: 99%;" class="input_password" />
					</dd>
					<dt>
						<label for="ftp_path">', $txt['package_ftp_path'], ':</label>
					</dt>
					<dd>
						<input type="text" size="50" name="ftp_path" id="ftp_path" value="', $context['package_ftp']['path'], '" style="width: 99%;" class="input_text" />
					</dd>
				</dl>
				<div class="righttext">
					<input type="submit" value="', $txt['package_proceed'], '" class="button_submit" />
				</div>
			</form>
		</div>';
	}

	echo '
		<div class="windowbg2 wrc">
			<fieldset>
				<legend>' . $txt['package_servers'] . '</legend>
				<ul class="package_servers">';

	foreach ($context['servers'] as $server)
		echo '
					<li class="flow_auto">
						<span class="floatleft">' . $server['name'] . '</span>
						<span class="package_server floatright"><a href="' . $scripturl . '?action=admin;area=packages;get;sa=remove;server=' . $server['id'] . ';', $context['session_var'], '=', $context['session_id'], '">[ ' . $txt['delete'] . ' ]</a></span>
						<span class="package_server floatright"><a href="' . $scripturl . '?action=admin;area=packages;get;sa=browse;server=' . $server['id'] . '">[ ' . $txt['package_browse'] . ' ]</a></span>
					</li>';

	echo '
				</ul>
			</fieldset>
			<fieldset>
				<legend>' . $txt['add_server'] . '</legend>
				<form action="' . $scripturl . '?action=admin;area=packages;get;sa=add" method="post" accept-charset="UTF-8">
					<dl class="settings">
						<dt>
							<strong>' . $txt['server_name'] . ':</strong>
						</dt>
						<dd>
							<input type="text" name="servername" size="40" value="SMF" class="input_text" />
						</dd>
						<dt>
							<strong>' . $txt['serverurl'] . ':</strong>
						</dt>
						<dd>
							<input type="text" name="serverurl" size="50" value="http://" class="input_text" />
						</dd>
					</dl>
					<div class="righttext">
						<input type="submit" value="' . $txt['add_server'] . '" class="button_submit" />
						<input type="hidden" name="' . $context['session_var'] . '" value="' . $context['session_id'] . '" />
					</div>
				</form>
			</fieldset>
			<fieldset>
				<legend>', $txt['package_download_by_url'], '</legend>
				<form action="', $scripturl, '?action=admin;area=packages;get;sa=download;byurl;', $context['session_var'], '=', $context['session_id'], '" method="post" accept-charset="UTF-8">
					<dl class="settings">
						<dt>
							<strong>' . $txt['serverurl'] . ':</strong>
						</dt>
						<dd>
							<input type="text" name="package" size="50" value="http://" class="input_text" />
						</dd>
						<dt>
							<strong>', $txt['package_download_filename'], ':</strong>
						</dt>
						<dd>
							<input type="text" name="filename" size="50" class="input_text" />
							<dfn>', $txt['package_download_filename_info'], '</dfn>
						</dd>
					</dl>
					<div class="righttext">
						<input type="submit" value="', $txt['download'], '" class="button_submit" />
					</div>
				</form>
			</fieldset>
		</div>
		<br />
		<div class="cat_bar">
			<h3>' . $txt['package_upload_title'] . '</h3>
		</div>
		<div class="windowbg wrc">
			<form action="' . $scripturl . '?action=admin;area=packages;get;sa=upload" method="post" accept-charset="UTF-8" enctype="multipart/form-data" style="margin-bottom: 0;">
				<dl class="settings">
					<dt>
						<strong>' . $txt['package_upload_select'] . ':</strong>
					</dt>
					<dd>
						<input type="file" name="package" class="input_file" />
					</dd>
				</dl>
				<div class="righttext">
					<input type="submit" value="' . $txt['package_upload'] . '" class="button_submit" />
					<input type="hidden" name="' . $context['session_var'] . '" value="' . $context['session_id'] . '" />
				</div>
			</form>
		</div>
	</div>
	<br class="clear" />';
}

function template_package_confirm()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>', $context['page_title'], '</h3>
		</div>
		<div class="windowbg wrc">
			<p>', $context['confirm_message'], '</p>
			<a href="', $context['proceed_href'], '">[ ', $txt['package_confirm_proceed'], ' ]</a> <a href="JavaScript:history.go(-1);">[ ', $txt['package_confirm_go_back'], ' ]</a>
		</div>
	</div>
	<br class="clear" />';
}

function template_package_list()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>' . $context['page_title'] . '</h3>
		</div>
		<div class="windowbg wrc">';

	// No packages, as yet.
	if (empty($context['package_list']))
		echo '
			<ul>
				<li>', $txt['no_packages'], '</li>
			</ul>';

	// List out the packages...
	else
	{
		echo '
			<ul id="package_list">';

		foreach ($context['package_list'] as $i => $packageSection)
		{
			echo '
				<li>
					<strong><img id="ps_img_', $i, '" src="', $settings['images_url'], '/upshrink.png" alt="*" style="display: none;" /> ', $packageSection['title'], '</strong>';

			if (!empty($packageSection['text']))
				echo '
					<div class="information">', $packageSection['text'], '</div>';

			echo '
					<', $context['list_type'], ' id="package_section_', $i, '" class="packages">';

			$alt = false;

			foreach ($packageSection['items'] as $id => $package)
			{
				echo '
						<li>';

				// Textual message. Could be empty just for a blank line...
				if ($package['is_text'])
					echo '
							', empty($package['name']) ? '&nbsp;' : $package['name'];

				// This is supposed to be a rule..
				elseif ($package['is_line'])
					echo '
							<hr class="hrcolor" />';

				// A remote link.
				elseif ($package['is_remote'])
					echo '
							<strong>', $package['link'], '</strong>';

				// A title?
				elseif ($package['is_heading'] || $package['is_title'])
					echo '
							<strong>', $package['name'], '</strong>';

				// Otherwise, it's a package.
				else
				{
					// 1. Some mod [ Download ].
					echo '
							<strong><img id="ps_img_', $i, '_pkg_', $id, '" src="', $settings['images_url'], '/upshrink.png" alt="*" style="display: none;" /> ', $package['can_install'] ? '<strong>' . $package['name'] . '</strong> <a href="' . $package['download']['href'] . '">[ ' . $txt['download'] . ' ]</a>' : $package['name'];

					// Mark as installed and current?
					if ($package['is_installed'] && !$package['is_newer'])
						echo '<img src="', $settings['images_url'], '/icons/package_', $package['is_current'] ? 'installed' : 'old', '.gif" width="12" height="11" class="middle" style="margin-left: 2ex" alt="', $package['is_current'] ? $txt['package_installed_current'] : $txt['package_installed_old'], '" />';

					echo '</strong>
							<ul id="package_section_', $i, '_pkg_', $id, '" class="package_section">';

					// Show the mod type?
					if ($package['type'] != '')
						echo '
								<li class="package_section">', $txt['package_type'], ':&nbsp; ', westr::ucwords(westr::strtolower($package['type'])), '</li>';
					// Show the version number?
					if ($package['version'] != '')
						echo '
								<li class="package_section">', $txt['mod_version'], ':&nbsp; ', $package['version'], '</li>';
					// How 'bout the author?
					if (!empty($package['author']) && $package['author']['name'] != '' && isset($package['author']['link']))
						echo '
								<li class="package_section">', $txt['mod_author'], ':&nbsp; ', $package['author']['link'], '</li>';
					// The homepage....
					if ($package['author']['website']['link'] != '')
						echo '
								<li class="package_section">', $txt['author_website'], ':&nbsp; ', $package['author']['website']['link'], '</li>';

					// Desciption: bleh bleh!
					// Location of file: http://someplace/.
					echo '
								<li class="package_section">', $txt['file_location'], ':&nbsp; <a href="', $package['href'], '">', $package['href'], '</a></li>
								<li class="package_section"><div class="information">', $txt['package_description'], ':&nbsp; ', $package['description'], '</div></li>
							</ul>';
				}
				$alt = !$alt;
				echo '
						</li>';
			}
			echo '
					</', $context['list_type'], '>
				</li>';
		}
		echo '
			</ul>';
	}
	echo '
		</div>
		<div class="padding smalltext floatleft">
			', $txt['package_installed_key'], '
			<img src="', $settings['images_url'], '/icons/package_installed.gif" class="middle" style="margin-left: 1ex" /> ', $txt['package_installed_current'], '
			<img src="', $settings['images_url'], '/icons/package_old.gif" class="middle" style="margin-left: 2ex" /> ', $txt['package_installed_old'], '
		</div>
	</div>
	<br class="clear" />';

	// Now go through and turn off all the sections.
	if (!empty($context['package_list']))
	{
		$section_count = count($context['package_list']);

		foreach ($context['package_list'] as $section => $ps)
		{
			add_js('
	var oPackageServerToggle_', $section, ' = new smc_Toggle({
		bToggleEnabled: true,
		bCurrentlyCollapsed: ', count($ps['items']) == 1 || $section_count == 1 ? 'false' : 'true', ',
		aSwappableContainers: [
			\'package_section_', $section, '\'
		],
		aSwapImages: [
			{
				sId: \'ps_img_', $section, '\',
				srcExpanded: smf_images_url + \'/upshrink.png\',
				altExpanded: \'*\',
				srcCollapsed: smf_images_url + \'/upshrink2.png\',
				altCollapsed: \'*\'
			}
		]
	});');

			foreach ($ps['items'] as $id => $package)
				if (!$package['is_text'] && !$package['is_line'] && !$package['is_remote'])
					add_js('
	var oPackageToggle_', $section, '_pkg_', $id, ' = new smc_Toggle({
		bToggleEnabled: true,
		bCurrentlyCollapsed: true,
		aSwappableContainers: [
			\'package_section_', $section, '_pkg_', $id, '\'
		],
		aSwapImages: [
			{
				sId: \'ps_img_', $section, '_pkg_', $id, '\',
				srcExpanded: smf_images_url + \'/upshrink.png\',
				altExpanded: \'*\',
				srcCollapsed: smf_images_url + \'/upshrink2.png\',
				altCollapsed: \'*\'
			}
		]
	});');
		}
	}
}

function template_downloaded()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>', $context['page_title'], '</h3>
		</div>
		<div class="windowbg wrc">
			<p>', (empty($context['package_server']) ? $txt['package_uploaded_successfully'] : $txt['package_downloaded_successfully']), '</p>
			<ul class="reset">
				<li class="reset">
					<span class="floatleft"><strong>', $context['package']['name'], '</strong></span>
					<span class="package_server floatright">', $context['package']['list_files']['link'], '</span>
					<span class="package_server floatright">', $context['package']['install']['link'], '</span>
				</li>
			</ul>
			<br /><br />
			<p><a href="', $scripturl, '?action=admin;area=packages;get', (isset($context['package_server']) ? ';sa=browse;server=' . $context['package_server'] : ''), '">[ ', $txt['back'], ' ]</a></p>
		</div>
	</div>
	<br class="clear" />';
}

function template_install_options()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<div id="admincenter">
		<div class="cat_bar">
			<h3>', $txt['package_install_options'], '</h3>
		</div>
		<div class="information">
			', $txt['package_install_options_ftp_why'], '
		</div>

		<div class="windowbg wrc">
			<form action="', $scripturl, '?action=admin;area=packages;sa=options" method="post" accept-charset="UTF-8">
				<dl class="settings">
					<dt>
						<label for="pack_server"><strong>', $txt['package_install_options_ftp_server'], ':</strong></label>
					</dt>
					<dd>
						<input type="text" name="pack_server" id="pack_server" value="', $context['package_ftp_server'], '" size="30" class="input_text" />
					</dd>
					<dt>
						<label for="pack_port"><strong>', $txt['package_install_options_ftp_port'], ':</strong></label>
					</dt>
					<dd>
						<input type="text" name="pack_port" id="pack_port" size="3" value="', $context['package_ftp_port'], '" class="input_text" />
					</dd>
					<dt>
						<label for="pack_user"><strong>', $txt['package_install_options_ftp_user'], ':</strong></label>
					</dt>
					<dd>
						<input type="text" name="pack_user" id="pack_user" value="', $context['package_ftp_username'], '" size="30" class="input_text" />
					</dd>
				</dl>
				<label for="package_make_backups"><input type="checkbox" name="package_make_backups" id="package_make_backups" value="1" class="input_check"', $context['package_make_backups'] ? ' checked="checked"' : '', ' /> ', $txt['package_install_options_make_backups'], '</label><br /><br />
				<div class="righttext">
					<input type="submit" name="submit" value="', $txt['save'], '" class="button_submit" />
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</div>
			</form>
		</div>
	</div>
	<br class="clear" />';
}

function template_control_chmod()
{
	global $context, $settings, $options, $txt, $scripturl;

	// Nothing to do? Brilliant!
	if (empty($context['package_ftp']))
		return false;

	if (empty($context['package_ftp']['form_elements_only']))
	{
		echo '
				', sprintf($txt['package_ftp_why'], '$(\'#need_writable_list\').show(); return false;'), '<br />
				<div id="need_writable_list" class="smalltext">
					', $txt['package_ftp_why_file_list'], '
					<ul style="display: inline;">';

		if (!empty($context['notwritable_files']))
			foreach ($context['notwritable_files'] as $file)
				echo '
						<li>', $file, '</li>';

		echo '
					</ul>
				</div>';
	}

	echo '
				<div class="bordercolor" id="ftp_error_div" style="', (!empty($context['package_ftp']['error']) ? '' : 'display:none;'), 'padding: 1px; margin: 1ex;"><div class="windowbg2" id="ftp_error_innerdiv" style="padding: 1ex;">
					<tt id="ftp_error_message">', !empty($context['package_ftp']['error']) ? $context['package_ftp']['error'] : '', '</tt>
				</div></div>';

	if (!empty($context['package_ftp']['destination']))
		echo '
				<form action="', $context['package_ftp']['destination'], '" method="post" accept-charset="UTF-8" style="margin: 0;">';

	echo '
					<fieldset>
					<dl class="settings">
						<dt>
							<label for="ftp_server">', $txt['package_ftp_server'], ':</label>
						</dt>
						<dd>
							<input type="text" size="30" name="ftp_server" id="ftp_server" value="', $context['package_ftp']['server'], '" class="input_text" />
							<label for="ftp_port">', $txt['package_ftp_port'], ':&nbsp;</label> <input type="text" size="3" name="ftp_port" id="ftp_port" value="', $context['package_ftp']['port'], '" class="input_text" />
						</dd>
						<dt>
							<label for="ftp_username">', $txt['package_ftp_username'], ':</label>
						</dt>
						<dd>
							<input type="text" size="50" name="ftp_username" id="ftp_username" value="', $context['package_ftp']['username'], '" style="width: 98%;" class="input_text" />
						</dd>
						<dt>
							<label for="ftp_password">', $txt['package_ftp_password'], ':</label>
						</dt>
						<dd>
							<input type="password" size="50" name="ftp_password" id="ftp_password" style="width: 98%;" class="input_password" />
						</dd>
						<dt>
							<label for="ftp_path">', $txt['package_ftp_path'], ':</label>
						</dt>
						<dd>
							<input type="text" size="50" name="ftp_path" id="ftp_path" value="', $context['package_ftp']['path'], '" style="width: 98%;" class="input_text" />
						</dd>
					</dl>
					</fieldset>';

	if (empty($context['package_ftp']['form_elements_only']))
		echo '

					<div class="righttext" style="margin: 1ex;">
						<span id="test_ftp_placeholder_full"></span>
						<input type="submit" value="', $txt['package_proceed'], '" class="button_submit" />
					</div>';

	if (!empty($context['package_ftp']['destination']))
		echo '
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</form>';

	// Hide the details of the list.
	if (empty($context['package_ftp']['form_elements_only']))
		add_js_inline('
	document.getElementById(\'need_writable_list\').style.display = \'none\';');

	// Quick generate the test button.
	add_js('
	// Generate a "test ftp" button.
	var generatedButton = false;
	function generateFTPTest()
	{
		// Don\'t ever call this twice!
		if (generatedButton)
			return false;
		generatedButton = true;

		// No XML?
		if (!can_ajax || (!$("#test_ftp_placeholder").length && !$("#test_ftp_placeholder_full").length))
			return false;

		var ftpTest = $(\'<input type="button"></input>\').click(testFTP);

		if ($("#test_ftp_placeholder").length)
			ftpTest.val(', JavaScriptEscape($txt['package_ftp_test']), ').appendTo($("#test_ftp_placeholder"));
		else
			ftpTest.val(', JavaScriptEscape($txt['package_ftp_test_connection']), ').appendTo($("#test_ftp_placeholder_full"));
	}
	function testFTP()
	{
		ajax_indicator(true);

		// What we need to post.
		var oPostData = {
			0: "ftp_server",
			1: "ftp_port",
			2: "ftp_username",
			3: "ftp_password",
			4: "ftp_path"
		}

		var sPostData = "";
		for (i = 0; i < 5; i++)
			sPostData = sPostData + (sPostData.length == 0 ? "" : "&") + oPostData[i] + "=" + escape(document.getElementById(oPostData[i]).value);

		// Post the data out.
		sendXMLDocument(smf_prepareScriptUrl(smf_scripturl) + \'action=admin;area=packages;sa=ftptest;xml;' . $context['session_var'] . '=' . $context['session_id'] . '\', sPostData, testFTPResults);
	}
	function testFTPResults(oXMLDoc)
	{
		ajax_indicator(false);

		// This assumes it went wrong!
		var wasSuccess = false;
		var message = ' . JavaScriptEscape($txt['package_ftp_test_failed']) . ';

		var results = oXMLDoc.getElementsByTagName(\'results\')[0].getElementsByTagName(\'result\');
		if (results.length > 0)
		{
			if (results[0].getAttribute(\'success\') == 1)
				wasSuccess = true;
			message = results[0].firstChild.nodeValue;
		}

		$("#ftp_error_div").show().css("backgroundColor", wasSuccess ? "green" : "red");
		$("#ftp_error_innerdiv").css("backgroundColor", wasSuccess ? "#DBFDC7" : "#FDBDBD");
		$("#ftp_error_message").html(message);
	}
	generateFTPTest();');
}

function template_ftp_required()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
		<fieldset>
			<legend>
				', $txt['package_ftp_necessary'], '
			</legend>
			<div class="ftp_details">
				', template_control_chmod(), '
			</div>
		</fieldset>';
}

function template_view_operations()
{
	global $context, $txt, $settings;

	// Determine the position text.
	$operation_text = $context['operations']['position'] == 'replace' ? 'operation_replace' : ($context['operations']['position'] == 'before' ? 'operation_after' : 'operation_before');

	echo '<!DOCTYPE html>
<html', $context['right_to_left'] ? ' dir="rtl"' : '', '>
<head>
	<title>', $txt['operation_title'], '</title>
	<meta charset="utf-8" />',
	theme_base_css(), '
	<link rel="stylesheet" href="', $settings['theme_url'], '/css/admin.css" />',
	theme_base_js(1), '
</head>
<body>
	<div class="padding windowbg">
		<div class="cat_bar">
			<h3>
				', $txt['operation_find'], '<a href="#" onclick="return smfSelectText(\'find_code\', true);" class="smalltext" style="font-weight: normal;">' . $txt['code_select'] . '</a>
			</h3>
		</div>
		<div class="padding">
			<code id="find_code" style="overflow: auto; max-height: 200px; white-space: pre;">', $context['operations']['position'] == 'end' ? '?&gt;' : $context['operations']['search'], '</code>
		</div>
		<div class="cat_bar">
			<h3 class="topmargin">
				', $txt[$operation_text], '<a href="#" onclick="return smfSelectText(\'replace_code\', true);" class="smalltext" style="font-weight: normal;">' . $txt['code_select'] . '</a>
			</h3>
		</div>
		<div class="padding">
			<code id="replace_code" style="overflow: auto; max-height: 200px; white-space: pre;">', $context['operations']['replace'], '</code>
		</div>
	</div>
</body>
</html>';
}

function template_file_permissions()
{
	global $txt, $scripturl, $context, $settings;

	// This will handle expanding the selection.
	add_js('
	var oRadioColors = {
		0: "#D1F7BF",
		1: "#FFBBBB",
		2: "#FDD7AF",
		3: "#C2C6C0",
		4: "#EEEEEE"
	}
	var oRadioValues = {
		0: "read",
		1: "writable",
		2: "execute",
		3: "custom",
		4: "no_change"
	}
	function expandFolder(folderIdent, folderReal)
	{
		// See if it already exists.
		var possibleTags = document.getElementsByTagName("tr");
		var foundOne = false;

		for (var i = 0; i < possibleTags.length; i++)
		{
			if (possibleTags[i].id.indexOf("content_" + folderIdent + ":-:") == 0)
			{
				possibleTags[i].style.display = possibleTags[i].style.display == "none" ? "" : "none";
				foundOne = true;
			}
		}

		// Got something then we\'re done.
		if (foundOne)
			return false;

		// Otherwise we need to get the wicked thing.
		else if (can_ajax)
		{
			ajax_indicator(true);
			getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + \'action=admin;area=packages;onlyfind=\' + escape(folderReal) + \';sa=perms;xml;', $context['session_var'], '=', $context['session_id'], '\', onNewFolderReceived);
		}
		// Otherwise reload.
		else
			return true;

		return false;
	}
	function dynamicExpandFolder()
	{
		expandFolder(this.ident, this.path);

		return false;
	}
	function dynamicAddMore()
	{
		ajax_indicator(true);

		getXMLDocument(smf_prepareScriptUrl(smf_scripturl) + \'action=admin;area=packages;fileoffset=\' + (parseInt(this.offset) + ', $context['file_limit'], ') + \';onlyfind=\' + escape(this.path) + \';sa=perms;xml;', $context['session_var'], '=', $context['session_id'], '\', onNewFolderReceived);
	}
	function repeatString(sString, iTime)
	{
		if (iTime < 1)
			return \'\';
		else
			return sString + repeatString(sString, iTime - 1);
	}
	// Create a named element dynamically - thanks to: http://www.thunderguy.com/semicolon/2005/05/23/setting-the-name-attribute-in-internet-explorer/
	function createNamedElement(type, name, customFields)
	{
		var element = null;

		if (!customFields)
			customFields = "";

		// Try the IE way; this fails on standards-compliant browsers
		try
		{
			element = document.createElement("<" + type + \' name="\' + name + \'" \' + customFields + ">");
		}
		catch (e)
		{
		}
		if (!element || element.nodeName != type.toUpperCase())
		{
			// Non-IE browser; use canonical method to create named element
			element = document.createElement(type);
			element.name = name;
		}

		return element;
	}
	// Getting something back?
	function onNewFolderReceived(oXMLDoc)
	{
		ajax_indicator(false);

		var fileItems = $("folders folder", oXMLDoc);

		// No folders, no longer worth going further.
		if (!fileItems.length)
		{
			if (oXMLDoc.getElementsByTagName(\'roots\')[0].getElementsByTagName(\'root\')[0])
			{
				var itemLink = $(\'#link_\' + $("roots root", oXMLDoc).text())[0];

				// Move the children up.
				for (i = 0; i <= itemLink.childNodes.length; i++)
					itemLink.parentNode.insertBefore(itemLink.childNodes[0], itemLink);

				// And remove the link.
				itemLink.parentNode.removeChild(itemLink);
			}
			return false;
		}
		var tableHandle = false;
		var isMore = false;
		var ident = "";
		var my_ident = "";
		var curLevel = 0;

		for (var i = 0; i < fileItems.length; i++)
		{
			if (fileItems[i].getAttribute(\'more\') == 1)
			{
				isMore = true;
				var curOffset = fileItems[i].getAttribute(\'offset\');
			}

			if (fileItems[i].getAttribute(\'more\') != 1 && document.getElementById("insert_div_loc_" + fileItems[i].getAttribute(\'ident\')))
			{
				ident = fileItems[i].getAttribute(\'ident\');
				my_ident = fileItems[i].getAttribute(\'my_ident\');
				curLevel = fileItems[i].getAttribute(\'level\') * 5;
				curPath = fileItems[i].getAttribute(\'path\');

				// Get where we\'re putting it next to.
				tableHandle = document.getElementById("insert_div_loc_" + fileItems[i].getAttribute(\'ident\'));

				var curRow = document.createElement("tr");
				curRow.className = "windowbg";
				curRow.id = "content_" + my_ident;
				curRow.style.display = "";
				var curCol = document.createElement("td");
				curCol.className = "smalltext";
				curCol.width = "40%";

				// This is the name.
				var fileName = document.createTextNode(fileItems[i].firstChild.nodeValue);

				// Start by wacking in the spaces.
				curCol.innerHTML = repeatString("&nbsp;", curLevel);

				// Create the actual text.
				if (fileItems[i].getAttribute(\'folder\') == 1)
				{
					var linkData = document.createElement("a");
					linkData.name = "fol_" + my_ident;
					linkData.id = "link_" + my_ident;
					linkData.href = \'#\';
					linkData.path = curPath + "/" + fileItems[i].firstChild.nodeValue;
					linkData.ident = my_ident;
					linkData.onclick = dynamicExpandFolder;

					var folderImage = document.createElement("img");
					folderImage.src = \'', addcslashes($settings['default_images_url'], "\\"), '/board.gif\';
					linkData.appendChild(folderImage);

					linkData.appendChild(fileName);
					curCol.appendChild(linkData);
				}
				else
					curCol.appendChild(fileName);

				curRow.appendChild(curCol);

				// Right, the permissions.
				curCol = document.createElement("td");
				curCol.className = "smalltext";

				var writeSpan = document.createElement("span");
				writeSpan.style.color = fileItems[i].getAttribute(\'writable\') ? "green" : "red";
				writeSpan.innerHTML = fileItems[i].getAttribute(\'writable\') ? \'', $txt['package_file_perms_writable'], '\' : \'', $txt['package_file_perms_not_writable'], '\';
				curCol.appendChild(writeSpan);

				if (fileItems[i].getAttribute(\'permissions\'))
				{
					var permData = document.createTextNode("\u00a0(', $txt['package_file_perms_chmod'], ': " + fileItems[i].getAttribute(\'permissions\') + ")");
					curCol.appendChild(permData);
				}

				curRow.appendChild(curCol);

				// Now add the five radio buttons.
				for (j = 0; j < 5; j++)
				{
					curCol = document.createElement("td");
					curCol.style.backgroundColor = oRadioColors[j];
					curCol.align = "center";

					var curInput = createNamedElement("input", "permStatus[" + curPath + "/" + fileItems[i].firstChild.nodeValue + "]", j == 4 ? \'checked="checked"\' : "");
					curInput.type = "radio";
					curInput.checked = "checked";
					curInput.value = oRadioValues[j];

					curCol.appendChild(curInput);
					curRow.appendChild(curCol);
				}

				// Put the row in.
				tableHandle.parentNode.insertBefore(curRow, tableHandle);

				// Put in a new dummy section?
				if (fileItems[i].getAttribute(\'folder\') == 1)
				{
					var newRow = document.createElement("tr");
					newRow.id = "insert_div_loc_" + my_ident;
					newRow.style.display = "none";
					tableHandle.parentNode.insertBefore(newRow, tableHandle);
					var newCol = document.createElement("td");
					newCol.colspan = 2;
					newRow.appendChild(newCol);
				}
			}
		}

		// Is there some more to remove?
		$("#content_" + ident + "_more").remove();

		// Add more?
		if (isMore && tableHandle)
		{
			// Create the actual link.
			var linkData = document.createElement("a");
			linkData.href = \'#fol_\' + my_ident;
			linkData.path = curPath;
			linkData.offset = curOffset;
			linkData.onclick = dynamicAddMore;

			linkData.appendChild(document.createTextNode(\'', $txt['package_file_perms_more_files'], '\'));

			curRow = document.createElement("tr");
			curRow.className = "windowbg";
			curRow.id = "content_" + ident + "_more";
			tableHandle.parentNode.insertBefore(curRow, tableHandle);
			curCol = document.createElement("td");
			curCol.className = "smalltext";
			curCol.width = "40%";

			curCol.innerHTML = repeatString("&nbsp;", curLevel);
			curCol.appendChild(document.createTextNode(\'\\u00ab \'));
			curCol.appendChild(linkData);
			curCol.appendChild(document.createTextNode(\' \\u00bb\'));

			curRow.appendChild(curCol);
			curCol = document.createElement("td");
			curCol.className = "smalltext";
			curRow.appendChild(curCol);
		}

		// Keep track of it.
		var curInput = createNamedElement("input", "back_look[]");
		curInput.type = "hidden";
		curInput.value = curPath;

		curCol.appendChild(curInput);
	}');

		echo '
	<div class="information">
		<div>
			<strong>', $txt['package_file_perms_warning'], ':</strong>
			<div class="smalltext">
				<ol style="margin-top: 2px; margin-bottom: 2px">
					', $txt['package_file_perms_warning_desc'], '
				</ol>
			</div>
		</div>
	</div>

	<form action="', $scripturl, '?action=admin;area=packages;sa=perms;', $context['session_var'], '=', $context['session_id'], '" method="post" accept-charset="UTF-8">
		<div class="title_bar">
			<h3>
				<span class="fperm floatright">', $txt['package_file_perms_new_status'], '</span>
				', $txt['package_file_perms'], '
			</h3>
		</div>
		<table class="table_grid w100 cs0 cp0">
		<thead>
			<tr class="catbg center">
				<th class="first_th left" style="width: 30%">&nbsp;', $txt['package_file_perms_name'], '&nbsp;</th>
				<th style="width: 30%" class="left">', $txt['package_file_perms_status'], '</th>
				<th style="width: 8%"><span class="filepermissions">', $txt['package_file_perms_status_read'], '</span></th>
				<th style="width: 8%"><span class="filepermissions">', $txt['package_file_perms_status_write'], '</span></th>
				<th style="width: 8%"><span class="filepermissions">', $txt['package_file_perms_status_execute'], '</span></th>
				<th style="width: 8%"><span class="filepermissions">', $txt['package_file_perms_status_custom'], '</span></th>
				<th style="width: 8%" class="last_th"><span class="filepermissions">', $txt['package_file_perms_status_no_change'], '</span></th>
			</tr>
		</thead>';

	foreach ($context['file_tree'] as $name => $dir)
	{
		echo '
		<tbody>
			<tr class="windowbg2 center">
				<td style="width: 30%" class="left"><strong>';

		if (!empty($dir['type']) && ($dir['type'] == 'dir' || $dir['type'] == 'dir_recursive'))
			echo '
					<img src="', $settings['default_images_url'], '/board.gif" alt="*" />';

		echo '
					', $name, '
				</strong></td>
				<td style="width: 30%" class="left">
					<span style="color: ', ($dir['perms']['chmod'] ? 'green' : 'red'), '">', ($dir['perms']['chmod'] ? $txt['package_file_perms_writable'] : $txt['package_file_perms_not_writable']), '</span>
					', ($dir['perms']['perms'] ? '&nbsp;(' . $txt['package_file_perms_chmod'] . ': ' . substr(sprintf('%o', $dir['perms']['perms']), -4) . ')' : ''), '
				</td>
				<td style="width: 8%" class="perm_read"><input type="radio" name="permStatus[', $name, ']" value="read" class="input_radio" /></td>
				<td style="width: 8%" class="perm_write"><input type="radio" name="permStatus[', $name, ']" value="writable" class="input_radio" /></td>
				<td style="width: 8%" class="perm_execute"><input type="radio" name="permStatus[', $name, ']" value="execute" class="input_radio" /></td>
				<td style="width: 8%" class="perm_custom"><input type="radio" name="permStatus[', $name, ']" value="custom" class="input_radio" /></td>
				<td style="width: 8%" class="perm_nochange"><input type="radio" name="permStatus[', $name, ']" value="no_change" checked="checked" class="input_radio" /></td>
			</tr>
		</tbody>';

		if (!empty($dir['contents']))
			template_permission_show_contents($name, $dir['contents'], 1);
	}

	echo '

		</table>
		<br />
		<div class="cat_bar">
			<h3>', $txt['package_file_perms_change'], '</h3>
		</div>
		<div class="windowbg wrc">
			<fieldset>
				<dl>
					<dt>
						<input type="radio" name="method" value="individual" checked="checked" id="method_individual" class="input_radio" />
						<label for="method_individual"><strong>', $txt['package_file_perms_apply'], '</strong></label>
					</dt>
					<dd>
						<em class="smalltext">', $txt['package_file_perms_custom'], ': <input type="text" name="custom_value" value="0755" maxlength="4" size="5" class="input_text" />&nbsp;<a href="', $scripturl, '?action=helpadmin;help=chmod_flags" onclick="return reqWin(this);" class="help">(?)</a></em>
					</dd>
					<dt>
						<input type="radio" name="method" value="predefined" id="method_predefined" class="input_radio" />
						<label for="method_predefined"><strong>', $txt['package_file_perms_predefined'], ':</strong></label>
						<select name="predefined" onchange="$(\'#method_predefined\').attr(\'checked\', true);">
							<option value="restricted" selected="selected">', $txt['package_file_perms_pre_restricted'], '</option>
							<option value="standard">', $txt['package_file_perms_pre_standard'], '</option>
							<option value="free">', $txt['package_file_perms_pre_free'], '</option>
						</select>
					</dt>
					<dd>
						<em class="smalltext">', $txt['package_file_perms_predefined_note'], '</em>
					</dd>
				</dl>
			</fieldset>';

	// Likely to need FTP?
	if (empty($context['ftp_connected']))
		echo '
			<p>
				', $txt['package_file_perms_ftp_details'], ':
			</p>
			', template_control_chmod(), '
			<div class="information">', $txt['package_file_perms_ftp_retain'], '</div>';

	echo '
			<span id="test_ftp_placeholder_full"></span>
			<div class="righttext padding">
				<input type="hidden" name="action_changes" value="1" />
				<input type="submit" value="', $txt['package_file_perms_go'], '" name="go" class="button_submit" />
			</div>
		</div>';

	// Any looks fors we've already done?
	foreach ($context['look_for'] as $path)
		echo '
		<input type="hidden" name="back_look[]" value="', $path, '" />';

	echo '
	</form><br />';
}

function template_permission_show_contents($ident, $contents, $level, $has_more = false)
{
	global $settings, $txt, $scripturl, $context;

	$js_ident = preg_replace('~[^A-Za-z0-9_\-=:]~', ':-:', $ident);
	// Have we actually done something?
	$drawn_div = false;

	foreach ($contents as $name => $dir)
	{
		if (isset($dir['perms']))
		{
			if (!$drawn_div)
			{
				$drawn_div = true;
				echo '
		</table>
		<table class="table_grid w100 cs0" id="', $js_ident, '">';
			}

			$cur_ident = preg_replace('~[^A-Za-z0-9_\-=:]~', ':-:', $ident . '/' . $name);
			echo '
			<tr class="windowbg center" id="content_', $cur_ident, '">
				<td style="width: 30%" class="smalltext left">' . str_repeat('&nbsp;', $level * 5), '
					', (!empty($dir['type']) && $dir['type'] == 'dir_recursive') || !empty($dir['list_contents']) ? '<a id="link_' . $cur_ident . '" href="' . $scripturl . '?action=admin;area=packages;sa=perms;find=' . base64_encode($ident . '/' . $name) . ';back_look=' . $context['back_look_data'] . ';' . $context['session_var'] . '=' . $context['session_id'] . '#fol_' . $cur_ident . '" onclick="return expandFolder(\'' . $cur_ident . '\', \'' . addcslashes($ident . '/' . $name, "'\\") . '\');">' : '';

			if (!empty($dir['type']) && ($dir['type'] == 'dir' || $dir['type'] == 'dir_recursive'))
				echo '
					<img src="', $settings['default_images_url'], '/board.gif" alt="*" />';

			echo '
					', $name, '
					', (!empty($dir['type']) && $dir['type'] == 'dir_recursive') || !empty($dir['list_contents']) ? '</a>' : '', '
				</td>
				<td style="width: 30%" class="smalltext left">
					<span class="', ($dir['perms']['chmod'] ? 'success' : 'error'), '">', ($dir['perms']['chmod'] ? $txt['package_file_perms_writable'] : $txt['package_file_perms_not_writable']), '</span>
					', ($dir['perms']['perms'] ? '&nbsp;(' . $txt['package_file_perms_chmod'] . ': ' . substr(sprintf('%o', $dir['perms']['perms']), -4) . ')' : ''), '
				</td>
				<td style="width: 8%" class="perm_read"><input type="radio" name="permStatus[', $ident . '/' . $name, ']" value="read" class="input_radio" /></td>
				<td style="width: 8%" class="perm_write"><input type="radio" name="permStatus[', $ident . '/' . $name, ']" value="writable" class="input_radio" /></td>
				<td style="width: 8%" class="perm_execute"><input type="radio" name="permStatus[', $ident . '/' . $name, ']" value="execute" class="input_radio" /></td>
				<td style="width: 8%" class="perm_custom"><input type="radio" name="permStatus[', $ident . '/' . $name, ']" value="custom" class="input_radio" /></td>
				<td style="width: 8%" class="perm_nochange"><input type="radio" name="permStatus[', $ident . '/' . $name, ']" value="no_change" checked="checked" class="input_radio" /></td>
			</tr>
			<tr id="insert_div_loc_' . $cur_ident . '" style="display: none;"><td></td></tr>';

			if (!empty($dir['contents']))
				template_permission_show_contents($ident . '/' . $name, $dir['contents'], $level + 1, !empty($dir['more_files']));
		}
	}

	// We have more files to show?
	if ($has_more)
		echo '
			<tr class="windowbg" id="content_', $js_ident, '_more">
				<td class="smalltext" style="width: 40%">' . str_repeat('&nbsp;', $level * 5), '
					&#171; <a href="' . $scripturl . '?action=admin;area=packages;sa=perms;find=' . base64_encode($ident) . ';fileoffset=', ($context['file_offset'] + $context['file_limit']), ';' . $context['session_var'] . '=' . $context['session_id'] . '#fol_' . preg_replace('~[^A-Za-z0-9_\-=:]~', ':-:', $ident) . '">', $txt['package_file_perms_more_files'], '</a> &#187;
				</td>
				<td colspan="6"></td>
			</tr>';

	if ($drawn_div)
	{
		// Hide anything too far down the tree.
		$isFound = false;
		foreach ($context['look_for'] as $tree)
			if (substr($tree, 0, strlen($ident)) == $ident)
				$isFound = true;

		if ($level > 1 && !$isFound)
		{
			echo '
		</table>';
			add_js('
	expandFolder(\'', $js_ident, '\', \'\');');
		}

		echo '
		<table class="table_grid w100 cs0">
			<tr style="display: none"><td></td></tr>';
	}
}

function template_action_permissions()
{
	global $txt, $scripturl, $context, $settings;

	$countDown = 3;

	echo '
	<div id="admincenter">
		<form action="', $scripturl, '?action=admin;area=packages;sa=perms;', $context['session_var'], '=', $context['session_id'], '" id="perm_submit" method="post" accept-charset="UTF-8">
			<div class="cat_bar">
				<h3>', $txt['package_file_perms_applying'], '</h3>
			</div>';

	if (!empty($context['skip_ftp']))
		echo '
			<div class="errorbox">
				', $txt['package_file_perms_skipping_ftp'], '
			</div>';

	// How many have we done?
	$remaining_items = count($context['method'] == 'individual' ? $context['to_process'] : $context['directory_list']);
	$progress_message = sprintf($context['method'] == 'individual' ? $txt['package_file_perms_items_done'] : $txt['package_file_perms_dirs_done'], $context['total_items'] - $remaining_items, $context['total_items']);
	$progress_percent = round(($context['total_items'] - $remaining_items) / $context['total_items'] * 100, 1);

	echo '
			<div class="windowbg wrc">
				<div style="padding-left: 20%; padding-right: 20%; margin-top: 1ex;">
					<strong>', $progress_message, '</strong>
					<div style="font-size: 8pt; height: 12pt; border: 1px solid black; background-color: white; padding: 1px; position: relative;">
						<div style="padding-top: ', $context['browser']['is_webkit'] ? '2pt' : '1pt', '; width: 100%; z-index: 2; color: black; position: absolute; text-align: center; font-weight: bold;">', $progress_percent, '%</div>
						<div style="width: ', $progress_percent, '%; height: 12pt; z-index: 1; background-color: #98b8f4;">&nbsp;</div>
					</div>
				</div>';

	// Second progress bar for a specific directory?
	if ($context['method'] != 'individual' && !empty($context['total_files']))
	{
		$file_progress_message = sprintf($txt['package_file_perms_files_done'], $context['file_offset'], $context['total_files']);
		$file_progress_percent = round($context['file_offset'] / $context['total_files'] * 100, 1);

		echo '
				<br />
				<div style="padding-left: 20%; padding-right: 20%; margin-top: 1ex;">
					<strong>', $file_progress_message, '</strong>
					<div style="font-size: 8pt; height: 12pt; border: 1px solid black; background-color: white; padding: 1px; position: relative;">
						<div style="padding-top: ', $context['browser']['is_webkit'] ? '2pt' : '1pt', '; width: 100%; z-index: 2; color: black; position: absolute; text-align: center; font-weight: bold;">', $file_progress_percent, '%</div>
						<div style="width: ', $file_progress_percent, '%; height: 12pt; z-index: 1; background-color: #c1ffc1;">&nbsp;</div>
					</div>
				</div>';
	}

	echo '
				<br />';

	// Put out the right hidden data.
	if ($context['method'] == 'individual')
		echo '
				<input type="hidden" name="custom_value" value="', $context['custom_value'], '" />
				<input type="hidden" name="totalItems" value="', $context['total_items'], '" />
				<input type="hidden" name="toProcess" value="', base64_encode(serialize($context['to_process'])), '" />';
	else
		echo '
				<input type="hidden" name="predefined" value="', $context['predefined_type'], '" />
				<input type="hidden" name="fileOffset" value="', $context['file_offset'], '" />
				<input type="hidden" name="totalItems" value="', $context['total_items'], '" />
				<input type="hidden" name="dirList" value="', base64_encode(serialize($context['directory_list'])), '" />
				<input type="hidden" name="specialFiles" value="', base64_encode(serialize($context['special_files'])), '" />';

	// Are we not using FTP for whatever reason.
	if (!empty($context['skip_ftp']))
		echo '
				<input type="hidden" name="skip_ftp" value="1" />';

	// Retain state.
	foreach ($context['back_look_data'] as $path)
		echo '
				<input type="hidden" name="back_look[]" value="', $path, '" />';

	echo '
				<input type="hidden" name="method" value="', $context['method'], '" />
				<input type="hidden" name="action_changes" value="1" />
				<div class="righttext padding">
					<input type="submit" name="go" id="cont" value="', westr::htmlspecialchars($txt['not_done_continue']), '" class="button_submit" />
				</div>
			</div>
		</form>
	</div>
	<br class="clear" />';

	// Just the countdown stuff
	add_js_inline('
	var countdown = ', $countDown, ';
	doAutoSubmit();

	function doAutoSubmit()
	{
		if (countdown == 0)
			document.forms.perm_submit.submit();
		else if (countdown == -1)
			return;

		$(\'#cont\').val(', JavaScriptEscape($txt['not_done_continue']), ' + " (" + countdown + ")");
		countdown--;

		setTimeout("doAutoSubmit();", 1000);
	}');
}

?>