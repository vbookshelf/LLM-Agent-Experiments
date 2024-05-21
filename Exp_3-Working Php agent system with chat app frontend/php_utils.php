<?php

function make_api_call($message_history) {
	
	
	global $apiKey;
	global $url;
	global $model_type;
	global $max_tokens;
	global $temperature;
	global $presence_penalty;
	global $frequency_penalty;
	
	
	$headers = array(
	    "Authorization: Bearer {$apiKey}",
	    "Content-Type: application/json"
	);
	
	// Define data
	$data = array();
	$data["model"] = $model_type;
	$data["messages"] = $message_history;
	$data["max_tokens"] = $max_tokens;
	$data["temperature"] = $temperature;
	$data["presence_penalty"] = $presence_penalty;
	$data["frequency_penalty"] = $frequency_penalty;
	
	
	
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
	
	$text = $response['choices'][0]['message']['content'];
	
	return $text;
	
}


function run_agent_without_memory($system_message, $prompt) {

	// Set up the system message
	$message_history = array();
	$message_history[] = array("role" => "system", "content" => $system_message);
	
	// Add the user message
	$message_history[] = array("role" => "user", "content" => $prompt);
	
	// Call the functions
	$json_response = make_api_call($message_history);
	$response_text = extract_text_from_response($json_response);
	
	return $response_text;
	
}


/*
$message_history = array();
$message_history[] = array("role" => "system", "content" => $system_message);
$message_history[] = array("role" => "user", "content" => $prompt);
*/

	
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

?>