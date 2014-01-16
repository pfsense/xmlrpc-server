<?php
/*
	$Id: xmlrpc.php,v 1.3 2008/07/06 01:30:33 sullrich Exp $

        xmlrpc_pfsense_com.php
        Copyright (C) 2005 Colin Smith
        All rights reserved.

        Redistribution and use in source and binary forms, with or without
        modification, are permitted provided that the following conditions are met:

        1. Redistributions of source code must retain the above copyright notice,
           this list of conditions and the following disclaimer.

        2. Redistributions in binary form must reproduce the above copyright
           notice, this list of conditions and the following disclaimer in the
           documentation and/or other materials provided with the distribution.

        THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
        INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
        AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
        AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
        OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
        SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
        INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
        CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
        ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
        POSSIBILITY OF SUCH DAMAGE.
*/

require_once("xmlrpc_server.inc");
require_once("xmlparse.inc");
require_once("xmlrpc.inc");
include_once("array_intersect_key.php");

$XML_RPC_erruser = 200;

//$get_firmware_version_sig = array(array($XML_RPC_Array, $XML_RPC_Array));
$get_firmware_version_doc = 'Method used to get the current firmware, kernel, and base system versions. This must be called with an array. This method returns an array.';

function get_firmware_version($raw_params) {
	global $branch;

	// Variables.
	$path_to_files = './xmlrpc/';
	$toreturn = array();
	$working = array();
	$toparse = array();
	$params = array_shift(xmlrpc_params_to_php($raw_params));
	
	// Branches to track
	$branches = array(
				'stable' => array('stable'),
				'beta' => array('stable', 'beta'),
				'alpha' => array('stable', 'beta', 'alpha')
			);

	$platforms = array(
				'pfSense',
				'embedded'
			);

	// Version manifest filenames.
	if(!$platforms[$params['platform']]) $params['platform'] = "pfSense";
	$versions = array(
				'firmware'	=> 'version',
				'base'		=> 'version_base',
				'kernel'	=> 'version_' . $params['platform']
			);

	// Categories we know about.
	$categories = array(
				'firmware',
				'base',
				'kernel'
			);

	// Load the version manifests into the versions array and initialize our returned struct.
	foreach($params as $key => $value) {
		if(isset($versions[$key])) { // Filter out other params like "platform"
			$versions[$key] = parse_xml_config($path_to_files . $versions[$key], "pfsenseupdates", $categories);
			$versions[$key] = $versions[$key][$key];
			if(is_array($versions[$key])) { // If we successfully parsed the XML, start processing versions
				for($i = 0; $i < count($versions[$key]); $i++) {
					if(version_compare($params[$key]['version'], $versions[$key][$i]['version'], "=")) {
						$toreturn[$key] = array_slice($versions[$key], $i + 1);
					}
				}
				if(count($toreturn[$key]) < 1) {
                                        $toreturn[$key] = $versions[$key];
                                }
				// Now that we have the versions we need, find the newest full update.
				$latestfull = 0;
				foreach($toreturn[$key] as $index => $version) {
					if(array_key_exists('full', $version)) {
						$latestfull = $index;
					}
				}
				$toreturn[$key] = array_slice($toreturn[$key], $latestfull);
				// For the client's convenience toss the latest version of this type into an array.
				$toreturn['latest'][$key] = $toreturn[$key][count($toreturn[$key]) - 1]['version'];
				// Now that we have our base array, process branches.
				$branch = $params['branch'] ? $branches[$params['branch']] : $branches['stable'];
				$toreturn[$key] = array_filter($toreturn[$key], "filter_versions");
				if(count($toreturn[$key]) < 1) {
					$toreturn[$key] = "latest_in_branch";
				}
			} else {
				$toreturn[$key] = "xml_error";
			}
		}
	}

	$response = XML_RPC_encode($toreturn);
	return new XML_RPC_Response($response);
}

function filter_versions($value) {
	global $branch;
	return in_array($value['branch'], $branch);
}

function get_pkgs($raw_params) {
	$path_to_files = '../packages/';
	$pkg_rootobj = 'pfsensepkgs';
	$apkgs = array();
	$toreturn = array();
	$params = array_shift(xmlrpc_params_to_php($raw_params));
	if($params['freebsd_version']) 
		$freebsd_version = "." . $params['freebsd_version'];
	else 
		$freebsd_version = "";
	if(!empty($params['freebsd_machine']))
		$freebsd_machine = $params['freebsd_machine'];
	else
		$freebsd_machine = "i386";

	$xml_config_file = $path_to_files . 'pkg_config' . $freebsd_version . '.xml';
	if (file_exists($xml_config_file . '.' . $freebsd_machine))
		$xml_config_file .= '.' . $freebsd_machine;

	$pkg_config = parse_xml_config_pkg($xml_config_file, $pkg_rootobj);
	foreach($pkg_config['packages']['package'] as $pkg) {
		if(($params['pkg'] != 'all') and (!in_array($pkg['name'], $params['pkg'])))
			continue;

		if (!empty($pkg['only_for_archs'])) {
			$allowed_archs = explode(' ', preg_replace('/\s+/', ' ', trim($pkg['only_for_archs'])));
			if (!in_array($freebsd_machine, $allowed_archs))
				continue;
		}

		if (isset($pkg['depends_on_package_pbi']) &&
		    preg_match('/##ARCH##/', $pkg['depends_on_package_pbi']))
			$pkg['depends_on_package_pbi'] = preg_replace('/##ARCH##/',
				$freebsd_machine, $pkg['depends_on_package_pbi']);

		if($params['info'] == 'all') {
			$apkgs[$pkg['name']] = $pkg;
		} else {
			$apkgs[$pkg['name']] = array_intersect_key($pkg, array_flip($params['info']));
		}
	}
	$response = XML_RPC_encode($apkgs);
	return new XML_RPC_Response($response);
}

function report_bug($raw_params) {
	/* give us access to XML listtags */
	global $listtags;
	$listtags = array(
				'bug'
		);
	/* where do we stuff the bug reports? */
	$path_to_bugfile = '../bugreports/reports.xml';
	/* get our params */
	$params = array_shift(xmlrpc_params_to_php($raw_params));
	$bugfile = parse_xml_config_raw($path_to_bugfile, 'bugfile');
	if($params['desc']) {
		if($params['name'])
			$toput['name'] = $params['name'];
		if($params['email'])
			$toput['email'] = $params['email'];
		$toput['desc'] = $params['desc'];
		$toput['config'] = base64_encode($params['config']);
		$toput['rules'] = base64_encode($params['rules']);
		$toput['time'] = time();
		$bugfile['bugs']['bug'][] = $toput;
		$xmlout = dump_xml_config_raw($bugfile, 'bugfile');
		$fout = fopen($path_to_bugfile, "w");
		fwrite($fout, $xmlout);
		fclose($fout);
		return new XML_RPC_Response(XML_RPC_encode(true));
	} else {
		return new XML_RPC_Response(XML_RPC_encode(false));
	}
	return new XML_RPC_Response(XML_RPC_encode(false));
}

$methodMap = array(
			'pfsense.get_firmware_version' =>   array('function' => 'get_firmware_version'),
			'pfsense.get_pkgs'             =>   array('function' => 'get_pkgs'),
			'pfsense.report_bug'           =>   array('function' => 'report_bug')
		);

$server = new XML_RPC_Server($methodMap, 0);

$server->service();

?>

