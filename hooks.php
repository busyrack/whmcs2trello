<?php

// Run on new tickets 
/*
Variable	Type	Notes
ticketid	Integer	The ID of the ticket being opened. tbltickets.id
userid		Integer	The user that the ticket was opened for.
deptid		Integer	The ID of the department the ticket is in. tblticketdepartments.id
deptname	String	The name of the department the ticket is opened in
subject		String	The subject of the ticket opened
message		String	The message of the opened ticket.
priority	String	The priority of the opened ticket. High, Medium or Low
*/

function trello_TicketOpen($input_vars) {
	global $customadminpath, $CONFIG;

	$trello = whmcs2trello_module_settings();
	if ($input_vars['priority']=='High' OR ((int)$trello['support_department'] > 0 AND $input_vars['deptid'] == (int)$trello['support_department'] ) ) {
		$ticket_URL = $CONFIG['SystemURL'].'/'.$customadminpath.'/supporttickets.php?action=viewticket&id='.$input_vars['ticketid'] ;
		$ticket_id = $input_vars['ticketid'];
		$ticket_userid	  = $input_vars['userid'];
		$ticket_deptid	  = $input_vars['deptid'];
		$ticket_deptname = $input_vars['deptname'];
		$ticket_subject  = $input_vars['subject'];
		$ticket_message  = $input_vars['message'];
		$ticket_priority = $input_vars['priority'];
		$companyname=''; 
		$adminuser = $trello['api_user'];
		$responsetype = "json";  // Probably unnecessary

		 if (!empty($ticket_userid)) {
			 // Get customer details
			 $command = "getclientsdetails";
			 $api_input["clientid"] = $ticket_userid;
			 $api_input["stats"] = false;
			 $api_input["responsetype"] = $responsetype ; 

			 $getclientsdetails = localAPI($command,$api_input,$adminuser);

			 $companyname=$getclientsdetails["companyname"];
		 }
		$command = "getticket";
		$api_input = array(); 
		$api_input["ticketid"] = $input_vars['ticketid'];
		$getticket = localAPI($command,$api_input,$adminuser);
		$ticket_number = $getticket['tid']; 
		
		// Get subject from template in WHMCS configuration. 
		$subject_template=$trello['subject_template'];
		eval ("\$card_subject = \"$subject_template\";");

		$body_template=$trello['body_template'];
		eval ("\$card_desc = \"$body_template\";");

		$trello_response = json_decode(
				send_to_trello(  $trello['trello_key'],
					$trello['trello_token'],
					$trello['trello_list_id'],
					$trello['card_position'],
					$card_subject,
					$card_desc,
					$trello['debug'],
					$adminuser
				)
			);
		if ($trello['cc_card'] == 'on') {
			$email = $trello_response->email;
			$cc = ( empty($getticket["cc"]) ? $email : $getticket["cc"] . "," . $email ); 
			update_ticket_cc($cc, $ticket_id, $adminuser);
		}
	
	}
}

// Run on existing tickets - department change
/*
Variable	Type	Notes
ticketid	Integer	The ID of the ticket the department is being changed for. tbltickets.id
deptid		Integer	The new department ID.
deptname	String	The new department name.
*/
function trello_TicketDepartmentChange($input_vars) {
	global $customadminpath, $CONFIG;
	$trello = whmcs2trello_module_settings();
	if ( (int)$trello['support_department'] > 0 AND $input_vars['deptid'] == (int)$trello['support_department'] ){


		$ticket_URL = $CONFIG['SystemURL'].'/'.$customadminpath.'/supporttickets.php?action=viewticket&id='.$input_vars['ticketid'] ;
		$ticket_id = $input_vars['ticketid'];
		$ticket_deptid	  = $input_vars['deptid'];
		$ticket_deptname = $input_vars['deptname'];
	
		$companyname=''; 
		$adminuser = $trello['api_user'];
		$responsetype = "json";  // Probably unnecessary
		 // Get ticket information 
		$command = "getticket";
		$api_input = array(); 
		$api_input["ticketid"] = $input_vars['ticketid'];
		$getticket = localAPI($command,$api_input,$adminuser);

		$ticket_number = $getticket['tid']; 
		$ticket_userid	  = $getticket['userid'];
		$ticket_subject  = $getticket['subject'];
		// $ticket_message  = $getticket['message'];
		// The getticket API returns an array of responses, first one is the first message. 
		$ticket_message  = $getticket['replies']['reply'][0]['message'];
		$ticket_priority = $getticket['priority'];
		
		 if (!empty($ticket_userid)) {
			 // Get customer details
			 $command = "getclientsdetails";
			 $api_input = array();
			 $api_input["clientid"] = $ticket_userid;
			 $api_input["stats"] = false;
			 $api_input["responsetype"] = $responsetype ; 

			 $getclientsdetails = localAPI($command,$api_input,$adminuser);
			 $companyname=$getclientsdetails["companyname"];
		 }

		$subject_template=$trello['subject_template'];
		eval ("\$card_subject = \"$subject_template\";");

		$body_template=$trello['body_template'];
		eval ("\$card_desc = \"$body_template\";");
		
		$trello_response = json_decode(
				send_to_trello(  $trello['trello_key'],
					$trello['trello_token'],
					$trello['trello_list_id'],
					$trello['card_position'],
					$card_subject,
					$card_desc,
					$trello['debug'],
					$adminuser
				)
			);
		if ($trello['cc_card'] == 'on') {
			$email = $trello_response->email;
			$cc = ( empty($getticket["cc"]) ? $email : $getticket["cc"] . "," . $email ); 
			update_ticket_cc($cc, $ticket_id, $adminuser);
		}
	}

}


		function update_ticket_cc($cc, $ticket_id, $adminuser) {
			$responsetype = "json";  // Probably unnecessary
			$command = "updateticket";		
			// initialize API data structure
			$apivalues = array (
				"ticketid" => $ticket_id,
				"responsetype" => "json",	// Probably unnecessary
				"cc" => $cc
				); 
			$updateticket = localAPI($command,$apivalues,$adminuser);
		}

function send_to_trello( $trello_key, $trello_token, $trello_list_id, $card_position, $card_name, $card_desc = '', $debug='', $adminuser) 
{
	$url = "https://api.trello.com/1/cards?key=".$trello_key."&token=".$trello_token;

		$trello_message = array( 
		  "name" => $card_name, 
		  "desc" => $card_desc,
		  "pos" => $card_position, 
		  "due" => null,
		  "idList" => $trello_list_id
		);

  
		
	$trello_json = json_encode($trello_message); 
    $ch = curl_init();  
	
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_HEADER, false); 
	curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Accept: application/json'
			));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $trello_json );
    curl_setopt($ch,CURLOPT_ENCODING , "gzip");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
    $output=curl_exec($ch);
    $GLOBALS['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
	if ($debug == 'on' ){
		$command = "logactivity";
		$responsetype = "json";  // Probably unnecessary
		$api_input["description"] = "trello request: ".$trello_json;
		$api_input["responsetype"] = $responsetype ; 
		$results = localAPI($command,$api_input,$adminuser);
		$api_input["description"] = "trello response: ".$output;
		$api_input["responsetype"] = $responsetype ; 
		$results = localAPI($command,$api_input,$adminuser);

	}
    return $output;

}

function whmcs2trello_module_settings() {
	$fields = "module,setting,value";
	$where = array("module"=>"whmcs2trello");
	$result = select_query('tbladdonmodules',$fields,$where);
	$whmcs2trello_configuration = array ();
	while ($row = mysql_fetch_array($result,  MYSQL_ASSOC)) {
		$whmcs2trello_configuration[$row["setting"]]= $row["value"];
			
	}		
		return $whmcs2trello_configuration ;
}

add_hook("TicketOpen",888,"trello_TicketOpen");
add_hook("TicketOpenAdmin",888,"trello_TicketOpen");
add_hook("TicketDepartmentChange",888,"trello_TicketDepartmentChange");

