<?php
session_start();

include "name_config.php";
include "php_utils_revised.php";


// PHP Config
//------------
	
// Your Gemini API Key
$apiKey = 'YOUR-API-KEY';

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent";




// Define the agents
// ------------------

// Chat Agent
$chat_agent_system_message = <<<EOT
Your name is Gemini. You are a virtual assistant. Keep your responses short and conversational.
EOT;



// Proofreader Agent	
$proofreader_agent_system_message = <<<EOT
You are a highly skilled english proofreader. You will be given text delimited by triple hash tags (###). You task is to correct the spelling, punctuation and grammar errors. Think step by step. Return your corrected text. If the original text does not contain any errors then respond with: "---". 
	Respond in a consistent format. Output a JSON string with the following schema:
{
"correction": <"Your corrected version of the user_message or '---'.">
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





// If the list does NOT exist, create an empty array
if (!isset($_SESSION['message_history'])) {
	
	// Create a messages list
	$_SESSION['message_history'] = array();
	$message_history = $_SESSION['message_history'];
	

}




// This function cleans and secures the user input
function test_input(&$data) {
		$data = trim($data);
		//$data = stripslashes($data);
		$data = strip_tags($data);
		//$data = htmlentities($data);
		
		return $data;
	}



	


// This code is triggered when the user submits a message
//--------------------------------------------------------

if (isset($_REQUEST["my_message"]) && empty($_REQUEST["robotblock"])) {
	
	// Get the user's first language
	$translation_language = $_REQUEST["user_language"];
	
	
	// Get the user's message
	$user_message = $_REQUEST["my_message"];
	
	
	// Clean and secure the user's text input
	$user_message = test_input($user_message);
	
	// Run the chat agent
	//-------------------
	
	// Create the first message and add it to the message history
	//$prompt = $chat_agent_system_message . " " . $user_message;
	
	$prompt = $user_message;
	
	
	/*
	// Only add the system message to the first user message
	
	if (count($message_history) == 0) {
		
		// The message history list is empty
		$prompt = $chat_agent_system_message . " " . $user_message;
		
    	
	} else {
		
		// The message history list is not empty
		$prompt = $user_message;
	    
	}
	
	*/
	
	
	$my_message1 = array("text" => $prompt);
	$parts_list = array();
	$parts_list[] = $my_message1;
	$message_history[] = array("role" => "user", "parts" => $parts_list);
	
	$chat_agent_response_list = run_agent_with_memory($message_history);
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
	
	// Remove any html	
	$user_message = strip_tags($user_message);
	
	$text_to_proofread = "###" . $user_message . "###";
	$corrected_user_message_list = run_agent_without_memory($proofreader_agent_system_message, $text_to_proofread);
	
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
	
	// Remove any html
	$chat_agent_response = strip_tags($chat_agent_response);
	
	$translated_chat_agent_response_list = run_agent_without_memory($translation_agent_system_message, $chat_agent_response);
	
	
	$text_type = $translated_chat_agent_response_list[0];
	
	
	// Process the response
	if ($translated_chat_agent_response_list[0] != "is_plain_text") {
		// It is json
		$translated_chat_agent_response = $translated_chat_agent_response_list[1]["translation"];
	} else {
		// It is plain text
		$translated_chat_agent_response = $translated_chat_agent_response_list[1];
	}
	
	

	
	$final_text = "
		<p class='lighter-black'><i>Correction: {$corrected_user_message}</i></p>
			    <p>{$chat_agent_response}</p>
			    <p>{$translated_chat_agent_response}</p>
				 <p>{$text_type}</p>
					";
	
	
	// Display a message on the page
	// *** This is what we need to process on the index.php page ***
	$response = array('success' => true, 'chat_text' => $final_text, 'translation_language' => $translation_language);
	
  	echo json_encode($response);

	
}

?>