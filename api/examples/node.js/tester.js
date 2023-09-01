const https = require('https');
const fs = require('fs');

// Load the configuration file
const configFile = __dirname + '/../tester.json';
const config_text = fs.readFileSync(configFile, 'utf8');
const config = JSON.parse(config_text);

// Get the URL and token from the configuration file
const url = config.url;
const token = config.token;

// Send a request to the API
const requestOptions = {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer' + token
  },
  json: config
};

const req = https.request(url, requestOptions, (res) => {
  let responseData = '';
  res.on('data', (chunk) => {
    responseData += chunk;
  });

  res.on('end', () => {
    // Decode the response
    console.log(responseData);
    const response = JSON.parse(responseData);

    // Check if the response contains the session ID
    const sessionID = response.session_id;
    if (!sessionID) {
      throw new Error('Unable to find session ID!');
    }

    // Check if the response contains the response text
    const responseText = response.choices[0].message.content;
    if (!responseText) {
      throw new Error('Unable to find response text!');
    }

    console.log("Press CTRL+C to exit...\n${responseText}\n> ");

    // Loop to interact with the API
    process.stdin.on('data', (userInput) => {
      userInput = userInput.toString().trim();

      // Prepare the data to send to the API
      const postData = {
        session: sessionID,
        messages: [
          {
            role: 'user',
            content: userInput
          }
        ],
        passthru: true,
        prompt_tokens: 512,
        stream: true
      };

      // Send the request to the API
      const requestOptions = {
        ...requestOptions,
        headers: {
          ...requestOptions.headers,
          'Content-Length': JSON.stringify(postData).length
        }
      };
      const req = https.request(url, requestOptions, (res) => {
        res.on('data', (chunk) => {
          const responseData = chunk.toString();
          console.log(responseData);
          console.log('> ');
        });
      });

      req.write(JSON.stringify(postData));
      req.end();
    });
  });
});

req.end();
