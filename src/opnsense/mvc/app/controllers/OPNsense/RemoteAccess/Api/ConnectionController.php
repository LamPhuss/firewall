<?php

namespace OPNsense\RemoteAccess\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\RemoteAccess\RemoteAccess;

class ConnectionController extends ApiControllerBase
{
    public function testAction($uuid)
    {
        if ($this->request->isPost()) {
            $model = new RemoteAccess();
            $connection = $model->getConnectionByUuid($uuid);
            
            if ($connection === null) {
                return array(
                    "status" => "error",
                    "message" => "Connection not found"
                );
            }

            if (!$model->validateConnection($connection->getNodes())) {
                return array(
                    "status" => "error",
                    "message" => "Invalid connection configuration"
                );
            }

            $protocol = (string)$connection->protocol;
            $hostname = (string)$connection->hostname;
            $port = (string)$connection->port;

            $result = $this->testConnectionPort($hostname, $port, $protocol);
            
            return array(
                "status" => $result ? "success" : "failed",
                "message" => $result ? "Connection successful" : "Connection failed"
            );
        }
        
        return array("status" => "error", "message" => "Invalid request");
    }

    private function testConnectionPort($hostname, $port, $protocol)
    {
        $timeout = 5;
        $errno = 0;
        $errstr = '';
        
        $socket = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
        
        if ($socket) {
            fclose($socket);
            return true;
        }
        
        return false;
    }

    public function getTokenAction($uuid)
    {
        $model = new RemoteAccess();
        $connection = $model->getConnectionByUuid($uuid);
        
        if ($connection === null) {
            return array(
                "status" => "error",
                "message" => "Connection not found"
            );
        }

        $config = $model->generateGuacamoleConfig($connection->getNodes());
        $token = $this->generateConnectionToken($config);

        return array(
            "status" => "success",
            "token" => $token,
            "protocol" => $config['protocol']
        );
    }

    private function generateConnectionToken($config)
    {
        $data = array(
            'connection' => $config,
            'timestamp' => time(),
            'random' => bin2hex(random_bytes(16))
        );
        
        return base64_encode(json_encode($data));
    }
}
