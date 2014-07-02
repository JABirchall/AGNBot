<?php
while(1){
	foreach($ts3_VirtualServer->clientList() as $ts3_Client)
	{	
		//var_dump($ts3_Client);
		$nickname = (string)$ts3_Client;
		$uid = (string)$ts3_Client['client_unique_identifier'];
		if(/*strtolower($ts3_Client) === */in_array(strtolower($nickname), $badnames)){

			if($warned[$uid]['chances'] >= 2){
				$ts3_Client->poke("[COLOR=red][B]I gave you 3 chances, Don't waste my time![/B][/COLOR]");
				$warned[$uid]['chances'] = 0;
				$ts3_VirtualServer->clientKick($ts3_Client, TeamSpeak3::KICK_SERVER, "BOTKICK: {$nickname} is blacklisted nickname. (3 Chances)");
				break;
			}

			switch (@$warned[$uid]['warnings']) {
				default:
					$ts3_Client->poke("[COLOR=red][B]Please read your private message from me![/B][/COLOR]");
					$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change you name now or you will be kicked (10 second warning)[/B][/COLOR]");
					$warned[$uid]['warnings'] = 1;
					break;
				case 1:
					break;
				case 2:
					break;
				case 3:
					break;
				case 4:
					break;

				case 5:
					$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change you name now or you will be kicked. (5 second warning)[/B][/COLOR]");
					break;

				case 6:
					$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change you name now or you will be kicked. (4 second warning)[/B][/COLOR]");
					break;

				case 7:
					$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change you name now or you will be kicked. (3 second warning)[/B][/COLOR]");
					break;

				case 8:
					$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change you name now or you will be kicked. (2 second warning)[/B][/COLOR]");
					break;

				case 9:
					$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, Change you name now or you will be kicked. (1 second warning)[/B][/COLOR]");
					break;

				case 10:
					$ts3_Client->poke("[COLOR=red][B]Fine, I will just kick you![/B][/COLOR]");
					$ts3_Client->message("[COLOR=red][B]Your nickname is blacklisted, You have been kicked from the Aggressive Gaming teamspeak3 server for using an offensive name.[/B][/COLOR]");
					$warned[$uid]['warnings'] = 0;
					$warned[$uid]['chances'] = 0;
					$ts3_VirtualServer->clientKick($ts3_Client, TeamSpeak3::KICK_SERVER, "BOTKICK: {$nickname} is blacklisted nickname. (10 Second warning)");
					break;
			}
			$warned[$uid]['warnings'] += 1;
			//$warned[$uid] = array('warnings' => (isset($warned[$uid])) ? $warned[$uid]['warnings']+1 : 1);

			echo "tagKnife warned {$warned[$uid]['warnings']} times".PHP_EOL;
		}
		if(@$warned[$uid]['warnings'] >= 1 && !in_array(strtolower($nickname), $badnames)){
			$warned[$uid]['warnings'] = 0;
			echo "Changes username".PHP_EOL;
			$warned[$uid]['chances'] = isset($warned[$uid]['chances']) ? $warned[$uid]['chances']+1 : 1;
			$ts3_Client->message("[COLOR=green]Thankyou for changing your name.[/COLOR]");
			var_dump($warned);
		}

	}
	sleep(1);
	//unset($arr_ClientList); unset($ts3_Client);
	$ts3_VirtualServer->clientListReset();
}
