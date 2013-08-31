<?php
// Better Linguist List RSS feeds
// Michael Yoshitaka Erlewine <mitcho@mitcho.com>
// Dedicated to the public domain, 2010
// https://github.com/mitcho/betterlinguistlist/
// More: http://mitcho.com/blog/projects/better-linguist-list-rss-feeds/

include('cleanup.php');

$feedname = $_GET['feed'];
if (!isset($feedname))
	$feedname = 'mostrecent';

$feed = getlink("http://linguistlist.org/issues/rss/{$feedname}.xml");

$rss = simplexml_load_string($feed);

$rss->channel->title = preg_replace('!^The LINGUIST List:!','BLingList:',$rss->channel->title);

for($key = 0; $key < count($rss->channel->item); $key++) {
	$file = getlink($rss->channel->item[$key]->link,true);
	$file = preg_replace('/^.*E-mail this message to a friend<\/a><br><br>/ms','',$file);
	$file = preg_replace('/<hr size=1.*$/ms','',$file);
	$file = trim($file);

	// fix email addresses inline:
	$file = preg_replace("!<img src='[^']+address-marker.gif' align='absbottom'\s*/?>!", '@', $file);

	// fix email links:
	$file = preg_replace('!\<a href="JavaScript:;" onclick="window.open\(\'([^\'"]+)\'[^)]*\);"\>\s*&lt;\s*click here to access email\s*&gt;\s*\</a\>!i', '<a href="\1">email link</a>', $file);
	
	$id = preg_capture('/^(\d+\.\d+), /', $rss->channel->item[$key]->title);
	$file .= '<br/><a href="' . $rss->channel->item[$key]->link . '">[Linguist List announcement' . ($id ? ' ' . $id : '') . ']</a>';

	// rm issue numbers at beginning
	$rss->channel->item[$key]->title = preg_replace('/^(\d+\.\d+), /', "", $rss->channel->item[$key]->title);

	// if there's a real link:
	if ($capture = preg_capture('!(?:Web Address for Applications|Book URL):\s+\<a\s+href="([^"<]+?)"\>[^"<]+?\</a\>!i', $file))
		$rss->channel->item[$key]->link = $capture;
	elseif ($capture = preg_capture('!(?:Web Address|Web Site):\s+\<a\s+href="([^"<]+?)"\>[^"<]+?\</a\>!i', $file))
		$rss->channel->item[$key]->link = $capture;
	
	if ($feedname == 'calls') {
		if ($capture = preg_capture('!Full title: (.*?)\s*<br!i',$file))
			$rss->channel->item[$key]->title = $rss->channel->item[$key]->title . ": ".$capture;
		if ($capture = preg_capture('!(?:Web Site):\s+\<a\s+href="([^"<]+?)"\>[^"<]+?\</a\>!i', $file))
			$rss->channel->item[$key]->link = $capture;
	}

	if ($feedname == 'confs') {
		if ($capture = preg_capture('!^(.*?)\s*<br!i',$file,$realtitlematches))
			$rss->channel->item[$key]->title = $rss->channel->item[$key]->title . ": ".$capture;
		if ($capture = preg_capture('!(?:Meeting URL):\s+\<a\s+href="([^"<]+?)"\>[^"<]+?\</a\>!i', $file))
			$rss->channel->item[$key]->link = $capture;
	}

	// add bold
	$file = preg_replace('!^([\w\s()]+:)(\s|$|<)!m', '<b>\1</b>\2', $file);

	$node = $rss->channel->item[$key]->addChild('description');
	$node = dom_import_simplexml($node);
	$no = $node->ownerDocument;
	$node->appendChild($no->createCDATASection($file));
}

echo($rss->asXML());

function getlink($url,$cache = false) {

	if ($cache) {
		$filename = preg_replace('!^.*/([^/]*)$!','cache/$1',$url);
		if (file_exists($filename)) {
			touch($filename);
			return file_get_contents($filename);
		}
	}

	// create a new cURL resource
	$ch = curl_init();
	
	// set URL and other appropriate options
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// grab URL and pass it to the browser
	$feed = curl_exec($ch);
	
	// close cURL resource, and free up system resources
	curl_close($ch);
	
	if ($cache)
		file_put_contents($filename,$feed);

	return $feed;
}

function preg_capture($pattern, $text) {
	$matches = array();
	if (preg_match($pattern, $text, $matches))
		return $matches[1];
	return false;	
}
