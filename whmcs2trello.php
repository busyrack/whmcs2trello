<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

function whmcs2trello_config() {
    $configarray = array(
    "name" => "WHMCS to Trello",
    "description" => "Get tickets from WHMCS into your Trello account",
    "version" => "0.1",
    "author" => "Shalom Carmel",
    "language" => "english",
    "fields" => array(
        "trello_key" => array ("FriendlyName" => "Trello Key", "Type" => "text", "Size" => "50", "Description" => "Your Trello Key", "Default" => "", ),
        "trello_token" => array ("FriendlyName" => "Trello Token", "Type" => "text", "Size" => "50", "Description" => "Your Trello permanent token", "Default" => "", ),		
		"trello_list_id" => array ("FriendlyName" => "Trello List ID", "Type" => "text", "Size" => "50", "Description" => "The List ID to post to", "Default" => "", ),		
        "card_position" => array ("FriendlyName" => "Card Position in List", "Type" => "dropdown", "Options" => "top,bottom,2,3,4,5,6,7,8,9,10", "Description" => "Position on board", "Default" => "bottom", ),		
		"support_department" => array ("FriendlyName" => "Support Department ID", "Type" => "text", "Size" => "5", "Description" => "Leave empty to include all", "Default" => "", ),		
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

function whmcs2trello_output($vars) {
	
	//===========================
	echo "<p><a href='addonmodules.php?module=whmcs2trello&disable=1'>Disable WHMCS to Trello</a></p>";
	echo '<p> Nothing to do here</p>'; 
    
	echo json_encode($vars);
	

}
