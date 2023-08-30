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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@10.7.2/styles/default.min.css">
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

        #apiData,
        #apiResponse {
            height: 100%;
        }

        .btn {
            margin-bottom: 0.5rem;
            max-width: 150px;
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
                        <textarea class="form-control flex-grow" id="apiData" placeholder="Enter Request JSON here..."></textarea>
                    </div>
                    <div class="mb-3 flex-grow">
                        <textarea class="form-control" id="apiResponse" readonly>Response will appear here...</textarea>
                    </div>
                    <div class="mb-3 flex-row">
                        <button type="submit" id="submitButton" class="btn btn-primary">Send Request</button>
                        <button type="button" id="clearButton" class="btn btn-secondary">Clear</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                ChatFlow &copy; 2023. All rights reserved.
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/ajv@8.6.2/dist/ajv.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/highlight.js@10.7.2/highlight.min.js"></script>
        <script>
            document.getElementById('apiForm').addEventListener('submit', function(event) {
                event.preventDefault();
            });

            var inputBox = document.getElementById('apiData');
            var ajv = new Ajv(); // Create an instance of Ajv
            inputBox.addEventListener('change', function(event) {
                hljs.highlightBlock(inputBox);
                try {
                    JSON.parse(inputBox.value);
                    console.log('JSON is valid');
                } catch (error) {
                    console.log('JSON is invalid');
                    console.log(error);
                }
            });

            document.getElementById('submitButton').addEventListener('click', function(event) {
                var apiEndpoint = document.getElementById('apiEndpoint').value;
                var apiToken = document.getElementById('apiToken').value;
                var apiData = document.getElementById('apiData').value;
                var apiResponse = document.getElementById('apiResponse');
                apiResponse.value = "Loading...";

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
                                    apiResponse.value = responseText.replace(/\0/g, '');
                                    return;
                                }
                                responseText += decoder.decode(value);
                                responseText = responseText.replace(/\0/g, '');
                                apiResponse.value = responseText;
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
                document.getElementById('apiData').value = "";
                document.getElementById('apiResponse').value = "";
            });
        </script>
</body>

</html>