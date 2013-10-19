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
*/

	if (!defined('SMF'))
		die('Hacking attempt!!');
	//Integrate into Admin section only!
	$hooks = array(
		'integrate_admin_include' => '$sourcedir/Subs-fbevents.php',
		'integrate_admin_areas' => 'fbevents_settings',
		'integrate_modify_modifications' => 'fbevents_config',
	);

	foreach($hooks as $hook => $call)
		add_integration_function($hook,$call);
?>