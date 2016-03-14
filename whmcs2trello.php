<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

function whmcs2trello_config() {
    $configarray = array(
    "name" => "WHMCS to Trello",
    "description" => "Get tickets from WHMCS into your Trello account",
    "version" => "0.7",
    "author" => "Shalom Carmel",
    "language" => "english",
    "fields" => array(
        "api_user" => array ("FriendlyName" => "WHMCS API user", "Type" => "text", "Size" => "50", "Description" => "The administration user to use for API execution. <a href=configadmins.php target=_new>Click here to set it up</a>", "Default" => "", ),

        "trello_key" => array ("FriendlyName" => "Trello Key", "Type" => "text", "Size" => "50", "Description" => "Your Trello Key", "Default" => "", ),

        "trello_token" => array ("FriendlyName" => "Trello Token", "Type" => "text", "Size" => "50", "Description" => "Your Trello permanent token", "Default" => "", ),		

		"trello_list_id" => array ("FriendlyName" => "Trello List ID", "Type" => "text", "Size" => "50", "Description" => "The List ID to post to", "Default" => "", ),		

        "card_position" => array ("FriendlyName" => "Card Position in List", "Type" => "dropdown", "Options" => "top,bottom,2,3,4,5,6,7,8,9,10", "Description" => "Position on board", "Default" => "bottom", ),		

		"support_department" => array ("FriendlyName" => "Support Department ID", "Type" => "text", "Size" => "5", "Description" => "Leave empty to include all", "Default" => "", ),		

		"cc_card" => array ("FriendlyName" => "Add card to cc", "Type" => "yesno",  "Description" => "Each card has a unique email address. It can be placed in cc of ticket." ),		
		
		"subject_template" => array ("FriendlyName" => "Card subject template", "Type" => "text",  "Size" => "60",  
			"Description" => '<br>Use the following variables for both templates: 
			       <ul><li>{$ticket_URL}<li>{$ticket_id}<li>{$ticket_userid}<li>{$ticket_deptid}<li>{$ticket_deptname}<li>{$ticket_subject}<li>{$ticket_message}<li>{$ticket_priority}<li>{$companyname}<li>{$ticket_number}</ul>
				   <a href="http://help.trello.com/article/821-using-markdown-in-trello" target="_new">Click here for Trello Formatting Help</a>', 
			"Default" => '#{$ticket_number} - {$ticket_subject} ({$companyname})', ),		

			"body_template" => array ("FriendlyName" => "Card body template", "Type" => "textarea",  "Rows" => "10", "Cols" => "60",  "Description" => '', 
			"Default" => 
'[Link to ticket]({$ticket_URL})
{$companyname}
--------------

-----------------------
{$ticket_message}
', ),		

		"debug" => array ("FriendlyName" => "Debug whmcs2trello", "Type" => "yesno",  "Description" => "Trello requests and responses will be written to the activity log." ),		
    ));
    return $configarray;
}


function whmcs2trello_activate() {
	$result = true ;
}

function whmcs2trello_deactivate() {
	$result = true;
}

function whmcs2trello_output($module_vars) {
	
	//===========================
	echo "<p><a href='addonmodules.php?module=whmcs2trello&disable=1'>Disable WHMCS to Trello</a></p>";
	echo '<p> Nothing to do here</p>'; 
    
	// echo json_encode($module_vars);
	

}
