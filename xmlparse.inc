<?php
/* $Id: xmlparse.inc,v 1.44 2006/01/15 10:09:40 sullrich Exp $ */
/*
	xmlparse.inc
	functions to parse/dump configuration files in XML format
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
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

require_once("xml_serializer.inc");
require_once("xml_unserializer.inc");

/* The following items will be treated as arrays in config.xml */
function listtags($type = 'config') {
	switch($type) {
	case "config":
		return array(
				"alias",
				"aliasurl",
				"allowedip",
				"cacert",
				"config",
				"columnitem",
				"dnsserver",
				"domainoverrides",
				"earlyshellcmd",
				"encryption-algorithm-option",
				"field",
				"fieldname",
				"hash-algorithm-option",
				"hosts",
				"interface_array",
				"item",
				"key",
				"lbpool",
				"menu",
				"mobilekey",
				"onetoone",
				"option",
				"package",
				"passthrumac",
				"proxyarpnet",
				"queue",
				"pipe",
				"route",
				"row",
				"rule",
				"service",
				"servernat",
				"servers",
				"earlyshellcmd",
				"shellcmd",
				"staticmap",
				"subqueue",
				"tunnel",
				"user",
				"vip",
				"virtual_server",
				"vlan",
				"winsserver",
				"wolentry"
		);
	case "package":
		return array(
				"onetoone",
				"depends_on_package",
				"queue",
				"rule",
				"servernat",
				"alias",
				"additional_files_needed",
				"tab",
				"template",
				"menu",
				"rowhelperfield",
				"service",
				"step",
				"package",
				"columnitem",
				"option",
				"item",
				"field",
				"package",
				"file"
		);
	}
}

function parse_xml_config($config, $rootobj, $listtags = array(), $isfile = true) {
	return parse_xml_config_raw($config, $rootobj, 'config', $listtags, $isfile);
}

function parse_xml_config_pkg($config, $rootobj, $listtags = array(), $isfile = true) {
	return parse_xml_config_raw($config, $rootobj, 'package', $listtags, $isfile);
}

function parse_xml_config_raw($config, $rootobj, $type, $listtags = array(), $isfile = true) {
	$options = array(
				'parseAttributes' => true,
				'attributesArray' => '_attrs',
				'forceEnum' => array_merge(listtags($type), $listtags)
		);
	$us = new XML_Unserializer($options);
	$us->unserialize($config, $isfile);
	/* we should probably raise a PEAR error object here */
	if($us->getRootName() != $rootobj) die("XML error: No {$rootobj} object found!\n");
	return $us->getUnserializedData();
}

function dump_xml_config($arr, $rootobj) {
	return dump_xml_config_raw($arr, $rootobj, 'config');
}

function dump_xml_config_pkg($arr, $rootobj) {
	return dump_xml_config_raw($arr, $rootobj, 'package');
}

function dump_xml_config_raw($arr, $rootobj, $type) {
	$options = array(
				'mode' => 'simplexml',
				'attributesArray' => '_attrs',
				'addDecl' => true,
				'indent' => "\t",
				'rootName' => 'pfsense',
				// enable parser hacks for compatibility
				'ignoreFalse' => true,
				'condenseBools' => true
		);
	$s = new XML_Serializer($options);
	$s->serialize($arr);
	return $s->getSerializedData();
}
?>
