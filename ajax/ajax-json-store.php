<?php
/*
Strongman Password Manager
Copyright 2019 Lloyd Standish
contact: lloyd@crnatural.net
source: https://github.com/lstandish/strongman/
website: https://strongman.tech

This file is part of Strongman.

    Strongman is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Strongman is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Strongman.  If not, see <https://www.gnu.org/licenses/>.
*/
function remove_notes ($opts) {
	$awork = explode(",",$opts);
	array_splice($awork,3,1);
	return implode(",",$awork);
}
    $hashPass = json_decode(stripslashes($_POST["hPass"]));
    $check = json_decode(stripslashes($_POST["check"]));
    $entries = json_decode(stripslashes($_POST["entries"]),true);

    $multiple = (count($entries)>1);
    $beta_mode = true;
    $retval = 0;
    $start_date= 0;
    $aes_expire = 0;
    $trial_secs = 60*60*24*21;
    $curtime = time();

/*
var venc = (retval & (1 << 0)); // venc
var aes = (retval & (1 << 1)); // aes
var paid = (retval & (1 << 2));
var exists = (retval & (1 << 3)); // check only: domain  + username has stored password
*/

    $datapath = dirname(__FILE__,2) . "/data-source";
    if (file_exists("$datapath/$hashPass")) {
	$config_data = parse_ini_file("$datapath/$hashPass",true);
	$account = array_shift($config_data);
	list( $start_date,$aes_expire) = explode(',',$account['dates']);
    } else {
	$start_date = $curtime;
	$aes_expire = -1; // $start_date + $trial_secs; // -1 is for permanent free account
	$account = array(
		'dates' => "$start_date,$aes_expire", // 21 days free aes password storage.
		'custcats' => "Uncategorized,Banking,E-commerce,Services,Social Media,Tools/Admin,Forums,Government,Unused,Unused"
	);
	$config_data = array();
	if (!$check) {
		include "new_account.php";
	}
    }
	$aes_expire = -1; // permanent free account
	if ($aes_expire > -1 and time() > $aes_expire) $retval += 1;  // account expired
	if ($aes_expire - $start_date > $trial_secs) $retval += 4; // paid account
//	if (substr_count($options,",") > 3) {
// aes password to be stored
//		$retval += 2;
//	}

// when checking bits, enclose in parens before negating
	if (!($retval & 1)) {
		foreach ($entries as $entry) {
			$domain = preg_replace('/[?{}|&~!()^"]/',"",$entry["domain"]);
			if (strlen($domain) > 100) continue;
			$user = preg_replace('/[?{}|&~!()^"]/',"",$entry["user"]);
			if (strlen($user) > 70 ) continue;
			$options = $entry["opts"];
			$awork = explode(",",$options);
			$cont = (sizeof($awork)<7);
			if ($cont) {
				$cont = ($awork[0] > 0 and $awork[0] < 16);
				if ($cont) $cont = ($awork[1] > 3 and $awork[1] < 35);
				if ($cont) $cont = ($awork[2] > -1 and $awork[2] < 100);
				if ($cont) $cont = (preg_match('/[?{}|&~!()^"]/',$awork[3]) === 0 and strlen($awork[3])<1280);
				if ($cont and sizeof($awork)>5) $cont = (preg_match('/[?{}|&~!()^"]/',$awork[5]) === 0 and strlen($awork[5])<400);
			}
			if (!$cont) continue;
			$warn = false;
			$changed = true;
			if (isset($config_data[$domain][$user])) {
				$work = $config_data[$domain][$user];
				$changed = (substr($work,0,strrpos($work,',')) != $options);
				$warn = $changed;
			}
			if ($check) {
				if ($warn) $retval += 8;
				header("Content-Type: text/json; charset=UTF-8;");
				echo json_encode( array($retval,$start_date) );
				return;
			} elseif ($changed) {
				if (isset($config_data[$domain][$user])) {
					$work = $config_data[$domain][$user];
					$lastcomma = strrpos($work,',');
					$notime = substr($work,0,$lastcomma);
					if ($multiple or remove_notes($notime) == remove_notes($options)) $curtime = substr($work,$lastcomma);
				}
				$config_data[$domain][$user]=$options . ",$curtime" ;
			}
		}
		ksort($config_data);
		$config_data = array('_my_account_' => $account) + $config_data;
		$new_content = '';
		foreach ($config_data as $section => $section_content) {
			if ($section) {
				$new_content .= "[$section]\n";
				foreach ($section_content as $key => $value) {
					$new_content .= "$key = $value\n";
				}
			}
		}
		file_put_contents("$datapath/$hashPass", $new_content);
//	file_put_contents ( "/var/www/lloyd/passwords/data-source/mytest.txt",print_r($new_content,true) . "\n"); //,FILE_APPEND);
	}
    header("Content-Type: text/json; charset=UTF-8;");
    echo json_encode( array($retval,$start_date) );
?>
