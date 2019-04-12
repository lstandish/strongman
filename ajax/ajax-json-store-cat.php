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
    $hashPass = json_decode(stripslashes($_POST["hPass"]));
    $domain = json_decode(stripslashes($_POST["domain"]));
    $cat = json_decode(stripslashes($_POST["cat"]));
    $user = json_decode(stripslashes($_POST["user"]));
    $retval = 0;
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
	$aes_expire = -1; //$start_date + $trial_secs; // -1 is for permanent free account
	$account = array(
		'dates' => "$start_date,$aes_expire" // 21 days free aes password storage.
	    );
	$config_data = array();
    }
	if ($aes_expire > -1 and time() > $aes_expire) $retval += 1;  // account expired
	if ($aes_expire - $start_date > $trial_secs) $retval += 4; // paid account
// when checking bits, enclose in parens before negating
	if (!($retval & 1)) {
		$changed = true;
		if (isset($config_data[$domain][$user])) {
			$ocat = explode(",",$config_data[$domain][$user])[4];
			$changed = ($cat != $ocat);
			if ($changed) {
				$work = $config_data[$domain][$user];
				$aopts = explode(",",$config_data[$domain][$user]);
				$aopts[4] = $cat;
				$config_data[$domain][$user]=implode(",",$aopts);
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
// file_put_contents ( "$datapath/mytest.txt","changed: ".print_r($cat,true) . "\n",FILE_APPEND);
				file_put_contents("$datapath/$hashPass", $new_content);
			}
		} else $retval += 2; // can't save, no existing entry
	}
    header("Content-Type: text/json; charset=UTF-8;");
    echo json_encode( $retval );
?>
