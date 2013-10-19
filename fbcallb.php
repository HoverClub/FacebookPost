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

	callback function to get & save facebook user access token after FB user login 
*/

	global $ssi_guest_access, $sourcedir;
	$ssi_guest_access = 1;
	require 'SSI.php';

	if(!isset($_GET['error']) AND isset($_GET['code']))
	{
		$curl_handle = curl_init('https://graph.facebook.com/oauth/access_token?client_id='.$modSettings['fbappid'] . '&redirect_uri='.urlencode($boardurl . '/fbcallb.php').'&client_secret=' . $modSettings['fbappsecret'] . '&code=' . $_GET['code']);
		curl_setopt_array($curl_handle, array(CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_RETURNTRANSFER=>1));
		$buffer = curl_exec($curl_handle);
		curl_close($curl_handle);    

		parse_str(trim($buffer), $params);
		if (!empty($params) AND !isset($params['error']) AND isset($params['access_token']))
			updateSettings(array('fbaccesstoken' => $params['access_token']));
		else
			exit ('Facebook event & post Mod - unable to get user access token: ' . htmlspecialchars($buffer, ENT_QUOTES));
	}

	ob_clean();
	header('Location: ' . $scripturl .'?action=admin;area=modsettings;sa=fbevents');
	exit;

?>