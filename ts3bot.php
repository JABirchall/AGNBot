<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
// Variables
define(VERSION, "1.1.3 Beta");
$warned = array();
$warnedi = array();
$jailed = array();
$badnames = array('fukyou', 'poontang', 'fucyou', 'fuck', 'cunt', 'fuku', 'bitch', 'b!tch', 'nigga', 'nigger', 'niga', 'shit', 'sh!t', 'penis', 'pen!s', 'fag', 'pussy', '[admin]', '[mod]','[moderator]', '[manager]', '[owner]', '[momerator]'); 
$afkTime = 60*60; //60 minutes 
$muteTime = 15*60;
$reported = array();
$pattern = "/[^a-zA-Z0-9ğüşıöçĞÜŞİÖÇ?!.;\\,\-_?'\/!*½=+$#£()<>{}\[\] % ]+/xi";
// load framework files
require_once("libraries/TeamSpeak3/TeamSpeak3.php");
// connect to local server, authenticate and spawn an object for the virtual server on port 9987
try{
	$ts3_VirtualServer = TeamSpeak3::factory("serverquery://afkbot:-protected-@142.4.205.65:10011/?server_port=9987&blocking=0&nickname=AGNBot");
	$ts3_BlacklistServer = TeamSpeak3::factory("blacklist");
}catch(TeamSpeak3_Adapter_ServerQuery_Exception $e){
	echo $e->getMessage();
}

$botChannel = $ts3_VirtualServer->channelGetByName("Bot House(Admins only)");
$ts3_VirtualServer->clientMove($ts3_VirtualServer->whoamiGet("client_id"), $botChannel);

$botChannel->message("BOT IS STARTING");

$ts3_VirtualServer->notifyRegister("textserver");
$ts3_VirtualServer->notifyRegister("textchannel");
//$ts3_VirtualServer->notifyRegister("textprivate");

// checdk for blacklisted servers
if($ts3_BlacklistServer->isBlacklisted("192.99.147.50")) $ts3_VirtualServer->message("[COLOR=red]your server is [B]blacklisted[/B]... disconnect now![/COLOR]");
else $ts3_VirtualServer->message("[COLOR=green]your server is [B]Not Blacklisted[/B].[/COLOR]");

TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyTextmessage", "onTextMessage");

$ts3_VirtualServer->message("[COLOR=blue][B]Aggressive Gaming Network teamspeak management bot started, version [COLOR=green]'". VERSION ."' [COLOR=blue]Server messages: [COLOR=green]ENABLED [COLOR=blue]Private messages: [COLOR=red]DISABLED! [COLOR=blue]your server is {$blacklisted}");

// Check server packet loss every second.
while(true){
	// 1 second ticks
	$sleepstart = microtime(TRUE)+1;
	$start = microtime(TRUE);

	$serverInfo = $ts3_VirtualServer->getInfo();
	$totalPacketloss = (float)$serverInfo["virtualserver_total_packetloss_total"]->toString() *100;
	
	if($totalPacketloss >= 49.9999) {
		$ts3_VirtualServer->message("[COLOR=red][B]The server is for being DDOS'D! (Average packet loss {$totalPacketloss}%[/COLOR]");
		sleep(30);
		continue;
	}
	else if($totalPacketloss >= 29.9999)
	{	
		$ts3_VirtualServer->message("[COLOR=red][B]The server is experiencing alot of lagg. (Average packet loss {$totalPacketloss}%[/COLOR]");
		sleep(30);
		continue;
	}
	else if($totalPacketloss >= 18.9999)
	{	
		$ts3_VirtualServer->message("[COLOR=orange][B]The server is experiencing moderate lagg. (Average packet loss {$totalPacketloss}%[/COLOR]");
		sleep(20);
		continue;
	}
	else if($totalPacketloss >= 9.9999)
	{
		$ts3_VirtualServer->message("[COLOR=orange][B]The server is experiencing minor lagg. (Average packet loss {$totalPacketloss}%)[/COLOR]");
		sleep(30);
		continue;
	}
	
	// walk through list of clients
	foreach($ts3_VirtualServer->clientList() as $ts3_Client)
	{
		if($ts3_Client["client_type"]) continue; // If Client is a client carry on else skip to next client

		try{
			$nickname = (string)strtolower(str_replace(' ', '', $ts3_Client));
			$uid = (string)$ts3_Client['client_unique_identifier'];
			$info = $ts3_Client->getInfo();
			$clAFK = $info['client_idle_time']/1000;
		}catch(Exception $e){
			$botChannel->message($e->getMessage());
		}

		try{
			if(stristr((string)$info['client_servergroups'], '13') || stristr((string)$info['client_servergroups'], '14') || stristr((string)$info['client_servergroups'], '32')){
				foreach($ts3_VirtualServer->complaintList() as $complaint){
					if(isset($reported[$nickname]) && @$reported[$nickname]['a'.$complaint['timestamp']] == $uid || $ts3_Client['client_type'] == 1){
						if(!isset($reported[$uid])){
							$ts3_Client->message("[COLOR=blue][B]After you have dealt with this, can you please remove the complaint from the list as to not confuse other moderators.[B][/COLOR]");
							$reported[$uid] = TRUE;
						}
					}else{
						unset($reported[$uid]);
						$reported[$nickname]['a'.$complaint['timestamp']] = $uid;
						$ts3_Client->message("[COLOR=blue][B]Hey {$nickname}, [U]{$complaint['fname']}[/U] has complained about [U]{$complaint['tname']}[/U] for \"{$complaint['message']}\" at ".date('Y-m-d H:i:s',$complaint['timestamp'])."[B][/COLOR]");
					}
				}
			}
		}catch(Exception $e){}

		if(stristr((string)$info['client_servergroups'], '44')){ // Check if the client is in the jailed group and handle with them accordingly
			try{
				$ts3_Client->move(213);
				$ts3_Client->poke("[COLOR=red][B]You have been sent to jail![/B][/COLOR]");
				$ts3_Client->poke("[COLOR=red][B]Serve your time and I will free you after 10 minutes.[/B][/COLOR]");
				$jailed[$uid] = time()+600;
			}catch(Exception $e){}

			if($jailed[$uid] <= time()){ // check time client has been in jail and if they spent there time.
				$ts3_VirtualServer->serverGroupClientDel(44,$ts3_Client["client_database_id"]);
				$ts3_Client->kick(TeamSpeak3::KICK_CHANNEL);
				$ts3_Client->poke("You have spent your jail time, you are now free to go.");
			}
		}
		// AFK MOVER
		try{
			if($nickname == "musicbot" || $nickname == "clubbot"){
			}else{
				if($clAFK >= $afkTime){
					$ts3_Client->move(216);
					$ts3_Client->message("[COLOR=green][B]You have been moved to the ~AFK~ Channel for being AFK for 1 hour.[/B][/COLOR]");
					$botChannel->message("Moved {$nickname} for being afk for {$clAFK}");
				} else if($clAFK >= $muteTime && $info['client_input_hardware'] == 0){
					$ts3_Client->move(216);
					$ts3_Client->message("[COLOR=green][B]You have been moved to the ~AFK~ Channel for being on another teamspeak.[/B][/COLOR]");
					$botChannel->message("Moved {$nickname} for being on another teamspeak for 15 minutes");
				} else if($clAFK >= $muteTime && $info['client_output_muted'] == 1){
					$ts3_Client->move(216);
					$ts3_Client->message("[COLOR=green][B]You have been moved to the ~AFK~ Channel for being muted for 15 minutes.[/B][/COLOR]");
					$botChannel->message("Moved {$nickname} for being muted on teamspeak for 15 minutes");
				}
			}
		}catch(Exception $e){}

		if(strposa($nickname, $badnames)){ // Check if the clients username is in violation of the bad names array
			try{
				if(@$warned[$uid]['chances'] >= 2){
					$ts3_Client->poke("[COLOR=red][B]I gave you 3 chances, Don't waste my time![/B][/COLOR]");
					$warned[$uid]['chances'] = 0;
					$ts3_VirtualServer->clientKick($ts3_Client, TeamSpeak3::KICK_SERVER, "BOTKICK: {$nickname} is blacklisted nickname. (3 Chances)");
					break;
				}
				
				$num = &$warned[$uid]['warnings'];

				if($num == 0) $ts3_Client->poke("[COLOR=red][B]Read your private message from me![/B][/COLOR]");
				if($num > 10 || (($num % 5) == 0)) $ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change your name now or you will be kicked. (" . (20 - $num) . " second warning)[/B][/COLOR]");

				if($num == 20){
					$ts3_Client->poke("[COLOR=red][B]Fine, I will just kick you![/B][/COLOR]");
					$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, You have been kicked from the Aggressive Gaming teamspeak 3 server for using an offensive name.[/B][/COLOR]");
					$warned[$uid]['warnings'] = 0;
					$warned[$uid]['chances'] = 0;
					$ts3_VirtualServer->clientKick($ts3_Client, TeamSpeak3::KICK_SERVER, "BOTKICK: {$nickname} is blacklisted nickname. (20 Second warning)");
				}
				$warned[$uid]['warnings'] = isset($warned[$uid]['warnings']) ? $warned[$uid]['warnings']+1 : 0;
			}catch(Exception $e){
				$botChannel->message($e->getMessage());
			}
			$botChannel->message("{$nickname} warned {$warned[$uid]['warnings']} times for blacklisted nickname.");
		}
		if(@$warned[$uid]['warnings'] >= 1 && !strposa($nickname, $badnames)){
			$warned[$uid]['warnings'] = 0;
			$botChannel->message("{$nickname} changed username.");
			$warned[$uid]['chances'] = isset($warned[$uid]['chances']) ? $warned[$uid]['chances']+1 : 1;
			$ts3_Client->message("[COLOR=green]Thank you for changing your name.[/COLOR]");
		}

		if(preg_match($pattern, $nickname)){ // Check if the client username is in violation of the character rules
			try{
				if(@$warnedi[$uid]['chances'] >= 2){
					$ts3_Client->poke("[COLOR=red][B]I gave you 3 chances, Don't waste my time![/B][/COLOR]");
					$warnedi[$uid]['chances'] = 0;
					$ts3_VirtualServer->clientKick($ts3_Client, TeamSpeak3::KICK_SERVER, "BOTKICK: {$nickname} is using illegal characters. (3 Chances)");
					break;
				}
	
				$warnedi[$uid]['warnings'] = isset($warnedi[$uid]['warnings']) ? $warnedi[$uid]['warnings']+1 : 0;
				$num = &$warnedi[$uid]['warnings'];

				if($num == 0) $ts3_Client->poke("[COLOR=red][B]Read your private message from me![/B][/COLOR]");
				if($num > 10 || (($num % 5) == 0)) $ts3_Client->message("[COLOR=red][B]Your nickname is using illegal characters, Change your name now or you will be kicked. (" . (20 - $num) . " second warning)[/B][/COLOR]");

				if($num == 20){
					$ts3_Client->poke("[COLOR=red][B]Fine, I will just kick you![/B][/COLOR]");
					$ts3_Client->message("[COLOR=red][B]Your nickname is using illegal characters, You have been kicked from the Aggressive Gaming teamspeak 3 server.[/B][/COLOR]");
					$warnedi[$uid]['warnings'] = 0;
					$warnedi[$uid]['chances'] = 0;
					$ts3_VirtualServer->clientKick($ts3_Client, TeamSpeak3::KICK_SERVER, "BOTKICK: {$nickname} is using illegal characters. (20 Second warning)");
				}
				$botChannel->message("{$nickname} warned {$warnedi[$uid]['warnings']} times for illegal characters");
			}catch(Exception $e){
				$botChannel->message($e->getMessage());
			}
		}
		if(@$warnedi[$uid]['warnings'] >= 1 && !preg_match($pattern, $nickname)){
			$warnedi[$uid]['warnings'] = 0;
			$botChannel->message("{$nickname} changed username.");
			$warnedi[$uid]['chances'] = isset($warnedi[$uid]['chances']) ? $warnedi[$uid]['chances']+1 : 1;
			$ts3_Client->message("[COLOR=green]Thank you for changing your name.[/COLOR]");
		}
	}

	$ts3_VirtualServer->clientListReset(); // Refresh the client list
	$sleep = ($sleepstart - microtime(TRUE)) * 1000000; // Calculate the remainder of the seond
	//$botChannel->message("[TICK] took ". (microtime(TRUE) - $start) ." to complete, sleep for ".($sleep/1000000));
	if($sleepstart > microtime(TRUE)) usleep($sleep); 
	else $botChannel->message("LAGG!");
}



function strposa($haystack, $needle, $offset=0) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $query) {
        if(strpos($haystack, $query, $offset) !== false) return true; // stop on first true result
    }
    return false;
}

function onTextMessage(TeamSpeak3_Adapter_ServerQuery_Event $event, TeamSpeak3_Node_Host $host) // Callback event function
{
	echo "[{$event["invokername"]}]: {$event["msg"]}".PHP_EOL;
	$serv = $host->serverGetByPort(9987);

	if(stristr(strtolower((string)$event["msg"]),'hello') && strtolower($event["invokername"]) != "agnbot")	$serv->message("Howdy {$event["invokername"]}, isn't it a nice day today."); // Debug command to check if bot is still running?

	if(stristr(strtolower((string)$event["msg"]),'!meetingmove') && strtolower($event["invokername"]) != "agnbot"){ // Move all clients to the meeting channel.
		foreach($serv->clientList() as $ts3_Client)
		{	
			$nickname = (string)strtolower($ts3_Client);
			if($nickname != "musicbot"){ // skip musicbot. (Should add client check.)
				try{
					$ts3_Client->move(30);
				}catch(Exception $e){}
			}
		}
	}

	if(stristr(strtolower((string)$event["msg"]),'!meetingtime') && strtolower($event["invokername"]) != "agnbot"){ // Tell all the users of a decided meeting time.
		$time = file_get_contents('meeting.txt');
		foreach($serv->clientList() as $ts3_Client)
		{
			try{
				$ts3_Client->message("[COLOR=blue][B]{$event["invokername"]} wanted to remind you the next meeting will be: {$time}[/B][/COLOR]");
			}catch(Exception $e){}
		}
	}
	
	if(stristr(strtolower((string)$event["msg"]),'!website') && strtolower($event["invokername"]) != "agnbot"){ // Tell all users of the website.
		$website = file_get_contents('website.txt');
		foreach($serv->clientList() as $ts3_Client)
		{
			try{
				$ts3_Client->message("[COLOR=blue][B]{$event["invokername"]} wanted to remind you to register on our website to receive member rank and full access to our servers and teamspeak![/B][/COLOR]");
				$ts3_Client->message("Website: [URL]http://aggressivegaming.org/[/URL]");
			}catch(Exception $e){}
		}
	}
	
	if(stristr(strtolower((string)$event["msg"]),'!pz') && strtolower($event["invokername"]) != "agnbot"){ // ??? Some ugly shit
		$pz = file_get_contents('pz.txt');
		foreach($serv->clientList() as $ts3_Client)
		{
			$nickname = (string)strtolower($ts3_Client);
			try{
				$ts3_Client->message("[COLOR=blue][B][B] Just a heads up {$pz} [/B][/COLOR]");
			}catch(Exception $e){}
		}
	}
	
	if(stristr(strtolower((string)$event["msg"]),'!dayz') && strtolower($event["invokername"]) != "agnbot"){ // ??? Some ugly shit
		$dayz = file_get_contents('dayz.txt');
		foreach($serv->clientList() as $ts3_Client)
		{
			$nickname = (string)strtolower($ts3_Client);
			try{
				$ts3_Client->message("[COLOR=blue][B] For you Dayz Mod players,  {$dayz} [/B][/COLOR]");
			}catch(Exception $e){}
		}
	}
	
	if(stristr(strtolower((string)$event["msg"]),'!minecraft') && strtolower($event["invokername"]) != "agnbot"){ // ??? Some ugly shit
		$minecraft = file_get_contents('minecraft.txt');
		foreach($serv->clientList() as $ts3_Client)
		{
			try{
				$ts3_Client->message("[COLOR=blue][B]{$event["invokername"]} wanted to remind you to: {$nickname}, {$minecraft}[/B][/COLOR]");
			}catch(Exception $e){}
		}
	}

	if(stristr(strtolower((string)$event["msg"]),'!time') && strtolower($event["invokername"]) != "agnbot") $serv->message("The current server time is ".date("F j, Y, g:i a")); // manualy time check if the bot is lagging.

	if(stristr(strtolower((string)$event["msg"]),'!info') && strtolower($event["invokername"]) != "agnbot"){ // Get info on a user
		$name = strtolower(str_replace("!info", '',str_replace("!info ", '',(string)$event["msg"])));
		try{
			$info = $serv->clientInfoDb($serv->clientFindDb($name));

			foreach($serv->clientList() as $ts3_Client)
			{
				$nickname = (string)strtolower($ts3_Client);
				if($nickname == (string)strtolower($event["invokername"])){
						$ts3_Client->message("[COLOR=blue][B]{$name}: Database ID {$info["client_database_id"]}[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Unique ID {$info["client_unique_identifier"]}[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Joined ".date("F j, Y, g:i a",$info["client_created"])."[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Last connection ". date("F j, Y, g:i a",$info["client_lastconnected"])."[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Total connections {$info["client_totalconnections"]}[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Client description {$info["client_description"]}[/COLOR]");
						$ts3_Client->message("[COLOR=blue][B]{$name}: Last IP {$info["client_lastip"]}[/COLOR]");
						break;
				}
			}
		}catch(Exception $e){}
	}

	if(stristr(strtolower((string)$event["msg"]),'!troll') || stristr(strtolower((string)$event["msg"]),'!jail') && strtolower($event["invokername"]) != "agnbot"){ // Add a user to the jail group (this will trigger the jail user later in the second.)
		$matches = '';
		$name = strtolower(str_replace("!troll ", '', str_replace("!jail ", '', (string)$event["msg"])));
		try{
			foreach($serv->clientList() as $ts3_Client)
			{
				$nickname = (string)strtolower($ts3_Client);

				if($nickname == $name){
					$serv->serverGroupClientAdd(44,$ts3_Client["client_database_id"]);
					break;
				} else if(stristr($nickname,$name)){
					$matches .= $nickname." ";
					$serv->serverGroupClientAdd(44,$ts3_Client["client_database_id"]);
				}
			}
			foreach($serv->clientList() as $ts3_Client)
			{
				$nickname = (string)strtolower($ts3_Client);
				if($nickname == 'moddedtibby' && $matches != ''){
					$ts3_Client->message("[COLOR=blue][B]{$event["invokername"]} has sent users matching {$name} to jail, users: {$matches} at ".date("F j, Y, g:i a",time())."[/B][/COLOR]"); // Tell moddedtibby someone has been jailed.
					echo "{$event["invokername"]} has sent {$matches} to jail at ".date("F j, Y, g:i a",time()).PHP_EOL;
					break;
				} else if($nickname == 'moddedtibby'){
					$ts3_Client->message("[COLOR=blue][B]{$event["invokername"]} has sent {$name} to jail at ".date("F j, Y, g:i a",time())."[/B][/COLOR]");
					echo "{$event["invokername"]} has sent {$name} to jail at ".date("F j, Y, g:i a",time()).PHP_EOL;
					break;
				}
			}
			if(isset($matches) && $matches != '') $serv->message("Found matches for {$name}: {$matches} - Sent to jail.");
		}catch(Exception $e){}
	}

	if(stristr(strtolower((string)$event["msg"]),'!unjail') && strtolower($event["invokername"]) != "agnbot"){ // Unjail a user manualy, if a issue if resolved or a mistaken jail.
		$matches = '';
		$name = strtolower(str_replace("!unjail ", '', (string)$event["msg"]));
		try{
			foreach($serv->clientList() as $ts3_Client)
			{
				$nickname = (string)strtolower($ts3_Client);

				if($nickname == $name){
					$serv->serverGroupClientDel(44,$ts3_Client["client_database_id"]);
					$ts3_Client->kick(TeamSpeak3::KICK_CHANNEL);
					$ts3_Client->poke("You have spent your jail time, you are now free to go.");
					break;
				} else if(stristr($nickname,$name)){
					$matches .= $nickname." ";
					$serv->serverGroupClientDel(44,$ts3_Client["client_database_id"]);
					$ts3_Client->kick(TeamSpeak3::KICK_CHANNEL);
					$ts3_Client->poke("You have spent your jail time, you are now free to go.");
				}
			}
		}catch(Exception $e){}
	}
	
	if(stristr(strtolower((string)$event["msg"]),'!kill') && strtolower($event["invokername"]) != "agnbot"){ // WHACK A MOLE
		$matches = '';
		$name = strtolower(str_replace("!kill ", '', (string)$event["msg"]));
		try{
			foreach($serv->clientList() as $ts3_Client)
			{
				$nickname = (string)strtolower($ts3_Client);

				if($nickname == $name){
					for($i=0; $i<=1000; $i++){
						$ts3_Client->message("[COLOR=red][B]a[/B][/color]");
						$ts3_Client->message("[COLOR=red][B]".bin2hex(mcrypt_create_iv(480))."[/B][/color]");
					}
				}
			}
		}catch(Exception $e){
			echo $e->getMessage();
		}
	}
}
