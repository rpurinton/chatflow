<?php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Playground</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                API Playground
            </div>
            <div class="card-body">
                <form id="apiForm">
                    <div class="mb-3">
                        <label for="apiEndpoint" class="form-label">API Endpoint</label>
                        <select class="form-select" id="apiEndpoint">
                            <option value="/api/v1/messages">/api/v1/messages</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="apiToken" class="form-label">API Token</label>
                        <input type="text" class="form-control" id="apiToken" placeholder="Enter API Token">
                    </div>
                    <div class="mb-3">
                        <label for="apiData" class="form-label">API Data</label>
                        <textarea class="form-control" id="apiData" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="apiResponse" class="form-label">API Response</label>
                        <textarea class="form-control" id="apiResponse" rows="3" readonly></textarea>
                    </div>
                    <button type="submit" id="submitButton" class="btn btn-primary">Send Request</button>
                    <button type="button" id="clearButton" class="btn btn-secondary">Clear</button>
                </form>
            </div>

        </div>

        <script>
            document.getElementById('apiForm').addEventListener('submit', function(event) {
                event.preventDefault();
            });

            document.getElementById('submitButton').addEventListener('click', function(event) {
                var apiEndpoint = document.getElementById('apiEndpoint').value;
                var apiToken = document.getElementById('apiToken').value;
                var apiData = document.getElementById('apiData').value;
                var apiResponse = document.getElementById('apiResponse');
                // use HTTPS
                var apiURL = "https://chatflow.discommand.com" + apiEndpoint;
                var xhr = new XMLHttpRequest();
                xhr.open("POST", apiURL, true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.setRequestHeader("Authorization", apiToken);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        apiResponse.value = xhr.responseText;
                    }
                };
                xhr.send(apiData);
            });
            document.getElementById('clearButton').addEventListener('click', function(event) {
                document.getElementById('apiData').value = "";
                document.getElementById('apiResponse').value = "";
            });
        </script>
</body>

</html>