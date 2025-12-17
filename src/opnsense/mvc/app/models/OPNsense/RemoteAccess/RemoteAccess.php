<?php

namespace OPNsense\RemoteAccess;

use OPNsense\Base\BaseModel;

class RemoteAccess extends BaseModel
{
    public function getConnectionByUuid($uuid): ?object
    {
        foreach ($this->connections->connection->iterateItems() as $key => $connection) {
            if ($key == $uuid) {
                return $connection;
            }
        }
        return null;
    }

    public function validateConnection($connection)
    {
        if (empty($connection['hostname']) || empty($connection['port'])) {
            return false;
        }

        switch ($connection['protocol']) {
            case 'ssh':
                if (empty($connection['username'])) {
                    return false;
                }
                if (empty($connection['password']) && empty($connection['private_key'])) {
                    return false;
                }
                break;
            case 'rdp':
                if (empty($connection['username']) || empty($connection['password'])) {
                    return false;
                }
                break;
        }

        return true;
    }

    public function generateGuacamoleConfig($connection)
    {
        $config = [
            'protocol' => $connection['protocol'],
            'parameters' => []
        ];

        $config['parameters']['hostname'] = $connection['hostname'];
        $config['parameters']['port'] = $connection['port'];

        switch ($connection['protocol']) {
            case 'ssh':
                $config['parameters']['username'] = $connection['username'] ?? '';
                $config['parameters']['password'] = $connection['password'] ?? '';
                if (!empty($connection['private_key'])) {
                    $config['parameters']['private-key'] = $connection['private_key'];
                }
                $config['parameters']['color-scheme'] = $connection['color_scheme'] ?? 'gray-black';
                $config['parameters']['font-size'] = $connection['font_size'] ?? 12;
                $config['parameters']['enable-sftp'] = 'true';
                break;

            case 'vnc':
                if (!empty($connection['password'])) {
                    $config['parameters']['password'] = $connection['password'];
                }
                $config['parameters']['color-depth'] = 24;
                $config['parameters']['swap-red-blue'] = 'false';
                break;

            case 'rdp':
                $config['parameters']['username'] = $connection['username'] ?? '';
                $config['parameters']['password'] = $connection['password'] ?? '';
                $config['parameters']['security'] = 'any';
                $config['parameters']['ignore-cert'] = 'true';
                $config['parameters']['enable-drive'] = 'true';
                $config['parameters']['create-drive-path'] = 'true';
                break;

            case 'telnet':
                $config['parameters']['username'] = $connection['username'] ?? '';
                $config['parameters']['password'] = $connection['password'] ?? '';
                $config['parameters']['color-scheme'] = $connection['color_scheme'] ?? 'gray-black';
                $config['parameters']['font-size'] = $connection['font_size'] ?? 12;
                break;
        }

        return $config;
    }
}
