<?php

if (date("i"){1} != 0) //wenn es mit crontab jede minuten aufgerufen wird, wird es jedoch nur alle 10min ausgeführt.
	exit;


set_time_limit(0); //normalerweise wird ein skript nach 30sec beendet. der skript dauert zwar nicht so lange, aber falls doch verhindert dieser befehl das beenden

date_default_timezone_set('UTC');
require __DIR__.'/vendor/autoload.php';
/////// CONFIG ///////
$username = '';
$password = '';
$debug = true;
$truncatedDebug = false;
//////////////////////
$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}



$name = 'instagram';//username des Accounts, von dem die Liker des neusten Bildes gesucht werden


$likers = $ig->media->getLikers($ig->timeline->getUserFeed($ig->people->getUserIdForName($name))->getItems()[0]->pk)->fullResponse->users;
shuffle($likers); //damit nicht immer die gleichen am anfang sind und das Skript jedes mal sieht "ach, das habe ich ja schon geliked..."


$done = 0;


foreach ($likers as $i) {
    if ($i->is_private == false) {
		if ($ig->people->getFriendship($i->pk)->followed_by == false) {
			
			echo "acc zum liken gefunden: {$i->pk}\n";
			
			$accbilder = $ig->timeline->getUserFeed($i->pk)->getItems();
			
			if (count($accbilder)>0) {
				echo "bild vorhanden: {$i->pk}\n";
				
				
				$bildpk = $accbilder[0]->pk;
				if ($ig->media->getInfo($bildpk)->items[0]->has_liked == false) {
					echo "bild noch nicht geliked: {$i->pk}\n";
					//print_r($i);
					$done += 1;
					
					$ig->media->like($bildpk);
				}
			}
			
			//TODO: PAGENATION, wenn alle durch?
			
			if ($done >= 4) { //ALle 10 Minuten 4 Bilder liken -> 1h = 24 -> 24h = 576 Bilder.
				//unfollow(); 
				exit;
			}
		}
	}
}



function unfollow() { //ist momentan in auskommentiert /\
	global $ig;

	//Alle 10 Minuten 2 Leute entfolgen -> 1h = 12 Leute -> 24h = 288 Leute

	$keepFollowing = array();//hier die PKs ($ig->people->getUserIdForName('username')) von den freunden eintrage, die nicht entfolgt werden sollen


	$following = $ig->people->getSelfFollowing()->fullResponse->users;

	$done = 0;
	foreach ($following as $i) {
		if (!(in_array($i->pk, $keepFollowing))) {
			$ig->people->unfollow($i->pk);
			$done += 1;
			if ($done >= 2)
				exit;
		}
	}

}
