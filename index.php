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
// TO DO: add "account loaded" global to know when an account has been loaded via ajax.
//ini_set("display_errors",1);
//       error_reporting(E_ALL);
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies
$smversion = "1.34";
?>
<!DOCTYPE html>
<html>
<head>
<title>Strongman Password Manager</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="css/w3.css">
<link rel="stylesheet" type="text/css" href="css/style.css">
<link rel="stylesheet" type="text/css" href="css/jquery-autocomplete.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="icon" type="image/png" href="image/favicon-16x16.png" sizes="16x16">
<link rel="icon" type="image/png" href="image/favicon-32x32.png" sizes="32x32">
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jshashes-1.0.7/hashes.min.js"></script>
<script type="text/javascript" src="js/aes-js-master/index.js"></script>
<script type="text/javascript" src="js/jquery-autocomplete.js"></script>
<script type="text/javascript" src="js/javascript-biginteger-master/biginteger.js"></script>
<script type="text/javascript" src="js/moment.min.js"></script>
<script type="text/javascript" src="js/PapaParse-5.0.0/papaparse.min.js"></script>
<script type="text/javascript">
// globals
use_aes = true;
version = "";
hPub="";
hPriv="";
gsettings = 2;
gcats = [ "Uncategorized","Banking","E-commerce","Services","Social Media","Tools/Admin","Forums","Government","Unused","Unused" ];
gcatsw = gcats;
t="";
secs=0;

//if (!Date.now) {
//	Date.now = function() { return new Date().getTime(); }
//}

$(function(){
	$("#entry").autocomplete({
		ajax: {
			url: "ajax/ajax-json-list.php", // change the ajax post url
			timeout: 1000,
			error: function(x, t, m) {
				alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
				document.getElementById("enable").checked = false;
				setoffline();
			}
		},
		cb: {
			populate: function() {                // change the post data
				return {
					hPass: hPub,    // add custom data from the text input
					domainUser: $(this).val()             // add the value
				}
			},
			select: function(item, i) {          // modify item before display
				if (item.data.indexOf(': ')<0) {
					document.getElementById("entry").value = "";
					document.getElementById("username").value = "";
					return;
				}
				var res = item.data.match(/(.*?): (.*?) (#|\d*d)/);
				var notes="";
				if (document.getElementById("matchdel").checked) {
					document.getElementById("entry").value = "";
					if (confirm("Delete entry " + item.data + " from server?")) {
						$.ajax({
							type: 'POST',
							url: 'ajax/ajax-json-delete.php',
							cache: false,
							async: true,
							dataType: 'json',
							data: { hPass: JSON.stringify(hPub), user: JSON.stringify(res[2]), domain: JSON.stringify(res[1]) },
							error: function(xhr,status,error) {
								console.log(xhr.statusText);
								console.log(status);
								console.log(error);
							},
							success: function(retval) {
								if (retval == "OK") {
									showMsg("Entry deleted. If done deleting server entries, remember to uncheck 'Delete'.","w3-green");
									$("#entry").autocomplete("flushCache");
								} else {
									showMsg("There was an error: " + retval);
								}
							}
						});
					}
				} else {
					document.getElementById("entry").value = res[1];
					document.getElementById("username").value = res[2];
					document.getElementById("msgbox").style.display='none';
					var aopts = item.opts.split(',');
					document.getElementById("incr").value = (aopts[2]) ? aopts[2] : "1";
					if (aopts[3]) {
						var origtext;
						try {
							var akey = aesjs.utils.hex.toBytes(hPriv);
							var atext = aesjs.utils.hex.toBytes(aopts[3]);
							var counter = 5; //= Math.floor(Math.random() * 10000);
							var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
							var decryptedBytes = aesCtr.decrypt(atext);
							notes = aesjs.utils.utf8.fromBytes(decryptedBytes);
						}
						catch(err) {
							if (use_aes) {
								showMsg("There was an error AES-decrypting your secure notes. This can occur if you are using an unsupported device.","w3-red");
							} else {
								showMsg("AES decryption failed due to your low IOS version number. v10+ is required","w3-red");
							}
							notes = "Decryption Error!";
						}
					}
					var categ = aopts[4];
					if (aopts.length>5) {
						var origtext;
						try {
							var akey = aesjs.utils.hex.toBytes(hPriv);
							var atext = aesjs.utils.hex.toBytes(aopts[5]);
							var counter = 5; //= Math.floor(Math.random() * 10000);
							var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
							var decryptedBytes = aesCtr.decrypt(atext);
							origtext = aesjs.utils.utf8.fromBytes(decryptedBytes);
						}
						catch(err) {
							if (use_aes) {
								showMsg("There was an error AES-decrypting your password. This can occur if you are using an unsupported device.","w3-red");
							} else {
								showMsg("AES decryption failed due to your low IOS version number. v10+ is required","w3-red");
							}
							origtext = "Decryption Error!";
						}
						document.getElementById("cPassword").value = origtext;
						if (origtext != "Decryption Error!") {
							autoClip("Custom Password");
						}
					} else {
						setoptslen(aopts[0],aopts[1]);
						setpagerules();
						document.getElementById("cPassword").value = computePass(document.getElementById("fPassword").value.trim() + document.getElementById("entry").value.trim() + document.getElementById("username").value.trim() + aopts[2],aopts[1]);
						autoClip("Computed Password");
					}
					document.getElementById("categ").value = categ;
					document.getElementById("cPassword").focus();
				}
				setnotes(notes);
				document.getElementById("savebut").disabled=true;
			},
			cast: function(item) { // convert item to string
				if ("opts" in item) {
					if (item.data.indexOf("AES") == -1) {
						return "<span style='color:blue'>" + item.data + "</span>";
					} else {
						return "<span style='color:green'>" + item.data.replace(/ \#\d*/,"") + "</span>";
					} 
				} else {
					return "<span style='background-color:pink'>" + item.data + "</span>";
				}
			},
			process: function(data) {
				var result = [];
				var dfilter = document.getElementById("dfilter").value;
				if (dfilter>-1) {
					if (data[dfilter] !== undefined) {
						result = data[dfilter];
						result.unshift({data: gcatsw[dfilter]});
					}
				} else {
					var aLen = Object.keys(data).length;
					for (var i=0; i<aLen; i++) {
						var k = Object.keys(data)[i];
						if (data[k].length) {
							result.push({ data: gcatsw[k]});
							result = result.concat(data[k]);
						}
					}
				}
				return result;
			}
		},
		match: true,
		cache: true,
		once: true
	});
	$('.setMatch').click(function() {
		setmatch();
	});
	$("#prefixon").click(function() {
		initprefix(document.getElementById("prefixon").checked);
	});
	$('#savesettings').click(function() {
		var cursettings = 0;
		if (document.getElementById("prefixon").checked) cursettings += 2;
		if (document.getElementById("manualcopy").checked) cursettings += 1;
		for (var i=0; i<gcatsw.length; i++) {
			var el = document.getElementById('cc' + i);
			if (el !== null) {
				if (el.value.search(/[,<>\|\\]/) > -1) {
					alert("The following characters are not allowed in category names:,<>|\\");
					return;
				}
				if (!el.value) el.value = "Unused";
				gcatsw[i] = el.value;
			}
		}
		$.ajax({
			type: 'POST',
			url: 'ajax/ajax-json-settings-store.php',
			cache: false,
			async: true,
			dataType: 'json',
			data: { hPass: JSON.stringify(hPub), settings: JSON.stringify(cursettings), cats: JSON.stringify(gcatsw) },
			timeout: 1000,
			error: function(x, t, m) {
				alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
				document.getElementById("enable").checked = false;
				setoffline();
			},
			success: function(retval) {
				if (!retval) { 
					alert("Settings saved for future sessions.");
					resetcats();
					document.getElementById("notes").value = "";
					var x = document.getElementById("notesdiv");
					x.className = x.className.replace(" w3-show", "");
					document.getElementById("cPassword").value="";
					document.getElementById("username").value="";
					document.getElementById("entry").value="";
				} else alert("Error saving settings: " + retval);
			}
		});
	});
	$('#doaccnt').click(function() {
		var action = $("input[name='accntact']:checked").val();
		var sourcemp;
		var sPriv="";
		var sPub="";
		if (action != "delete") {
			if (!hPub) {
				alert("You need to enter a master password first.");
				return;
			}
		}
		if (action == "import" || action == "delete") {
			sourcemp = prompt("Enter the master password of the account to act upon.");
			if (!sourcemp) return;
			sPub = genhash(sourcemp,1000);
			if (action == "import") sPriv = genhash(sourcemp,5000);
		} else {
			sPub = hPub;
			sPriv = hPriv;
			sourcemp = document.getElementById("fPassword").value.trim();
		}
		switch(action) {
		case "delete":
			if (!confirm("Are you sure to want to DELETE the Strongman account associated with the provided master password? If a matching account is found, if will be irrevocably removed, and all passwords it contains lost.")) {
				return;
			}
			if (sPub == hPub) {
				if (!confirm("WARNING: The password provided is for your currently open account. Are you sure you want to remove this account?")) {
					return;
				}
			}
			$.ajax({
				type: 'POST',
				url: 'ajax/ajax-json-delaccount.php',
				cache: false,
				async: true,
				dataType: 'json',
				data: { hPass: JSON.stringify(sPub) },
				timeout: 1000,
				error: function(x, t, m) {
					alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
					document.getElementById("enable").checked = false;
					setoffline();
				},
				success: function(retval) {
					if (retval == 1) alert("The passphrase provided did not unlock any Strongman account. Deletion failed.");
					else if (retval == 2) alert("Error deleting account. Please contact administrator.");
					else {
						if (sPub == hPub) {
							lock();
						}
						alert("Strongman account deleted.");
					}
				}
			});
			return;
		case "import":
			if (!confirm("Are you sure you want to import all password data from the Strongman account associated with the master password you supplied? If a matching account is found, all computed passwords will be converted to AES-encrypted passwords and added to your currently open account. Settings such as custom category names will not be imported. Imported passwords will overwrite any matching password entries in the current account. The source Strongman account will not be deleted.  You may wish to do that using the 'Delete Strongman Account' option.")) {
				return;
			}
			if (sPub == hPub && action == "import") {
				alert("ERROR: The origin account for password import is for your currently open account. If you are trying to change the master password, create a new account with it first.");
				return;
			}
			break;
		case "local":
			var file = fileInput.files[0];
			if (!file) {
				alert("You need to first choose a local CSV password file for import.");
				return;
			}
			if (!confirm("Imported passwords entries will be added to your currently open account, overwriting any matching entries in the current account. Category names will not be imported.")) {
				return;
			}
			var textType = /text\/csv/;
			if (file.type.match(textType)) {
				Papa.parse(file,
				{
					header: true,
					skipEmptyLines: 'greedy',
					complete: function(results, file) {
//						console.log("Parsing complete:", results, file);
//lgs
						var missing = "";
						var userheaders = [];
						var lengths = [ 100,70,400,1280 ];
//						var mfields = results.meta.fields;
						for (var i=0; i<4 ; i++) {
							var test = document.getElementById('csv' + i).value;
							userheaders = userheaders.concat(test);
							if ($.inArray(test,results.meta.fields) < 0) {
								missing += " '" + test + "'";
							}
						}
						if (missing) {
							alert("The following header(s) are missing from your CSV file: " + missing + ". If the fields are present but with different headers, you can rename the headers via the 'custom CSV headers' link");
							return;
						}
						var aLen = Object.keys(results).length;
						if (!aLen) {
							alert("The selected file does not contain CSV data.");
							return;
						}
						var ajresult = [];
						for (var i=0; i<aLen; i++) {
							var k = Object.keys(results)[i];
							if (results[k].length) {
								ajresult = ajresult.concat(results[k]);
							}
						}
						var arraylen = ajresult.length;
						var resultary = [];
						var re = /[?{}|&~!()^"\\=]+/;
						var skipped = "";
						outerloop:
						for (var i=0; i<arraylen; i++) {
							var line = ajresult[i];
							if (('code' in line) && ('row' in line)) {
								skipped += "Row " + line['row'] + ": " + line['code'];
								continue;
							}
							var sdomain = line[userheaders[0]];
							var suser = line[userheaders[1]];
							var spass = line[userheaders[2]];
							var snotes = line[userheaders[3]];
							for (var j=0; j<4; j++) {
								if (line[userheaders[j]].length > lengths[j]) {
									skipped += sdomain + '/' + suser + ": excessive field length\n";
									continue outerloop;
								}
							}
							if (re.test(suser) || re.test(sdomain)) {
								skipped += sdomain + '/' + suser + ": Illegal characters found in username or domain\n";
								continue;
							}
							if (!suser || !sdomain || !spass) {
								skipped += sdomain + '/' + suser + ": Empty";
								if (!suser) skipped += " username";
								if (!sdomain) skipped += " domain";
								if (!spass) skipped += " password";
								skipped += "\n";
								continue;
							}
							if (snotes) {
								try {
									var akey = aesjs.utils.hex.toBytes(hPriv);
									var ctext = aesjs.utils.utf8.toBytes(snotes);
									var counter = 5;//Math.floor(Math.random() * 10000);
									var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
									var encryptedBytes = aesCtr.encrypt(ctext);
									snotes = aesjs.utils.hex.fromBytes(encryptedBytes);
								}
								catch(err) {
									if (use_aes) {
										showMsg("There was an AES-encryption error. This can occur if you are using an unsupported device.","w3-red");
									} else {
										showMsg("AES encryption of the notes failed due to your low IOS version number (" + version + ")","w3-red");
									}
									return;
								}
							}
							var chex;
							try {
								var akey = aesjs.utils.hex.toBytes(hPriv);
								var ctext = aesjs.utils.utf8.toBytes(spass);
								var counter = 5;//Math.floor(Math.random() * 10000);
								var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
								var encryptedBytes = aesCtr.encrypt(ctext);
								chex = aesjs.utils.hex.fromBytes(encryptedBytes);
							}
							catch(err) {
								if (use_aes) {
									showMsg("There was an AES-encryption error. This can occur if you are using an unsupported device.","w3-red");
								} else {
									showMsg("AES encryption failed due to your low IOS version number (" + version + ")","w3-red");
								}
								return;
							}
							resultary.push({ user: suser.toLowerCase(), domain: sdomain.toLowerCase(), opts: "15,14,1,"+snotes+",0,"+chex});
						}
//						console.log(resultary);
						$.ajax({
							type: 'POST',
							url: 'ajax/ajax-json-store.php',
							cache: false,
							async: true,
							dataType: 'json',
							data: { hPass: JSON.stringify(hPub), check: JSON.stringify(0), entries: JSON.stringify(resultary) },
							timeout: 1000,
							error: function(x, t, m) {
								alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
								document.getElementById("enable").checked = false;
								setoffline();
							},
							success: function(retval) {
								alert("Entry import complete");
								$("#entry").autocomplete("flushCache");
							}
						});
						if (skipped) {
							alert("Warning, the following entries were skipped:\n" + skipped);
						}
					},
					error: function(err,file) {
						alert("File could not be read, error:" + err);
					}
				});
			} else {
				alert("Only CSV type files are supported");
			}
			return;
		case "export":
			if (!confirm("Are you sure you want to export to a CSV file all password data from the currently open Strongman account?")) {
				return;
			}
			break;
		default:
			alert("No action selected");
			return;
		}
		$.ajax({
			type: 'POST',
			url: 'ajax/ajax-json-list.php',
			cache: false,
			async: true,
			dataType: 'json',
			data: { hPass: sPub },
			timeout: 1000,
			error: function(x, t, m) {
				alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
				document.getElementById("enable").checked = false;
				setoffline();
			},
			success: function(data) {
				var aLen = Object.keys(data).length;
				if (!aLen) {
					alert("The master password provided does not unlock any existing Strongman account.");
					return;
				}
				var ajresult = [];
				for (var i=0; i<aLen; i++) {
					var k = Object.keys(data)[i];
					if (data[k].length) {
						ajresult = ajresult.concat(data[k]);
					}
				}
				var arraylen = ajresult.length;
				var resultary = [];
				var csv = '\ufeff"Account","Login Name","Password","Web Site","Comments"' + "\n";
// https://stackoverflow.com/questions/17879198/adding-utf-8-bom-to-string-blob
				for (var i=0; i<arraylen; i++) {
					var item = ajresult[i];
					item.data.replace(/ \#\d*/,"");
					var res = item.data.match(/(.*?): (.*?) (#|\d*d)/);
					var sdomain = res[1];
					var suser = res[2];
					var aopts = item.opts.split(',');
					var incr = (aopts[2]) ? aopts[2] : "1";
					var snotes = "";
					var spass;
					if (aopts[3]) {
// notes
						try {
							var akey = aesjs.utils.hex.toBytes(sPriv);
							var atext = aesjs.utils.hex.toBytes(aopts[3]);
							var counter = 5; //= Math.floor(Math.random() * 10000);
							var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
							var decryptedBytes = aesCtr.decrypt(atext);
							snotes = aesjs.utils.utf8.fromBytes(decryptedBytes);
						}
						catch(err) {
							if (use_aes) {
								showMsg("There was an error AES-decrypting secure notes. This can occur if you are using an unsupported device.","w3-red");
							} else {
								showMsg("AES decryption failed due to your low IOS version number. v10+ is required","w3-red");
							}
							return;
						}
						if (action == "import") {
							try {
								var akey = aesjs.utils.hex.toBytes(hPriv);
								var ctext = aesjs.utils.utf8.toBytes(snotes);
								var counter = 5;//Math.floor(Math.random() * 10000);
								var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
								var encryptedBytes = aesCtr.encrypt(ctext);
								snotes = aesjs.utils.hex.fromBytes(encryptedBytes);
							}
							catch(err) {
								if (use_aes) {
									showMsg("There was an AES-encryption error. This can occur if you are using an unsupported device.","w3-red");
								} else {
									showMsg("AES encryption of the notes failed due to your low IOS version number (" + version + ")","w3-red");
								}
								return;
							}
						}
					}
					if (aopts.length>5) {
						try {
							var akey = aesjs.utils.hex.toBytes(sPriv);
							var atext = aesjs.utils.hex.toBytes(aopts[5]);
							var counter = 5; //= Math.floor(Math.random() * 10000);
							var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
							var decryptedBytes = aesCtr.decrypt(atext);
							spass = aesjs.utils.utf8.fromBytes(decryptedBytes);
						}
						catch(err) {
							if (use_aes) {
								showMsg("There was an AES-decryption error. This can occur if you are using an unsupported device.","w3-red");
							} else {
								showMsg("AES decryption failed due to your low IOS version number. v10+ is required","w3-red");
							}
							return;
						}
					} else {
						setpagerules(aopts[0]);
						spass = computePass(sourcemp.trim() + sdomain.trim() + suser.trim() + aopts[2],aopts[1]);
					}
					if (action == "export") {
						csv += '"Strongman' +  '","' + suser.trim() + '","' + spass + '","' + sdomain.trim() + '","' + snotes + '"' + "\n";
					} else {
						var chex;
						try {
							var akey = aesjs.utils.hex.toBytes(hPriv);
							var ctext = aesjs.utils.utf8.toBytes(spass);
							var counter = 5;//Math.floor(Math.random() * 10000);
							var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
							var encryptedBytes = aesCtr.encrypt(ctext);
							chex = aesjs.utils.hex.fromBytes(encryptedBytes);
						}
						catch(err) {
							if (use_aes) {
								showMsg("There was an AES-encryption error. This can occur if you are using an unsupported device.","w3-red");
							} else {
								showMsg("AES encryption failed due to your low IOS version number (" + version + ")","w3-red");
							}
							return;
						}
						resultary.push({ user: suser, domain: sdomain, opts: "15,14,1,"+snotes+","+aopts[4]+","+chex});
					}
				}
				if (action == "import") {
					$.ajax({
						type: 'POST',
						url: 'ajax/ajax-json-store.php',
						cache: false,
						async: true,
						dataType: 'json',
						data: { hPass: JSON.stringify(hPub), check: JSON.stringify(0), entries: JSON.stringify(resultary) },
						timeout: 1000,
						error: function(x, t, m) {
							alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
							document.getElementById("enable").checked = false;
							setoffline();
						},
						success: function(retval) {
							alert("Entry import complete");
							$("#entry").autocomplete("flushCache");
//							var venc = (retval & (1 << 0)); // aes
//							var aes = (retval & (1 << 1)); // aes
//							var paid = (retval & (1 << 2));
//							var warn = (retval & (1 << 3)); // exists and has changed (warn)
						}
					});
				} else {
					var blob = new Blob([csv], {type: 'text/csv'});
					if(window.navigator.msSaveOrOpenBlob) {
						window.navigator.msSaveBlob(blob, 'Strongman-passwords.csv');
					} else {
						var elem = window.document.createElement('a');
						elem.href = window.URL.createObjectURL(blob);
						elem.download = 'Strongman-passwords.csv';
						document.body.appendChild(elem);
						elem.click();
						document.body.removeChild(elem);
					}
				}
			}
		});
	});
	$("#enable").click(function() {
		if ($(this).is(":checked")) {
			document.getElementById("matchon").disabled = false;
			document.getElementById("matchdel").disabled = false;
			eraseCookie("offline");
			document.getElementById("doaccnt").disabled = false;
			if (hPub) {
				document.getElementById("savesettings").disabled = false;
				autoenable(true);
			}
			showMsg("Online mode","w3-green");
		} else {
			setoffline();
//	doaccnt and savesettings buttons disabled by setoffline()
			setCookie("offline","1",1000);
			showMsg("Offline mode","w3-yellow");
		}
	});

	$("#eyemaster").click(function() {
		eyework();
		if ($(".myeye").hasClass("fa-eye")) {
			$('#fPassword').focus();
		}
	});
	$("#eyecomp").click(function() {
		eyework();
		if ($(".myeye").hasClass("fa-eye")) {
			$('#cPassword').focus();
		}
	});

	$("#savegeneral").click(function() {
		if ($("#autoclear").is(":checked")) {
			if ($("#autosecs").val() < 30 || $("#autosecs").val() > 9999) {
				alert("Autoclear seconds must be between 60 and 9999.");
				return;
			}
			secs = $("#autosecs").val();
			setCookie("autoclear",secs,1000);
		} else {
			secs = 0;
			eraseCookie("autoclear");
		}
		if (!$("#permitnodw").is(":checked")) {
			eraseCookie("permitnodw");
		} else {
			setCookie("permitnodw","1",1000);
		}
		alert("General Settings saved via cookies.");
	});
	$("#permitnodw").click(function() {
		if ($("#permitnodw").is(":checked")) {
			if (!confirm("Are you sure you want to allow non-Diceware master passwords? Note that anything less than a (pseudo)random 9-character password of mixed characters or 6+ word 'Diceware' type passphrase is INSECURE and could lead to your account being hacked!")) {
				$("#permitnodw").prop("checked",false);
			}
		}
	});
	$("#autoclear").click(function() {
		if ($("#autoclear").is(":checked")) {
			$("#autosecs").attr("disabled",false);
		} else {
			$("#autosecs").attr("disabled",true);
		}
	});

	$("#fileInput").change(function() {
		var val = $(this).val();
		var regex = /\.csv$/i;
		document.getElementById('radio_l').checked=true;
		if (val && !regex.test(val)) {
			$(this).val('');
			alert('Please select a CSV file (.csv extension).');
		}
	});

	<?=(isset($_COOKIE["matchon"])) ? "initmatch(1);" : "initmatch(0);";?>
	<?=(isset($_COOKIE["permitnodw"])) ? "initnodw(1);" : "initnodw(0);";?>
	resetcats();
// Disable autocomplete to avoid hammering ajax before a password account is opened or created
//	$("#entry").autocomplete("disable");
	autoenable(false);
	if (getCookie("offline")) {
		$("#entry").autocomplete("matchoff");
//		$("#entry").autocomplete("disable");
		document.getElementById("matchon").checked = false;
		document.getElementById("matchon").disabled=true;
	}
	version = is_ios();

	// clear out user data upon navigate away
	window.onbeforeunload = function(event) {
		lock();
		return null;
	};
/*
	if (version) {
		if (version < 10) {
			document.getElementById("copybut").style.display="none";
			alert("Detected IOS version " + version + ". 10+ is necessary for custom password support.");
			use_aes = false;
			setComputed();
		}
	}
*/
	// 0 = disable
    window.onmousemove = resetTimer; // catches mouse movements
    window.onmousedown = resetTimer; // catches mouse movements
    window.onclick = resetTimer;     // catches mouse clicks
    window.onscroll = resetTimer;    // catches scrolling
    window.onkeypress = resetTimer;  //catches keyboard actions
	<?php if (isset($_COOKIE["autoclear"])) { ?>
		document.getElementById("autoclear").checked = true;
		secs = getCookie('autoclear');
		document.getElementById("autosecs").value = secs;
		document.getElementById("autosecs").disabled = false;
	<?php } ?>

});


//window.onload = function() {
//    document.getElementById('myreset').onclick = clearform;
//    setTimeout(clearform,600000);
//};
var _characterSubsets = {
lcase: "abcdefghijklmnopqrstuvwxyz",
ucase: "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
num: "0123456789",
symb: "!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~"
};
var rules;
var setOfCharacters;

function resetTimer() {
    clearTimeout(t);
	if (secs>0) {
    t = setTimeout(lock, secs*1000);  // time is in milliseconds (1000 is 1 second) 1800000
//    t= setTimeout(reload, 300000);  // time is in milliseconds (1000 is 1 second)
	}
}

function eyework() {
	if ($(".myeye").hasClass("fa-eye")) {
		setCookie("focushidepwd","1",1000);
		$(".myeye").attr("title","always hide password");
		$(".myeye").attr("type","password");
	} else {
		eraseCookie("focushidepwd");
		$(".myeye").attr("title","show password upon focus");
		$(".myeye").attr("type","text");
	}
	$(".myeye").toggleClass("fa-eye");
	$(".myeye").toggleClass("fa-eye-slash");
}

function autoenable(on) {
	var x = document.getElementById("entry");
	if (on) {
		$("#entry").autocomplete("enable");
		if (x.className.indexOf("w3-pale-green") == -1) {
			x.className += " w3-pale-green";
		}
	} else {
//		$("#entry").autocomplete("flushCache");
		$("#entry").autocomplete("disable");
		x.className = x.className.replace(" w3-pale-green", "");
	}
}

function setoffline() {
	document.getElementById("savesettings").disabled = true;
	document.getElementById("doaccnt").disabled = true;
//	document.getElementById("mergepass").disabled = true;
	autoenable(false);
	$("#entry").autocomplete("matchoff");
	document.getElementById("matchon").checked = false;
	document.getElementById("matchon").disabled = true;
	document.getElementById("matchdel").checked = false;
	document.getElementById("matchdel").disabled = true;
}

function checkext() {
	var file = document.querySelector("#fileInput");
	document.getElementById('radio_l').checked=true;
//lgs
	if ( /\.csv$/i.test(file.files[0].name) === false ) {
		alert("Please select a CSV file for import");
		return false;
	}
}

function setnotes(notes) {
	document.getElementById("notes").value = notes;
	var x = document.getElementById("notesdiv");
	if (notes) {
		if (x.className.indexOf("w3-show") == -1) {
			x.className += " w3-show";
		}
	} else {
		x.className = x.className.replace(" w3-show", "");
	}
}

function editpass() {
	var ob = document.getElementById("cPassword");
	if (checkpass(ob)) {
		ob.placeholder = "Enter password, click Save";
	}
}	

function compute(ob) {
	if (!checkpass(ob)) {
		return;
	}
	var incr = document.getElementById("incr").value;
	setpagerules();
	document.getElementById("cPassword").value = computePass(document.getElementById("fPassword").value.trim() + document.getElementById("entry").value.trim() + document.getElementById("username").value.trim() + incr,document.getElementById("len").value);
	if (document.getElementById("enable").checked) {
		ajaxsave(0,1,incr);
	}
	autoClip("Computed Password");
}
/*
function hexToBytes(hex) {
    for (var bytes = [], c = 0; c < hex.length; c += 2)
    bytes.push(parseInt(hex.substr(c, 2), 16));
    return bytes;
}
*/
function savepass(butob) {
	if (checkpass(butob)) {
		if (!document.getElementById("enable").checked) {
			alert("You can't save custom passwords in offline mode. To try for an Internet connection, tic the 'Online' checkbox");
			return;
		}
		var pass = document.getElementById("cPassword").value;
		var chex;
		try {
			var akey = aesjs.utils.hex.toBytes(hPriv);
			var ctext = aesjs.utils.utf8.toBytes(pass);
			var counter = 5;//Math.floor(Math.random() * 10000);
			var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
			var encryptedBytes = aesCtr.encrypt(ctext);
			chex = aesjs.utils.hex.fromBytes(encryptedBytes);
		}
		catch(err) {
			if (use_aes) {
				showMsg("There was an error AES-encrypting your password. This can occur if you are using an unsupported device.","w3-red");
			} else {
				showMsg("AES encryption of the password failed due to your low IOS version number (" + version + ")","w3-red");
			}
			return;
		}
		ajaxsave(chex,1,1);
		autoClip("Custom Password");
	}
}
function ajaxsave(cipher,checkexisting,incr) {
	var opts = collectopts();
	opts += ',' + document.getElementById("len").value + ',' + incr;

	var pass = document.getElementById("cPassword").value;
	var notes = document.getElementById("notes").value;
	if (notes) {
		try {
			var akey = aesjs.utils.hex.toBytes(hPriv);
			var ctext = aesjs.utils.utf8.toBytes(notes);
			var counter = 5;//Math.floor(Math.random() * 10000);
			var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
			var encryptedBytes = aesCtr.encrypt(ctext);
			notes = aesjs.utils.hex.fromBytes(encryptedBytes);
		}
		catch(err) {
			if (use_aes) {
				showMsg("There was an error AES-encrypting your secure notes. This can occur if you are using an unsupported device.","w3-red");
			} else {
				showMsg("AES encryption of the notes failed due to your low IOS version number (" + version + ")","w3-red");
			}
			return;
		}
	}
	var catval = document.getElementById("categ").value;
	opts += ',' + notes + ',' + catval;
	if (cipher) {
		opts += ',' + cipher;
	}
	var domain = document.getElementById("entry").value.trim();
	var user = document.getElementById("username").value.trim();
	var aentry = [{ user: user, domain: domain, opts: opts}];
	$.ajax({
		type: 'POST',
		url: 'ajax/ajax-json-store.php',
		cache: false,
		async: true,
		dataType: 'json',
		data: { hPass: JSON.stringify(hPub), check: JSON.stringify(checkexisting), entries: JSON.stringify(aentry)},
		timeout: 1000,
		error: function(x, t, m) {
			alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
			document.getElementById("enable").checked = false;
			setoffline();
		},
		success: function(ret) {
			var retval = ret[0];
			var venc = (retval & (1 << 0)); // aes
			var aes = (retval & (1 << 1)); // aes
			var paid = (retval & (1 << 2));
			var warn = (retval & (1 << 3)); // exists and has changed (warn)
			if (checkexisting) {
				if (!warn) {
					ajaxsave(cipher,0,incr);
				} else if (confirm("Password and/or secure notes have been changed.  Update old password/notes with new ones?")) {
					ajaxsave(cipher,0,incr);
				} else {
					alert("Old password and/or notes were not replaced. To show old password/notes, click in the 'Domain' field and choose your domain/username pair again.");
				}
			} else {
				if (venc) {
					if (aes) {
						if (paid) {
							showMsg("<p>ERROR: Your AES-encrypted password was NOT saved due to account expiry.</p><p>You can still access all your saved AES-encrypted passwords, but not change them or store new ones. Renew your account <a href='/payment.php'>here</a></p>","w3-red");
						} else {
							showMsg("<p>ERROR: Your AES-encrypted password was NOT saved due to expiry of your 3-week free trial period.</p><p>To save AES-encrypted passwords, there is a fee of $2.00/mo. One year is only $24. (Calculated passwords can be used for free.) Make your payment <a href='/payment.php'>here</a></p>","w3-red");
						}
					}
				} else {
					
					$("#entry").autocomplete("flushCache");
					autoenable(true);
					document.getElementById("savebut").disabled=true;
					document.getElementById("accountdata").innerHTML="Free account, started " + moment(ret[1]*1000).format('L');
				}
			}
/*			} else {
				showMsg("Undetermined communication error, " + aes ? "password" : "data" + " not saved.","w3-red");
			}
*/
		}
	});
}

function ajaxsavenotes(checkexisting) {
	if (!document.getElementById("enable").checked) {
		alert("Secure notes cannot be saved in offline mode.");
		return;
	}
	if (!checkpass(document.getElementById("notessavelink"))) {
		return;
	}
	var notes = document.getElementById("notes").value;
	if (notes) {
		try {
			var akey = aesjs.utils.hex.toBytes(hPriv);
			var ctext = aesjs.utils.utf8.toBytes(notes);
			var counter = 5;//Math.floor(Math.random() * 10000);
			var aesCtr = new aesjs.ModeOfOperation.ctr(akey, new aesjs.Counter(counter));
			var encryptedBytes = aesCtr.encrypt(ctext);
			notes = aesjs.utils.hex.fromBytes(encryptedBytes);
		}
		catch(err) {
			if (use_aes) {
				showMsg("There was an error AES-encrypting your secure notes. This can occur if you are using an unsupported device.","w3-red");
			} else {
				showMsg("AES encryption of the notes failed due to your low IOS version number (" + version + ")","w3-red");
			}
			return;
		}
	}

	var domain = document.getElementById("entry").value.trim();
	var user = document.getElementById("username").value.trim();
	$.ajax({
		type: 'POST',
		url: 'ajax/ajax-json-store-notes.php',
		cache: false,
		async: true,
		dataType: 'json',
		data: { hPass: JSON.stringify(hPub), user: JSON.stringify(user), domain: JSON.stringify(domain), notes: JSON.stringify(notes), check: JSON.stringify(checkexisting) },
		timeout: 1000,
		error: function(x, t, m) {
			alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
			document.getElementById("enable").checked = false;
			setoffline();
		},
		success: function(retval) {
			var venc = (retval & (1 << 0)); // aes
			var absent = (retval & (1 << 1));
			var paid = (retval & (1 << 2));
			var warn = (retval & (1 << 3)); // exists and has changed (warn)
			if (checkexisting) {
				if (!warn) {
					ajaxsavenotes(0);
				} else if (confirm("Secure notes have changed.  Update notes?")) {
					ajaxsavenotes(0);
				} else {
					alert("Secure notes were not updated. To show old notes, click in the 'Domain' field and choose your domain/username pair again.");
				}
			} else {
				alert("Secure notes encrypted and saved to server. (If you need to undo, click in the 'Secure Notes' textbox and use CTRL+z.)");
				$("#entry").autocomplete("flushCache");
			}
		}
	});
}

function ajaxsavecat() {
	if (!document.getElementById("enable").checked) {
		alert("Categories cannot be saved in offline mode.");
		return;
	}
	if (!checkpass(document.getElementById("catsavelink"))) {
		return;
	}
	var categ = document.getElementById("categ").value;

	var domain = document.getElementById("entry").value.trim();
	var user = document.getElementById("username").value.trim();
	$.ajax({
		type: 'POST',
		url: 'ajax/ajax-json-store-cat.php',
		cache: false,
		async: true,
		dataType: 'json',
		data: { hPass: JSON.stringify(hPub), user: JSON.stringify(user), domain: JSON.stringify(domain), cat: JSON.stringify(categ) },
		timeout: 1000,
		error: function(x, t, m) {
			alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
			document.getElementById("enable").checked = false;
			setoffline();
		},
		success: function(retval) {
			if (retval) alert("You need to Compute or Save your password before you can change the category.");
			else {
				alert("Category saved.");// lgs2 need to add check for retval 1 and 2
				$("#entry").autocomplete("flushCache");
			}
		}
	});
}

function initmatch(ismatch) {
	if (ismatch) {
		document.getElementById("matchon").checked=true;
//		$("#entry").val = ""; //lgs
		$("#entry").autocomplete("matchon");
	} else {
		document.getElementById("matchon").checked=false;
		$("#entry").autocomplete("matchoff");
	}
}
function initnodw(weak) {
	if (weak) {
		document.getElementById("permitnodw").checked=true;
	} else {
		document.getElementById("permitnodw").checked=false;
	}
}

function initprefix(on) {
	if (on) {
		$("#entry").autocomplete("prefixon");
	} else {
		$("#entry").autocomplete("prefixoff");
	}
	document.getElementById("entry").value="";
}

function setmatch() {
	if (document.getElementById("matchon").checked) {
		setCookie("matchon","1",1000);
		$("#entry").autocomplete("matchon");
		var ins = document.getElementById("entry").value.trim();
		$("#entry").val(ins);
		$("#entry").autocomplete("trigger"); // run the completion
	} else {
		eraseCookie("matchon");
		$("#entry").autocomplete("matchoff");
		openselect();
	}
	document.getElementById("msgbox").style.display='none';
	if (document.getElementById('fPassword').value.trim()) {
		document.getElementById('entry').focus();
	}
}

function help(id) {
	var msg;
	if (id == 'filter') {
		msg = "<p><strong>Search</strong> (incremental search): When checked, shows domain-username entries matching the letters you type. When unchecked, the dropdown list shows all domain-username password entries in your account. In either case, results are limited to entries matching <strong>filter</strong>. Note that there is a relevant setting (see Settings and Tools/Settings): 'Domain incremental search matches from beginning'</p><p><strong>Delete</strong> causes the selected entry to be removed from the server.</p><p><strong>Refresh</strong> (<i class='fa fa-refresh'></i>) updates the domain-username information from the server. This is useful when sharing an account with co-workers.";
	} else if (id == 'password') {
		msg = "<p>The 'master password' protects every password for all your sites. It is very important to choose it carefully. It should be a <i>random</i> password or passphrase, not something you invent to make it easy to remember.</p><p>Since memorizing a minimum 9 character random password is very difficult, we instead recommend Diceware passphrases. They are extremely secure, yet also easy to memorize. Read more about Diceware <a target='_blank' href='https://theintercept.com/2015/03/26/passphrases-can-memorize-attackers-cant-guess/'>here</a>. Diceware passphrases can be generated online <a target='_blank' href='https://www.rempe.us/diceware/#eff'>here</a>.</p><p>Strongman now <strong>requires</strong> a 6+ word master passphrase, unless overridden by a setting (see 'Settings and Tools/General Settings/Permit non-Diceware master passwords' at the bottom of the app).</p>";
	} else if (id == 'passtype') {
		msg = "<p>The 'Compute' button calculates a unique password based on the master password, domain, and username. It uses the characters and length selected in the options menu (click the '☰' button).</p><p>To change a computed password, increment the password number (options menu), or switch to a custom encrypted password (see below).</p><p>Computed passwords are <strong>not</strong> stored on the server; they are calculated by your browser.</p>If you want to use your own password or modify a computed password, click the edit button, or simply click into the Password field and make your changes. When done, click the 'Save' button, which will activate.</p><p>Custom passwords are encrypted using your master password, in your browser, <strong>before</strong> sending to the server. They are as secure as the calculated ones.</p>";
	} else if (id == 'accountact') {
		msg = "<p>Use 'Import passwords from another Strongman account' to <strong>change the master password,</strong> as follows:</p><ol><li>Enter the new master password and click 'Submit'.</li><li>Do the password import, specifying the master password of the old account.</li><li>After the passwords and notes are imported, you can delete the old Strongman account via the 'Remove Strongman Account' option.</li></ol>";
	}
	document.getElementById("sbmessage").innerHTML = msg;
	togAccordian('sidebar');
	$('html, body').animate({ scrollTop: 0 }, 'fast');
}
function is_ios() {
  var userAgent = navigator.userAgent || navigator.vendor || window.opera;
      // Windows Phone must come first because its UA also contains "Android"
//    if (/windows phone/i.test(userAgent)) {
//        return "Windows Phone";
//    }
//    if (/android/i.test(userAgent)) {
//        return "Android";
//    }
    // iOS detection from: http://stackoverflow.com/a/9039885/177710
    if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
	var v = (navigator.appVersion).match(/OS (\d+)_(\d+)_?(\d+)?/);
//		return [parseInt(v[1], 10), parseInt(v[2], 10), parseInt(v[3] || 0, 10)];
	return parseInt(v[1], 10);
    } else {
//    return /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
	return 0;
    }
}

function lock() {
	hPriv="";
	hPub="";
	$("#entry").autocomplete("flushCache");
	//lgs2
	autoenable(false);
	document.getElementById("lockstate").className="fa fa-lock w3-large";
	if (version && version <10) {
		showMsg("Cleared passwords, cannot clear clipboard automatically (Old IOS version).","w3-yellow");
	} else {
		copyWork(" ");
//			iosCopy(copyText);
		showMsg("Cleared master password, site password, and clipboard","w3-green");
	}
	document.getElementById("notes").value = "";
	var x = document.getElementById("notesdiv");
	x.className = x.className.replace(" w3-show", "");
	document.getElementById("fPassword").value="";
	document.getElementById("cPassword").value="";
	document.getElementById("entry").value="";
	document.getElementById("categ").value="0";
	document.getElementById("accountdata").innerHTML="(Compute or Save a password in order to view account information.)";
	document.getElementById("editcats").innerHTML="<em>You need to have entered a master password and generated at least one site password to edit category names.</em>";
	document.getElementById("savesettings").disabled = true;
//		document.getElementById("doaccnt").disabled = true;
	document.getElementById("fPassword").focus();
	gcatsw = gcats;
	resetcats();
	return null;
}

function myCopy(msg,id) {
// id is always cPassword except when copying domain (entry) or username
	var copyText = document.getElementById(id);
//		if ((id == "cPassword" || id == "username" || id == "entry") && !checkpass(copyText)) {
//			return;
//		}
	if (version && version < 10) {
		if (msg) showMsg("Old IOS version: manually copy password to clipboard.","w3-yellow");
	} else {
		if (id == "entry") copyText.value = copyText.value.trim();
		copyWork(copyText.value);
		copyText.select();
		if (msg) {
			var color;
			if (msg == "Password") color = "w3-grey";
			else color = (id != "cPassword" || msg == "Custom Password") ? "w3-green" : "w3-blue";
			showMsg(msg + " Copied to Clipboard",color);
		}
	}
}

function copyWork(msg) {
	var el = document.createElement('textarea');
	el.value = msg;
//	el.setAttribute('readonly', false);
	el.style.position = 'absolute';
	el.style.left = '-9999px';
	document.body.appendChild(el);
	if (version) {
		var range = document.createRange();
		range.selectNodeContents(el);
		var s = window.getSelection();
		s.removeAllRanges();
		s.addRange(range);
		el.setSelectionRange(0, 999); // A big number, to cover anything that could be inside the element.
	} else el.select();
	document.execCommand('copy');
	document.body.removeChild(el);
}

function autoClip(msg) {
	if (document.getElementById("manualcopy").checked) {
		if (msg) {
			var color = (msg == "Custom Password") ? "w3-green" : "w3-blue";
			showMsg(msg + " selected and ready to copy to clipboard",color);
		}
		document.getElementById("cPassword").select();
	} else {
		myCopy(msg,"cPassword");
	}
}

function showMsg(txt,color) {
	document.getElementById("mymessage").innerHTML=txt;
	var ob=document.getElementById("msgbox");
	ob.className="w3-panel w3-display-container " + color;
	ob.style.display="block";
}

function togAccordian(id) {
    var x = document.getElementById(id);
	if (x.className.indexOf("w3-show") == -1) {
		x.className += " w3-show";
	} else { 
		x.className = x.className.replace(" w3-show", "");
	}
}

function setpagerules(opts) {
// pass opts, a bitfield, if char set is not found on settings form.
	rules = [];
	setOfCharacters = "";
	if (opts) {
		var i=0;
		for (var mykey in _characterSubsets) {
			if (opts & (1 << i)) {
				rules.push(mykey);
				setOfCharacters+=_characterSubsets[mykey];
			}
			i++;
		}
	} else {
		['lcase','ucase','num','symb'].forEach(function(myrule) {
			if (document.getElementById(myrule).checked) {
				rules.push(myrule);
				setOfCharacters+=_characterSubsets[myrule];
			}
		});
	}
}

function computePass(seed,length) {
	var entropy = BigInteger.parse(genhash(seed,5000),16);
  var password = consumeEntropy(
    "",
    entropy,
    setOfCharacters,
    length - rules.length
  );
  var charactersToAdd = getOneCharPerRule(password.entropy, rules);

  var password2= insertStringPseudoRandomly(
    password.value,
    charactersToAdd.entropy,
    charactersToAdd.value
  );

    return password2;
}

function getOneCharPerRule(entropy, rules) {
  var oneCharPerRules = "";
  rules.forEach(function(rule) {
    var password = consumeEntropy("", entropy, _characterSubsets[rule], 1);
    oneCharPerRules += password.value;
    entropy = password.entropy;
  });
  return {value: oneCharPerRules, entropy: entropy};
}


function insertStringPseudoRandomly(generatedPassword, entropy, string) {
  for (var i = 0; i < string.length; i++) {
    var longDivision = entropy.divRem(generatedPassword.length);
    generatedPassword =
      generatedPassword.slice(0, longDivision[1]) +
      string[i] +
      generatedPassword.slice(longDivision[1]);
    entropy = longDivision[0];
  }
  return generatedPassword;
}
 function consumeEntropy(generatedPassword,quotient,setOfCharacters,maxLength) {
	if (generatedPassword.length >= maxLength) {
		return {value: generatedPassword, entropy: quotient};
	}
	var longDivision = quotient.divRem(setOfCharacters.length);
	generatedPassword += setOfCharacters[longDivision[1]];
	return consumeEntropy(generatedPassword,longDivision[0],setOfCharacters,maxLength);
}

function setoptslen(opts,length) {
	document.getElementById("lcase").checked = (opts & (1 << 0)); // lcase
	document.getElementById("ucase").checked = (opts & (1 << 1)); // ucase
	document.getElementById("num").checked = (opts & (1 << 2));
	document.getElementById("symb").checked = (opts & (1 << 3));
	document.getElementById("len").value = length;
}

function resetcats() {
	var cathtml = "";
	for (var i=0; i< gcatsw.length; i++) {
		cathtml += "<input type='text' maxlength='25' id='cc" + i + "' value='" + gcatsw[i] + "'> (default: " + gcats[i] + ")<br>";
	}
	document.getElementById('editcats').innerHTML = cathtml;
	var sel = document.getElementById('dfilter');
	$('#dfilter').empty();
	sel.style="width:75px; font-size:.8em;";
	var opt = document.createElement('option');
	opt.value = -1;
	opt.innerHTML = 'No Filter';
	sel.appendChild(opt);
	for (var i= 0; i<gcatsw.length; i++){
	    if (gcatsw[i] == 'Unused') continue;
	    opt = document.createElement('option');
	    opt.value = i;
	    opt.innerHTML = gcatsw[i];
	    sel.appendChild(opt);
	}
	sel.selectedIndex = "0";
	sel = document.getElementById('categ');
	$('#categ').empty();
	for (var i= 0; i<gcatsw.length; i++){
	    if (gcatsw[i] == 'Unused') continue;
	    opt = document.createElement('option');
	    opt.value = i;
	    opt.innerHTML = gcatsw[i];
	    sel.appendChild(opt);
	}
	sel.selectedIndex = "0";
}


function clearhash() {
	hPub = "";
	hPriv = "";
	autoenable(false);
}

function regexpass (msg) {
	var ob = document.getElementById("fPassword");
//	var re=/^.*(?=.{9,})(?=.*\d)((?=.*[a-z]){1})((?=.*[A-Z]){1}).*$/; // no forced special chars
	var re=/^.*(?=.{8,})((?=.*[!@#$%^&*()\-_=+{};:,<.>]){1})(?=.*\d)((?=.*[a-z]){1})((?=.*[A-Z]){1}).*$/;
	var dw=/^\s*[a-zA-Z]+(?:(-| +)[a-zA-Z]+){5,}\S*?\s*$/;
	var dwnosp=/^\s*[a-zA-Z]{40,}\S*?\s*$/;
	var accepted = 0;
	var fail = " provided does not pass our strength test.</h3>";
	if (!dw.test(ob.value) && !dwnosp.test(ob.value)) {
		if (document.getElementById("permitnodw").checked) {
			if (document.getElementById("enable").checked) msg += "<h4>Non-Diceware passwords permitted via General Settings (not recommended)</h4>";
			if (re.test(ob.value)) {
				accepted = 1;
				if (document.getElementById("enable").checked) msg += "<p>The provided password passes our non-Diceware password checker, but unless your password was chosen randomly (i.e., by a machine, dice roll, etc.), it will NOT be secure. If you created the password yourself by some scheme you think is clever, you should STOP and go <a target='_blank' href='https://theintercept.com/2015/03/26/passphrases-can-memorize-attackers-cant-guess/'>read about</a> easy-to-memorize Diceware passphrases</a>.</p><p>On the other hand, if you provided a truly random mixed-character password, you must have an amazing memory. Please proceed.</p>";
			} else 	msg += "<h3>The password" + fail +"<p>A non-Diceware master password should be at least 9 characters long, with at least one uppercase, one lowercase, and one special character from !@#$%^&*()\-_=+{};:,\<.\>.</p><p>Since random mixed case passwords are too hard to memorize, please reconsider using <a target='_blank' href='https://theintercept.com/2015/03/26/passphrases-can-memorize-attackers-cant-guess/'>Diceware</a>.</p>";
		} else if (document.getElementById("enable").checked) msg += "<h3>The Diceware passphrase" + fail + "<p>If you don't know what Diceware is, <a target='_blank' href='https://theintercept.com/2015/03/26/passphrases-can-memorize-attackers-cant-guess/'>read this</a>.</p><p>An acceptable Diceware passphrase consists of 6+ random words. If you run the words together, the total length should be at least 24.</p><p>By default Strongman <strong>requires</strong> a 6+ word passphrase, presumably Diceware. (Generate <a target='_blank' href='https://www.rempe.us/diceware/#eff'>here</a>). The requirement for a Diceware passphrase can be overridden in Strongman General Settings.</p>";
	} else {
		if (document.getElementById("enable").checked) msg += "<p>It looks like you chose a 6+ word passphrase. We hope this is a Diceware type passphrase (generate <a target='_blank' href='https://www.rempe.us/diceware/#eff'>here</a>) and not a sentence or word combination you thought up. If you invented this passphrase <strong>yourself</strong>, it is probably NOT secure.";
		accepted = 1;
	}
	return [accepted, msg];
}

function hashpass() {
	var ob = document.getElementById("fPassword");
	if (ob.value) {
//		hPub = genhash(ob.value + "T=|JkDp[)97oS-",1000);
		hPub = genhash(ob.value,1000);
		hPriv = genhash(ob.value,5000);
		if (document.getElementById("enable").checked) {
			$.ajax({
				type: 'POST',
				url: 'ajax/ajax-json-settings.php',
				cache: false,
				async: true,
				dataType: 'json',
				data: { hPass: JSON.stringify(hPub) },
				timeout: 1000,
				error: function(x, t, m) {
					alert("No Internet connection. Entering offline mode. Tic 'Online' to try again for an Internet connection.");
					document.getElementById("enable").checked = false;
					setoffline();
				},
				success: function(data) {
//					var venc = (retval & (1 << 0)); // aes
//					var aes = (retval & (1 << 1)); // aes
//					var paid = (retval & (1 << 2));
//					var warn = (retval & (1 << 3)); // exists and has changed (warn)
					$("#entry").autocomplete("flushCache");
					if (data[0]['settings'] == "empty") {
						var msg = "Warning: No stored password information found for this master password. If this is the first use of this master password, you can ignore this warning.  Otherwise, this master password is incorrect, and calculated passwords will NOT work.";
						var accepted;
						document.getElementById("accountdata").innerHTML="(Compute or save a password in order to view account information.)";
						autoenable(false);
						var aresult = regexpass(msg);
						$("#entry").autocomplete("close");
						$("#entry").autocomplete("disable");
						if (!aresult[0]) {
							clearhash();
							showMsg(aresult[1],"w3-yellow");
							document.getElementById('fPassword').focus();
							return;
						} else {
							showMsg(aresult[1],"w3-yellow");
							gsettings = 0;
							gcatsw = gcats;
						}
//						document.getElementById("pppaymt").style.display="none"; 
					} else {
						gsettings = data[0]['settings'];
						var dates = data[0]['dates'].split(',');
						if (data[0]['custcats'] !== undefined) {
							gcatsw = data[0]['custcats'].split(',');
						} else gcatsw = gcats;
						var ainfo="";
						var asum="";
						var startdate = moment(dates[0]*1000).format('L');
						document.getElementById("accountdata").innerHTML="Free account, started " + startdate;
						document.getElementById("msgbox").style.display='none';
						autoenable(true);
						$("#entry").autocomplete("enable");
						showMsg("Master password profile successfully loaded","w3-green");
					}
					document.getElementById("savesettings").disabled = false;
//					document.getElementById("doaccnt").disabled = false;
					document.getElementById("manualcopy").checked = (gsettings & (1 << 0));
					var prefixon = (gsettings & (1 << 1));
					document.getElementById("prefixon").checked = prefixon;
					initprefix(prefixon);
					document.getElementById("username").value="";
					document.getElementById("cPassword").value="";
					setnotes("");
					resetcats();
				}
			});
		} else {
			autoenable(false);
			var accepted;
			var msg = "Offline mode.";
			var aresult = regexpass(msg);
			$("#entry").autocomplete("flushCache");
			$("#entry").autocomplete("disable");
			if (!aresult[0]) {
				clearhash();
//				$("#entry").autocomplete("close");
//				$("#entry").autocomplete("disable");
				showMsg(aresult[1],"w3-yellow");
				document.getElementById('fPassword').focus();
				return;
			} else {
				showMsg(aresult[1],"w3-yellow");
				gsettings = 0;
				gcatsw = gcats;
				document.getElementById("accountdata").innerHTML="Offline mode.";
				document.getElementById("manualcopy").checked = (gsettings & (1 << 0));
				var prefixon = (gsettings & (1 << 1));
				document.getElementById("prefixon").checked = prefixon;
				initprefix(prefixon);
				document.getElementById("username").value="";
				document.getElementById("cPassword").value="";
				setnotes("");
				resetcats();
				document.getElementById("doaccnt").disabled = true;
				document.getElementById("savesettings").disabled = true;
			}
		}
	}
}

function genhash(myhash,num) {
// new SHA256 instance
	var SHA256 =  new Hashes.SHA256;
	for (var i= 0; i<num; i++) {
		myhash = SHA256.hex(myhash);
	}
	return myhash;
}

function verifypp() {
	if (hPriv=="") {
		alert("You must enter your master password before you can make payment.");
		return false;
	}
}
function validateuserdom(ob) {
	var re = /[?{}|&~!()^"\\=]+/;
	var msg = "The following characters are not permitted in usernames and domain: ?{}|&~!()^\"\\=";
	if (ob.id == "entry") {
		if (re.test(ob.value)) {
			alert(msg);
			ob.value = "";
			return false;
		} else ob.value=ob.value.toLowerCase();
	} else if (ob.id == "username") {
		if (re.test(ob.value)) {
			alert(msg);
			ob.value = "";
//			ob.focus();
			return false;
		}
	}
	return true;
}

function checkpass(ob) {
    var lacking = "";
    var focusid;
    list: {
	if (document.getElementById('fPassword').value.trim() == "") {
		lacking += "master password, ";
		focusid = 'fPassword';
	} else if (hPub == "") {
		lacking = "Please enter an acceptable master password. Don't forget to click 'Submit'. ";
		focusid = 'fPassword';
		alert(lacking);
		document.getElementById('fPassword').focus();
		return false;
	}
	if (ob.id == "entry") break list;

	if (document.getElementById('entry').value.trim() == "") {
		lacking += "domain, ";
		if (!focusid) focusid = 'entry';
	}
	if (ob.id == "username") break list;

	if (document.getElementById('username').value.trim() == "") {
		lacking += "username, ";
		if (!focusid) focusid = 'username';
	}
    }
	if (lacking) {
		lacking=lacking.substring(0,lacking.length-2);
		alert("Please first enter " + lacking + ".");
		document.getElementById(focusid).focus();
		return false;
	}
	else if (ob.id == 'cPassword') {
		ob.focus();
	}
	else if (ob.id == "entry") {
		openselect();
		return;
	}
	else if (ob.id == "savebut") {
		if (document.getElementById('cPassword').value.trim() == "") {
			alert("No password to save!");
			return false;
		}
	}
	return true;
}

function collectopts() {
	var optslist = [ 'lcase','ucase','num','symb' ];
	var opts = 0;
	for (i = 0; i < optslist.length; i++) {
		var ob = document.getElementById(optslist[i]);
		if (ob.checked) {
			opts += +ob.value;
		}
	}
	return opts;
}

function setCookie(name,value,days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
// path parameter ommitted, defaults to current page
    document.cookie = name + "=" + (value || "") + expires + ";";
}
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
function eraseCookie(name) {   
    document.cookie = name+'=; Max-Age=-99999999;';  
}
function openselect() {
	if (!document.getElementById("matchon").checked && document.getElementById("enable").checked) {
		var val = document.getElementById("entry").value;
		var ins = (val) ? val : " ";
		$("#entry").val(ins);
		$("#entry").autocomplete("trigger"); // run the completion
	}
}

function validatelen(ob) {
	if (ob.value < 5 || ob.value > 35) {
		alert("Generated password length must be between 5 and 35");
		if (ob.value < 5) ob.value = "5";
		else ob.value = "35";
		ob.focus();
	}
}
function validateincr(ob) {
	if (ob.value < 1 || ob.value > 99) {
		alert("Password number must be between 1 and 99");
		if (ob.value < 1) ob.value = "1";
		else ob.value = "99";
		ob.focus();
	}
}

function alertSpecial() {
//symb: "!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~"
    var msg = "At least one member of each selected character class will be present in the computed password.\n\n";
    msg += "Symbols are chosen from the following 32 special characters:\n";
    msg += $('<span/>').html('!&quot;#$%&amp;&apos;()*+,-./:;&lt;=&gt;?@[\\]^_`{|}~').text();
    msg += "\nTip: If a site rejects one or more of these symbols in a computed password, edit out the disallowed characters and save the password, which switches from computed to encrypted mode.";
    alert(msg);
}
</script>
</head>
<body style="max-width:520px;">
<!-- Sidebar -->
<div style="display:none" class="w3-container w3-card-4 w3-margin w3-leftbar w3-sand w3-hide" id="sidebar">
<button onclick="togAccordian('sidebar');" class="w3-round-xlarge w3-right w3-blue">Close</button>
<div id="sbmessage"></div>
</div>

<div id='passvaultMain' class="w3-container w3-card-4 w3-light-grey w3-margin">
<h4 title="Strongman Password Manager"><img src="image/favicon-32x32.png" alt="strongarm"> Strongman &trade; v<?=$smversion?>
 <a href="javascript:togAccordian('intro');"><i class="fa fa-question-circle-o" style="color:blue;"></i></a>
 <small><a href='/' target='_blank'>website</a></small>
<span style="float:right; font-size:50%;">
<input type="checkbox" id="enable" name="enable" <?=isset($_COOKIE["offline"]) ? "" : "checked";?>><label for="enable"> Online</label>
</h4>
<div id="intro" class="w3-container w3-panel w3-leftbar w3-sand w3-hide">
<h4>How to Use</h4>
<ol>
<li>Enter the master passphrase</li>
<li>Start typing the domain name in the 'Domain' field. If you have used this password before, you will see the domain-username in a dropdown list. Click to choose it. You're done; the password is automatically copied to the clipboard for you (unless you've disabled that in the settings menu.)</li>
<li>For a new entry, just enter the domain and username.</li>
<li>Change the password computation options for this username if necessary (use the '☰' button), then click the "Compute" button.</li>
<li>The password is calculated and (optionally) copied to clipboard. The next time you need this password, you can select it from the dropdown list.</li>
<li>If you want to modify or replace the computed password, just edit it and click the "Save" button. This "custom" password will be AES256-encrypted and stored on the server.</li>
</ol>
<a href="javascript:togAccordian('intro');" >Hide</a>
</div>
  <p>
  <label><strong>Master Password</strong></label> <i class="fa fa-question-circle-o w3-large" style="color:blue;" onclick="help('password');" title="Password Help"></i>
<i class="fa fa-lock w3-large" onclick="lock();" style="color:goldenrod;" title="Clear master password, site password, and clipboard" id="lockstate"></i>
<i class="myeye w3-large fa <?=isset($_COOKIE["focushidepwd"]) ? 'fa-eye-slash" title="password always hidden"' : 'fa-eye" title="show password on focus"';?> id="eyemaster"></i>
<span class="w3-row">
<span class="w3-col s11">
  <input class="w3-input w3-border w3-round icon-input" name="fPassword" id='fPassword' type='password' placeholder="Enter a strong passphrase" tabindex="1" onchange="clearhash();">
</span>
<span class="w3-col s1" style="margin: 8px 0 0 0;">
  <button class="w3-button w3-small w3-blue w3-round w3-ripple fa" style="padding:5px 5px;" id="submitmp" onclick="hashpass();" title="Submit Master Password">Submit</button>
</span>
</span>
  </p>
  <p>
  <label class="tooltip"><strong>Domain</strong><span class="tooltiptext">Choose from list or enter new domain</span></label> &nbsp;
<select style='font-size: 80%;' name="dfilter" id="dfilter" onclick="openselect();">
<option value="-1" selected>No filter</option>
</select>
<input class="setMatch" type="checkbox" id='matchon' title="If checked, incrementally search entries matching Filter. If unchecked, show all entries matching Filter." name="setMatch" <?=isset($_COOKIE["matchon"]) ? "checked" : "";?>>Search &nbsp;<input class="setMatch" type="checkbox" id='matchdel' name="matchdel" title="Turn delete mode on/off">Del
<i class="fa fa-refresh w3-large" style="color:blue;" onclick="$('#entry').autocomplete('flushCache'); alert('Domain/username cache has been cleared.');" title="Refresh domain/username password list from server"></i>
<i class='fa fa-copy w3-large' onclick="myCopy('Domain','entry');" title="Copy domain to clipboard"></i>
<i class="fa fa-question-circle-o w3-large" style="color:blue;" onclick="help('filter');"></i>
  <input class="w3-input w3-border w3-round icon-input" name="domainUser" id='entry' type='text' title="Green background means password profiles are available" placeholder="Enter a domain" onclick="checkpass(this);" onfocusout="validateuserdom(this);" tabindex="2" maxlength="100">
</p>
  <p>
  <label class="tooltip"><strong>Username</strong><span class="tooltiptext">Autofilled if restoring existing entry</span></label>
  <i class='fa fa-copy w3-large' onclick="myCopy('Username','username');" title="Copy username to clipboard"></i>
  <input class="w3-input w3-border w3-round icon-input" name="username" id='username' type='text' placeholder="Enter a username" onfocus="checkpass(this);" onblur="validateuserdom(this);" onchange="setnotes(''); document.getElementById('incr').value='1';" tabindex="3" maxlength="70">
  </p>
  <p>
  <label><strong>Password</strong></label><br>
<i class="myeye w3-large fa <?=isset($_COOKIE["focushidepwd"]) ? 'fa-eye-slash" title="password always hidden"' : 'fa-eye" title="show password on focus"';?> id="eyecomp"></i>
<i class='fa fa-edit w3-large' onclick="editpass();" title="Edit password"></i>
<i class='fa fa-copy w3-large' onclick="myCopy('Password','cPassword');" title="Copy password to clipboard"></i>
<i class='fa fa-navicon w3-large' onclick="togAccordian('optionsdiv');" title="Password Computation Options"></i>
  <i class='fa fa-book w3-large' onclick="togAccordian('notesdiv');" title="Show/hide secure notes"></i>
<select name="categ" id="categ">
</select>
 <a onclick="ajaxsavecat();" href="javascript:void(0);" id="catsavelink" title="Save category">save</a>
 <i class='fa fa-question-circle-o w3-large' style='color:blue;' onclick='help("passtype");'></i>
<div id='optionsdiv' class="w3-panel w3-leftbar w3-hide w3-display-container">
<p>
  <label><strong>Password Computation Options</strong> <i class="fa fa-close w3-display-topright" style="padding-top:5px; padding-right:5px;" onclick="togAccordian('optionsdiv');"></i></label><br>
Characters to Include:<br><input type="checkbox" id="lcase" name="lcase" value="1" checked>&nbsp;a-z &nbsp;<input type="checkbox" id="ucase" name="ucase" value="2" checked>&nbsp;A-Z &nbsp;
<input type="checkbox" id="num" name="num" value="4" checked>&nbsp;0-9 &nbsp;<input type="checkbox" id="symb" name="symb" value="8" checked> symb <i class='fa fa-question-circle-o w3-large' style='color:blue;' onclick="alertSpecial();" title="Show symbols"></i><br>
Length&nbsp;<input class="w3-border w3-round" type="number" id="len" name="len" value="14" maxlength="2" min="5" max="35" onblur="validatelen(this);" style="width: 3em;">
 Incr No.&nbsp;<input class="w3-border w3-round" type="number" id="incr" name="incr" value="1" maxlength="2" min="1" max="99" onblur="validateincr(this);" title="Increment to calculate a new password" style="width: 3em;">
</p>
</div>
<span class="w3-row">
<span class="w3-col s7">
<input class="w3-input w3-border w3-round icon-input" name="cPassword" id='cPassword' type='password' placeholder="computed or custom password" onkeyup="document.getElementById('savebut').disabled=false;" tabindex="4" maxlength="200">
</span>
<span class="w3-col s4" style="margin: 8px 0 0 3px;">
  <button class="w3-button w3-small w3-blue w3-round w3-ripple w3-padding fa" id="compute" onclick="compute(this);" title="Compute unique password based on master password, domain, username and options">Compute</button>
  <button class="w3-button w3-small w3-green w3-round w3-ripple w3-padding fa" id="savebut" onclick="savepass(this);" title="Save custom password using AES256 encryption" disabled>Save</button>
</span>
</span>
  </p>
<div id='notesdiv' class="w3-panel w3-leftbar w3-hide w3-pale-green w3-border-green w3-display-container">
<label><strong>Secure Notes</strong> &nbsp;<a onclick="ajaxsavenotes(1);" href="javascript:void(0);" id="notessavelink">save</a><i class="fa fa-close w3-display-topright" onclick="togAccordian('notesdiv');"></i></label><br>
<textarea rows="5" maxlength="640" name='notes' id='notes' placeholder='Content here will be AES-encrypted and stored on the server. Max length 640.' style="width:100%;"></textarea>
</div>

<div class="w3-panel w3-display-container" id='msgbox' style="display:none;">
  <i onclick="this.parentElement.style.display='none';"
  class="fa fa-close w3-display-topright" style="padding-top:5px; padding-right:5px;"></i>
<p id="mymessage"></p>
</div><br>

<a href="javascript:togAccordian('settingsdiv');">Settings and Tools</a>
<span style="float:right; line-height:90%;"><small>arm icon by <a target='_blank' href="https://www.freepik.com" target="_blank">Freepik</a><br>from <a target='_blank' href="https://www.flaticon.com/" target="_blank">www.flaticon.com</a><br>
<a target='_blank' href='https://theintercept.com/2015/03/26/passphrases-can-memorize-attackers-cant-guess/'>about Diceware</a> <a target='_blank' href='https://www.rempe.us/diceware/#eff'>generate</a></small></span>
<div id="settingsdiv" class="w3-display w3-panel w3-leftbar w3-sand w3-hide w3-display-container">
  <i onclick="javascript:togAccordian('settingsdiv');" class="fa fa-close w3-display-topright" style="padding-top:5px; padding-right:5px;"></i>
<p>
<label><strong>General Settings</label></strong><br>
<input type="checkbox" id="autoclear" name="autoclear" value="0"> Auto clear master password after <input type="number" id="autosecs" name="autosecs" value="600" size="4" maxlength="4" min="60" max="9999" disabled> secs inactivity.<br>
<input type="checkbox" id="permitnodw" name="permitnodw" value="1"> Permit non-Diceware master passwords.
</p>
<button id="savegeneral" class="w3-button w3-blue w3-small w3-round">Save General Settings</button>
<p>
<label><strong>Account Settings</label></strong><br>
<input type="checkbox" id="manualcopy" name="manualcopy" value="1"> Do not automatically copy passwords to clipboard<br>
<input type="checkbox" id="prefixon" name="prefixon" value="1" checked> Domain incremental search matches from beginning<br>
</p>
<p>
<label>Customize Category Names</label><br>
<span id='editcats'> <em>You need to have entered a master password and generated at least one site password to edit category names.</em>
</span>
</p>
<button id="savesettings" class="w3-button w3-blue w3-small w3-round" title="Account settings can only be saved after master password is entered." disabled>Save Account Settings</button>
<p>
<label><strong>Account Actions</label></strong>
 <i class='fa fa-question-circle-o w3-large' style='color:blue;' onclick='help("accountact");'></i>
<br>
<input type="radio" name="accntact" value="export" id="radio_e"> Export all password data to Keepass compatible CSV file<br>
<input type="radio" name="accntact" value="import" id="radio_i"> Import passwords from another Strongman account<br>
<input type="radio" name="accntact" value="local" id="radio_l"> Import passwords from CSV file chosen below
 <input class="w3-small" type="file" id="fileInput"> <a href="javascript:togAccordian('csvcust');">custom CSV headers</a>
<div id='csvcust' style="display:none;">
<input type='text' maxlength='25' id='csv0' value='Web Site'> (default: Web Site)<br>
<input type='text' maxlength='25' id='csv1' value='Login Name'> (default: Login Name)<br>
<input type='text' maxlength='25' id='csv2' value='Password'> (default: Password)<br>
<input type='text' maxlength='25' id='csv3' value='Comments'> (default: Comments)<br>
</div>
<input type="radio" name="accntact" value="delete" id="radio_d"> Remove Strongman account
</p>
<button id="doaccnt" class="w3-button w3-blue w3-small w3-round" title="These actions require that a master password be entered.">Submit</button>
</div><br>
<a href="javascript:togAccordian('myaccount');">My Account</a>
<div id="myaccount" class="w3-display w3-panel w3-leftbar w3-sand w3-hide w3-display-container">
  <i onclick="javascript:togAccordian('myaccount');" class="fa fa-close w3-display-topright" style="padding-top:5px; padding-right:5px;"></i>
<label><strong>Account Information</strong><br>
<div id="accountdata">(Compute or save a password in order to view account information.)</div>
</div>
</div>
<script>

fPassword.onblur = function() {
	fPassword.type = "password";
};

fPassword.onfocus = function() {
	if ($("#eyemaster").hasClass("fa-eye")) {
		fPassword.type = "text";
	}
};

cPassword.onblur = function() {
	cPassword.type = "password";
};

cPassword.onfocus = function() {
	if ($("#eyecomp").hasClass("fa-eye")) {
		cPassword.type = "text";
	}
//	return checkpass(document.getElementById("cPassword"));
};
</script>
</body>
</html>
