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
    $settings = (int) json_decode(stripslashes($_POST["settings"]));
    $cats = json_decode(stripslashes($_POST["cats"]),true); // cats is an array
    $beta_mode = true;
    $retval = 0;
//    $start_date= 0;
    $aes_expire = 0;
    $trial_secs = 60*60*24*21;
    $curtime = time();
    $changed = true;

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
//	$ocats = (isset($account['custcats'])) ? explode(',',$account['custcats']) : [];
	$changed = ($account['settings'] != $settings || $ocats != $cats);

    } else {
	$start_date = $curtime;
	$aes_expire = -1; //$start_date + $trial_secs; // this is for permanent free account
	$account = array(
		'dates' => "$start_date,$aes_expire", // 21 days free aes password storage.
		'custcats' => "Uncategorized,Banking,E-commerce,Services,Social Media,Tools/Admin,Forums,Government,Unused,Unused"
	);
	$config_data = array();
    }
// when checking bits, enclose in parens before negating
	$cont = is_array($cats);
	if ($cont) {
		foreach ($cats as $el) {
			if (strlen($el) > 25) {
				$cont = 0;
				break;
			}
		}
	}
	if ($cont) {
		$cont = ($settings >-1 and $settings < 4);
	}
	if ($cont and $changed) {
		$account['settings'] = $settings;
		$account['custcats'] = filter_var(implode(',',$cats), FILTER_SANITIZE_STRING);
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
	}
//file_put_contents ( "/var/www/lloyd/passwords/data-source/mytest.txt","retval: ".print_r($retval,true) . "\n",FILE_APPEND);
    header("Content-Type: text/json; charset=UTF-8;");
    echo json_encode( $retval );
?>
