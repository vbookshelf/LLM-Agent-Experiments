<?php
session_start();

include "name_config.php";
include "php_utils.php";


// PHP Config
//------------
	
// Your Groq API Key
$apiKey = 'YOUR-API-KEY';


$model_type = "llama3-70b-8192";
$url = 'https://api.groq.com/openai/v1/chat/completions';


// If this number PLUS the number of tokens in the message_history exceed
// the max value for the model (e.g. 4096) then the response from the api will
// an error dict instead of the normal message response. Thos error dict will
// contain an error message saying that the number of tokens for 
// this model has been exceeded.
$max_tokens = 200; //300
$max_tokens_api2 = 500;

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


$suffixes_list = [
    'How can I help you?',
    'How can I assist you today?',
    'How can I help you today?',
    'Is there anything else you would like to chat about?',
    'Is there anything else I can assist you with today?',
    'Is there anything I can help you with today?',
    'Is there anything else you would like to chat about today?',
    'Is there anything else I can assist you with?',
    'What brings you here today?',
    'So, what brings you here today?'
];





// Define the agents
// ------------------

// Chat Agent
$chat_agent_system_message = <<<EOT
Your name is Maiya. You are a helpful assistant. Keep your responses short.
EOT;



// Proofreader Agent	
$proofreader_agent_system_message = <<<EOT
You are a highly skilled english proofreader. You will be given text delimited by triple hash tags (###). You task is to correct the spelling, punctuation and grammar errors. Return your corrected text. If the original text does not contain any errors then respond with: "---". 
	Respond in a consistent format. Output a JSON string with the following schema:
{
"correction": "<Your corrected version of the user_message or "---".>"
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
	
	// Append the system role to the messages list.
	// This will included in every message that get's submitted
	$_SESSION['message_history'][] = array("role" => "system", "content" => $chat_agent_system_message);

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
	//--------------------
	$message_history[] = array("role" => "user", "content" => $user_message);
	
	$chat_agent_response = run_agent_with_memory($message_history);
	
	$message_history[] = array("role" => "assistant", "content" => $chat_agent_response);
	
	
	// Run the proofreader agent
	//--------------------------
	// Checks the user message for errors
	$text_to_proofread = "###" . $user_message . "###";
	$corrected_user_message = run_agent_without_memory($proofreader_agent_system_message, $text_to_proofread);
	
	
	// Run the translation agent
	//---------------------------
	// Translates the chat agent's response into Spanish
	$translated_chat_agent_response = run_agent_without_memory($translation_agent_system_message, $chat_agent_response);
	
	
	
	$final_text = "
		<p class='lighter-black'><i>Correction: {$corrected_user_message}</i></p>
			    <p>{$chat_agent_response}</p>
			    <p>{$translated_chat_agent_response}</p>
					";
	
	
	// Display a message on the page
	// *** This is what we need to process on the index.php page ***
	$response = array('success' => true, 'chat_text' => $final_text, 'translation_language' => $translation_language);
	
  	echo json_encode($response);

	
}

?>