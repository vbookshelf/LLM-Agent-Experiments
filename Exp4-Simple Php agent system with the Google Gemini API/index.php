<?php

// Your Google Gemini API Key
$apiKey = 'YOUR-API-KEY';

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent";


function make_api_call($message_history) {
	
	
	global $apiKey;
	global $url;
	
	
	// Define data
	//$data = array();
	$data = array(
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
		
	} else {
		
	    $generatedText = json_decode($result, true);
	}
	
	return $generatedText;
    
}



function extract_text_from_response($response) {
	
	$text = $response["candidates"][0]['content']['parts'][0]['text'];
	
	return $text;
	
}


function run_agent_without_memory($system_message, $prompt) {

	$user_message = $system_message . " " . $prompt;
	

	$my_message1 = array("text" => $prompt);
	
	$parts_list = array();
	$parts_list[] = $my_message1;
	
	$message_history = array();
	$message_history[] = array("role" => "user", "parts" => $parts_list);
	
	// Call the functions
	$json_response = make_api_call($message_history);
	$response_text = extract_text_from_response($json_response);
	
	return $response_text;
	
}


	
function run_agent_with_memory($message_history) {
	
	// Call the functions
	$json_response = make_api_call($message_history);
	$response_text = extract_text_from_response($json_response);
	
	return $response_text;
	
}


function process_json_output($json_output) {
	
	// Check if the output is a json string
	if ($json_output !== null) {
		
		// Convert '{}' into {}
		$response_text = json_decode($json_output, true);
		
	} else if (is_object($json_output)) {
					
		// Convert to JSON string
		$json_output = json_encode($json_output);
		
		// Convert '{}' into {}
		$response_text = json_decode($json_output, true);
	} 
	
	return $response_text;
			
}




// Run the agent system
//----------------------

$system_message = "You are a helpful assistant.";

$user_message = "Hello";

$response = run_agent_without_memory($system_message, $user_message);




echo $response;




?>