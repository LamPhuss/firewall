<?php

/*
 * Copyright (C) 2023 Deciso B.V.
 * Copyright (C) 2018 Michael Muenz <m.muenz@gmail.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Wireguard\Api;

use OPNsense\Base\ApiMutableModelControllerBase;
use OPNsense\Core\Config;
use OPNsense\Wireguard\Server;
use OPNsense\Core\Backend;
use OPNsense\Firewall\Util;

class ClientController extends ApiMutableModelControllerBase
{
    protected static $internalModelName = 'client';
    protected static $internalModelClass = '\OPNsense\Wireguard\Client';

    public function pskAction()
    {
        return ['psk' => trim((new Backend())->configdRun('wireguard gen_psk')), 'status' => 'ok' ];
    }

    public function listServersAction()
    {
        if ($this->request->isGet()) {
            $results = ['rows' => [], 'status' => 'ok'];
            foreach ((new Server())->servers->server->iterateItems() as $key => $node) {
                $results['rows'][] = [
                    'uuid' => $key,
                    'name' => (string)$node->name
                ];
            }
            return $results;
        }
        return ['status' => 'failed'];
    }

    public function searchClientAction()
    {
        $servers = $this->request->get('servers');
        $filter_funct = function ($record) use ($servers) {
            return empty($servers) || array_intersect(explode(',', $record->servers), $servers);
        };

        return $this->searchBase('clients.client', null, null, $filter_funct);
    }

    public function getClientAction($uuid = null)
    {
        return $this->getBase('client', 'clients.client', $uuid);
    }

    public function addClientAction()
    {
        return $this->setClientAction(null);
    }

    public function delClientAction($uuid)
    {
        if ($this->request->isPost()) {
            Config::getInstance()->lock();
            $mdl = new Server();
            foreach ($mdl->servers->server->iterateItems() as $key => $node) {
                $peers = array_filter(explode(',', (string)$node->peers));
                if (in_array($uuid, $peers)) {
                    $node->peers = implode(',', array_diff($peers, [$uuid]));
                }
            }
            $mdl->serializeToConfig(false, true);
        }
        return $this->delBase('clients.client', $uuid);
    }

    public function setClientAction($uuid)
    {
        $add_uuid = null;
        if (!empty($this->request->getPost(static::$internalModelName)) && $this->request->isPost()) {
            $servers = [];
            if (!empty($this->request->getPost(static::$internalModelName)['servers'])) {
                $servers = explode(',', $this->request->getPost(static::$internalModelName)['servers']);
            }
            Config::getInstance()->lock();
            $mdl = new Server();
            if (empty($uuid)) {
                // add new client, generate uuid
                $uuid = $mdl->servers->generateUUID();
                $add_uuid = $uuid;
            }
            foreach ($mdl->servers->server->iterateItems() as $key => $node) {
                $peers = array_filter(explode(',', (string)$node->peers));
                if (in_array($uuid, $peers) && !in_array($key, $servers)) {
                    $node->peers = implode(',', array_diff($peers, [$uuid]));
                } elseif (!in_array($uuid, $peers) && in_array($key, $servers)) {
                    $node->peers = implode(',', array_merge($peers, [$uuid]));
                }
            }
            /**
             * Save to in memory model.
             * Ignore validations as $uuid might be new or trigger an existing validation issue.
             * Persisting the data is handled by setBase()
             */
            $mdl->serializeToConfig(false, true);
        }
        $result = $this->setBase('client', 'clients.client', $uuid);
        if (!empty($add_uuid) && $result['result'] == 'saved') {
            $result['uuid'] = $add_uuid;
        }
        return $result;
    }

    public function toggleClientAction($uuid)
    {
        return $this->toggleBase('clients.client', $uuid);
    }

    public function getClientBuilderAction()
    {
        return $this->getBase('configbuilder', 'clients.client', null);
    }

    public function addClientBuilderAction()
    {
        $uuid = null;
        $server = null;
        if ($this->request->isPost() && !empty($this->request->getPost('configbuilder'))) {
            Config::getInstance()->lock();
            $mdl = new Server();
            $uuid = $this->getModel()->clients->generateUUID();
            $server = $this->request->getPost('configbuilder')['server'];
            
            // Lấy dữ liệu từ configbuilder
            $configbuilder = $this->request->getPost('configbuilder');
            
            // Map serveraddress và serverport từ form configbuilder
            if (isset($configbuilder['serveraddress']) && !empty($configbuilder['serveraddress'])) {
                // Đã có serveraddress từ form
            } elseif (isset($configbuilder['endpoint_ip']) && !empty($configbuilder['endpoint_ip'])) {
                // Chuyển từ endpoint_ip sang serveraddress
                $_POST['configbuilder']['serveraddress'] = $configbuilder['endpoint_ip'];
            }
            
            if (isset($configbuilder['serverport']) && !empty($configbuilder['serverport'])) {
                // Đã có serverport từ form
            } elseif (isset($configbuilder['endpoint_port']) && !empty($configbuilder['endpoint_port'])) {
                // Chuyển từ endpoint_port sang serverport
                $_POST['configbuilder']['serverport'] = $configbuilder['endpoint_port'];
            }
            
            foreach ($mdl->servers->server->iterateItems() as $key => $node) {
                if ($key == $server) {
                    $peers = array_filter(explode(',', (string)$node->peers));
                    $node->peers = implode(',', array_merge($peers, [$uuid]));
                    break;
                }
            }
            /**
             * Save to in memory model.
             * Ignore validations as $uuid might be new or trigger an existing validation issue.
             * Persisting the data is handled by setBase()
             */
            $mdl->serializeToConfig(false, true);
        }

        $result = $this->setBase('configbuilder', 'clients.client', $uuid);
        
        // Xác định UUID chính xác
        if (isset($result['uuid'])) {
            $add_uuid = $result['uuid'];
        } elseif ($uuid) {
            $add_uuid = $uuid;
        } else {
            return $result;
        }
        
        // Nếu lưu thành công, trả về toàn bộ cấu hình
        if ($result['result'] == 'saved' && !empty($add_uuid)) {
            try {
                // Lấy thông tin peer vừa tạo
                $clientNode = $this->getModel()->getNodeByReference('clients.client.' . $add_uuid);
                if ($clientNode !== null) {
                    $result['peer'] = [
                        'uuid' => $add_uuid,
                        'name' => (string)$clientNode->name,
                        'enabled' => (string)$clientNode->enabled,
                        'pubkey' => (string)$clientNode->pubkey,
                        'tunneladdress' => (string)$clientNode->tunneladdress,
                        'keepalive' => (string)$clientNode->keepalive,
                        'psk' => !empty((string)$clientNode->psk) ? '[SET]' : '',
                        'serveraddress' => (string)$clientNode->serveraddress,
                        'serverport' => (string)$clientNode->serverport,
                    ];
                    
                    // Lấy thông tin server
                    $server_uuid = $this->request->getPost('configbuilder')['server'] ?? $server;
                    if (!empty($server_uuid)) {
                        $serverModel = new Server();
                        $serverNode = $serverModel->getNodeByReference('servers.server.' . $server_uuid);
                        if ($serverNode !== null) {
                            $result['server'] = [
                                'name' => (string)$serverNode->name,
                                'endpoint' => (string)$serverNode->endpoint,
                                'pubkey' => (string)$serverNode->pubkey,
                            ];
                            
                            // Tạo cấu hình WireGuard
                            $configLines = [];
                            $configLines[] = '[Interface]';
                            $configLines[] = 'PrivateKey = [HIDDEN - Check peer generator form]';
                            
                            $tunnelAddr = (string)$clientNode->tunneladdress;
                            if (!empty($tunnelAddr)) {
                                $configLines[] = 'Address = ' . $tunnelAddr;
                            }
                            
                            // Thêm DNS nếu có từ request hoặc server
                            $configbuilder = $this->request->getPost('configbuilder');
                            if (is_array($configbuilder) && !empty($configbuilder['peer_dns'])) {
                                $configLines[] = 'DNS = ' . $configbuilder['peer_dns'];
                            } elseif (!empty((string)$serverNode->peer_dns)) {
                                $configLines[] = 'DNS = ' . (string)$serverNode->peer_dns;
                            }
                            
                            // Thêm MTU nếu có từ server
                            if (!empty((string)$serverNode->mtu)) {
                                $configLines[] = 'MTU = ' . (string)$serverNode->mtu;
                            }
                            
                            $configLines[] = '';
                            $configLines[] = '[Peer]';
                            $configLines[] = 'PublicKey = ' . (string)$serverNode->pubkey;
                            
                            if (!empty((string)$clientNode->psk)) {
                                $configLines[] = 'PresharedKey = [SET]';
                            }
                            
                            $endpoint = (string)$serverNode->endpoint;
                            if (!empty($endpoint)) {
                                $configLines[] = 'Endpoint = ' . $endpoint;
                            }
                            
                            // AllowedIPs - sử dụng tunnel address của client
                            if (!empty($tunnelAddr)) {
                                $configLines[] = 'AllowedIPs = ' . $tunnelAddr;
                            } else {
                                $configLines[] = 'AllowedIPs = 0.0.0.0/0, ::/0';
                            }
                            
                            $keepalive = (string)$clientNode->keepalive;
                            if (!empty($keepalive)) {
                                $configLines[] = 'PersistentKeepalive = ' . $keepalive;
                            }
                            
                            $result['config_preview'] = implode("\n", $configLines);
                        }
                    }
                }
            } catch (\Exception $e) {
                $result['error'] = 'Error generating config: ' . $e->getMessage();
            }
        }
        
        return $result;
    }

    public function getServerInfoAction($uuid = null)
    {
        $result = ['status' => 'failed'];
        if ($this->request->isGet()) {
            $peers = [];
            $subnets = [];
            $used_addresses = []; /* We cleanse addresses before storing here, to allow string matching */

            foreach ((new Server())->servers->server->iterateItems() as $key => $node) {
                if ($key == $uuid) {
                    $peers = array_filter(explode(',', (string)$node->peers));
                    $result['endpoint'] = (string)$node->endpoint;
                    $result['peer_dns'] = (string)$node->peer_dns;
                    $result['mtu'] = (string)$node->mtu;
                    $result['pubkey'] = (string)$node->pubkey;
                    foreach (array_filter(explode(',', (string)$node->tunneladdress)) as $addr) {
                        $proto = str_contains($addr, ':') ? 'inet6' : 'inet';
                        if (!isset($subnets[$proto])) {
                            $subnets[$proto] = $addr;
                        }
                        $used_addresses[] = inet_ntop(inet_pton(explode('/', $addr)[0]));
                    }
                    foreach ($peers as $peer) {
                        $this_peer = $this->getModel()->getNodeByReference('clients.client.' . $peer);
                        if ($this_peer != null) {
                            foreach (array_filter(explode(',', (string)$this_peer->tunneladdress)) as $addr) {
                                $used_addresses[] = inet_ntop(inet_pton(explode('/', $addr)[0]));
                            }
                        }
                    }
                    $tunneladdress = [];
                    foreach ($subnets as $cidr) {
                        foreach (Util::cidrRangeIterator($cidr) as $addr) {
                            if (!in_array($addr, $used_addresses)) {
                                $netmask = str_contains($addr, ':') ? '128' : '32';
                                $tunneladdress[] = $addr . '/' . $netmask;
                                break;
                            }
                        }
                    }
                    $result['address'] = implode(',', $tunneladdress);
                    $result['status'] = 'ok';
                    break;
                }
            }
        }
        return $result;
    }
}
