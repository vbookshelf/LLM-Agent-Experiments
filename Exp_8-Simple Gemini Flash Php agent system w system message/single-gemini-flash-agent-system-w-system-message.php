<?php

// Your Google Gemini API Key
$apiKey = 'YOUR-API-KEY';

// Gemini 1.5 Pro - Limit: 2 requests per minute
// Includes system message support
//$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent";

// Gemini 1.5 Flash - Limit: 15 requests per minute
// Includes system message support
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent";


function make_api_call($system_message, $message_history) {
	
	
	global $apiKey;
	global $url;
	
	
	$system_instruction = array(
	  "parts" => array(
	    "text" => $system_message
	  )
	);
	
	
	// Define data
	//$data = array();
	$data = array(
	"system_instruction" => $system_instruction,
    "contents" => $message_history
	);
	
	
	$headers = array(
	"x-goog-api-key: {$apiKey}",
    "Content-Type: application/json"
	);
	
	
	// init curl
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	$result = curl_exec($curl);
	
	if (curl_errno($curl)) {
		
	    echo 'Error:' . curl_error($curl);
		
		return 'api_error';
		
	} else {
		
	    $generatedText = json_decode($result, true);
		
		return $generatedText;
	}
    
}



function extract_text_from_response($response) {
	
	$text = $response["candidates"][0]['content']['parts'][0]['text'];
	
	return $text;
	
}


function run_agent_without_memory($system_message, $prompt) {

	//$user_message = $system_message . " " . $prompt;
	

	$my_message1 = array("text" => $prompt);
	
	$parts_list = array();
	$parts_list[] = $my_message1;
	
	$message_history = array();
	$message_history[] = array("role" => "user", "parts" => $parts_list);
	
	// Make an API call
	$response = make_api_call($system_message, $message_history);
	
	
	if ($response != "api_error") {
		
		$response_text = extract_text_from_response($response);
		
		
		$output_type = check_output_type($response_text);
		
		
		// Check if the output is a json string
		if ($output_type == "is_json_string") {
			
			// Convert '{}' into {}
			$output_text = json_decode($response_text, true);
			
		// Check if the output is a json object
		} else if ($output_type == "is_json_object") {
			
			// Convert to JSON string
			$response_text = json_encode($response_text);
			
			// Convert '{}' into {}
			$output_text = json_decode($response_text, true);
		
		// The output is a plain string
		} else {
			
			$output_text = $response_text; 
		}
		
		
		return $output_text;
		
		
	} else {
		
		return 'api_error';
	}
	
}


	
function run_agent_with_memory($system_message, $message_history) {
	
	
	// Make an API call
	$response = make_api_call($system_message, $message_history);
	
	
	if ($response != "api_error") {
		
		$response_text = extract_text_from_response($response);
		
		
		$output_type = check_output_type($response_text);
		
		
		// Check if the output is a json string
		if ($output_type == "is_json_string") {
			
			// Convert '{}' into {}
			$output_text = json_decode($response_text, true);
			
		// Check if the output is a json object
		} else if ($output_type == "is_json_object") {
			
			// Convert to JSON string
			$response_text = json_encode($response_text);
			
			// Convert '{}' into {}
			$output_text = json_decode($response_text, true);
		
		// The output is a plain string
		} else {
			
			$output_text = $response_text; 
		}
		
		
		return $output_text;
		
		
	} else {
		
		return 'api_error';
	}
	
	
}


function check_output_type($output) {
	

	if (is_object($output)) {
					
		return "is_json_object";
	} 
	
	if (is_string($output)) {
		
    	// Attempt to decode the JSON string
		$decoded_json = json_decode($output, false);
		
		if (json_last_error() == JSON_ERROR_NONE) {
			
			return "is_json_string";
			
		} else {
			
			return "is_plain_string";
		}
	}
			
}


// Run the agent system
//----------------------

$system_message = "You are a pirate.";

$user_message = "How are you?";

$response = run_agent_without_memory($system_message, $user_message);


echo $response;



?>