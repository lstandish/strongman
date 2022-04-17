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
    $user = json_decode(stripslashes($_POST["user"]));
    $retval = "OK";
    $datapath = dirname(__FILE__,2) . "/data-source";
    if (file_exists("$datapath/$hashPass")) {
	$config_data = parse_ini_file("$datapath/$hashPass",true);
//file_put_contents ( "/var/www/lloyd/passwords/data-source/mytest.txt","old account info: ".print_r($config_data,true) . "\n");
	$account = array_shift($config_data);
	unset($config_data[$domain][$user]);
	ksort($config_data);
	$config_data = array('_my_account_' => $account) + $config_data;
	$new_content = '';
	foreach ($config_data as $section => $section_content) {
		if ($section and $section_content) {
			$new_content .= "[$section]\n";
			foreach ($section_content as $key => $value) {
				$new_content .= "$key = $value\n";
			}
		}
	}
//file_put_contents ( "/var/www/lloyd/passwords/data-source/mytest.txt","new account info: ".print_r($config_data,true) . "\n",FILE_APPEND);
	file_put_contents("$datapath/$hashPass", $new_content);
    } else $retval = "entry not found";
    header("Content-Type: text/json; charset=UTF-8;");
    echo json_encode( $retval );
?>
