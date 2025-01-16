#!/usr/bin/php
<?php

// Edit below variables to your own liking
const MY_DOMAIN = "example.com"; // your domain
const MAX_MESSAGES = 5; // number of possible mails globaly per interval
const INTERVAL = 3600; //interval in seconds
const CUSTOM_COMMAND = ''; // custom command to execute if you send more mails than the limit (like sendmail). Run this in a background so that script will not wait for execution (command > /dev/null 2>&1 &) Mail over the limit is rejected anyway.

$address = "127.0.0.1";
$port = 10031;
$counter = array();

// Create a socket
$server = stream_socket_server("tcp://$address:$port", $errno, $errstr);
if (!$server) {
    die("Error: $errstr ($errno)\n");
}

echo "Server started at $address:$port\n";

while (true) {
    $preg_domain = preg_quote(MY_DOMAIN);
    while ($conn = @stream_socket_accept($server, 2)) {
        if($conn === false)
            continue;

        // Read the incoming request
        $request = fread($conn, 1024);
        $response = "action=DUNNO\n\n"; // Default response

        // Log the request (for debugging)
        echo "Received request:\n";
        
        $lines = explode("\n", $request);
        $sender_match = false;
        $recipient_match = false;
        
        foreach($lines as $line) {
            if(preg_match('/^sender=.*$/', $line)) {
                echo "\t$line\n";
                if(preg_match('/^sender=[\w.+-]+@' . $preg_domain . '$/', $line))
                    $sender_match = true;
            }
            if(preg_match('/^recipient=.*$/', $line)) {
                echo "\t$line\n";
                if(!preg_match('/^recipient=[\w.+-]+@' . $preg_domain . '$/', $line))
                    $recipient_match = true;
            }
        }

        if($sender_match && $recipient_match) {
            $now = time();
            foreach($counter as $key => $timestamp) {
                if($timestamp < $now - INTERVAL)
                    unset($counter[$key]);
            }
            $counter[] = time();
            $count = count($counter);
            echo "\tMessages to outside domains: $count\n";
            if($count > MAX_MESSAGES) {
                $response = "action=REJECT message limit to outside domains reached!\n\n";
                if(CUSTOM_COMMAND != '')
                    exec(CUSTOM_COMMAND);
            }
        }
        else {
            echo "\tNo rule matched\n";
        }
        
        echo "\tReturned: " . $response;

        // Write the response to the client
        fwrite($conn, $response);

        // Close the connection
        fclose($conn);
    }
    $response = "DUNNO\n\n"; // Default response
}

fclose($server);
?>

