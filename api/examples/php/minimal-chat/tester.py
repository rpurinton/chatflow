# this is supposed to work but my python wont connect to HTTPS
import json
import requests

# Load the JSON configuration file
with open("tester.json") as file:
    data = json.load(file)

# Send the initial request
response = requests.post(data['url'], headers={"Content-Type": "application/json", "Authorization": "Bearer " + data['token']}, data=json.dumps(data)).json()
if 'choices' in response and response['choices']:
    print("Press CTRL+C to exit...\n" + response['choices'][0]['text'] + "\n> ")
else:
    print("Unable to find response text!")
    exit()

# Interact with the model
try:
    while True:
        user_input = input("> ")
        payload = {
            'session': response['session_id'],
            'messages': [{'role': 'user', 'content': user_input}],
            'passthru': True,
            'stream': True
        }
        response = requests.post(data['url'], headers={"Content-Type": "application/json", "Authorization": "Bearer " + data['token']}, data=json.dumps(payload)).json()
        for message in response['messages']:
            print(message['content'])
except KeyboardInterrupt:
    pass