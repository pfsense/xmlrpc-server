<?php

/*
 * $Id: xmlrpc_tester.php,v 1.2 2005/03/29 03:33:41 colin Exp $
 * pfSense XMLRPC test program.
 * Colin Smith
 * *insert pfSense license etc here*
 */

require_once("xmlrpc.inc");

function get_firmware_version($return_php = true) {
        global $g;
        $versioncheck_base_url = "www.pfsense.com";
        $versioncheck_path = "/pfSense/xmlrpc.php";
	$params = array(
			"pkg" => 'all',
			"info" => array('version', 'name')
			);
        $msg = new XML_RPC_Message('pfsense.get_pkgs', array(php_value_to_xmlrpc($params)));
        $cli = new XML_RPC_Client($versioncheck_path, $versioncheck_base_url);
        $resp = $cli->send($msg);
	$raw_versions = $resp->value();
	return xmlrpc_value_to_php($raw_versions);
}

$versions = get_firmware_version();
print_r($versions);
?>


