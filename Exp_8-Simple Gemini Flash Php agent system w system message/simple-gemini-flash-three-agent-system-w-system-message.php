<?php

// Your Google Gemini API Key
$apiKey = 'YOUR-API-KEY';

// Gemini 1.5 Pro - Limit: 2 requests per minute
// Includes system message support
//$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent";

// Gemini 1.5 Flash - Limit: 15 requests per minute
// Includes system message support
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent";


/*
1- Trying to handle ```json ``` outputs just creates errors and crashes.
Rather use code to replace these things just before displaying on the page.
We can manually remove curly braces etc. from the output that gets classified as is_plain_text.

*/


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

		
		// The output is plain text
		} else {
			
			$output_text = $response_text; 
		}
		
		
		
		return array($output_type, $output_text);
		
		
	} else {
		
		return array("is_plain_text", "api_error");
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







//////////////////////////////
//Run the three Agent System
//////////////////////////////


// Objective:
//-----------
// Create a simple agentic system to help Spanish
// speaking people practice english through text chat.
// The system has three agents - one for chat, one for correcting the user's message
// and one for translating the chat agent's responses into Spanish.
// The chat agent has memory. The other two agents don't have memory.


// Define the agents
// ------------------

// Chat Agent
$chat_agent_system_message = <<<EOT
You are Dracula. Respond like Dracula.
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


$message_history = array();


// Run the agent system
//----------------------

$user_message = "Hello";

// Run the chat agent
//---------------------

// Create the first message and add it to the message history
$my_message1 = array("text" => $user_message);
$parts_list = array();
$parts_list[] = $my_message1;

$message_history[] = array("role" => "user", "parts" => $parts_list);

$chat_agent_response_list = run_agent_with_memory($chat_agent_system_message, $message_history);
// This response is always plain text
$chat_agent_response = $chat_agent_response_list[1];


// Update the chat history
$message_dict = array("text" => $chat_agent_response);
$parts_list = array();
$parts_list[] = $message_dict;
$message_history[] = array("role" => "model", "parts" => $parts_list);


// Run the proofreader agent
//---------------------------
// Checks the user message for errors
$user_message_hash = "###" . $user_message . "###";
$corrected_user_message_list = run_agent_without_memory($proofreader_agent_system_message, $user_message_hash);

echo $corrected_user_message_list[0];


// Process the response
if ($corrected_user_message_list[0] != "is_plain_text") {
	// It is json
	$corrected_user_message = $corrected_user_message_list[1]["correction"];
} else {
	// It is plain text
	$corrected_user_message = $corrected_user_message_list[1];
}



// Run the translation agent
//---------------------------
// Translates the chat agent's response into Spanish
$translated_chat_agent_response_list = run_agent_without_memory($translation_agent_system_message, $chat_agent_response);

// Process the response
if ($translated_chat_agent_response_list[0] != "is_plain_text") {
	// It is json
	$translated_chat_agent_response = $translated_chat_agent_response_list[1]["translation"];
} else {
	// It is plain text
	$translated_chat_agent_response = $translated_chat_agent_response_list[1];
}



echo "user_message: ";
echo $user_message;
echo "<br>";

echo "corrected_user_message: ";
echo $corrected_user_message;
echo "<br>";

echo "chat_agent_response: ";
echo $chat_agent_response;
echo "<br>";

echo "translated_chat_agent_response: ";
echo $translated_chat_agent_response;
echo "<br>";
echo "<br>";
echo "<br>";
print_r($message_history);


// Run the agent system
//----------------------

$user_message = "How r you?";

// Run the chat agent
//---------------------

// Create the first message and add it to the message history
$prompt = $chat_agent_system_message . " " . $user_message;
$my_message1 = array("text" => $prompt);
$parts_list = array();
$parts_list[] = $my_message1;

$message_history[] = array("role" => "user", "parts" => $parts_list);

$chat_agent_response_list = run_agent_with_memory($chat_agent_system_message, $message_history);
// This response is always plain text
$chat_agent_response = $chat_agent_response_list[1];


// Update the chat history
$message_dict = array("text" => $chat_agent_response);
$parts_list = array();
$parts_list[] = $message_dict;
$message_history[] = array("role" => "model", "parts" => $parts_list);


// Run the proofreader agent
//---------------------------
// Checks the user message for errors
$user_message_hash = "###" . $user_message . "###";
$corrected_user_message_list = run_agent_without_memory($proofreader_agent_system_message, $user_message_hash);

echo $corrected_user_message_list[0];


// Process the response
if ($corrected_user_message_list[0] != "is_plain_text") {
	// It is json
	$corrected_user_message = $corrected_user_message_list[1]["correction"];
} else {
	// It is plain text
	$corrected_user_message = $corrected_user_message_list[1];
}



// Run the translation agent
//---------------------------
// Translates the chat agent's response into Spanish
$translated_chat_agent_response_list = run_agent_without_memory($translation_agent_system_message, $chat_agent_response);

// Process the response
if ($translated_chat_agent_response_list[0] != "is_plain_text") {
	// It is json
	$translated_chat_agent_response = $translated_chat_agent_response_list[1]["translation"];
} else {
	// It is plain text
	$translated_chat_agent_response = $translated_chat_agent_response_list[1];
}



echo "user_message: ";
echo $user_message;
echo "<br>";

echo "corrected_user_message: ";
echo $corrected_user_message;
echo "<br>";

echo "chat_agent_response: ";
echo $chat_agent_response;
echo "<br>";

echo "translated_chat_agent_response: ";
echo $translated_chat_agent_response;






?>

