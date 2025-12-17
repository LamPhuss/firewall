<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote Access Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            overflow: hidden;
            background-color: #1a1a1a;
        }
        
        #viewer-container {
            width: 100%;
            height: 100%;
            position: relative;
        }
        
        #viewer-frame {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }
        
        #loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #1a1a1a;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: opacity 0.5s ease;
        }
        
        #loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .spinner {
            border: 4px solid #333;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: #ffffff;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }
        
        #error-message {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #dc3545;
            color: white;
            padding: 20px 40px;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            text-align: center;
            z-index: 2000;
        }
    </style>
</head>
<body>
    <div id="viewer-container">
        <div id="loading-overlay">
            <div class="spinner"></div>
            <div class="loading-text">Connecting to remote host...</div>
        </div>
        
        <div id="error-message"></div>
        
        <!-- ✅ Iframe points to proxy endpoint -->
        <iframe id="viewer-frame" src="about:blank"></iframe>
    </div>

    <script>
        (function() {
            var sessionId = '{{ sessionId }}';
            var connectionId = '{{ connectionId }}';
            
            var iframe = document.getElementById('viewer-frame');
            var loadingOverlay = document.getElementById('loading-overlay');
            var errorMessage = document.getElementById('error-message');
            
            if (!sessionId || !connectionId) {
                showError('Missing session parameters');
                return;
            }
            
            // ✅ Proxy URL - will redirect to nginx:444
            var proxyUrl = '/ui/remoteaccess/viewer/proxy?s=' + encodeURIComponent(sessionId);
            
            console.log('Loading via proxy:', proxyUrl);
            
            iframe.src = proxyUrl;
            
            iframe.onload = function() {
                console.log('Iframe loaded');
                setTimeout(function() {
                    loadingOverlay.classList.add('hidden');
                }, 500);
            };
            
            iframe.onerror = function() {
                console.error('Iframe failed to load');
                showError('Failed to load remote session');
            };
            
            setTimeout(function() {
                if (!loadingOverlay.classList.contains('hidden')) {
                    loadingOverlay.classList.add('hidden');
                }
            }, 10000);
            
            function showError(message) {
                loadingOverlay.classList.add('hidden');
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
            }
        })();
    </script>
</body>
</html>