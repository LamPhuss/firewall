<?php

namespace OPNsense\RemoteAccess\Api;

use OPNsense\Base\ApiMutableModelControllerBase;
use OPNsense\RemoteAccess\RemoteAccess;

class SettingsController extends ApiMutableModelControllerBase
{
    protected static $internalModelName = 'remoteaccess';
    protected static $internalModelClass = 'OPNsense\RemoteAccess\RemoteAccess';

    public function searchConnectionAction()
    {
        return $this->searchBase(
            'connections.connection',
            array('enabled', 'name', 'description', 'protocol', 'hostname', 'port'),
            'name'
        );
    }

    public function getConnectionAction($uuid = null)
    {
        return $this->getBase('connection', 'connections.connection', $uuid);
    }

    public function addConnectionAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            if (isset($data['remoteaccess']['connections']['connection'])) {
                $this->request->setPost('connection', $data['remoteaccess']['connections']['connection']);
            }
        }
        return $this->addBase('connection', 'connections.connection');
    }

    public function setConnectionAction($uuid)
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            if (isset($data['remoteaccess']['connections']['connection'])) {
                $this->request->setPost('connection', $data['remoteaccess']['connections']['connection']);
            }
        }
        return $this->setBase('connection', 'connections.connection', $uuid);
    }

    public function delConnectionAction($uuid)
    {
        return $this->delBase('connections.connection', $uuid);
    }

    public function toggleConnectionAction($uuid, $enabled = null)
    {
        return $this->toggleBase('connections.connection', $uuid, $enabled);
    }

    /**
     * Get viewer URL for connection
     * Returns clean URL without token
     */
    public function getConnectionUrlAction($uuid)
    {
        if (!$this->request->isGet()) {
            return ["result" => "failed", "error" => "Invalid request method"];
        }

        /** @var RemoteAccess $model */
        $model = $this->getModel();
        $connection = $model->getConnectionByUuid($uuid);

        if (!$connection) {
            return ["result" => "failed", "error" => "Connection not found"];
        }

        // ✅ For now, use fixed guacamole_id
        // TODO: Get from connection->guacamole_id when field is added
        $guacamoleId = 'MQBjAHBvc3RncmVzcWw';

        if (empty($guacamoleId)) {
            return ["result" => "failed", "error" => "Guacamole connection ID not configured"];
        }

        // ✅ Return clean URL (no token)
        $url = "/ui/remoteaccess/viewer?c=" . urlencode($guacamoleId);

        return [
            "result" => "success",
            "url" => $url,
            "connection_name" => (string)$connection->name
        ];
    }
}