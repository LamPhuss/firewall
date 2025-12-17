<?php

namespace OPNsense\RemoteAccess;

use OPNsense\Base\IndexController;

class ViewerController extends IndexController
{
    /**
     * Viewer page - displays Guacamole session in iframe
     */
    public function indexAction()
    {
        $connectionId = $this->request->get('c', 'string', '');

        if (empty($connectionId)) {
            $this->view->error = 'Missing connection ID';
            $this->view->pick('OPNsense/RemoteAccess/error');
            return;
        }

        // Get auth token from Guacamole
        $token = $this->getGuacamoleToken();

        if (!$token) {
            $this->view->error = 'Failed to authenticate with Guacamole';
            $this->view->pick('OPNsense/RemoteAccess/error');
            return;
        }

        // Store token in session
        $sessionId = bin2hex(random_bytes(16));
        $this->session->set('guac_session_' . $sessionId, [
            'token' => $token,
            'connection_id' => $connectionId,
            'expires' => time() + 3600,
            'created' => time()
        ]);

        $this->view->sessionId = $sessionId;
        $this->view->connectionId = $connectionId;
        
        // ✅ Pass nginx port
        $this->view->nginxPort = 444;

        $this->view->pick('OPNsense/RemoteAccess/viewer');
    }

    private function getGuacamoleToken()
    {
        // ✅ Call Guacamole via nginx proxy
        $authUrl = 'https://127.0.0.1:444/guacamole/api/tokens';

        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'username' => 'guacadmin',
            'password' => 'guacadmin'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Self-signed cert
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            return false;
        }

        $authData = json_decode($response, true);
        return $authData['authToken'] ?? false;
    }

    /**
     * Proxy endpoint - redirects to nginx with token
     */
    public function proxyAction()
    {
        $this->view->disable();
        
        $sessionId = $this->request->get('s', 'string', '');

        if (empty($sessionId)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Invalid session']);
            return;
        }

        $sessionKey = 'guac_session_' . $sessionId;
        $sessionData = $this->session->get($sessionKey);

        if (!$sessionData || !isset($sessionData['token'])) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Session not found']);
            return;
        }

        if ($sessionData['expires'] < time()) {
            $this->session->remove($sessionKey);
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Session expired']);
            return;
        }

        // ✅ Build URL to nginx proxy
        $token = $sessionData['token'];
        $connectionId = $sessionData['connection_id'];
        $nginxUrl = "https://" . $_SERVER['HTTP_HOST'] . ":444/guacamole/#/client/{$connectionId}?token={$token}";

        header('Location: ' . $nginxUrl);
        exit;
    }
}