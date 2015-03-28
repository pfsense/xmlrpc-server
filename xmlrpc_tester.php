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
        $versioncheck_base_url = "packages.pfsense.org";
        $versioncheck_path = "/xmlrpc.php";
        if(isset($config['system']['alt_firmware_url']['enabled']) and isset($config['system']['alt_firmware_url']['versioncheck_base_url'])) {
                $versioncheck_base_url = $config['system']['alt_firmware_url']['versioncheck_base_url'];
        }
	$params = array(
			"platform" => "pfSense",
			"firmware" => array("version" => "0.62.5", "branch" => "stable"),
			"kernel" => array("version" => "5.4"),
			"base" => array("version" => "5.4")
			);
	print_r($params);
        $msg = new XML_RPC_Message('pfsense.get_firmware_version', array(php_value_to_xmlrpc($params)));
	print "Formed message.\n";
        $cli = new XML_RPC_Client($versioncheck_path, $versioncheck_base_url);
	print "Formed client.\n";
	$cli->setDebug(1);
        $resp = $cli->send($msg);
	print "Message sent.\n";
	$raw_versions = $resp->value();
	return xmlrpc_value_to_php($raw_versions);
}

$versions = get_firmware_version();
print_r($versions);
?>


