<?php
/**
 * Various functions concerning smiley sets, the adding and editing thereof.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * Portions are © 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*
	void ManageSmileys()
		// !!!

	void EditSmileySettings()
		// !!!

	void EditSmileySets()
		// !!!

	void AddSmiley()
		// !!!

	void EditSmileys()
		// !!!

	void EditSmileyOrder()
		// !!!

	void InstallSmileySet()
		// !!!

	void ImportSmileys($smileyPath)
		// !!!

	void sortSmileyTable()
		// !!!
*/

function ManageSmileys()
{
	global $context, $txt, $settings;

	isAllowedTo('manage_smileys');

	loadLanguage('ManageSmileys');
	loadTemplate('ManageSmileys');

	$subActions = array(
		'addsmiley' => 'AddSmiley',
		'editicon' => 'EditMessageIcons',
		'editicons' => 'EditMessageIcons',
		'editsets' => 'EditSmileySets',
		'editsmileys' => 'EditSmileys',
		'import' => 'EditSmileySets',
		'modifyset' => 'EditSmileySets',
		'modifysmiley' => 'EditSmileys',
		'setorder' => 'EditSmileyOrder',
		'settings' => 'EditSmileySettings',
		'install' => 'InstallSmileySet'
	);

	// Default the sub-action to 'edit smiley settings'.
	$_REQUEST['sa'] = isset($_REQUEST['sa'], $subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'editsets';

	$context['page_title'] = $txt['smileys_manage'];
	$context['sub_action'] = $_REQUEST['sa'];
	wetem::load($context['sub_action']);

	// Load up all the tabs...
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['smileys_manage'],
		'help' => 'smileys',
		'description' => $txt['smiley_settings_explain'],
		'tabs' => array(
			'editsets' => array(
				'description' => $txt['smiley_editsets_explain'],
			),
			'addsmiley' => array(
				'description' => $txt['smiley_addsmiley_explain'],
			),
			'editsmileys' => array(
				'description' => $txt['smiley_editsmileys_explain'],
			),
			'setorder' => array(
				'description' => $txt['smiley_setorder_explain'],
			),
			'editicons' => array(
				'description' => $txt['icons_edit_icons_explain'],
			),
			'settings' => array(
				'description' => $txt['smiley_settings_explain'],
			),
		),
	);

	// Some settings may not be enabled, disallow these from the tabs as appropriate.
	if (empty($settings['smiley_enable']))
	{
		$context[$context['admin_menu_name']]['tab_data']['tabs']['addsmiley']['disabled'] = true;
		$context[$context['admin_menu_name']]['tab_data']['tabs']['editsmileys']['disabled'] = true;
		$context[$context['admin_menu_name']]['tab_data']['tabs']['setorder']['disabled'] = true;
	}

	// Call the right function for this sub-action.
	$subActions[$_REQUEST['sa']]();
}

function EditSmileySettings($return_config = false)
{
	global $settings, $context, $txt;

	// Get the names of the smiley sets.
	$smiley_sets = explode(',', $settings['smiley_sets_known']);
	$set_names = explode("\n", $settings['smiley_sets_names']);

	$smiley_context = array();
	foreach ($smiley_sets as $i => $set)
		$smiley_context[$set] = $set_names[$i];

	$context['page_title'] = $txt['smileys_manage'];

	// All the settings for the page...
	$config_vars = array(
		array('title', 'settings'),
			// Inline permissions.
			array('permissions', 'manage_smileys', 'exclude' => array(-1)),
			array('check', 'smiley_enable', 'subtext' => $txt['smileys_enable_subtext']),
		'',
			array('select', 'smiley_sets_default', $smiley_context),
			array('check', 'smiley_sets_enable'),
	);

	if ($return_config)
		return $config_vars;

	// Setup the basics of the settings template.
	loadSource('ManageServer');
	wetem::load('show_settings');

	// Finish up the form...
	$context['post_url'] = '<URL>?action=admin;area=smileys;save;sa=settings';

	// Saving the settings?
	if (isset($_GET['save']))
	{
		checkSession();

		// Validate the smiley set name.
		$_POST['smiley_sets_default'] = empty($smiley_context[$_POST['smiley_sets_default']]) ? 'default' : $_POST['smiley_sets_default'];

		// Make sure that the smileys are in the right order after enabling them.
		if (isset($_POST['smiley_enable']))
			sortSmileyTable();

		saveDBSettings($config_vars);
		cleanSmileyCache();

		redirectexit('action=admin;area=smileys;sa=settings');
	}

	prepareDBSettingContext($config_vars);
}

function EditSmileySets()
{
	global $settings, $context, $txt;

	// Set the right tab to be selected.
	$context[$context['admin_menu_name']]['current_subsection'] = 'editsets';

	// They must've been submitted a form.
	if (isset($_POST[$context['session_var']]))
	{
		checkSession();

		// Delete selected smiley sets.
		if (!empty($_POST['delete']) && !empty($_POST['smiley_set']))
		{
			$set_paths = explode(',', $settings['smiley_sets_known']);
			$set_names = explode("\n", $settings['smiley_sets_names']);
			foreach ($_POST['smiley_set'] as $id => $val)
				if (isset($set_paths[$id], $set_names[$id]) && !empty($id))
					unset($set_paths[$id], $set_names[$id]);

			updateSettings(array(
				'smiley_sets_known' => implode(',', $set_paths),
				'smiley_sets_names' => implode("\n", $set_names),
				'smiley_sets_default' => in_array($settings['smiley_sets_default'], $set_paths) ? $settings['smiley_sets_default'] : $set_paths[0],
			));

			cleanSmileyCache();
		}
		// Add a new smiley set.
		elseif (!empty($_POST['add']))
			$context['sub_action'] = 'modifyset';
		// Create or modify a smiley set.
		elseif (isset($_POST['set']))
		{
			$set_paths = explode(',', $settings['smiley_sets_known']);
			$set_names = explode("\n", $settings['smiley_sets_names']);

			// Create a new smiley set.
			if ($_POST['set'] == -1 && isset($_POST['smiley_sets_path']))
			{
				if (in_array($_POST['smiley_sets_path'], $set_paths))
					fatal_lang_error('smiley_set_already_exists');

				updateSettings(array(
					'smiley_sets_known' => $settings['smiley_sets_known'] . ',' . $_POST['smiley_sets_path'],
					'smiley_sets_names' => $settings['smiley_sets_names'] . "\n" . $_POST['smiley_sets_name'],
					'smiley_sets_default' => empty($_POST['smiley_sets_default']) ? $settings['smiley_sets_default'] : $_POST['smiley_sets_path'],
				));
			}
			// Modify an existing smiley set.
			else
			{
				// Make sure the smiley set exists.
				if (!isset($set_paths[$_POST['set']]) || !isset($set_names[$_POST['set']]))
					fatal_lang_error('smiley_set_not_found');

				// Make sure the path is not yet used by another smileyset.
				if (in_array($_POST['smiley_sets_path'], $set_paths) && $_POST['smiley_sets_path'] != $set_paths[$_POST['set']])
					fatal_lang_error('smiley_set_path_already_used');

				$set_paths[$_POST['set']] = $_POST['smiley_sets_path'];
				$set_names[$_POST['set']] = $_POST['smiley_sets_name'];
				updateSettings(array(
					'smiley_sets_known' => implode(',', $set_paths),
					'smiley_sets_names' => implode("\n", $set_names),
					'smiley_sets_default' => empty($_POST['smiley_sets_default']) ? $settings['smiley_sets_default'] : $_POST['smiley_sets_path']
				));
			}

			// The user might have checked to also import smileys.
			if (!empty($_POST['smiley_sets_import']))
				ImportSmileys($_POST['smiley_sets_path']);

			cleanSmileyCache();
		}
	}

	// Load all available smileysets...
	$context['smiley_sets'] = explode(',', $settings['smiley_sets_known']);
	$set_names = explode("\n", $settings['smiley_sets_names']);
	foreach ($context['smiley_sets'] as $i => $set)
		$context['smiley_sets'][$i] = array(
			'id' => $i,
			'path' => htmlspecialchars($set),
			'name' => htmlspecialchars($set_names[$i]),
			'selected' => $set == $settings['smiley_sets_default']
		);

	// Importing any smileys from an existing set?
	if ($context['sub_action'] == 'import')
	{
		checkSession('get');
		$_GET['set'] = (int) $_GET['set'];

		// Sanity check - then import.
		if (isset($context['smiley_sets'][$_GET['set']]))
			ImportSmileys(un_htmlspecialchars($context['smiley_sets'][$_GET['set']]['path']));

		// Force the process to continue.
		$context['sub_action'] = 'modifyset';
		wetem::load('modifyset');
	}
	// If we're modifying or adding a smileyset, some context info needs to be set.
	if ($context['sub_action'] == 'modifyset')
	{
		$_GET['set'] = !isset($_GET['set']) ? -1 : (int) $_GET['set'];
		if ($_GET['set'] == -1 || !isset($context['smiley_sets'][$_GET['set']]))
			$context['current_set'] = array(
				'id' => '-1',
				'path' => '',
				'name' => '',
				'selected' => false,
				'is_new' => true,
			);
		else
		{
			$context['current_set'] =& $context['smiley_sets'][$_GET['set']];
			$context['current_set']['is_new'] = false;

			// Calculate whether there are any smileys in the directory that can be imported.
			if (!empty($settings['smiley_enable']) && is_dir(ASSETS_DIR . '/smileys/' . $context['current_set']['path']))
			{
				$smileys = array();
				$dir = dir(ASSETS_DIR . '/smileys/' . $context['current_set']['path']);
				while ($entry = $dir->read())
				{
					if (in_array(strrchr($entry, '.'), array('.jpg', '.gif', '.jpeg', '.png')))
						$smileys[strtolower($entry)] = $entry;
				}
				$dir->close();

				// Exclude the smileys that are already in the database.
				$request = wesql::query('
					SELECT filename
					FROM {db_prefix}smileys
					WHERE filename IN ({array_string:smiley_list})',
					array(
						'smiley_list' => $smileys,
					)
				);
				while ($row = wesql::fetch_assoc($request))
					if (isset($smileys[strtolower($row['filename'])]))
						unset($smileys[strtolower($row['filename'])]);
				wesql::free_result($request);

				$context['current_set']['can_import'] = count($smileys);
				// Setup this string to look nice.
				$txt['smiley_set_import_multiple'] = sprintf($txt['smiley_set_import_multiple'], $context['current_set']['can_import']);
			}
		}

		// Retrieve all potential smiley set directories.
		$context['smiley_set_dirs'] = array();
		if (is_dir(ASSETS_DIR . '/smileys'))
		{
			$dir = dir(ASSETS_DIR . '/smileys');
			while ($entry = $dir->read())
			{
				if (!in_array($entry, array('.', '..')) && is_dir(ASSETS_DIR . '/smileys/' . $entry))
					$context['smiley_set_dirs'][] = array(
						'id' => $entry,
						'path' => ASSETS_DIR . '/smileys/' . $entry,
						'selectable' => $entry == $context['current_set']['path'] || !in_array($entry, explode(',', $settings['smiley_sets_known'])),
						'current' => $entry == $context['current_set']['path'],
					);
			}
			$dir->close();
		}
	}

	$listOptions = array(
		'id' => 'smiley_set_list',
		'base_href' => '<URL>?action=admin;area=smileys;sa=editsets',
		'default_sort_col' => 'default',
		'get_items' => array(
			'function' => 'list_getSmileySets',
		),
		'get_count' => array(
			'function' => 'list_getNumSmileySets',
		),
		'columns' => array(
			'default' => array(
				'header' => array(
					'value' => $txt['smiley_sets_default'],
				),
				'data' => array(
					'function' => function ($rowData) {
						return $rowData['selected'] ? '<strong>*</strong>' : '';
					},
					'style' => 'text-align: center',
				),
				'sort' => array(
					'default' => 'selected DESC',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['smiley_sets_name'],
				),
				'data' => array(
					'db_htmlsafe' => 'name',
					'class' => 'windowbg',
				),
				'sort' => array(
					'default' => 'name',
					'reverse' => 'name DESC',
				),
			),
			'url' => array(
				'header' => array(
					'value' => $txt['smiley_sets_url'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => SMILEYS . '/<strong>%1$s</strong>/...',
						'params' => array(
							'path' => true,
						),
					),
					'class' => 'windowbg',
				),
				'sort' => array(
					'default' => 'path',
					'reverse' => 'path DESC',
				),
			),
			'modify' => array(
				'header' => array(
					'value' => $txt['smiley_set_modify'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="<URL>?action=admin;area=smileys;sa=modifyset;set=%1$d">' . $txt['smiley_set_modify'] . '</a>',
						'params' => array(
							'id' => true,
						),
					),
					'style' => 'text-align: center',
				),
			),
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" onclick="invertAll(this, this.form);">',
				),
				'data' => array(
					'function' => function ($rowData) {
						return $rowData['id'] == 0 ? '' : sprintf('<input type="checkbox" name="smiley_set[%1$d]">', $rowData['id']);
					},
					'style' => 'text-align: center',
				),
			),
		),
		'form' => array(
			'href' => '<URL>?action=admin;area=smileys;sa=editsets',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" name="delete" value="' . $txt['smiley_sets_delete'] . '" onclick="return ask(' . JavaScriptEscape($txt['smiley_sets_confirm']) . ', e);" class="delete floatright"><span style="line-height: 25px">[<a href="<URL>?action=admin;area=smileys;sa=modifyset">' . $txt['smiley_sets_add'] . '</a>]&nbsp;&nbsp;</span>',
			),
		),
	);

	loadSource('Subs-List');
	createList($listOptions);
}

// !!! to be moved to Subs-Smileys.
function list_getSmileySets($start, $items_per_page, $sort)
{
	global $settings;

	$known_sets = explode(',', $settings['smiley_sets_known']);
	$set_names = explode("\n", $settings['smiley_sets_names']);
	$cols = array(
		'id' => array(),
		'selected' => array(),
		'path' => array(),
		'name' => array(),
	);
	foreach ($known_sets as $i => $set)
	{
		$cols['id'][] = $i;
		$cols['selected'][] = $i;
		$cols['path'][] = $set;
		$cols['name'][] = $set_names[$i];
	}
	$sort_flag = strpos($sort, 'DESC') === false ? SORT_ASC : SORT_DESC;
	if (substr($sort, 0, 4) === 'name')
		array_multisort($cols['name'], $sort_flag, SORT_REGULAR, $cols['path'], $cols['selected'], $cols['id']);
	elseif (substr($sort, 0, 4) === 'path')
		array_multisort($cols['path'], $sort_flag, SORT_REGULAR, $cols['name'], $cols['selected'], $cols['id']);
	else
		array_multisort($cols['selected'], $sort_flag, SORT_REGULAR, $cols['path'], $cols['name'], $cols['id']);

	$smiley_sets = array();
	foreach ($cols['id'] as $i => $id)
		$smiley_sets[] = array(
			'id' => $id,
			'path' => $cols['path'][$i],
			'name' => $cols['name'][$i],
			'selected' => $cols['path'][$i] == $settings['smiley_sets_default']
		);

	return $smiley_sets;
}

// !!! to be moved to Subs-Smileys.
function list_getNumSmileySets()
{
	global $settings;

	return count(explode(',', $settings['smiley_sets_known']));
}

function AddSmiley()
{
	global $settings, $context, $txt;

	// Get a list of all known smiley sets.
	$context['smileys_dir_found'] = is_dir(ASSETS_DIR . '/smileys');
	$context['smiley_sets'] = explode(',', $settings['smiley_sets_known']);
	$set_names = explode("\n", $settings['smiley_sets_names']);
	foreach ($context['smiley_sets'] as $i => $set)
		$context['smiley_sets'][$i] = array(
			'id' => $i,
			'path' => htmlspecialchars($set),
			'name' => htmlspecialchars($set_names[$i]),
			'selected' => $set == $settings['smiley_sets_default']
		);

	// Submitting a form?
	if (isset($_POST[$context['session_var']], $_POST['smiley_code']))
	{
		checkSession();

		// Some useful arrays... types we allow - and ports we don't!
		$allowedTypes = array('jpeg', 'jpg', 'gif', 'png', 'bmp');
		$disabledFiles = array('con', 'com1', 'com2', 'com3', 'com4', 'prn', 'aux', 'lpt1', '.htaccess', 'index.php');

		$_POST['smiley_code'] = htmltrim__recursive($_POST['smiley_code']);
		$_POST['smiley_location'] = empty($_POST['smiley_location']) || $_POST['smiley_location'] > 2 || $_POST['smiley_location'] < 0 ? 0 : (int) $_POST['smiley_location'];
		$_POST['smiley_filename'] = htmltrim__recursive($_POST['smiley_filename']);

		// Make sure some code was entered.
		if (empty($_POST['smiley_code']))
			fatal_lang_error('smiley_has_no_code');

		// Check whether the new code has duplicates. It should be unique.
		$request = wesql::query('
			SELECT id_smiley
			FROM {db_prefix}smileys
			WHERE code = BINARY {string:smiley_code}',
			array(
				'smiley_code' => $_POST['smiley_code'],
			)
		);
		if (wesql::num_rows($request) > 0)
			fatal_lang_error('smiley_not_unique');
		wesql::free_result($request);

		// If we are uploading - check all the smiley sets are writable!
		if ($_POST['method'] != 'existing')
		{
			$writeErrors = array();
			foreach ($context['smiley_sets'] as $set)
			{
				if (!is_writable(ASSETS_DIR . '/smileys/' . un_htmlspecialchars($set['path'])))
					$writeErrors[] = $set['path'];
			}
			if (!empty($writeErrors))
				fatal_lang_error('smileys_upload_error_notwritable', true, array(implode(', ', $writeErrors)));
		}

		// Uploading just one smiley for all of them?
		if (isset($_POST['sameall'], $_FILES['uploadSmiley']['name']) && $_FILES['uploadSmiley']['name'] != '')
		{
			if (!is_uploaded_file($_FILES['uploadSmiley']['tmp_name']) || (ini_get('open_basedir') == '' && !file_exists($_FILES['uploadSmiley']['tmp_name'])))
				fatal_lang_error('smileys_upload_error');

			// Sorry, no spaces, dots, or anything else but letters allowed.
			$_FILES['uploadSmiley']['name'] = preg_replace(array('/\s/', '/\.{2,}/', '/[^\w.-]/'), array('_', '.', ''), $_FILES['uploadSmiley']['name']);

			// We only allow image files - it's THAT simple - no messing around here...
			if (!in_array(strtolower(substr(strrchr($_FILES['uploadSmiley']['name'], '.'), 1)), $allowedTypes))
				fatal_lang_error('smileys_upload_error_types', false, array(implode(', ', $allowedTypes)));

			// We only need the filename...
			$destName = basename($_FILES['uploadSmiley']['name']);

			// Make sure they aren't trying to upload a nasty file - for their own good here!
			if (in_array(strtolower($destName), $disabledFiles))
				fatal_lang_error('smileys_upload_error_illegal');

			// Check if the file already exists... and if not move it to EVERY smiley set directory.
			$i = 0;
			// Keep going until we find a set the file doesn't exist in. (or maybe it exists in all of them?)
			while (isset($context['smiley_sets'][$i]) && file_exists(ASSETS_DIR . '/smileys/' . un_htmlspecialchars($context['smiley_sets'][$i]['path']) . '/' . $destName))
				$i++;

			// Okay, we're going to put the smiley right here, since it's not there yet!
			if (isset($context['smiley_sets'][$i]['path']))
			{
				$smileyLocation = ASSETS_DIR . '/smileys/' . un_htmlspecialchars($context['smiley_sets'][$i]['path']) . '/' . $destName;
				move_uploaded_file($_FILES['uploadSmiley']['tmp_name'], $smileyLocation);
				@chmod($smileyLocation, 0644);

				// Now, we want to move it from there to all the other sets.
				for ($n = count($context['smiley_sets']); $i < $n; $i++)
				{
					$currentPath = ASSETS_DIR . '/smileys/' . un_htmlspecialchars($context['smiley_sets'][$i]['path']) . '/' . $destName;

					// The file is already there!  Don't overwrite it!
					if (file_exists($currentPath))
						continue;

					// Okay, so copy the first one we made to here.
					copy($smileyLocation, $currentPath);
					@chmod($currentPath, 0644);
				}
			}

			// Finally make sure it's saved correctly!
			$_POST['smiley_filename'] = $destName;
		}
		// What about uploading several files?
		elseif ($_POST['method'] != 'existing')
		{
			foreach ($_FILES as $name => $data)
			{
				if ($_FILES[$name]['name'] == '')
					fatal_lang_error('smileys_upload_error_blank');

				if (empty($newName))
					$newName = basename($_FILES[$name]['name']);
				elseif (basename($_FILES[$name]['name']) != $newName)
					fatal_lang_error('smileys_upload_error_name');
			}

			foreach ($context['smiley_sets'] as $i => $set)
			{
				$set['name'] = un_htmlspecialchars($set['name']);
				$set['path'] = un_htmlspecialchars($set['path']);

				if (!isset($_FILES['individual_' . $set['name']]['name']) || $_FILES['individual_' . $set['name']]['name'] == '')
					continue;

				// Got one...
				if (!is_uploaded_file($_FILES['individual_' . $set['name']]['tmp_name']) || (ini_get('open_basedir') == '' && !file_exists($_FILES['individual_' . $set['name']]['tmp_name'])))
					fatal_lang_error('smileys_upload_error');

				// Sorry, no spaces, dots, or anything else but letters allowed.
				$_FILES['individual_' . $set['name']]['name'] = preg_replace(array('/\s/', '/\.{2,}/', '/[^\w.-]/'), array('_', '.', ''), $_FILES['individual_' . $set['name']]['name']);

				// We only allow image files - it's THAT simple - no messing around here...
				if (!in_array(strtolower(substr(strrchr($_FILES['individual_' . $set['name']]['name'], '.'), 1)), $allowedTypes))
					fatal_lang_error('smileys_upload_error_types', false, array(implode(', ', $allowedTypes)));

				// We only need the filename...
				$destName = basename($_FILES['individual_' . $set['name']]['name']);

				// Make sure they aren't trying to upload a nasty file - for their own good here!
				if (in_array(strtolower($destName), $disabledFiles))
					fatal_lang_error('smileys_upload_error_illegal');

				// If the file exists - ignore it.
				$smileyLocation = ASSETS_DIR . '/smileys/' . $set['path'] . '/' . $destName;
				if (file_exists($smileyLocation))
					continue;

				// Finally - move the image!
				move_uploaded_file($_FILES['individual_' . $set['name']]['tmp_name'], $smileyLocation);
				@chmod($smileyLocation, 0644);

				// Should always be saved correctly!
				$_POST['smiley_filename'] = $destName;
			}
		}

		// Also make sure a filename was given.
		if (empty($_POST['smiley_filename']))
			fatal_lang_error('smiley_has_no_filename');

		// Find the position on the right.
		$smiley_order = '0';
		if ($_POST['smiley_location'] != 1)
		{
			$request = wesql::query('
				SELECT MAX(smiley_order) + 1
				FROM {db_prefix}smileys
				WHERE hidden = {int:smiley_location}
					AND smiley_row = {int:first_row}',
				array(
					'smiley_location' => $_POST['smiley_location'],
					'first_row' => 0,
				)
			);
			list ($smiley_order) = wesql::fetch_row($request);
			wesql::free_result($request);

			if (empty($smiley_order))
				$smiley_order = '0';
		}
		wesql::insert('',
			'{db_prefix}smileys',
			array('code' => 'string-30', 'filename' => 'string-48', 'description' => 'string-80', 'hidden' => 'int', 'smiley_order' => 'int'),
			array($_POST['smiley_code'], $_POST['smiley_filename'], $_POST['smiley_description'], $_POST['smiley_location'], $smiley_order)
		);

		cleanSmileyCache();

		// No errors? Out of here!
		redirectexit('action=admin;area=smileys;sa=editsmileys');
	}

	$context['selected_set'] = $settings['smiley_sets_default'];

	// Get all possible filenames for the smileys.
	$context['filenames'] = array();
	if ($context['smileys_dir_found'])
	{
		foreach ($context['smiley_sets'] as $smiley_set)
		{
			if (!file_exists(ASSETS_DIR . '/smileys/' . un_htmlspecialchars($smiley_set['path'])))
				continue;

			$dir = dir(ASSETS_DIR . '/smileys/' . un_htmlspecialchars($smiley_set['path']));
			while ($entry = $dir->read())
			{
				if (!in_array($entry, $context['filenames']) && in_array(strrchr($entry, '.'), array('.jpg', '.gif', '.jpeg', '.png')))
					$context['filenames'][strtolower($entry)] = array(
						'id' => htmlspecialchars($entry),
						'selected' => false,
					);
			}
			$dir->close();
		}
		ksort($context['filenames']);
	}

	// Create a new smiley from scratch.
	$context['filenames'] = array_values($context['filenames']);
	$context['current_smiley'] = array(
		'id' => 0,
		'code' => '',
		'filename' => @$context['filenames'][0]['id'],
		'description' => $txt['smileys_default_description'],
		'location' => 0,
		'is_new' => true,
	);
}

function EditSmileys()
{
	global $settings, $context, $txt;

	// Force the correct tab to be displayed.
	$context[$context['admin_menu_name']]['current_subsection'] = 'editsmileys';

	// Submitting a form?
	if (isset($_POST[$context['session_var']]))
	{
		checkSession();

		// Changing the selected smileys?
		if (isset($_POST['smiley_action']) && !empty($_POST['checked_smileys']))
		{
			foreach ($_POST['checked_smileys'] as $id => $smiley_id)
				$_POST['checked_smileys'][$id] = (int) $smiley_id;

			if ($_POST['smiley_action'] == 'delete')
				wesql::query('
					DELETE FROM {db_prefix}smileys
					WHERE id_smiley IN ({array_int:checked_smileys})',
					array(
						'checked_smileys' => $_POST['checked_smileys'],
					)
				);
			// Changing the status of the smiley?
			else
			{
				// Check it's a valid type.
				$displayTypes = array(
					'post' => 0,
					'hidden' => 1,
					'popup' => 2
				);
				if (isset($displayTypes[$_POST['smiley_action']]))
					wesql::query('
						UPDATE {db_prefix}smileys
						SET hidden = {int:display_type}
						WHERE id_smiley IN ({array_int:checked_smileys})',
						array(
							'checked_smileys' => $_POST['checked_smileys'],
							'display_type' => $displayTypes[$_POST['smiley_action']],
						)
					);
			}
		}
		// Create/modify a smiley.
		elseif (isset($_POST['smiley']))
		{
			// Is it a delete?
			if (!empty($_POST['deletesmiley']))
			{
				wesql::query('
					DELETE FROM {db_prefix}smileys
					WHERE id_smiley = {int:current_smiley}',
					array(
						'current_smiley' => $_POST['smiley'],
					)
				);
			}
			// Otherwise an edit.
			else
			{
				$_POST['smiley'] = (int) $_POST['smiley'];
				$_POST['smiley_code'] = htmltrim__recursive($_POST['smiley_code']);
				$_POST['smiley_filename'] = htmltrim__recursive($_POST['smiley_filename']);
				$_POST['smiley_location'] = empty($_POST['smiley_location']) || $_POST['smiley_location'] > 2 || $_POST['smiley_location'] < 0 ? 0 : (int) $_POST['smiley_location'];

				// Make sure some code was entered.
				if (empty($_POST['smiley_code']))
					fatal_lang_error('smiley_has_no_code');

				// Also make sure a filename was given.
				if (empty($_POST['smiley_filename']))
					fatal_lang_error('smiley_has_no_filename');

				// Check whether the new code has duplicates. It should be unique.
				$request = wesql::query('
					SELECT id_smiley
					FROM {db_prefix}smileys
					WHERE code = BINARY {string:smiley_code}' . (empty($_POST['smiley']) ? '' : '
						AND id_smiley != {int:current_smiley}'),
					array(
						'current_smiley' => $_POST['smiley'],
						'smiley_code' => $_POST['smiley_code'],
					)
				);
				if (wesql::num_rows($request) > 0)
					fatal_lang_error('smiley_not_unique');
				wesql::free_result($request);

				wesql::query('
					UPDATE {db_prefix}smileys
					SET
						code = {string:smiley_code},
						filename = {string:smiley_filename},
						description = {string:smiley_description},
						hidden = {int:smiley_location}
					WHERE id_smiley = {int:current_smiley}',
					array(
						'smiley_location' => $_POST['smiley_location'],
						'current_smiley' => $_POST['smiley'],
						'smiley_code' => $_POST['smiley_code'],
						'smiley_filename' => $_POST['smiley_filename'],
						'smiley_description' => $_POST['smiley_description'],
					)
				);
			}

			// Sort all smiley codes for more accurate parsing (longest code first).
			sortSmileyTable();
		}

		cleanSmileyCache();
	}

	// Load all known smiley sets.
	$context['smiley_sets'] = explode(',', $settings['smiley_sets_known']);
	$set_names = explode("\n", $settings['smiley_sets_names']);
	foreach ($context['smiley_sets'] as $i => $set)
		$context['smiley_sets'][$i] = array(
			'id' => $i,
			'path' => htmlspecialchars($set),
			'name' => htmlspecialchars($set_names[$i]),
			'selected' => $set == $settings['smiley_sets_default']
		);

	// Prepare overview of all (custom) smileys.
	if ($context['sub_action'] == 'editsmileys')
	{
		// Determine the language specific sort order of smiley locations.
		$smiley_locations = array(
			$txt['smileys_location_form'],
			$txt['smileys_location_hidden'],
			$txt['smileys_location_popup'],
		);
		asort($smiley_locations);

		// Create a list of options for selecting smiley sets.
		$smileyset_option_list = '
			<select name="set" onchange="changeSet(this.options[this.selectedIndex].value);">';
		foreach ($context['smiley_sets'] as $smiley_set)
			$smileyset_option_list .= '
				<option value="' . $smiley_set['path'] . '"' . ($settings['smiley_sets_default'] == $smiley_set['path'] ? ' selected' : '') . '>' . $smiley_set['name'] . '</option>';
		$smileyset_option_list .= '
			</select>';

		$listOptions = array(
			'id' => 'smiley_list',
			'items_per_page' => 40,
			'base_href' => '<URL>?action=admin;area=smileys;sa=editsmileys',
			'default_sort_col' => 'filename',
			'get_items' => array(
				'function' => 'list_getSmileys',
			),
			'get_count' => array(
				'function' => 'list_getNumSmileys',
			),
			'no_items_label' => $txt['smileys_no_entries'],
			'columns' => array(
				'picture' => array(
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="<URL>?action=admin;area=smileys;sa=modifysmiley;smiley=%1$d"><img src="' . SMILEYS . '/' . $settings['smiley_sets_default'] . '/%2$s" alt="%3$s" style="padding: 2px" id="smiley%1$d"><input type="hidden" name="smileys[%1$d][filename]" value="%2$s"></a>',
							'params' => array(
								'id_smiley' => false,
								'filename' => true,
								'description' => true,
							),
						),
						'style' => 'text-align: center',
					),
				),
				'code' => array(
					'header' => array(
						'value' => $txt['smileys_code'],
					),
					'data' => array(
						'db_htmlsafe' => 'code',
					),
					'sort' => array(
						'default' => 'code',
						'reverse' => 'code DESC',
					),
				),
				'filename' => array(
					'header' => array(
						'value' => $txt['smileys_filename'],
					),
					'data' => array(
						'db_htmlsafe' => 'filename',
					),
					'sort' => array(
						'default' => 'filename',
						'reverse' => 'filename DESC',
					),
				),
				'location' => array(
					'header' => array(
						'value' => $txt['smileys_location'],
					),
					'data' => array(
						'function' => function ($rowData) {
							global $txt;

							if (empty($rowData['hidden']))
								return $txt['smileys_location_form'];
							elseif ($rowData['hidden'] == 1)
								return $txt['smileys_location_hidden'];
							else
								return $txt['smileys_location_popup'];
						},
					),
					'sort' => array(
						'default' => 'FIND_IN_SET(hidden, \'' . implode(',', array_keys($smiley_locations)) . '\')',
						'reverse' => 'FIND_IN_SET(hidden, \'' . implode(',', array_keys($smiley_locations)) . '\') DESC',
					),
				),
				'tooltip' => array(
					'header' => array(
						'value' => $txt['smileys_description'],
					),
					'data' => array(
						'function' => function ($rowData) {
							global $context, $txt;

							if (!is_dir(ASSETS_DIR . '/smileys'))
								return htmlspecialchars($rowData['description']);

							// Check if there are smileys missing in some sets.
							$missing_sets = array();
							foreach ($context['smiley_sets'] as $smiley_set)
								if (!file_exists(sprintf('%1$s/%2$s/%3$s', ASSETS_DIR . '/smileys', $smiley_set['path'], $rowData['filename'])))
									$missing_sets[] = $smiley_set['path'];

							$description = htmlspecialchars($rowData['description']);

							if (!empty($missing_sets))
								$description .= sprintf('<div class="smalltext"><strong>%1$s:</strong> %2$s</div>', $txt['smileys_not_found_in_set'], implode(', ', $missing_sets));

							return $description;
						},
					),
					'sort' => array(
						'default' => 'description',
						'reverse' => 'description DESC',
					),
				),
				'modify' => array(
					'header' => array(
						'value' => $txt['smileys_modify'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="<URL>?action=admin;area=smileys;sa=modifysmiley;smiley=%1$d">' . $txt['smileys_modify'] . '</a>',
							'params' => array(
								'id_smiley' => false,
							),
						),
						'style' => 'text-align: center',
					),
				),
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);">',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="checked_smileys[]" value="%1$d">',
							'params' => array(
								'id_smiley' => false,
							),
						),
						'style' => 'text-align: center',
					),
				),
			),
			'form' => array(
				'href' => '<URL>?action=admin;area=smileys;sa=editsmileys',
				'name' => 'smileyForm',
			),
			'additional_rows' => array(
				array(
					'position' => 'above_column_headers',
					'value' => $smileyset_option_list,
					'style' => 'text-align: right',
				),
				array(
					'position' => 'below_table_data',
					'value' => '
						<select name="smiley_action" onchange="makeChanges(e, this.value);">
							<option value="-1" data-hide>' . $txt['smileys_with_selected'] . ':</option>
							<option value="hidden">' . $txt['smileys_make_hidden'] . '</option>
							<option value="post">' . $txt['smileys_show_on_post'] . '</option>
							<option value="popup">' . $txt['smileys_show_on_popup'] . '</option>
							<option value="delete">' . $txt['remove'] . '</option>
						</select>
						<noscript><input type="submit" name="perform_action" value="' . $txt['go'] . '"></noscript>',
					'style' => 'text-align: right',
				),
			),
			'javascript' => '
	function makeChanges(e, action)
	{
		if (action == "-1")
			return false;
		if (action != "delete" || ask(' . JavaScriptEscape($txt['smileys_confirm']) . ', e))
			document.forms.smileyForm.submit();
		return true;
	}
	function changeSet(newSet)
	{
		var currentImage, i, n, knownSmileys = [];

		if (knownSmileys.length == 0)
			for (i = 0, n = document.images.length; i < n; i++)
				if (document.images[i].id.slice(0, 6) == "smiley")
					knownSmileys.push(document.images[i].id.slice(6));

		for (i = 0; i < knownSmileys.length; i++)
			$("#smiley" + knownSmileys[i]).attr("src", "' . SMILEYS . '/" + newSet + "/" + document.forms.smileyForm["smileys[" + knownSmileys[i] + "][filename]"].value);
	}',
		);

		loadSource('Subs-List');
		createList($listOptions);

		// The list is the only thing to show, so make it the main template.
		$context['default_list'] = 'smiley_list';
		wetem::load('show_list');
	}
	// Modifying smileys.
	elseif ($context['sub_action'] == 'modifysmiley')
	{
		// Get a list of all known smiley sets.
		$context['smileys_dir_found'] = is_dir(ASSETS_DIR . '/smileys');
		$context['smiley_sets'] = explode(',', $settings['smiley_sets_known']);
		$set_names = explode("\n", $settings['smiley_sets_names']);
		foreach ($context['smiley_sets'] as $i => $set)
			$context['smiley_sets'][$i] = array(
				'id' => $i,
				'path' => htmlspecialchars($set),
				'name' => htmlspecialchars($set_names[$i]),
				'selected' => $set == $settings['smiley_sets_default']
			);

		$context['selected_set'] = $settings['smiley_sets_default'];

		// Get all possible filenames for the smileys.
		$context['filenames'] = array();
		if ($context['smileys_dir_found'])
		{
			foreach ($context['smiley_sets'] as $smiley_set)
			{
				if (!file_exists(ASSETS_DIR . '/smileys/' . un_htmlspecialchars($smiley_set['path'])))
					continue;

				$dir = dir(ASSETS_DIR . '/smileys/' . un_htmlspecialchars($smiley_set['path']));
				while ($entry = $dir->read())
				{
					if (!in_array($entry, $context['filenames']) && in_array(strrchr($entry, '.'), array('.jpg', '.gif', '.jpeg', '.png')))
						$context['filenames'][strtolower($entry)] = array(
							'id' => htmlspecialchars($entry),
							'selected' => false,
						);
				}
				$dir->close();
			}
			ksort($context['filenames']);
		}

		$request = wesql::query('
			SELECT id_smiley AS id, code, filename, description, hidden AS location, 0 AS is_new
			FROM {db_prefix}smileys
			WHERE id_smiley = {int:current_smiley}',
			array(
				'current_smiley' => (int) $_REQUEST['smiley'],
			)
		);
		if (wesql::num_rows($request) != 1)
			fatal_lang_error('smiley_not_found');
		$context['current_smiley'] = wesql::fetch_assoc($request);
		wesql::free_result($request);

		$context['current_smiley']['code'] = htmlspecialchars($context['current_smiley']['code']);
		$context['current_smiley']['filename'] = htmlspecialchars($context['current_smiley']['filename']);
		$context['current_smiley']['description'] = htmlspecialchars($context['current_smiley']['description']);

		if (isset($context['filenames'][strtolower($context['current_smiley']['filename'])]))
			$context['filenames'][strtolower($context['current_smiley']['filename'])]['selected'] = true;
	}
}

function list_getSmileys($start, $items_per_page, $sort)
{
	$request = wesql::query('
		SELECT id_smiley, code, filename, description, smiley_row, smiley_order, hidden
		FROM {db_prefix}smileys
		ORDER BY ' . $sort
	);
	$smileys = array();
	while ($row = wesql::fetch_assoc($request))
		$smileys[] = $row;
	wesql::free_result($request);

	return $smileys;
}

function list_getNumSmileys()
{
	$request = wesql::query('SELECT COUNT(*) FROM {db_prefix}smileys');
	list ($numSmileys) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numSmileys;
}

function EditSmileyOrder()
{
	global $context, $txt;

	// Move smileys to another position.
	if (isset($_REQUEST['reorder']))
	{
		checkSession('get');

		$_GET['location'] = empty($_GET['location']) || $_GET['location'] != 'popup' ? 0 : 2;
		$_GET['source'] = empty($_GET['source']) ? 0 : (int) $_GET['source'];

		if (empty($_GET['source']))
			fatal_lang_error('smiley_not_found');

		if (!empty($_GET['after']))
		{
			$_GET['after'] = (int) $_GET['after'];

			$request = wesql::query('
				SELECT smiley_row, smiley_order, hidden
				FROM {db_prefix}smileys
				WHERE hidden = {int:location}
					AND id_smiley = {int:after_smiley}',
				array(
					'location' => $_GET['location'],
					'after_smiley' => $_GET['after'],
				)
			);
			if (wesql::num_rows($request) != 1)
				fatal_lang_error('smiley_not_found');
			list ($smiley_row, $smiley_order, $smileyLocation) = wesql::fetch_row($request);
			wesql::free_result($request);
		}
		else
		{
			$smiley_row = (int) $_GET['row'];
			$smiley_order = -1;
			$smileyLocation = (int) $_GET['location'];
		}

		wesql::query('
			UPDATE {db_prefix}smileys
			SET smiley_order = smiley_order + 1
			WHERE hidden = {int:new_location}
				AND smiley_row = {int:smiley_row}
				AND smiley_order > {int:smiley_order}',
			array(
				'new_location' => $_GET['location'],
				'smiley_row' => $smiley_row,
				'smiley_order' => $smiley_order,
			)
		);

		wesql::query('
			UPDATE {db_prefix}smileys
			SET
				smiley_order = {int:smiley_order} + 1,
				smiley_row = {int:smiley_row},
				hidden = {int:new_location}
			WHERE id_smiley = {int:current_smiley}',
			array(
				'smiley_order' => $smiley_order,
				'smiley_row' => $smiley_row,
				'new_location' => $smileyLocation,
				'current_smiley' => $_GET['source'],
			)
		);
	}

	$request = wesql::query('
		SELECT id_smiley, code, filename, description, smiley_row, smiley_order, hidden
		FROM {db_prefix}smileys
		WHERE hidden != {int:popup}
		ORDER BY smiley_order, smiley_row',
		array(
			'popup' => 1,
		)
	);
	$context['smileys'] = array(
		'postform' => array(
			'rows' => array(),
		),
		'popup' => array(
			'rows' => array(),
		),
	);
	while ($row = wesql::fetch_assoc($request))
	{
		$location = empty($row['hidden']) ? 'postform' : 'popup';
		$context['smileys'][$location]['rows'][$row['smiley_row']][] = array(
			'id' => $row['id_smiley'],
			'code' => htmlspecialchars($row['code']),
			'filename' => htmlspecialchars($row['filename']),
			'description' => htmlspecialchars($row['description']),
			'row' => $row['smiley_row'],
			'order' => $row['smiley_order'],
			'selected' => !empty($_REQUEST['move']) && $_REQUEST['move'] == $row['id_smiley'],
		);
	}
	wesql::free_result($request);

	$context['move_smiley'] = empty($_REQUEST['move']) ? 0 : (int) $_REQUEST['move'];

	// Make sure all rows are sequential.
	foreach (array_keys($context['smileys']) as $location)
		$context['smileys'][$location] = array(
			'id' => $location,
			'title' => $location == 'postform' ? $txt['smileys_location_form'] : $txt['smileys_location_popup'],
			'description' => $location == 'postform' ? $txt['smileys_location_form_description'] : $txt['smileys_location_popup_description'],
			'last_row' => count($context['smileys'][$location]['rows']),
			'rows' => array_values($context['smileys'][$location]['rows']),
		);

	// Check & fix smileys that are not ordered properly in the database.
	foreach (array_keys($context['smileys']) as $location)
	{
		foreach ($context['smileys'][$location]['rows'] as $id => $smiley_row)
		{
			// Fix empty rows if any.
			if ($id != $smiley_row[0]['row'])
			{
				wesql::query('
					UPDATE {db_prefix}smileys
					SET smiley_row = {int:new_row}
					WHERE smiley_row = {int:current_row}
						AND hidden = {int:location}',
					array(
						'new_row' => $id,
						'current_row' => $smiley_row[0]['row'],
						'location' => $location == 'postform' ? '0' : '2',
					)
				);
				// Only change the first row value of the first smiley (we don't need the others :P).
				$context['smileys'][$location]['rows'][$id][0]['row'] = $id;
			}
			// Make sure the smiley order is always sequential.
			foreach ($smiley_row as $order_id => $smiley)
				if ($order_id != $smiley['order'])
					wesql::query('
						UPDATE {db_prefix}smileys
						SET smiley_order = {int:new_order}
						WHERE id_smiley = {int:current_smiley}',
						array(
							'new_order' => $order_id,
							'current_smiley' => $smiley['id'],
						)
					);
		}
	}

	cleanSmileyCache();
}

function InstallSmileySet()
{
	global $settings;

	isAllowedTo('manage_smileys');
	checkSession('request');

	loadSource('Subs-Package');

	$name = strtok(basename(isset($_FILES['set_gz']) ? $_FILES['set_gz']['name'] : $_REQUEST['set_gz']), '.');
	$name = preg_replace(array('/\s/', '/\.{2,}/', '/[^\w.-]/'), array('_', '.', ''), $name);

	// !!! Decide: overwrite or not?
	if (isset($_FILES['set_gz']) && is_uploaded_file($_FILES['set_gz']['tmp_name']) && (ini_get('open_basedir') != '' || file_exists($_FILES['set_gz']['tmp_name'])))
		$extracted = read_tgz_file($_FILES['set_gz']['tmp_name'], ASSETS_DIR . '/smileys/' . $name);
	elseif (isset($_REQUEST['set_gz']))
	{
		// Check that the smiley is from wedge.org, for now... maybe add mirroring later.
		if (preg_match('~^http://[\w-]+\.wedge\.org/~', $_REQUEST['set_gz']) == 0 || strpos($_REQUEST['set_gz'], 'dlattach') !== false)
			fatal_lang_error('only_on_wedge');

		$extracted = read_tgz_file($_REQUEST['set_gz'], ASSETS_DIR . '/smileys/' . $name);
	}
	else
		redirectexit('action=admin;area=smileys');

	updateSettings(array(
		'smiley_sets_known' => $settings['smiley_sets_known'] . ',' . $name,
		'smiley_sets_names' => $settings['smiley_sets_names'] . "\n" . strtok(basename(isset($_FILES['set_gz']) ? $_FILES['set_gz']['name'] : $_REQUEST['set_gz']), '.'),
	));

	cleanSmileyCache();

	// !!! Add some confirmation?
	redirectexit('action=admin;area=smileys');
}

// A function to import new smileys from an existing directory into the database.
function ImportSmileys($smileyPath)
{
	if (!is_dir(ASSETS_DIR . '/smileys/' . $smileyPath))
		fatal_lang_error('smiley_set_unable_to_import');

	$smileys = array();
	$dir = dir(ASSETS_DIR . '/smileys/' . $smileyPath);
	while ($entry = $dir->read())
	{
		if (in_array(strrchr($entry, '.'), array('.jpg', '.gif', '.jpeg', '.png')))
			$smileys[strtolower($entry)] = $entry;
	}
	$dir->close();

	// Exclude the smileys that are already in the database.
	$request = wesql::query('
		SELECT filename
		FROM {db_prefix}smileys
		WHERE filename IN ({array_string:smiley_list})',
		array(
			'smiley_list' => $smileys,
		)
	);
	while ($row = wesql::fetch_assoc($request))
		if (isset($smileys[strtolower($row['filename'])]))
			unset($smileys[strtolower($row['filename'])]);
	wesql::free_result($request);

	$request = wesql::query('
		SELECT MAX(smiley_order)
		FROM {db_prefix}smileys
		WHERE hidden = {int:postform}
			AND smiley_row = {int:first_row}',
		array(
			'postform' => 0,
			'first_row' => 0,
		)
	);
	list ($smiley_order) = wesql::fetch_row($request);
	wesql::free_result($request);

	$new_smileys = array();
	foreach ($smileys as $smiley)
		if (strlen($smiley) <= 48)
			$new_smileys[] = array(':' . strtok($smiley, '.') . ':', $smiley, strtok($smiley, '.'), 0, ++$smiley_order);

	if (!empty($new_smileys))
	{
		wesql::insert('',
			'{db_prefix}smileys',
			array('code' => 'string-30', 'filename' => 'string-48', 'description' => 'string-80', 'smiley_row' => 'int', 'smiley_order' => 'int'),
			$new_smileys
		);

		// Make sure the smiley codes are still in the right order.
		sortSmileyTable();

		cleanSmileyCache();
	}
}

function EditMessageIcons()
{
	global $context, $txt;

	// Get a list of icons.
	$context['icons'] = array();
	$request = wesql::query('
		SELECT m.id_icon, m.title, m.filename, m.icon_order, m.id_board, b.name AS board_name
		FROM {db_prefix}message_icons AS m
			LEFT JOIN {db_prefix}boards AS b ON (b.id_board = m.id_board)
		WHERE ({query_see_board} OR b.id_board IS NULL)',
		array(
		)
	);
	$last_icon = 0;
	$trueOrder = 0;
	while ($row = wesql::fetch_assoc($request))
	{
		$context['icons'][$row['id_icon']] = array(
			'id' => $row['id_icon'],
			'title' => $row['title'],
			'filename' => $row['filename'],
			'image_url' => ASSETS . '/post/' . $row['filename'] . '.gif',
			'board_id' => $row['id_board'],
			'board' => empty($row['board_name']) ? $txt['icons_edit_icons_all_boards'] : $row['board_name'],
			'order' => $row['icon_order'],
			'true_order' => $trueOrder++,
			'after' => $last_icon,
		);
		$last_icon = $row['id_icon'];
	}
	wesql::free_result($request);

	// Deleting icons?
	if (isset($_POST['delete']) && !empty($_POST['checked_icons']))
	{
		checkSession();
		$deleteIcons = array();
		foreach ($_POST['checked_icons'] as $icon)
			$deleteIcons[] = (int) $icon;

		// Do the actual delete!
		wesql::query('
			DELETE FROM {db_prefix}message_icons
			WHERE id_icon IN ({array_int:icon_list})',
			array(
				'icon_list' => $deleteIcons,
			)
		);

		// Sort by order, so it is quicker :)
		wesql::query('
			ALTER TABLE {db_prefix}message_icons
			ORDER BY icon_order',
			array(
				'db_error_skip' => true,
			)
		);

		redirectexit('action=admin;area=smileys;sa=editicons');
	}
	elseif ($context['sub_action'] == 'editicon' && isset($_GET['icon']) && !empty($_POST))
	{
		checkSession();
		$_GET['icon'] = (int) $_GET['icon'];

		// Do some preperation with the data... like check the icon exists.
		if (strpos($_POST['icon_filename'], '.gif') !== false)
			$_POST['icon_filename'] = substr($_POST['icon_filename'], 0, -4);
		if (!file_exists(ASSETS_DIR . '/post/' . $_POST['icon_filename'] . '.gif'))
			fatal_lang_error('icon_not_found');
		// There is a 16 character limit on message icons...
		elseif (strlen($_POST['icon_filename']) > 16)
			fatal_lang_error('icon_name_too_long');
		elseif ($_POST['icon_location'] == $_GET['icon'] && !empty($_GET['icon']))
			fatal_lang_error('icon_after_itself');

		// First do the sorting... if this is an edit reduce the order of everything after it by one ;)
		if ($_GET['icon'] != 0)
		{
			$oldOrder = $context['icons'][$_GET['icon']]['true_order'];
			foreach ($context['icons'] as $id => $data)
				if ($data['true_order'] > $oldOrder)
					$context['icons'][$id]['true_order']--;
		}

		// If there are no existing icons and this is a new one, set the id to 1
		if (empty($_GET['icon']) && empty($context['icons']))
			$_GET['icon'] = 1;

		// Get the new order.
		$newOrder = $_POST['icon_location'] == 0 ? 0 : $context['icons'][$_POST['icon_location']]['true_order'] + 1;
		// Do the same, but with the one that used to be after this icon, done to avoid conflict.
		foreach ($context['icons'] as $id => $data)
			if ($data['true_order'] >= $newOrder)
				$context['icons'][$id]['true_order']++;

		// Finally set the current icon's position!
		$context['icons'][$_GET['icon']]['true_order'] = $newOrder;

		// Simply replace the existing data for the other bits.
		$context['icons'][$_GET['icon']]['title'] = $_POST['icon_description'];
		$context['icons'][$_GET['icon']]['filename'] = $_POST['icon_filename'];
		$context['icons'][$_GET['icon']]['board_id'] = (int) $_POST['icon_board'];

		// Do a huge replace ;)
		$iconInsert = array();
		foreach ($context['icons'] as $id => $icon)
			$iconInsert[] = array($id, $icon['board_id'], $icon['title'], $icon['filename'], $icon['true_order']);

		wesql::insert('replace',
			'{db_prefix}message_icons',
			array('id_icon' => 'int', 'id_board' => 'int', 'title' => 'string-80', 'filename' => 'string-80', 'icon_order' => 'int'),
			$iconInsert
		);

		// Sort by order, so it is quicker :)
		wesql::query('
			ALTER TABLE {db_prefix}message_icons
			ORDER BY icon_order',
			array(
				'db_error_skip' => true,
			)
		);

		redirectexit('action=admin;area=smileys;sa=editicons');
	}

	$context[$context['admin_menu_name']]['current_subsection'] = 'editicons';

	$listOptions = array(
		'id' => 'message_icon_list',
		'base_href' => '<URL>?action=admin;area=smileys;sa=editicons',
		'get_items' => array(
			'function' => 'list_getMessageIcons',
		),
		'no_items_label' => $txt['icons_no_entries'],
		'columns' => array(
			'icon' => array(
				'data' => array(
					'function' => function ($rowData) {
						return sprintf('<img src="%1$s/post/%2$s.gif" alt="%3$s">', ASSETS, $rowData['filename'], htmlspecialchars($rowData['title']));
					},
				),
				'style' => 'text-align: center',
			),
			'filename' => array(
				'header' => array(
					'value' => $txt['smileys_filename'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '%1$s.gif',
						'params' => array(
							'filename' => true,
						),
					),
				),
			),
			'tooltip' => array(
				'header' => array(
					'value' => $txt['smileys_description'],
				),
				'data' => array(
					'db_htmlsafe' => 'title',
				),
			),
			'board' => array(
				'header' => array(
					'value' => $txt['icons_board'],
				),
				'data' => array(
					'function' => function ($rowData) {
						global $txt;

						return empty($rowData['board_name']) ? $txt['icons_edit_icons_all_boards'] : $rowData['board_name'];
					},
				),
			),
			'modify' => array(
				'header' => array(
					'value' => $txt['smileys_modify'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="<URL>?action=admin;area=smileys;sa=editicon;icon=%1$s">' . $txt['smileys_modify'] . '</a>',
						'params' => array(
							'id_icon' => false,
						),
					),
					'style' => 'text-align: center',
				),
			),
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" onclick="invertAll(this, this.form);">',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="checked_icons[]" value="%1$d">',
						'params' => array(
							'id_icon' => false,
						),
					),
					'style' => 'text-align: center',
				),
			),
		),
		'form' => array(
			'href' => '<URL>?action=admin;area=smileys;sa=editicons',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" name="delete" value="' . $txt['quickmod_delete_selected'] . '" class="delete floatright"> <input type="submit" name="add" class="new" value="' . $txt['icons_add_new'] . '">&nbsp;',
			),
		),
	);

	loadSource('Subs-List');
	createList($listOptions);

	// If we're adding/editing an icon we'll need a list of boards
	if ($context['sub_action'] == 'editicon' || isset($_POST['add']))
	{
		// Force the template block, just in case.
		wetem::load('editicon');

		$context['new_icon'] = !isset($_GET['icon']);

		// Get the properties of the current icon from the icon list.
		if (!$context['new_icon'])
			$context['icon'] = $context['icons'][$_GET['icon']];

		// Get a list of boards needed for assigning this icon to a specific board.
		$boardListOptions = array(
			'use_permissions' => true,
			'selected_board' => isset($context['icon']['board_id']) ? $context['icon']['board_id'] : 0,
		);
		loadSource('Subs-MessageIndex');
		$context['categories'] = getBoardList($boardListOptions);
	}
}

function list_getMessageIcons($start, $items_per_page, $sort)
{
	$request = wesql::query('
		SELECT m.id_icon, m.title, m.filename, m.icon_order, m.id_board, b.name AS board_name
		FROM {db_prefix}message_icons AS m
			LEFT JOIN {db_prefix}boards AS b ON (b.id_board = m.id_board)
		WHERE ({query_see_board} OR b.id_board IS NULL)',
		array(
		)
	);

	$message_icons = array();
	while ($row = wesql::fetch_assoc($request))
		$message_icons[] = $row;
	wesql::free_result($request);

	return $message_icons;
}

// This function sorts the smiley table by code length, it is needed as MySQL withdrew support for functions in order by.
function sortSmileyTable()
{
	loadSource('Class-DBHelper');

	// Add a sorting column.
	wedb::add_column('{db_prefix}smileys', array('name' => 'temp_order', 'size' => 8, 'type' => 'mediumint', 'null' => false));

	// Set the contents of this column.
	wesql::query('
		UPDATE {db_prefix}smileys
		SET temp_order = LENGTH(code)'
	);

	// Order the table by this column.
	wesql::query('
		ALTER TABLE {db_prefix}smileys
		ORDER BY temp_order DESC',
		array(
			'db_error_skip' => true,
		)
	);

	// Remove the sorting column.
	wedb::remove_column('{db_prefix}smileys', 'temp_order');
	cleanSmileyCache(false);
}

// A helper function to easily wipe out the smiley cache, both in the file cache and in the CSS cache.
function cleanSmileyCache($clean_cache = true)
{
	cache_put_data('smiley_parser', null, 'forever');
	cache_put_data('smiley_poster', null, 'forever');
	if ($clean_cache)
		clean_cache('css', 'smileys');
}
