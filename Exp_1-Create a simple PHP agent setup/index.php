<?php

// Your Groq API Key
$apiKey = 'YOUR-API-KEY';


$model_type = "llama3-70b-8192";
$url = 'https://api.groq.com/openai/v1/chat/completions';


// If this number PLUS the number of tokens in the message_history exceed
// the max value for the model (e.g. 4096) then the response from the api will
// an error dict instead of the normal message response. Thos error dict will
// contain an error message saying that the number of tokens for 
// this model has been exceeded.
$max_tokens = 500;

// 0 to 2. Higher values like 0.8 will make the output more random, 
// while lower values like 0.2 will make it more focused and deterministic.
// Alter this or top_p but not both.
$temperature = 0.3;

// -2 to 2. Higher values increase the model's likelihood to talk about new topics.
// Reasonable values for the penalty coefficients are around 0.1 to 1.
$presence_penalty = 0; 

// -2 to 2. Higher values decrease the model's likelihood to repeat the same line verbatim.
// Reasonable values for the penalty coefficients are around 0.1 to 1.
$frequency_penalty = 1;


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




////////////////////////

// Run the agent system

////////////////////////

// Create and run an agent
$system_message = "You are a helpful assistant.";
$prompt = "Hello";
$response_text = run_agent_without_memory($system_message, $prompt);

//echo $response;
//print_r($response);

echo $response_text;


?>


