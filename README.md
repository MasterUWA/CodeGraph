Step 3: Execution and Verification
To bring the entire system online on your local machine, execute these commands.

Start the Python Engine: Open your terminal and run the API.
python api.py
The terminal will output that the Flask server is running on [http://127.0.0.1:5000](http://127.0.0.1:5000). Leave this terminal window open.

Trigger from PHP: In your web application's upload script, ensure your cURL request is pointing to [http://127.0.0.1:5000/scan](http://127.0.0.1:5000/scan).

Upload a File: Go to your browser, open your PHP dashboard, and upload a .php file.

The PHP script will parse the file using nikic/php-parser, send the JSON over port 5000 to Flask, and Python will instantly convert the JSON into tensors, run the neural network, and return the vulnerability_probability back to your dashboard to be displayed.
