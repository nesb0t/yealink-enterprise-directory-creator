<?php

###################################################
// Header: Set our variables/constants/etc
###################################################

date_default_timezone_set('America/New_York');				// Not required, but usually a good idea in php

define("SERVER", "nms.example.com");						// Base URL to your nms server. No https or trailing slashes.
define("SUPERUSER", "directorycreator@example.com"); 		// A superuser login. Should use "Super User Read Only" for security.
define("PASSWORD", "Strong-Password-Here");					// Password for the above account
define("CLIENTID", "Example_API_User");						// API key client ID
define("CLIENTSECRET", "ExampleKey123");					// API key secret key

define("DIRECTORYLOCATION", "/var/www/html/example/");		// Absolute path to folder where directories will be stored. INCLUDE TRAILING SLASH.

define("DIRECTORYFIRSTNAME", "Z Enterprise");				// First name of the user account where enterprise contacts are stored. See README for details.
define("DIRECTORYLASTNAME", "Contacts");					// Last name of the user account where enterprise contacts are stored.
define("DIRECTORYEXTENSION", "9805");						// Extension of the user account where enterprise contacts are stored.

# $startTime = __pageLoadTimer();							// Track script load/processing time. See comments in the function definition at the bottom of this file.
															// Must uncomment here and in footer at the bottom to use it.

###################################################
// Step 1: Get API access token from the NMS
###################################################

$query = array(
    'grant_type' => "password",
    'username' => SUPERUSER,
    'password' => PASSWORD,
    'client_id' => CLIENTID,
    'client_secret' => CLIENTSECRET
);

$postFields = http_build_query($query);

$curl_result = __doCurl("https://" . SERVER . "/ns-api/oauth2/token", CURLOPT_POST, NULL, NULL, $postFields, $http_response);

if (!$curl_result) {					// Check if curl result was unsuccessful. Check 1 of 3. 
	# echo "Server error";				// Uncomment for basic debugging
	exit;								// Curl failed. Exiting so we don't overwrite all of our directories with failed results.
}

if ($http_response != "200") {											// Check if we got something other than 200/OK on key request. Check 2 of 3. 
	# echo "Key status: FAIL. http_response: $http_response.<br>";		// Uncomment for basic debugging
	exit;																// Key retrieval failed. Exiting so we don't overwrite all of our directories with failed results.
}
else {
	# echo "Key status: PASS<br>";					// Uncomment for basic debugging
}

$token = json_decode($curl_result, true);			// Decode JSON response

if (!isset($token['access_token'])) {				// Verify we got a token
    # echo "No token received.";					// Uncomment for basic debugging
    exit;											// Key retrieval failed. Exiting so we don't overwrite all of our directories with failed results. Check 3 of 3. 
}

$token = $token['access_token'];					// Set our API token as $token
# echo "<br>Token: $token";							// Uncomment for basic debugging


###################################################
// Step 2: Retrieve list of domains
###################################################

// Find all domains with a "Company Contacts" user
$query = array(
    'object' => "subscriber",						// Find all domains that have a user that matches the details below
    'action' => "read",
	'domain' => "*",
	'first_name' => DIRECTORYFIRSTNAME,				// User first name = DIRECTORYFIRSTNAME This is the shared accounts where enterprise contacts are stored. See the README for more details.
	'last_name' => DIRECTORYLASTNAME,				// User last name = DIRECTORYLASTNAME
);

$domains  = __doCurl("https://" . SERVER . "/ns-api/", CURLOPT_POST, "Authorization: Bearer " . $token, $query, null, $http_response);

$domains = simplexml_load_string($domains);					// Load the XML response from the API

foreach($domains->subscriber as $key => $value) {  			// Look for the "subscriber" field in the API response
	if(isset($value->domain)){  							// Then find the "domain" field and see if it has a value, then:
		$domainArray[] = "$value->domain";  				// Build an array with the domain names
	}
}

###################################################
// Step 3a: Begin creating directories
###################################################

foreach ($domainArray as $key => $domain) {									// Loop through each item (domain) in the array, one at a time.
	# echo "Domain: $domain.<br>";											// Uncomment for basic debugging

	set_time_limit(30);  													// Prevent PHP timeout while building the directories. Sets it to 30 seconds at the beginning of each loop.

	$contactsList = "";     												// Blank out our user list at the start of each loop

	$contactsList .= '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";    // This is the XML header, required by Yealink for the directory
	$contactsList .= '<YealinkIPPhoneDirectory>'."\n";

###################################################
// Step 3b: Retrieve list of users (contacts)
###################################################

// Query string to retrieve the user list
	$query = array(
    	'object' => "contact",									
    	'action' => "read",
    	'domain' => htmlspecialchars($domain),					// Sanitizing the domain name, just to be safe
    	'user' => DIRECTORYEXTENSION,							// Read all contacts from DIRECTORYEXTENSION. See README for more details.
		);

	$contacts = __doCurl("https://" . SERVER . "/ns-api/", CURLOPT_POST, "Authorization: Bearer " . $token, $query, null, $http_response);

	$contacts = simplexml_load_string($contacts);				// Load the XML response from the API
	
	// Loop to create the XML from the user list
	foreach($contacts->contact as $item => $value) {															// Take each contact found, one contact at a time
		if(!empty($value->first_name)) {																		// Check if first name has a value. Skip if it doesn't.
			
				$contactsList .= '<DirectoryEntry>'."\n";														// More Yealink XML
				$contactsList .= '<Name>' . $value->first_name . " " . $value->last_name ."</Name> \n";			// Add first name and last name to the directory
				if(!empty($value->work_phone)) {																// Check if there is a number in "work phone"
					$contactsList .= '<Telephone>' . $value->work_phone . "</Telephone> \n";					// Add work phone number
				}
				if(!empty($value->cell_phone)) {																// Check if there is a number in "cell phone"
					$contactsList .= '<Telephone>' . $value->cell_phone . "</Telephone> \n";					// Add cell phone number
				}
				if(!empty($value->home_phone)) {																// Check if there is a number in "home phone"
					$contactsList .= '<Telephone>' . $value->home_phone . "</Telephone> \n";					// Add home phone number
				}
				$contactsList .= '</DirectoryEntry>'."\n";
			}
		}
	
	$contactsList .= '</YealinkIPPhoneDirectory>'."\n";    														// Yealink XML footer

	
###################################################
// Step 4: Save directory to a file
###################################################

	$saveDirectory = fopen(DIRECTORYLOCATION . $domain . "-contacts.xml", "w");							// Open location to save to. It always overwrites the whole file. Saved as "DomainName-contacts.xml" in DIRECTORYLOCATION
	
	if (fwrite($saveDirectory, $contactsList) === FALSE) {												// Write the file, and check if failed to save
		# echo "Failed to write to file: " . DIRECTORYLOCATION . $domain . "-contacts.xml <br>";					// Uncomment for basic debugging
	}
	else {
		# echo "Successfully wrote: " . DIRECTORYLOCATION . $domain . "-contacts.xml <br>";						// Uncomment for basic debugging
	}
	fclose($saveDirectory);																				// Close the file
	        }

###################################################
// Footer
###################################################

# echo "Directories generated in " . __pageLoadTimer($startTime) . " seconds.";							// Uncomment to use load timer (2 of 2). See notes in header or functions section.


###################################################
// Functions stored below 
###################################################

function __pageLoadTimer($startTime = NULL){				// Track load/processing time. Can be used for debugging purposes, or for adding additional checks to verify if the script
															// may have failed (ex: if it runs too quickly/too slowly compared to your baseline). Must uncomment in Header and Footer to use it.

		$time = microtime();								// Initialize microtime function
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];

	if ($startTime == NULL) {								// We don't have a start time yet, so set one
		$startTime = $time;

		return $startTime;
	}

	else {													// We have a start time. Calculate finish time and total time
		$finish = $time;
		$total_time = round(($finish - $startTime), 4);

		return $total_time;
	}

}


function __doCurl($url, $method, $authorization, $query, $postFields, &$http_response) {		// Function for our curl requests. Taken from aaker's github. Source: https://github.com/aaker/domain-selfsignup
	$start        = microtime(true);
	$curl_options = array(
		CURLOPT_URL => $url . ($query ? '?' . http_build_query($query) : ''),
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FORBID_REUSE => true,
		CURLOPT_TIMEOUT => 60
	);

	$headers = array();
	if ($authorization != NULL)
		{
		$headers[$authorization] = $authorization;
		} //$authorization != NULL



	$curl_options[$method] = true;
	if ($postFields != NULL)
		{
		$curl_options[CURLOPT_POSTFIELDS] = $postFields;
		} //$postFields != NULL

	if (sizeof($headers) > 0)
		$curl_options[CURLOPT_HTTPHEADER] = $headers;

	$curl_handle = curl_init();
	curl_setopt_array($curl_handle, $curl_options);
	$curl_result   = curl_exec($curl_handle);
	$http_response = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
	//print_r($http_response);
	curl_close($curl_handle);
	$end = microtime(true);
	if (!$curl_result)
		return NULL;
	else if ($http_response >= 400)
		return NULL;
	else
		return $curl_result;
	}

?>