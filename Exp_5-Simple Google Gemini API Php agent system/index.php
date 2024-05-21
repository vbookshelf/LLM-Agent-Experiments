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
	

	$my_message1 = array("text" => $user_message);
	
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





// Objective:
//-----------
// Create a simple agentic system to help Spanish
// speaking people practice english through text chat.


// Define the agents
// ------------------

// Chat Agent
$chat_agent_system_message = <<<EOT
Your name is Maiya. You are a helpful assistant. Keep your responses short.
EOT;



// Proofreader Agent	
$proofreader_agent_system_message = <<<EOT
You are a highly skilled english proofreader. You will be given text delimited by triple hash tags ('###'). You task is to correct the spelling, punctuation and grammar errors. Return your corrected text. If the original text does not contain any errors then respond with: "No errors found". 
	Respond in a consistent format. Output a JSON string with the following schema:
{
"correction": "<Your corrected version of the user_message or "No errors found".>"
}
	
EOT;



// Translation Agent
$translation_agent_system_message = <<<EOT
You are a highly skilled spanish translator. You will be given text. You task is to translate the text into Spanish. Return your translated text.
	Respond in a consistent format. Output a JSON string with the following schema:
{
"translation": "<Your translated version of the text.>"
}
	
EOT;


// Run the agent system
//----------------------

$user_message = "Hello how r you?";

// Run the chat agent

// Create the first message and add it to the message history
$prompt = $chat_agent_system_message . " " . $user_message;
$my_message1 = array("text" => $prompt);
$parts_list = array();
$parts_list[] = $my_message1;
$message_history = array();
$message_history[] = array("role" => "user", "parts" => $parts_list);

$chat_agent_response = run_agent_with_memory($message_history);

// Update the chat history
$message_dict = array("text" => $chat_agent_response);
$parts_list = array();
$parts_list[] = $message_dict;
$message_history[] = array("role" => "model", "parts" => $parts_list);


// Run the proofreader agent
// Checks the user message for errors
$user_message_hash = "###" . $user_message . "###";
$corrected_user_message = run_agent_without_memory($proofreader_agent_system_message, $user_message_hash);

// Run the translation agent
// Translates the chat agent's response into Spanish
$translated_chat_agent_response = run_agent_without_memory($translation_agent_system_message, $chat_agent_response);


echo $user_message;
echo "<br>";
echo $corrected_user_message;
echo "<br>";
echo $chat_agent_response;
echo "<br>";
echo $translated_chat_agent_response;




?>