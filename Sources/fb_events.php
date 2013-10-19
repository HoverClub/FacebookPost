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
	added photo post function
*/
	if (!defined('SMF'))
		die('Hacking attempt...');

	function fbEventPost($eventOptions = array())
	{
		global $modSettings, $sourcedir, $scripturl;
		
		$boardList = (!empty($modSettings['fbboards']) ? unserialize($modSettings['fbboards']) : array());
//log_error('fb_events create 1 : opt = ' . print_r($eventOptions, true) . '<br/> sett = ' . print_r($modSettings, true) );

		if (	empty($eventOptions) 
				OR empty($modSettings['fbaccesstoken'])
				OR empty($modSettings['fbappid'])
				OR empty($modSettings['fbappsecret'])
				OR empty($modSettings['fbpageaccesstokens'])
				OR (!in_array($eventOptions['board'], $boardList) AND $eventOptions['topic'] != 0)
			)
			return null;

		// connect to app
		require_once($sourcedir . '/facebook.php');
		$fb = new facebook(array(
		  'appId'  =>  $modSettings['fbappid'],
		  'secret' => $modSettings['fbappsecret'],
		));

		$modSettings['fbpageaccesstokens'] = unserialize($modSettings['fbpageaccesstokens']);
		$fbEventIds = array();
		
		$body = array(
					'name' => (isset($modSettings['fbeventshdr']) ? $modSettings['fbeventshdr'] . ' : ' : '') . $eventOptions['title'],
					'start_time' => $eventOptions['start_date'],
					'description' => $scripturl . ($eventOptions['topic'] != 0 ? '?topic=' . $eventOptions['topic'] : '?action=calendar;year=' . (int) $_REQUEST['year'] . ';month=' . (int) $_REQUEST['month']),
					);
		if ($eventOptions['end_date'] != $eventOptions['start_date'])
			$body['end_time'] = $eventOptions['end_date'];

//log_error('fb_events create : body = ' . print_r($body, true) . '<br/> t = ' . print_r($modSettings['fbpageaccesstokens'], true));
		foreach ($modSettings['fbpageaccesstokens'] as $id => $token)
		{
			try{
				$body['access_token'] = $token;
				$res = $fb->api('/'. $id . '/events', 'POST', $body);
				if (isset($res['id']))
					$fbEventIds[$res['id']] = $token; // return array of fb events and tokens to SMF event data so we can remove/edit it later if needed
			} 
			catch (Exception $e)
			{
				log_error('fb_events create error:' . $e->getMessage() . ' FB id : ' . $id . ' token : ' . $token);
			}
		}
//log_error('fbeventids2 = ' . print_r(serialize($fbEventIds),true));
		return serialize($fbEventIds);
	}

	function fbEventModify($eventOptions = array())
	{
		global $modSettings, $sourcedir, $scripturl, $smcFunc, $topic;
		$fbEventIds = getFBids();
		
		if (	empty($fbEventIds)
				OR empty($eventOptions)
				OR empty($modSettings['fbappid'])
				OR empty($modSettings['fbappsecret'])
			)
			return null;

		// connect to app
		require_once($sourcedir . '/facebook.php');
		$fb = new facebook(array(
		  'appId'  =>  $modSettings['fbappid'],
		  'secret' => $modSettings['fbappsecret'],
		));

		$body = array(
					'name' => (isset($modSettings['fbeventshdr']) ? $modSettings['fbeventshdr'] . ' : ' : '') . $eventOptions['title'],
					'start_time' => $eventOptions['start_date'],
					'description' => $scripturl . ($topic != 0 ? '?topic=' . $topic : '?action=calendar;year=' . (int) $_REQUEST['year'] . ';month=' . (int) $_REQUEST['month']),
					);
		if ($eventOptions['end_date'] != $eventOptions['start_date'])
			$body['end_time'] = $eventOptions['end_date'];
				
//log_error('fb_events modify 1 : id = ' . print_r($fbEventIds, true));

		foreach ($fbEventIds as $id=>$token)
		{
			try{
				$body['access_token'] = $token;
				$res = $fb->api('/'. $id,'POST', $body);
			} 
			catch (Exception $e)
			{
				log_error('fb_events modify error:' . $e->getMessage()  . ' FB_eventID = ' . $id);
			}
		}
	}


	function fbEventRemove()
	{
		global $modSettings, $sourcedir, $smcFunc;
		
		$fbEventIds = getFBids();
		
		if (	empty($fbEventIds) 
				OR empty($modSettings['fbaccesstoken'])
				OR empty($modSettings['fbappid'])
				OR empty($modSettings['fbappsecret'])
			)
			return null;

		// connect to app
		require_once($sourcedir . '/facebook.php');
		$fb = new facebook(array(
		  'appId'  =>  $modSettings['fbappid'],
		  'secret' => $modSettings['fbappsecret'],
		));
//log_error('fb_events delete 1 : id = ' . print_r($fbEventIds, true));

		foreach ($fbEventIds as $id=>$token)
		{
			try{	
//log_error('fb_events delete : id = ' . $id . '<br/>token = ' . $token);
				$res = $fb->api('/'. $id, 'DELETE',array(
					'access_token' => $token,
					)
				);
			} 
			catch (Exception $e)
			{
				log_error('fb_event delete error: ' . $e->getMessage() . ' FB_eventID = ' . $id);
			}
		}
	}

	
	function fbPost($subject = '', $topicID = 0, $boardId = 0)
	{
		global $modSettings, $sourcedir, $scripturl;
		
		$subject = stripslashes($subject);
		$boardList = (!empty($modSettings['fbboards']) ? unserialize($modSettings['fbboards']) : array());

		if (	empty($subject) 
				OR empty($topicID) 
				OR empty($modSettings['fbaccesstoken'])
				OR empty($modSettings['fbpageaccesstokens'])
				OR empty($modSettings['fbappid'])
				OR empty($modSettings['fbappsecret'])
				OR !in_array($boardId,$boardList)
			)
			return;

		// connect to app
		require_once($sourcedir . '/facebook.php');		
		$fb = new facebook(array(
		  'appId'  =>  $modSettings['fbappid'],
		  'secret' => $modSettings['fbappsecret'],
		));

		$modSettings['fbpageaccesstokens'] = unserialize($modSettings['fbpageaccesstokens']);
		
/*
// batch request version - this won't work as we need different access tokens for some pages
		$body = (isset($modSettings['fbtopicshdr']) ? $modSettings['fbtopicshdr'] . "\r\n" ? '') . $subject "\r\n" . $scripturl . '?topic=' . $topicID;
		foreach ($modSettings['fbpageaccesstokens'] as $id => $token)
			$batchPost[] = array(
				'method' => 'POST',
				'relative_url' => '/' . $id . '/feed',
				'body' => http_build_query(array('access_token' => $token, 'message' => $body));
		
		try{	
			$response = $fb->api('?batch='.urlencode(json_encode($batchPost)), 'POST');
		} 
		catch (Exception $e)
		{
			log_error('fb_events mod:' . $e->getMessage());
		}

		// check for errors
		foreach ($response as $resp)
			if (empty($resp['status']) OR $resp['status'] != '200')
				log_error('fb_events mod post failed:' . print_r($resp,true));

*/
		$msg = (isset($modSettings['fbtopicshdr']) ? $modSettings['fbtopicshdr'] . "\r\n" : '') . $subject . "\r\n" . $scripturl . '?topic=' . $topicID;

		foreach ($modSettings['fbpageaccesstokens'] as $id => $token)
		{
			try{	
				$res = $fb->api('/'. $id . '/feed','POST', array('access_token' => $token, 'message' => $msg,));
			} 
			catch (Exception $e)
			{
				log_error('fbPost error:' . $e->getMessage());
			}
		}

	}
	
	function fbImage($image = array(), $catId = null)
	{
		global $modSettings, $sourcedir, $scripturl;
		
		$galleryList = (!empty($modSettings['fbgalleries']) ? unserialize($modSettings['fbgalleries']) : array());

		if (	empty($image) 
				OR empty($catId) 
				OR empty($modSettings['fbaccesstoken'])
				OR empty($modSettings['fbpageaccesstokens'])
				OR empty($modSettings['fbappid'])
				OR empty($modSettings['fbappsecret'])
				OR !in_array($catId, $galleryList)
			)
			return;

		// connect to app
		require_once($sourcedir . '/facebook.php');		
		$fb = new facebook(array(
		  'appId'  =>  $modSettings['fbappid'],
		  'secret' => $modSettings['fbappsecret'],
		));

		$modSettings['fbpageaccesstokens'] = unserialize($modSettings['fbpageaccesstokens']);
		
		$fb->setFileUploadSupport(true);
		$post_data = array(
			'message' => (isset($modSettings['fbimagehdr']) ? $modSettings['fbimagehdr'] . "\r\n" : '') . $image['title'] . "\r\n" . (!empty($image['description']) ? $image['description'] . "\r\n" : '') . (!empty($image['username']) ? $image['username'] . "\r\n" : '') . $scripturl . '?action=gallery',
			'source' => '@' . $modSettings['gallery_path'] . $image['filename'],
		);		

		foreach ($modSettings['fbpageaccesstokens'] as $id => $token)
		{
			$post_data['access_token'] = $token;
//log_error('fb_images : post_data =  ' . print_r($post_data, true));
			try{	
				$res = $fb->api('/'. $id . '/photos', 'POST', $post_data);
			} 
			catch (Exception $e)
			{
				log_error('fbImage error:' . $e->getMessage());
			}
		}
	}
	
	function getFBids()
	{
		global $smcFunc;
		// get FB event id
		$request = $smcFunc['db_query']('', '
			SELECT id_facebook
			FROM {db_prefix}calendar 
			WHERE id_event = {int:id_event}',
			array(
				'id_event' => $_REQUEST['eventid'],
			)
		);
		$fbrow = $smcFunc['db_fetch_assoc']($request);
		return (empty($fbrow) ? array() : unserialize($fbrow['id_facebook']));
	}
	
?>