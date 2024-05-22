<?php


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

	$user_message = $system_message . " " . $prompt;
	

	$my_message1 = array("text" => $user_message);
	
	$parts_list = array();
	$parts_list[] = $my_message1;
	
	$message_history = array();
	$message_history[] = array("role" => "user", "parts" => $parts_list);
	
	// Make an API call
	$response = make_api_call($message_history);
	
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

		
		// The output is plain text
		} else {
			
			$output_text = $response_text; 
		}
		
		
		
		return array($output_type, $output_text);
		
		
	} else {
		
		return array("is_plain_text", "api_error");
	}
	
}


	
function run_agent_with_memory($message_history) {
	
	
	// Make an API call
	$response = make_api_call($message_history);
	
	
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
		
		// The output is plain text
		} else {
			
			$output_text = $response_text; 
		}
		
		
		return array($output_type, $output_text);
		
		
	} else {
		
		return array("is_plain_text", "api_error");
	}
	
	
}


// The model can produce on of three 
// output types:
// - plain text
// - json string
// - json object
//- json with backticks ```json ```
// We need to know the output type in order
// to process the output correctly.
function check_output_type($output) {
	
	if (is_object($output)) {
					
		return "is_json_object";
		
	} else if (is_string($output)) {
		
		// Attempt to decode the JSON string
		$decoded = json_decode($output, true);
		
		if ($decoded !== null) {
			
			return "is_json_string";
			
		} else {
			
			return "is_plain_text";
		}
	}
			
}



?>

