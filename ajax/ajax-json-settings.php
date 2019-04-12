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
    $datapath = dirname(__FILE__,2) . "/data-source";
    if (file_exists("$datapath/$hashPass")) {
	$result = array();
	$aalldata = parse_ini_file("$datapath/$hashPass",true);
	$result[] = array_shift($aalldata); // account array
    } else {
	$result[] = array("settings" => "empty");
    }
//file_put_contents ( "/var/www/lloyd/passvault/data-source/mytest.txt",json_encode($result)."\n",FILE_APPEND);
    header("Content-Type: text/json; charset=UTF-8;");
    echo json_encode( $result );
?>
