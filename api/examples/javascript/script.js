// script.js

var json_data;
var url;
var token;
var initial_response;
var session_id;

document.addEventListener('DOMContentLoaded', function () {
    fetch('../tester.json')
        .then(response => response.json())
        .then(data => {
            json_data = data;
            url = json_data.url;
            token = json_data.token;
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(json_data)
            })
                .then(response => response.json())
                .then(data => {
                    initial_response = data;
                    session_id = initial_response.session_id;
                    appendMessage(initial_response.choices[0].message.role, initial_response.choices[0].message.content);
                });
        });

    // Event listener for send button click
    document.getElementById("send-btn").addEventListener("click", sendMessage);

    // Event listener for enter key press in the input field
    document.getElementById("user-input").addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            sendMessage();
        }
    });
});

// Function to handle sending a message
function sendMessage() {
    const userInput = document.getElementById("user-input").value;
    if (!userInput) return; // Ignore empty messages

    // Append the user message to the chat container
    appendMessage("user", userInput);

    $info = {
        "session": session_id,
    }

    // Clear the input field after sending the message
    document.getElementById("user-input").value = "";
}

// Function to append a message to the chat container
function appendMessage(role, content) {
    const chatContainer = document.getElementById("chat-container");

    // Create a new message element with the role and content
    const messageElement = document.createElement("div");
    messageElement.classList.add("message");
    messageElement.innerHTML = `<span class="role">${role}: </span>${content}`;

    // Append the message element to the chat container
    chatContainer.appendChild(messageElement);

    // Scroll to the bottom of the chat container to show the latest message
    chatContainer.scrollTop = chatContainer.scrollHeight;
}