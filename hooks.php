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

function trello_TicketOpen($vars) {
	global $customadminpath, $CONFIG;

	$trello = whmcs2trello_module_settings();
	if ($vars['priority']=='High' OR ((int)$trello['support_department'] > 0 AND $vars['deptid'] == (int)$trello['support_department'] ) ) {
		$companyname=''; 
		$adminuser = "apiapi";
		$responsetype = "json";  // Probably unnecessary
		 if (!empty($vars["userid"])) {
			 // Get customer details
			 $command = "getclientsdetails";
			 $api_input["clientid"] = $vars['userid'];
			 $api_input["stats"] = false;
			 $api_input["responsetype"] = $responsetype ; 

			 $getclientsdetails = localAPI($command,$api_input,$adminuser);
			 $companyname=$getclientsdetails["companyname"];
		 }
		$command = "getticket";
		$api_input = array(); 
		$api_input["ticketid"] = $vars['ticketid'];
		$getticket = localAPI($command,$api_input,$adminuser);
		$vars['tid'] = $getticket['tid']; 
//		$shortmessage=$vars['priority'] .' priority ticket: '. $vars['subject'] ; 
		$shortmessage='#'.$vars['tid'].' - '.$vars['subject'] . ' ('.$companyname.')'; 
		$card_desc = '[Ticket Link](' . $CONFIG['SystemURL'].'/'.$customadminpath.'/supporttickets.php?action=viewticket&id='.$vars['ticketid'] .')'. PHP_EOL 
				. 'Company: ' . $companyname . PHP_EOL 
				. 'Message: ' . PHP_EOL . $vars['message'] ; 

		sendtrello(  $trello['trello_key'],
					$trello['trello_token'],
					$trello['trello_list_id'],
					$trello['card_position'],
					$shortmessage,
					$card_desc,
					$trello['debug']
				);
	
	}
}

// Run on existing tickets - department change
/*
Variable	Type	Notes
ticketid	Integer	The ID of the ticket the department is being changed for. tbltickets.id
deptid		Integer	The new department ID.
deptname	String	The new department name.
*/
function trello_TicketDepartmentChange($vars) {
	global $customadminpath, $CONFIG;
	$trello = whmcs2trello_module_settings();
	if ( (int)$trello['support_department'] > 0 AND $vars['deptid'] == (int)$trello['support_department'] ){
		
		$companyname=''; 
		$adminuser = "apiapi"; // set your own admin user
		$responsetype = "json";  // Probably unnecessary
		 // Get ticket information 
		$command = "getticket";
		$api_input = array(); 
		$api_input["ticketid"] = $vars['ticketid'];
		$getticket = localAPI($command,$api_input,$adminuser);
		$vars=array_merge($vars, $getticket); 
		 if (!empty($vars["userid"])) {
			 // Get customer details
			 $command = "getclientsdetails";
			 $api_input = array();
			 $api_input["clientid"] = $vars['userid'];
			 $api_input["stats"] = false;
			 $api_input["responsetype"] = $responsetype ; 

			 $getclientsdetails = localAPI($command,$api_input,$adminuser);
			 $companyname=$getclientsdetails["companyname"];
		 }
//		$shortmessage=$vars['priority'] .' priority ticket: '. $vars['subject'] ; 
		$shortmessage='#'.$vars['tid'].' - '.$vars['subject'] . ' ('.$companyname.')'; 
		$card_desc = '[Ticket Link](' . $CONFIG['SystemURL'].'/'.$customadminpath.'/supporttickets.php?action=viewticket&id='.$vars['ticketid'] .')'. PHP_EOL 
				. 'Company: ' . $companyname . PHP_EOL 
				. 'Message: ' . PHP_EOL . $vars['message'] ; 

		sendtrello(  $trello['trello_key'],
					$trello['trello_token'],
					$trello['trello_list_id'],
					$trello['card_position'],
					$shortmessage,
					$card_desc,
					$trello['debug']
				);
	}

}


function sendtrello( $trello_key, $trello_token, $trello_list_id, $card_position, $card_name, $card_desc = '', $debug='') 
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
		$adminuser = "apiapi";
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

