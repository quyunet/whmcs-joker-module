<?php
/*
https://joker.com/faq/category/39/22-dmapi.html
OTE Platform: http://www.ote.joker.com
OTE Contral Panel: http://rpanel.ote.joker.com
API Documents: https://dmapi.ote.joker.com
*/
 /* **********************************************************************
 * Customization Development Services by QuYu.Net                        *
 * Copyright (c) Yunfu QuYu Tech Co.,Ltd, All Rights Reserved         	 *
 * (2013-09-23, 12:16:25)                                                *
 *                                                                       *
 *                                                                       *
 *  CREATED BY QUYU,INC.           ->       http://www.quyu.net          *
 *  CONTACT                        ->       support@quyu.net             *
 *                                                                       *
 *                                                                       *
 *                                                                       *
 *                                                                       *
 * This software is furnished under a license and may be used and copied *
 * only  in  accordance  with  the  terms  of such  license and with the *
 * inclusion of the above copyright notice.  This software  or any other *
 * copies thereof may not be provided or otherwise made available to any *
 * other person.  No title to and  ownership of the  software is  hereby *
 * transferred.                                                          *
 *                                                                       *
 *                                                                       *
 * ******************************************************************** */


require_once( dirname(__FILE__)."/cls_log.php" );     //Authorized Key file
require_once( dirname(__FILE__)."/cls_connect.php" );

function joker_connect($params) {
	
}

function joker_login($params) {
	require( dirname(__FILE__)."/config.php" );
	//print_r($jpc_config);exit;
	//if ($params["ote"]) $dmapi_url = $params["ote_url"];
	//else $dmapi_url = $params["real_url"];
	if ($params["ote"]) $dmapi_url = 'https://dmapi.ote.joker.com';
	else $dmapi_url = 'https://dmapi.joker.com';
	
	$fields = array(
		"username"  => $params["user"],
		"password"  => $params["pass"],
	);
	
	$connect = new JokerConnect($dmapi_url, $jpc_config);
	$connect->execute_request("login", $fields, $resp, $jpc_config["no_content"]);
	
	//global $sid, $uid;
	$connect->sid = $resp["response_header"]["auth-sid"];
	$connect->uid = $resp["response_header"]["uid"];
	//echo $sid.' '.$uid;
	//print_r($resp);
	//exit('login');
	return $connect;
}

function joker_getConfigArray() {
	$configarray = array(
	"FriendlyName" => array(
	"Type" =>      "System",
	"Value" =>     "Joker.com v 1.0"
	),

	"Description" => array(
	"Type" => "System",
	"Value" => "Created by <a href=\"http://www.quyu.net\" target=\"_blank\">QuYu.net</a>. For more information visit our <a href=\"https://github.com/quyunet\" target=\"_blank\">Open source</a>"
	),
	 "ote" => array( "Type" => "yesno", "Description" => "Whether the test environment", ),
	 'user' => array( "Type" => "text", "Size" => "30", "Description" => "Username", ),
	 'pass' => array( "Type" => "password", "Size" => "30", "Description" => "Password", ),
	);
	return $configarray;
}

function joker_GetRegistrarLock($params) {
	$domain = joker_GetDomainFromParams($params);
	
	$connect = joker_login($params);
	
	$info = $connect->info_domain($domain);
	if ($info['error']){
		$values["error"] = $info['error'];
		return $values;
	}else{
		if ($info['info']['domain.status'] == 'lock') return "locked";
		else return "unlocked";
	}
}

function joker_SaveRegistrarLock($params) {
	$domain = joker_GetDomainFromParams($params);
	$values["error"] = "";
	
	$action = ($params["lockenabled"] == "locked")? "domain-lock" : "domain-unlock";
	
	$fields = array(
		'domain' => $domain,
	);
	
	$connect = joker_login($params);
	//echo $action; print_r($fields);exit;
	if (!$connect->execute_request($action, $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}
	
	return $values;
}




function joker_GetEPPCode($params) {
	$domain = joker_GetDomainFromParams($params);
	$values["error"] = "";
	
	$fields = array(
		'domain' => $domain,
	);
	
	$connect = joker_login($params);
	//echo $action; print_r($fields);exit;
	if (!$connect->execute_request('domain-transfer-get-auth-id', $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}else{
		preg_match('#The Authorization ID is: "([^"]+)"#', $retrieve['result'], $matches);
		if ($matches[1]){
			$values["eppcode"] = htmlspecialchars($matches[1]);
		}else{
			$values["error"] = 'get eppe code failed.';
		}
	}
	/*
	$retrieve = $connect->result_retrieve($proc_id);
	preg_match('#The Authorization ID is: "([^"]+)"#', $retrieve, $matches);
	if ($matches[1]){
		$values["eppcode"] = htmlspecialchars($matches[1]);
	}else{
		echo $retrieve;
		//$values["error"] = 'get epp code timeout.';
	}
	*/
	return $values;
}


function joker_GetNameservers($params) {
	$domain = joker_GetDomainFromParams($params);
	
	$connect = joker_login($params);
	
	$info = $connect->info_domain($domain);
	if ($info['error']){
		$values["error"] = $info['error'];
		return $values;
	}else{
		$ns_val = $info['info']['domain.nservers.nserver.handle'];
		if ($ns_val){
			if (is_array($ns_val)){
				$ns_index = 1;
				foreach($ns_val as $val){
					$values["ns".$ns_index] = htmlspecialchars($val);
					$ns_index++;
				}
			}else{
				$values["ns1"] = htmlspecialchars($ns_val);
			}
		}
	}
	return $values;
}


function joker_SaveNameservers($params) {
	$domain = joker_GetDomainFromParams($params);
	
	$connect = joker_login($params);
	
	$values["error"] = "";
	
	$ns_str = '';
	if ($params["ns1"]) $ns_str = $params["ns1"];
	if ($params["ns2"]) $ns_str .= ':'.$params["ns2"];
	if ($params["ns3"]) $ns_str .= ':'.$params["ns3"];
	if ($params["ns4"]) $ns_str .= ':'.$params["ns4"];
	if ($params["ns5"]) $ns_str .= ':'.$params["ns5"];
	
	$fields = $contact_id;
	$fields['domain'] = $domain;
	$fields['ns-list'] = $ns_str;
	
	if (!$connect->execute_request("domain-modify", $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}
	
	return $values;
}




function joker_GetDNS($params) {
	$domain = joker_GetDomainFromParams($params);
	
	$connect = joker_login($params);
	
	$fields = array('domain' => $domain);
	
	$hostrecords = array ();
	
	if (!$connect->execute_request("dns-zone-get", $fields, $resp, $connect->sid)){
		//print_r($resp);
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	$lines = explode("\n", $resp['response_body']);
	//print_r($lines);
	foreach($lines as $line){
		if (substr($line, 0, 7) == '$dyndns') continue;
		//<label> <type> <pri> <target> <ttl> <valid-from> <valid-to> <parameters(s)>
		$part = explode(' ', $line);
		$part[0] = str_replace('@', '', $part[0]);
		$hostrecords[] = array(
			"hostname" => $part[0],
			"type" => $part[1],
			"address" => $part[3],
			"priority" => $part[2],
		);
	}
	//print_r($hostrecords);exit;
	return $hostrecords;
}


function joker_SaveDNS($params) {
	$domain = joker_GetDomainFromParams($params);
	
	$values["error"] = '';
	
	$type_check = array('A', 'AAAA', 'MX', 'CNAME', 'URL', 'MAILFW', 'TXT', 'NAPTR', 'DYNA', 'DYNAAAA', 'SRV');
	
	$fields['domain'] = $domain;
	$zone_arr = array();
	
	foreach ($params['dnsrecords'] as $d){
		//print_r($d);
		if (!in_array($d['type'], $type_check)){
			$values["error"] = 'not support type:'.$d['type'];
			return $values;
		}
		if ( !is_null( $d['address'] ) && $d['address'] != "" ){
			//<label> <type> <pri> <target> <ttl> <valid-from> <valid-to> <parameters(s)>
			$d['hostname'] = $d['hostname']?$d['hostname']:'@';
			$zone_line = $d['hostname'].' '.$d['type'].' '.intval($d['priority']).' '.$d['address'].' 14400';
			//echo $zone_line;
			$zone_arr[] = $zone_line;
		}
	}
	
	$fields['zone'] = implode("\n", $zone_arr);
	//$fields['zone'] = 'www A 0 127.0.0.1 86400';
	//print_r($fields);exit;
	
	$connect = joker_login($params);
	if (!$connect->execute_request("dns-zone-put", $fields, $resp, $connect->sid)){
		//print_r($resp);
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	/*
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}
	*/
	return $values;
}



function joker_GetEmailForwarding($params) {
	$dnszone = $params["sld"].".".$params["tld"].".";
	$values["error"] = "";
	$command = array(
		"COMMAND" => "QueryDNSZoneRRList",
		"DNSZONE" => $dnszone,
		"SHORT" => 1,
		"EXTENDED" => 1
	);
	$response = joker_call($command, joker_config($params));

	$result = array();

	if ( $response["CODE"] == 200 ) {
		foreach ( $response["PROPERTY"]["RR"] as $rr ) {
			$fields = explode(" ", $rr);
			$domain = array_shift($fields);
			$ttl = array_shift($fields);
			$class = array_shift($fields);
			$rrtype = array_shift($fields);

			if ( ($rrtype == "X-SMTP") && ($fields[1] == "MAILFORWARD") ) {
				if ( preg_match('/^(.*)\@$/', $fields[0], $m) ) {
					$address = $m[1];
					if ( !strlen($address) ) {
						$address = "*";
					}
				}
				$result[] = array("prefix" => $address, "forwardto" => $fields[2]);
			}
		}
	}
	else {
		$values["error"] = $response["DESCRIPTION"];
	}

	return $result;
}

function joker_SaveEmailForwarding($params) {
	
	//Bug fix - Issue WHMCS
	//###########
	if( is_array($params["prefix"][0]) )
		$params["prefix"][0] = $params["prefix"][0][0];
	if( is_array($params["forwardto"][0]) )
		$params["forwardto"][0] = $params["forwardto"][0][0];
	//###########
	
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	foreach ($params["prefix"] as $key=>$value) {
		$forwardarray[$key]["prefix"] =  $params["prefix"][$key];
		$forwardarray[$key]["forwardto"] =  $params["forwardto"][$key];
	}
	# Put your code to save email forwarders here

	$dnszone = $params["sld"].".".$params["tld"].".";
	$values["error"] = "";
	$command = array(
		"COMMAND" => "UpdateDNSZone",
		"DNSZONE" => $dnszone,
		"INCSERIAL" => 1,
		"EXTENDED" => 1,
		"DELRR" => array("@ X-SMTP"),
		"ADDRR" => array(),
	);

	foreach ($params["prefix"] as $key=>$value) {
		$prefix = $params["prefix"][$key];
		$target = $params["forwardto"][$key];
		if ( strlen($prefix) && strlen($target) ) {
			$redirect = "MAILFORWARD";
			if ( $prefix == "*" ) {
				$prefix = "";
			}
			$redirect = $prefix."@ ".$redirect;
			$command["ADDRR"][] = "@ X-SMTP $redirect $target";
		}
	}

	$response = joker_call($command, joker_config($params));

	if ( $response["CODE"] != 200 ) {
		$values["error"] = $response["DESCRIPTION"];
	}
	return $values;
}

function joker_GetContactDetails($params) {
	$domain = joker_GetDomainFromParams($params);
	
	$connect = joker_login($params);
	
	$info = $connect->info_domain($domain);
	if ($info['error']){
		$values["error"] = $info['error'];
		return $values;
	}else{
		//print_r($info['info']);
	}
	$contact_fields = array(
		'name',
		'organization',
		'email',
		'email',
		'address-1',
		'city',
		'state',
		'postal-code',
		'country',
		'phone',
		'fax',
	);
	foreach($contact_fields as $key){
		$Registrant[$key] = $info['info']['domain.'.$key];
	}
	$values['Registrant'] = $Registrant;
	
	$contact_type = array('Admin', 'Technical', 'Billing');
	$contact_handle = array($info['info']['domain.admin-c'], $info['info']['domain.tech-c'], $info['info']['domain.billing-c']);
	
	for ($i=0; $i < count($contact_type); $i++){
		$type = $contact_type[$i];
		$handle = $contact_handle[$i];
		$contact_info = $connect->query_whois('contact', $handle);
		if ($contact_info['error']){
			$values["error"] = $contact_info['error'];
			return $values;
		}else{
			foreach($contact_fields as $key){
				$values[$type][$key] = $contact_info['info']['contact.'.$key];
			}
		}
	}
	
	return $values;
}

function joker_SaveContactDetails($params) {
	//print_r($params);//exit;
	$values ["error"] = '';
	$domain = joker_GetDomainFromParams($params);
	
	$connect = joker_login($params);
	
	$info = $connect->info_domain($domain);
	if ($info['error']){
		$values["error"] = $info['error'];
		return $values;
	}else{
		//print_r($info['info']);
	}
	
	$contact_type = array('Admin', 'Technical', 'Billing');
	$contact_handle = array($info['info']['domain.admin-c'], $info['info']['domain.tech-c'], $info['info']['domain.billing-c']);
	
	$fields = $params['contactdetails']['Registrant'];
	if (!$fields['fax']) $fields['fax'] = $fields['phone'];
	if ($fields['organization']) $fields['individual'] = 'N';
	else {
		$fields['individual'] = 'Y';
		unset($fields['organization']);
	}
	
	foreach($fields as $key=>$val){
		if ($val == '') $fields[$key] = '!@!';
	}
	//print_r($fields);exit;
	$fields['domain'] = $domain;
	if (!$connect->execute_request('domain-owner-change', $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	$contact_proc['Registrant'] = $resp['response_header']['proc-id'];
	/*
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
		return $values;
	}
	*/
	
	for ($i=0; $i < count($contact_type); $i++){
		$type = $contact_type[$i];
		$handle = $contact_handle[$i];
		$fields = $params['contactdetails'][$type];
		if (!$fields['fax']) $fields['fax'] = $fields['phone'];
		if ($fields['organization']) $fields['individual'] = 'N';
		else {
			$fields['individual'] = 'Y';
			unset($fields['organization']);
		}
		foreach($fields as $key=>$val){
			if ($val == '') $fields[$key] = '!@!';
		}
		$fields['handle'] = $handle;
		//print_r($fields);exit;

		$contact_proc = array();

		if (!$connect->execute_request('contact-modify', $fields, $resp, $connect->sid)){
			$values["error"] = 'execute_request error,';
			if ($resp['response_header']){
				$values["error"] .= ' code:'.$resp['response_header']['status-code'];
				$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
			}
			return $values;
		}
		$contact_proc[$key] = $resp['response_header']['proc-id'];
	}
	
	foreach($contact_proc as $key=>$proc_id){
		$retrieve = $connect->wait_result_retrieve($proc_id);
		if ($retrieve['error']){
			$values["error"] = $retrieve['error'];
			return $values;
			break;
		}
	}
	
	return $values;
}


function joker_RegisterNameserver($params) {
	//print_r($params);exit;
	$domain = joker_GetDomainFromParams($params);

	$fields['host'] = $params["nameserver"];
	$fields['ip'] = $params["ipaddress"];
	
	$connect = joker_login($params);
	if (!$connect->execute_request("ns-create", $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}
	
	return $values;
}

function joker_ModifyNameserver($params) {
	//print_r($params);exit;
	$domain = joker_GetDomainFromParams($params);

	$fields['host'] = $params["nameserver"];
	$fields['ip'] = $params["newipaddress"];
	
	$connect = joker_login($params);
	if (!$connect->execute_request("ns-modify", $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}
	
	return $values;
}

function joker_DeleteNameserver($params) {
	//print_r($params);exit;
	$domain = joker_GetDomainFromParams($params);

	$fields['host'] = $params["nameserver"];
	
	$connect = joker_login($params);
	if (!$connect->execute_request("ns-delete", $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}
	
	return $values;
}


function joker_IDProtectToggle($params) {
	//print_r($params);exit;
	$domain = joker_GetDomainFromParams($params);
	$values["error"] = "";
	
	$protectenable = $params["protectenable"] == '1'?'1':'0';
	$fields = array(
		'domain' => $domain,
		'whois-opt-out' => $protectenable,
	);
	//print_r($fields);exit;
	
	$connect = joker_login($params);
	//echo $action; print_r($fields);exit;
	if (!$connect->execute_request('domain-set-property', $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}
	
	return $values;
}

function joker_GetDomainFromParams($params){
	$domain = '';
	if ($params['domainObj'] && is_object($params['domainObj'])){
		$sld = $params['domainObj']->getIDNSecondLevel();
		if ($sld) $domain = $sld.".".$params["tld"];
	}
	if ($domain == '') $domain = $params["sld"].".".$params["tld"];
	return $domain;
}

function joker_RegisterDomain($params) {
    //print_r($params);exit;
	$connect = joker_login($params);
	
	$origparams = $params;
	//$params = joker_get_utf8_params($params);

	$domain = joker_GetDomainFromParams($params);
	//print_r($params);

	$values["error"] = "";
	
	$registrant = array(
		"tld"       => $params["tld"],
		//"name"      => $_SESSION["httpvars"]["t_contact_name"],
		"fname"     => $params["firstname"],
		"lname"     => $params["lastname"],
		//"title"     => $_SESSION["httpvars"]["t_contact_title"],
		"organization"  => $params["companyname"],
		"email"     => $params["email"],
		"address-1" => $params["address1"],
		"address-2" => $params["address2"],
		//"address-3" => $_SESSION["httpvars"]["t_contact_address_3"],
		"city"      => $params["city"],
		"state"     => $params["state"],
		"postal-code"   => $params["postcode"],
		"country"   => $params["country"],
		"phone"     => $params["phonenumber"],
		//"extension" => $_SESSION["httpvars"]["t_contact_extension"],
		//"fax"       => $_SESSION["httpvars"]["t_contact_fax"]
	);
	if (!$registrant['fax']) $registrant['fax'] = $registrant['phone'];
	if ($registrant['organization']) $registrant['individual'] = 'N';
	else $registrant['individual'] = 'Y';
	if ($params["additionalfields"]){
		if ($params["additionalfields"]['Legal Type']){
			$registrant["account-type"] = $params["additionalfields"]['Legal Type'];
			$registrant["company-number"] = $params["additionalfields"]['Company ID Number'];
		}
	}
	
	$admin = array(
		"tld"       => $params["tld"],
		//"name"      => $_SESSION["httpvars"]["t_contact_name"],
		"fname"     => $params["adminfirstname"],
		"lname"     => $params["adminlastname"],
		//"title"     => $_SESSION["httpvars"]["t_contact_title"],
		"organization"  => $params["admincompanyname"],
		"email"     => $params["adminemail"],
		"address-1" => $params["adminaddress1"],
		"address-2" => $params["adminaddress2"],
		//"address-3" => $_SESSION["httpvars"]["t_contact_address_3"],
		"city"      => $params["admincity"],
		"state"     => $params["adminstate"],
		"postal-code"   => $params["adminpostcode"],
		"country"   => $params["admincountry"],
		"phone"     => $params["adminphonenumber"],
		//"extension" => $_SESSION["httpvars"]["t_contact_extension"],
		//"fax"       => $_SESSION["httpvars"]["t_contact_fax"]
	);
	if (!$admin['fax']) $admin['fax'] = $admin['phone'];
	if ($admin['organization']) $admin['individual'] = 'N';
	else $admin['individual'] = 'Y';
	
	$contact_info = array(
		"owner-c" => $registrant,
		"admin-c" => $admin,
		"tech-c" => $admin,
		"billing-c" => $admin,
	);
	$contact_id = array();
	$contact_proc = array();
	
	foreach($contact_info as $key=>$val){
		if (!$connect->execute_request("contact-create", $val, $resp, $connect->sid)){
			$values["error"] = 'execute_request error,';
			if ($resp['response_header']){
				$values["error"] .= ' code:'.$resp['response_header']['status-code'];
				$values["error"] .= ' error:'.$resp['response_header']['error'];
			}
			return $values;
		}
		//print_r($val);print_r($resp);exit;
		//$contact_id[$key] = 'c'.$params["tld"].'-'.$resp['response_header']['proc-id'];
		$contact_proc[$key] = $resp['response_header']['proc-id'];
	}
	
	foreach($contact_proc as $key=>$proc_id){
		$retrieve = $connect->wait_result_retrieve($proc_id);
		if ($retrieve['error']){
			$values["error"] = $retrieve['error'];
		}else{
			preg_match('#registry_roid:(.+)#', $retrieve['result'], $matches);
			if ($matches[1]){
				$contact_id[$key] = trim($matches[1]);
			}else{
				$values["error"] = 'get contact registry_roid failed.';
			}
		}
		
	}
	
	//print_r($contact_id);exit;
	$ns_str = '';
	if ($params["ns1"]) $ns_str = $params["ns1"];
	if ($params["ns2"]) $ns_str .= ':'.$params["ns2"];
	if ($params["ns3"]) $ns_str .= ':'.$params["ns3"];
	if ($params["ns4"]) $ns_str .= ':'.$params["ns4"];
	if ($params["ns5"]) $ns_str .= ':'.$params["ns5"];
	
	$fields = $contact_id;
	$fields['domain'] = $domain;
	$fields['autorenew'] = '0';
	$fields['period'] = $params["regperiod"]*12;
	$fields['status'] = "production";
	$fields['ns-list'] = $ns_str;
	
	if (substr($domain, 0, 4) == 'xn--'){
		$idnlang = explode( "|", $params["additionalfields"]["IDN Language"] );
		$idnlang = $idnlang[0];
		
		//默认中文
		if (!$idnlang) $idnlang = 'ZHO';
	
		if (( $idnlang && $idnlang != "NOIDN" )) {
			$fields['language'] = $idnlang;
		}
	}
	
	if (!$connect->execute_request("domain-register", $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}else{
	}
	
	return $values;
}


function joker_query_additionalfields(&$params) {
	$result = mysql_query("SELECT name,value FROM tbldomainsadditionalfields
		WHERE domainid='".mysql_real_escape_string($params["domainid"])."'");
	while ( $row = mysql_fetch_array($result, MYSQL_ASSOC) ) {
		$params['additionalfields'][$row['name']] = $row['value'];
	}
}


function joker_use_additionalfields($params, &$command) {
	include dirname(__FILE__).DIRECTORY_SEPARATOR.
		"..".DIRECTORY_SEPARATOR.
		"..".DIRECTORY_SEPARATOR.
		"..".DIRECTORY_SEPARATOR.
		"includes".DIRECTORY_SEPARATOR."additionaldomainfields.php";

	$myadditionalfields = array();
	if ( is_array($additionaldomainfields) && isset($additionaldomainfields[".".$params["tld"]]) ) {
		$myadditionalfields = $additionaldomainfields[".".$params["tld"]];
	}

	$found_additionalfield_mapping = 0;
	foreach ( $myadditionalfields as $field_index => $field ) {
		if ( isset($field["Ispapi-Name"]) || isset($field["Ispapi-Eval"]) ) {
			$found_additionalfield_mapping = 1;
		}
	}

	if ( !$found_additionalfield_mapping ) {
		include dirname(__FILE__).DIRECTORY_SEPARATOR."additionaldomainfields.php";
		if ( is_array($additionaldomainfields) && isset($additionaldomainfields[".".$params["tld"]]) ) {
			$myadditionalfields = $additionaldomainfields[".".$params["tld"]];
		}
	}

	foreach ( $myadditionalfields as $field_index => $field ) {
		if ( !is_array($field["Ispapi-Replacements"]) ) {
			$field["Ispapi-Replacements"] = array();
		}

		if ( isset($field["Ispapi-Options"]) && isset($field["Options"]) )  {
			$options = explode(",", $field["Options"]);
			foreach ( explode(",", $field["Ispapi-Options"]) as $index => $new_option ) {
				$option = $options[$index];
				if ( !isset($field["Ispapi-Replacements"][$option]) ) {
					$field["Ispapi-Replacements"][$option] = $new_option;
				}
			}
		}

		$myadditionalfields[$field_index] = $field;
	}

	foreach ( $myadditionalfields as $field ) {

		if ( isset($params['additionalfields'][$field["Name"]]) ) {
			$value = $params['additionalfields'][$field["Name"]];

			$ignore_countries = array();
			if ( isset($field["Ispapi-IgnoreForCountries"]) ) {
				foreach ( explode(",", $field["Ispapi-IgnoreForCountries"]) as $country ) {
					$ignore_countries[strtoupper($country)] = 1;
				}
			}

			if ( !$ignore_countries[strtoupper($params["country"])] ) {

				if ( isset($field["Ispapi-Replacements"][$value]) ) {
					$value = $field["Ispapi-Replacements"][$value];
				}

				if ( isset($field["Ispapi-Eval"]) ) {
					eval($field["Ispapi-Eval"]);
				}

				if ( isset($field["Ispapi-Name"]) ) {
					if ( strlen($value) ) {
						$command[$field["Ispapi-Name"]] = $value;
					}
				}
			}
		}
	}
}


function joker_TransferDomain($params) {
    $connect = joker_login($params);
	
	$origparams = $params;
	//$params = joker_get_utf8_params($params);

	$domain = joker_GetDomainFromParams($params);

	$values["error"] = "";
	
	$registrant = array(
		"tld"       => $params["tld"],
		//"name"      => $_SESSION["httpvars"]["t_contact_name"],
		"fname"     => $params["firstname"],
		"lname"     => $params["lastname"],
		//"title"     => $_SESSION["httpvars"]["t_contact_title"],
		"organization"  => $params["companyname"],
		"email"     => $params["email"],
		"address-1" => $params["address1"],
		"address-2" => $params["address2"],
		//"address-3" => $_SESSION["httpvars"]["t_contact_address_3"],
		"city"      => $params["city"],
		"state"     => $params["state"],
		"postal-code"   => $params["postcode"],
		"country"   => $params["country"],
		"phone"     => $params["phonenumber"],
		//"extension" => $_SESSION["httpvars"]["t_contact_extension"],
		//"fax"       => $_SESSION["httpvars"]["t_contact_fax"]
	);
	if (!$registrant['fax']) $registrant['fax'] = $registrant['phone'];
	if ($registrant['organization']) $registrant['individual'] = 'N';
	else $registrant['individual'] = 'Y';
	
	$admin = array(
		"tld"       => $params["tld"],
		//"name"      => $_SESSION["httpvars"]["t_contact_name"],
		"fname"     => $params["adminfirstname"],
		"lname"     => $params["adminlastname"],
		//"title"     => $_SESSION["httpvars"]["t_contact_title"],
		"organization"  => $params["admincompanyname"],
		"email"     => $params["adminemail"],
		"address-1" => $params["adminaddress1"],
		"address-2" => $params["adminaddress2"],
		//"address-3" => $_SESSION["httpvars"]["t_contact_address_3"],
		"city"      => $params["admincity"],
		"state"     => $params["adminstate"],
		"postal-code"   => $params["adminpostcode"],
		"country"   => $params["admincountry"],
		"phone"     => $params["adminphonenumber"],
		//"extension" => $_SESSION["httpvars"]["t_contact_extension"],
		//"fax"       => $_SESSION["httpvars"]["t_contact_fax"]
	);
	if (!$admin['fax']) $admin['fax'] = $admin['phone'];
	if ($admin['organization']) $admin['individual'] = 'N';
	else $admin['individual'] = 'Y';
	
	$contact_info = array(
		"owner-c" => $registrant,
		"admin-c" => $admin,
		"tech-c" => $admin,
		"billing-c" => $admin,
	);
	$contact_id = array();
	$contact_proc = array();
	
	foreach($contact_info as $key=>$val){
		if (!$connect->execute_request("contact-create", $val, $resp, $connect->sid)){
			$values["error"] = 'execute_request error,';
			if ($resp['response_header']){
				$values["error"] .= ' code:'.$resp['response_header']['status-code'];
				$values["error"] .= ' error:'.$resp['response_header']['error'];
			}
			return $values;
		}
		//print_r($val);print_r($resp);exit;
		//$contact_id[$key] = 'c'.$params["tld"].'-'.$resp['response_header']['proc-id'];
		$contact_proc[$key] = $resp['response_header']['proc-id'];
	}
	
	foreach($contact_proc as $key=>$proc_id){
		$retrieve = $connect->wait_result_retrieve($proc_id);
		if ($retrieve['error']){
			$values["error"] = $retrieve['error'];
		}else{
			preg_match('#registry_roid:(.+)#', $retrieve['result'], $matches);
			if ($matches[1]){
				$contact_id[$key] = trim($matches[1]);
			}else{
				$values["error"] = 'get contact registry_roid failed.';
			}
		}
		
	}
	
	//print_r($contact_id);exit;
	$ns_str = '';
	if ($params["ns1"]) $ns_str = $params["ns1"];
	if ($params["ns2"]) $ns_str .= ':'.$params["ns2"];
	if ($params["ns3"]) $ns_str .= ':'.$params["ns3"];
	if ($params["ns4"]) $ns_str .= ':'.$params["ns4"];
	if ($params["ns5"]) $ns_str .= ':'.$params["ns5"];
	
	$fields = $contact_id;
	$fields['domain'] = $domain;
	$fields['transfer-auth-id'] = $params['transfersecret'];
	$fields['autorenew'] = '0';
	$fields['period'] = $params["regperiod"]*12;
	$fields['status'] = "production";
	$fields['ns-list'] = $ns_str;
	
	if (!$connect->execute_request("domain-transfer-in-reseller", $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	//print_r($resp);exit;
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}else{
	}
	
	return $values;
}

function joker_RenewDomain($params) {

	$domain = joker_GetDomainFromParams($params);
	$values["error"] = "";
	
	$domain = joker_GetDomainFromParams($params);

	$connect = joker_login($params);
	
	/*
	$info = $connect->info_domain($domain);
	if ($info['error']){
		$values["error"] = $info['error'];
		return $values;
	}else{
		if (!$info['info']['domain.expires']){
			$values["error"] = 'expires is null';
			return $values;
		}
	}
	$expyear = substr($info['info']['domain.expires'], 0, 4);
	*/
	$fields = array(
		'domain' => $domain,
		'period' => $params["regperiod"]*12,
		//'expyear' => $expyear,
	);
	
	if (!$connect->execute_request('domain-renew', $fields, $resp, $connect->sid)){
		$values["error"] = 'execute_request error,';
		if ($resp['response_header']){
			$values["error"] .= ' code:'.$resp['response_header']['status-code'];
			$values["error"] .= ' error:'.print_r($resp['response_header']['error'], true);
		}
		return $values;
	}
	
	$proc_id = $resp['response_header']['proc-id'];
	$retrieve = $connect->wait_result_retrieve($proc_id);
	if ($retrieve['error']){
		$values["error"] = $retrieve['error'];
	}

	return $values;
}


function joker_TransferSync($params) {
	$domain = joker_GetDomainFromParams($params);
	$domain = joker_GetDomainFromParams($params);
	echo "joker_Sync:".$domain."<br>\r\n";
	$values = array();

	$connect = joker_login($params);
	$info = $connect->info_domain($domain);
	if ($info['error']){
		$values["error"] = $info['error'];
		return $values;
	}else{
		$values["completed"] = true;
		$values['expirydate'] = date('Y-m-d', $exp_time);
	}
	//print_r($values);
	//exit;
	return $values;
}



function joker_Sync($params) {
	$domain = joker_GetDomainFromParams($params);
	echo "joker_Sync:".$domain."<br>\r\n";
	$values = array();

	$connect = joker_login($params);
	$info = $connect->info_domain($domain);
	if ($info['error']){
		$values["error"] = $info['error'];
		return $values;
	}else{
		$exp_time = strtotime($info['info']['domain.expires']);
		if ($exp_time > time()){
			$values["active"] = true;
			$values['expirydate'] = date('Y-m-d', $exp_time);
		}else{
			$values['expired'] = true;
		}
	}
	//print_r($values);
	//exit;
	return $values;
}





/* Helper functions */


function joker_get_utf8_params($params) {
    if ( isset($params["original"]) ) {
        return $params["original"];
    }
	$config = array();
	$result = mysql_query("SELECT setting, value FROM tblconfiguration;");
	while ( $row = mysql_fetch_array($result, MYSQL_ASSOC) ) {
		$config[strtolower($row['setting'])] = $row['value'];
	}
	if ( (strtolower($config["charset"]) != "utf-8") && (strtolower($config["charset"]) != "utf8") )
		return $params;

	$result = mysql_query("SELECT orderid FROM tbldomains WHERE id='".mysql_real_escape_string($params["domainid"])."' LIMIT 1;");
	if ( !($row = mysql_fetch_array($result, MYSQL_ASSOC)) )
		return $params;

	$result = mysql_query("SELECT userid,contactid FROM tblorders WHERE id='".mysql_real_escape_string($row['orderid'])."' LIMIT 1;");
	if ( !($row = mysql_fetch_array($result, MYSQL_ASSOC)) )
		return $params;

	if ( $row['contactid'] ) {
		$result = mysql_query("SELECT firstname, lastname, companyname, email, address1, address2, city, state, postcode, country, phonenumber FROM tblcontacts WHERE id='".mysql_real_escape_string($row['contactid'])."' LIMIT 1;");
		if ( !($row = mysql_fetch_array($result, MYSQL_ASSOC)) )
			return $params;
		foreach ( $row as $key => $value ) {
			$params[$key] = $value;
		}
	}
	elseif ( $row['userid'] ) {
		$result = mysql_query("SELECT firstname, lastname, companyname, email, address1, address2, city, state, postcode, country, phonenumber FROM tblclients WHERE id='".mysql_real_escape_string($row['userid'])."' LIMIT 1;");
		if ( !($row = mysql_fetch_array($result, MYSQL_ASSOC)) )
			return $params;
		foreach ( $row as $key => $value ) {
			$params[$key] = $value;
		}
	}

	if ( $config['registraradminuseclientdetails'] ) {
		$params['adminfirstname'] = $params['firstname'];
		$params['adminlastname'] = $params['lastname'];
		$params['admincompanyname'] = $params['companyname'];
		$params['adminemail'] = $params['email'];
		$params['adminaddress1'] = $params['address1'];
		$params['adminaddress2'] = $params['address2'];
		$params['admincity'] = $params['city'];
		$params['adminstate'] = $params['state'];
		$params['adminpostcode'] = $params['postcode'];
		$params['admincountry'] = $params['country'];
		$params['adminphonenumber'] = $params['phonenumber'];
	}
	else {
		$params['adminfirstname'] = $config['registraradminfirstname'];
		$params['adminlastname'] = $config['registraradminlastname'];
		$params['admincompanyname'] = $config['registraradmincompanyname'];
		$params['adminemail'] = $config['registraradminemailaddress'];
		$params['adminaddress1'] = $config['registraradminaddress1'];
		$params['adminaddress2'] = $config['registraradminaddress2'];
		$params['admincity'] = $config['registraradmincity'];
		$params['adminstate'] = $config['registraradminstateprovince'];
		$params['adminpostcode'] = $config['registraradminpostalcode'];
		$params['admincountry'] = $config['registraradmincountry'];
		$params['adminphonenumber'] = $config['registraradminphone'];
	}

	$result = mysql_query("SELECT name,value FROM tbldomainsadditionalfields
		WHERE domainid='".mysql_real_escape_string($params["domainid"])."'");
	while ( $row = mysql_fetch_array($result, MYSQL_ASSOC) ) {
		$params['additionalfields'][$row['name']] = $row['value'];
	}

	return $params;
}



function joker_get_contact_info($contact, &$params) {
	if ( isset($params["_contact_hash"][$contact]) )
		return $params["_contact_hash"][$contact];

	$domain = joker_GetDomainFromParams($params);

	$values = array();
	$command = array(
		"COMMAND" => "StatusContact",
		"CONTACT" => $contact
	);
	$response = joker_call($command, joker_config($params));

	if ( 1 || $response["CODE"] == 200 ) {
		$values["First Name"] = htmlspecialchars($response["PROPERTY"]["FIRSTNAME"][0]);
		$values["Last Name"] = htmlspecialchars($response["PROPERTY"]["LASTNAME"][0]);
		$values["Company Name"] = htmlspecialchars($response["PROPERTY"]["ORGANIZATION"][0]);
		$values["Address"] = htmlspecialchars($response["PROPERTY"]["STREET"][0]);
		$values["Address 2"] = htmlspecialchars($response["PROPERTY"]["STREET"][1]);
		$values["City"] = htmlspecialchars($response["PROPERTY"]["CITY"][0]);
		$values["State"] = htmlspecialchars($response["PROPERTY"]["STATE"][0]);
		$values["Postcode"] = htmlspecialchars($response["PROPERTY"]["ZIP"][0]);
		$values["Country"] = htmlspecialchars($response["PROPERTY"]["COUNTRY"][0]);
		$values["Phone"] = htmlspecialchars($response["PROPERTY"]["PHONE"][0]);
		$values["Fax"] = htmlspecialchars($response["PROPERTY"]["FAX"][0]);
		$values["Email"] = htmlspecialchars($response["PROPERTY"]["EMAIL"][0]);

		if ( (count($response["PROPERTY"]["STREET"]) < 2)
			and preg_match('/^(.*) , (.*)/', $response["PROPERTY"]["STREET"][0], $m) ) {
			$values["Address"] = $m[1];
			$values["Address 2"] = $m[2];
		}

		// handle imported .ca domains properly
		if ( preg_match('/[.]ca$/i', $domain) && isset($response["PROPERTY"]["X-CA-LEGALTYPE"]) ) {
			if ( preg_match('/^(CCT|RES|ABO|LGR)$/i', $response["PROPERTY"]["X-CA-LEGALTYPE"][0]) ) {
				// keep name/org
			}
			else {
				if ( (!isset($response["PROPERTY"]["ORGANIZATION"])) || !$response["PROPERTY"]["ORGANIZATION"][0] ) {
					$response["PROPERTY"]["ORGANIZATION"] = $response["PROPERTY"]["NAME"];
				}
			}
		}

	}
	$params["_contact_hash"][$contact] = $values;
	return $values;
}


function joker_logModuleCall($registrar, $action, $requeststring, $responsedata, $processeddata = NULL, $replacevars = NULL) {
	if ( !function_exists('logModuleCall') ) {
		return;
	}
	return logModuleCall($registrar, $action, $requeststring, $responsedata, $processeddata, $replacevars);
}


function joker_config($params) {
	$url = "http://api.domainreselling.de/api/call.cgi";
	if ( $params["UseSSL"] == "on" ) {
		$url = "https://api.domainreselling.de/api/call.cgi";
	}
	$user = $params["Username"];
	$pass = $params["Password"];
	$s_entity = '';
	if ( $params["TestMode"] == "on" ) {
		$s_entity = '1234';
		$mreg_config = array('socket' => $url.'?s_entity='.$s_entity.'&s_login='.$user.'&s_pw='.$pass);
	}else{
		$mreg_config = array('socket' => $url.'?s_login='.$user.'&s_pw='.$pass);
	}
	
	
	
	
	return $mreg_config;
}


function joker_call($command, $config) {
	$oMREG = new mreg;
	return $oMREG->mreg_call( $command, $config );

	//return joker_parse_response(joker_call_raw($command, $config));
}


function joker_call_raw($command, $config) {
	global $joker_module_version;
	$args = array();
	$url = $config["url"];
	if ( isset($config["login"]) )
		$args["s_login"] = $config["login"];
	if ( isset($config["password"]) )
		$args["s_pw"] = $config["password"];
	if ( isset($config["user"]) )
		$args["s_user"] = $config["user"];
	if ( isset($config["entity"]) )
		$args["s_entity"] = $config["entity"];
	$args["s_command"] = joker_encode_command($command);

	$config["curl"] = curl_init($url);
	if ( $config["curl"] === FALSE ) {
		return "[RESPONSE]\nCODE=423\nAPI access error: curl_init failed\nEOF\n";
	}
	$postfields = array();
	foreach ( $args as $key => $value ) {
		$postfields[] = urlencode($key)."=".urlencode($value);
	}
	$postfields = implode('&', $postfields);
	die($url.'&'.$postfields);
	curl_setopt( $config["curl"], CURLOPT_POST, 1 );
	curl_setopt( $config["curl"], CURLOPT_POSTFIELDS, $postfields );
	
	curl_setopt( $config["curl"], CURLOPT_HEADER, 0 );
	curl_setopt( $config["curl"], CURLOPT_RETURNTRANSFER , 1 );
	if ( strlen($config["proxy"]) ) {
		curl_setopt( $config["curl"], CURLOPT_PROXY, $config["proxy"] );
	}
	curl_setopt($config["curl"], CURLOPT_USERAGENT, "ISPAPI/$joker_module_version WHMCS/".$GLOBALS["CONFIG"]["Version"]." PHP/".phpversion()." (".php_uname("s").")");
	curl_setopt($config["curl"], CURLOPT_REFERER, $GLOBALS["CONFIG"]["SystemURL"]);
	$response = curl_exec($config["curl"]);

	if ( preg_match('/(^|\n)\s*COMMAND\s*=\s*([^\s]+)/i', $args["s_command"], $m) ) {
		$command = $m[2];
		// don't log read-only requests
		if ( !preg_match('/^(Check|Status|Query|Convert)/i', $command) ) {
			joker_logModuleCall($config["registrar"], $command, $args["s_command"], $response);
		}
	}

	return $response;
}


function joker_to_punycode($domain) {
	if ( !strlen($domain) ) return $domain;
	if ( preg_match('/^[a-z0-9\.\-]+$/i', $domain) ) {
		return $domain;
	}

	$charset = $GLOBALS["CONFIG"]["Charset"];
	if ( function_exists("idn_to_ascii") ) {
		$punycode = idn_to_ascii($domain, $charset);
		if ( strlen($punycode) ) return $punycode;
	}
	return $domain;
}


function joker_encode_command( $commandarray ) {
    if (!is_array($commandarray)) return $commandarray;
    $command = "";
    foreach ( $commandarray as $k => $v ) {
        if ( is_array($v) ) {
	    $v = joker_encode_command($v);
            $l = explode("\n", trim($v));
            foreach ( $l as $line ) {
                $command .= "$k$line\n";
		    }
        }
        else {
            $v = preg_replace( "/\r|\n/", "", $v );
            $command .= "$k=$v\n";
        }
    }
    return $command;
}



function joker_parse_response ( $response ) {
    if (is_array($response)) return $response;
    $hash = array(
		"PROPERTY" => array(),
		"CODE" => "423",
		"DESCRIPTION" => "Empty response from API"
	);
    if (!$response) return $hash;
    $rlist = explode( "\n", $response );
    foreach ( $rlist as $item ) {
        if ( preg_match("/^([^\=]*[^\t\= ])[\t ]*=[\t ]*(.*)$/", $item, $m) ) {
            $attr = $m[1];
            $value = $m[2];
            $value = preg_replace( "/[\t ]*$/", "", $value );
            if ( preg_match( "/^property\[([^\]]*)\]/i", $attr, $m) ) {
                $prop = strtoupper($m[1]);
                $prop = preg_replace( "/\s/", "", $prop );
                if ( in_array($prop, array_keys($hash["PROPERTY"])) ) {
                    array_push($hash["PROPERTY"][$prop], $value);
                }
                else {
                     $hash["PROPERTY"][$prop] = array($value);
                }
            }
            else {
                $hash[strtoupper($attr)] = $value;
            }
        }
    }
	if ( (!$hash["CODE"]) || (!$hash["DESCRIPTION"]) ) {
		$hash = array(
			"PROPERTY" => array(),
			"CODE" => "423",
			"DESCRIPTION" => "Invalid response from API"
		);
	}
    return $hash;
}


?>
