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

	Mod admin hooks and functions
	
 */

	if (!defined('SMF'))
		die('Hacking attempt...');

//Admin Settings Hooks
function fbevents_settings(&$admin_areas)
{
	global $txt;
	$admin_areas['config']['areas']['modsettings']['subsections']['fbevents'] = array($txt['fb_admin']);
}

function fbevents_config(&$subActions)
{
	$subActions['fbevents'] = 'ModifyFbeventsSettings';
}

// save config settings
function ModifyFbeventsSettings($return_config = false)
{
	global $sourcedir, $txt, $scripturl, $boardurl, $context, $modSettings, $smcFunc;

	$config_vars = array(
			array('desc','fbapphelp', 'text_label' => $txt['fbapphelp']),
			array('text', 'fbappid'),
			array('text', 'fbappsecret'),
			);
			
	if (!empty($modSettings['fbappid']) AND !empty($modSettings['fbappsecret'])) // we've got the app info
	{
		if (isset($_GET['save']) AND (isset($_REQUEST['fbappid']) AND $_REQUEST['fbappid'] != $modSettings['fbappid']))  // invalidate tokens because the appid has changed!
			updateSettings(
				array(
					'fbaccesstoken' => '', 
					'fbpageaccesstokens' => '',
					'fbpageids' => '',
					)
			);

		if (empty($modSettings['fbaccesstoken'])) // not got access token yet!
			$config_vars[] = array('desc','callback', 'text_label' => $txt['fbsignin'] . '<a href="https://www.facebook.com/dialog/oauth?client_id=' . $modSettings['fbappid'] . '&redirect_uri=' . urlencode($boardurl . '/fbcallb.php') .'&scope=manage_pages,user_groups,user_events,publish_stream,create_event">' . $txt['fbsignin2'] . '</a>');
		else
		{
			// get a list of groups & pages this user can access
			$pages = array();
			try
			{
				require_once($sourcedir . '/facebook.php');
				$facebook = new facebook(array(
				  'appId'  =>  $modSettings['fbappid'],
				  'secret' => $modSettings['fbappsecret'],
				));

				$facebook->setAccessToken($modSettings['fbaccesstoken']);

				$fbpages = $fbtokens = array();
				$fbpages['me'] = $txt['facebook_yourprofile']; // can always post to own fb page!
				$fbtokens['me'] = $modSettings['fbaccesstoken'];
				
				// get pages first
				$temp_pages = $facebook->api('/'.$facebook->getUser().'/accounts?fields=id,category,perms,name,access_token','GET',array('access_token'=>$modSettings['fbaccesstoken']));
				if (count($temp_pages['data']) > 0)
					foreach($temp_pages['data'] as $page)
						if($page["category"] != "Application" AND (isset($page["perms"]) AND in_array('CREATE_CONTENT',$page["perms"])))
						{
							$fbpages[$page['id']] = 'Page: ' . $page['name'];
							$fbtokens[$page['id']] = $page['access_token'];
						}
				
				// now get groups
				$temp_pages = $facebook->api('/'.$facebook->getUser().'/groups?fields=id,name,administrator','GET',array('access_token'=>$modSettings['fbaccesstoken']));
				if (count($temp_pages['data']) > 0)
					foreach ($temp_pages['data'] as $page)
						if (isset($page['administrator']) AND $page['administrator']==true) // only way we can tell if user can post to a group feed!!!
						{
							$fbpages[$page['id']] = 'Group: ' . $page['name'];
							$fbtokens[$page['id']] = $modSettings['fbaccesstoken'];  // use the users accesstoken for groups
						}

//log_error("fbevents fbpages array = " . print_r($fbpages,true));
//log_error("fbevents fbtokens array = " . print_r($fbtokens,true));
							
				$config_vars[] = '';
				$config_vars[] = array('check', 'fbtopics');
				$boards = array(); // get list of available boards 
				$result = $smcFunc['db_query']('', 'SELECT id_board, name FROM {db_prefix}boards');
				while ($fbrow = $smcFunc['db_fetch_assoc']($result))
					$boards[$fbrow['id_board']] = $fbrow['name'];
				$config_vars[] = array('select', 'fbboards', $boards, 'multiple'=>true,);
					
				$config_vars[] = array('text', 'fbtopicshdr');
				
				$config_vars[] = '';
				$config_vars[] = array('check', 'fbevents');
				$config_vars[] = array('text', 'fbeventshdr');
				
				// gallery detection
				include ($sourcedir . '/Subs-Package.php');
				$installed = loadInstalledPackages(); // get list of installed packages
				foreach ($installed as $name)
				{
					if (stripos($name['name'], 'SMF Gallery') !== false)
					{
						$config_vars[] = '';
						// get a list of gallery categories this user can view
						$dbresult = $smcFunc['db_query']('', 'SELECT * FROM {db_prefix}gallery_cat ORDER BY roworder ASC');
						// we need to ignore private categories (HoverClub gallery only)!!!!
						while($row = $smcFunc['db_fetch_assoc']($dbresult))	
							if (!isset($row['private']) OR (isset($row['private']) AND $row['private'] != 1))
								$cats[$row['id_cat']] = $row['title'];

						$config_vars[] = array('check', 'fbimages');
						$config_vars[] = array('select', 'fbgalleries', $cats, 'multiple'=>true,);
						$config_vars[] = array('text', 'fbimagehdr');
						break;
					}
				}		
				
				$config_vars[] = '';
				$config_vars[] = array('select', 'fbpageids', $fbpages, 'multiple'=>true,);
			}

			catch (Exception $e)
			{
				$e = 'fbevents - page/group auth token error ' . $e->getMessage();
			}
		}
	}
	
	if (isset($_REQUEST['e']) OR isset($e))
		$config_vars[] = array('desc','error', 'text_label' => '<span style="color:red">ERROR : ' . (isset($e) ? $e : urldecode($_REQUEST['e'])) . '</span>');

	$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=fbevents';
	$context['settings_title'] = $txt['fb_admin'];

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_REQUEST['save']) AND !isset($e))
	{
		if (!empty($modSettings['fbappid']) AND !empty($modSettings['fbappsecret']) AND !empty($_REQUEST['fbpageids']) AND !empty($modSettings['fbaccesstoken']) AND !empty($fbtokens))
		{  
			// get auth token for all of the pages/groups selected
			$pageaccess = array();
			foreach($fbtokens as $id=>$token)
				if (!in_array($id, (array)$_REQUEST['fbpageids']))
					unset($fbtokens[$id]);  // remove the ones we don't need

			if (!empty($fbtokens))
				updateSettings(
					array(
						'fbpageaccesstokens' => serialize($fbtokens),
					)
				);
//log_error("fbevents - page auth tokens " . print_r($fbtokens,true));
		}

		checkSession();
		saveDBSettings($config_vars);
		redirectexit('action=admin;area=modsettings;sa=fbevents');
	}
		
	prepareDBSettingContext($config_vars);
}

?>