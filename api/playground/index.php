<?php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playground</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <link rel="stylesheet" href="/assets/css/github-dark.min.css">
    <style>
        .playground-card {
            width: 100%;
            max-width: 800px;
            height: 100%;
        }

        .flex {
            display: flex;
            flex-direction: column;
            align-content: center;
        }

        .flex-grow {
            flex-grow: 1;
        }

        .flex-row {
            flex-direction: row;
        }

        .btn {
            width: fit-content;
        }

        .backdrop {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .highlights {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow-y: scroll;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 10px;
            background-color: #212529;
            color: #fff;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.875rem;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        #apiData {
            z-index: 2;
            background-color: transparent;
            color: transparent;
            caret-color: transparent;
            animation: caretBlink 1s infinite
        }

        @keyframes caretBlink {
            0% {
                caret-color: transparent;
            }

            100% {
                caret-color: #fff;
            }
        }

        #underlay {
            z-index: 1;
        }

        .stale {
            opacity: 0.5;
        }

        .hidden {
            display: none;
        }

        #submitButton {
            margin: 0;
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="container flex">
        <div class="card playground-card flex">
            <div class="card-header flex flex-row">
                <img src='/assets/images/icon.png' width="24" height="24" class="d-inline-block align-top" alt="">
                <p>&nbsp;ChatFlow Playground</p>
            </div>
            <div class="card-body flex flex-grow">
                <form id="apiForm" class="flex flex-grow">
                    <div class="mb-3">
                        <label for="apiEndpoint" class="form-label">Endpoint</label>
                        <select class="form-select" id="apiEndpoint">
                            <option value="/api/v1/messages/">/api/v1/messages/</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="apiToken" placeholder="Enter a ChatFlow Token here...">
                    </div>
                    <div class="mb-3 flex-grow">
                        <div class="backdrop">
                            <div id="underlay" class="form-control flex-grow highlights">Enter Request JSON here...</div>
                            <textarea class="form-control flex-grow highlights" id="apiData"></textarea>
                        </div>
                    </div>
                    <div class="mb-3 flex flex-row">
                        <div id="apiDataError" class="mb-3 flex flex-row flex-grow">
                            <p>✅&nbsp;</p>
                            <p class='flex-grow'>JSON Validator Ready!</p>
                        </div>
                        <button type="submit" id="submitButton" class="btn hidden">Send Request</button>
                    </div>
                    <div class="mb-3 flex-grow">
                        <div class="backdrop">
                            <div class="form-control flex-grow highlights stale" id="apiResponse">Response will appear here...</div>
                        </div>
                    </div>
                    <div class="mb-3 flex flex-row">
                        <button type="button" id="clearButton" class="btn btn-secondary">Clear</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                ChatFlow &copy; 2023. All rights reserved.
            </div>
        </div>
        <script src="/assets/js/highlight.min.js"></script>
        <script src="/assets/js/json.min.js"></script>
        <script>
            document.getElementById('apiForm').addEventListener('submit', function(event) {
                event.preventDefault();
            });

            var underlay = document.getElementById('underlay');
            var inputBox = document.getElementById('apiData');
            var inputError = document.getElementById('apiDataError');
            var apiResponse = document.getElementById('apiResponse');
            var lastValue = inputBox.value;
            inputBox.addEventListener('input', function(event) {
                if (lastValue === inputBox.value) {
                    return;
                }
                apiResponse.classList.add('stale');
                try {
                    JSON.parse(inputBox.value);
                    var start = inputBox.selectionStart;
                    var end = inputBox.selectionEnd;
                    var scrollTop = inputBox.scrollTop;
                    inputBox.value = JSON.stringify(JSON.parse(inputBox.value), null, 4);
                    inputBox.selectionStart = start;
                    inputBox.selectionEnd = end;
                    inputBox.scrollTop = scrollTop;
                    inputError.innerHTML = "<p>✅&nbsp;</p><p class='flex-grow'>JSON is valid!</p>";
                    document.getElementById('submitButton').classList.remove('hidden');
                } catch (error) {
                    var errorLine = error.message.split('\n')[0];
                    inputError.innerHTML = "<p>❌&nbsp;</p><p class='flex-grow'>" + errorLine + "</p>";
                    document.getElementById('submitButton').classList.add('hidden');
                }
                underlay.innerHTML = inputBox.value;
                hljs.highlightElement(underlay);
            });

            inputBox.addEventListener('scroll', function(event) {
                underlay.scrollTop = inputBox.scrollTop;
            });

            document.getElementById('submitButton').addEventListener('click', function(event) {
                var apiEndpoint = document.getElementById('apiEndpoint').value;
                var apiToken = document.getElementById('apiToken').value;
                var apiData = document.getElementById('apiData').value;
                apiResponse.classList.remove('stale');
                apiResponse.value = "Loading...";
                lastValue = apiData;
                fetch(apiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': apiToken
                        },
                        body: apiData
                    }).then(response => {
                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let responseText = '';

                        function read() {
                            return reader.read().then(({
                                done,
                                value
                            }) => {
                                if (done) {
                                    apiResponse.innerHTML = responseText.replace(/\0/g, '');
                                    hljs.highlightElement(apiResponse);
                                    apiResponse.scrollTop = apiResponse.scrollHeight;
                                    return;
                                }
                                responseText += decoder.decode(value);
                                responseText = responseText.replace(/\0/g, '');
                                apiResponse.innerHTML = responseText;
                                hljs.highlightElement(apiResponse);
                                apiResponse.scrollTop = apiResponse.scrollHeight;
                                return read();
                            });
                        }
                        return read();
                    })
                    .catch(error => {
                        if (error.name !== 'AbortError') {
                            console.error(error);
                        }
                    });
            });
            document.getElementById('clearButton').addEventListener('click', function(event) {
                document.getElementById('apiEndpoint').value = "/api/v1/messages/";
                document.getElementById('underlay').innerHTML = "Enter Request JSON here...";
                document.getElementById('apiData').value = "";
                document.getElementById('apiResponse').value = "Response will appear here...";
            });
        </script>
</body>

</html>