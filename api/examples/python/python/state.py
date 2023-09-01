import reflex as rx
import json
import requests


# Location of the config file
json_file = "./config.json"

with open(json_file) as f:
        config_data = json.load(f)

url = config_data["url"]
token = config_data["token"]
headers = {
    "Content-Type": "application/json",
    "Authorization": f"Bearer {token}"
}

response = requests.post(url, headers=headers, json=config_data)
response_data = response.json()

session_id = response_data.get("session_id")
if not session_id:
    raise ValueError("Unable to find session ID!")

response_text = response_data["choices"][0]["message"]["content"]

def send_request(url, headers, data):
    response = requests.post(url, headers=headers, json=data, stream=False)
    return_data = response.json()
    return return_data

class State(rx.State):

    # The current question being asked.
    question: str

    # Keep track of the chat history as a list of (question, answer) tuples.
    chat_history: list[tuple[str, str]]

    def answer(self):
        post_data = {
            "session": session_id,
            "messages": [
                {
                    "role": "user",
                    "content": self.question
                }
            ],
            "passthru": True,
            "prompt_tokens": 512,
            "stream": False  # Always set stream to False
        }

        response_data = send_request(url, headers, post_data)

        response = response_data["choices"][0]["message"]["content"]


        # Add to the answer as the chatbot responds.
        answer = ""
        self.chat_history.append((self.question, answer))

        # Clear the question input.
        self.question = ""
        # Yield here to clear the frontend input before continuing.
        yield

        answer += response
        self.chat_history[-1] = (
            self.chat_history[-1][0],
            answer,
        )