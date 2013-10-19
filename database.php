<?php

/* 
	Copyright 2013 John Robertson
	Released under the GNU/GPL license

	This file is part of the FaceBook Event & Post mod for SMF2.

    This is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This software is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

	
	last update 14/9/13
	aded support for SMF gallery image posting*/

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');
	elseif (!defined('SMF'))
		die('<br /><b>Error:</b>ERROR - unable to change SMF database for the FaceBook Post Mod - please verify you put this file in the same place as SMF\'s index.php.');

	// add settings into SMF.
	$new_settings = array(
		'fbappid' => '',
		'fbappsecret' => '',
		'fbaccesstoken' => '',
		'fbtopics' => '1',
		'fbevents' => '1',
		'fbimages' => '1',
		'fbtopicshdr' => '',
		'fbeventshdr' => '',
		'fbimagehdr' => '',
		'fbboards' => '',
		'fbgalleries' => '',
		'fbpageids' => '',
		'fbpageaccesstokens' => '',
	);

	// only update settings if they don't exist already (from a previous install)
	$replaceArray = array();
	foreach ($new_settings as $variable => $value)
		if (!isset($modSettings[$variable]))
			$replaceArray[] = array($variable, $value);

	if (!empty($replaceArray))
		$smcFunc['db_insert']('replace','{db_prefix}settings', array('variable' => 'string-255', 'value' => 'string-65534'), $replaceArray, array('variable'));

	// add new column to SMF calendar table to store FB event id
	if ($smcFunc['db_add_column']('{db_prefix}calendar', 
		array(
			'name' => 'id_facebook',
			'type' => 'text',
		), 
		array()) === false)
		die ('<b>Error:</b> Failed to insert new FB event ID column.');
?>