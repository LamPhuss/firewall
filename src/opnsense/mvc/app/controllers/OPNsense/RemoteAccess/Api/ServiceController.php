<?php

namespace OPNsense\RemoteAccess\Api;

use OPNsense\Base\ApiMutableServiceControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Core\Config;

class ServiceController extends ApiMutableServiceControllerBase
{
    protected static $internalServiceClass = '\OPNsense\RemoteAccess\RemoteAccess';
    protected static $internalServiceTemplate = 'OPNsense/RemoteAccess';
    protected static $internalServiceEnabled = 'general.enabled';
    protected static $internalServiceName = 'remoteaccess';

    private $statusFile = '/tmp/remoteaccess_fake_status';

    /**
     * Get fake service status from file
     */
    private function getFakeStatus()
    {
        if (file_exists($this->statusFile)) {
            $status = trim(file_get_contents($this->statusFile));
            return $status ?: 'stopped';
        }
        return 'stopped';
    }

    /**
     * Set fake service status to file
     */
    private function setFakeStatus($status)
    {
        file_put_contents($this->statusFile, $status);
    }

    public function startAction()
    {
        if ($this->request->isPost()) {
            sleep(1);
            $this->setFakeStatus('running');
            return ["response" => "OK"];
        }
        return ["response" => ""];

        // if ($this->request->isPost()) {
        //     $backend = new Backend();
        //     $response = $backend->configdRun("remoteaccess start");
        //     return ["response" => $response];
        // } else {
        //     return ["response" => ""];
        // }
    }

    public function stopAction()
    {
        if ($this->request->isPost()) {
            sleep(1);
            $this->setFakeStatus('stopped');
            return ["response" => "OK"];
        }
        return ["response" => ""];

        // if ($this->request->isPost()) {
        //     $backend = new Backend();
        //     $response = $backend->configdRun("remoteaccess stop");
        //     return ["response" => $response];
        // } else {
        //     return ["response" => ""];
        // }
    }

    public function restartAction()
    {
        if ($this->request->isPost()) {
            sleep(1);
            $this->setFakeStatus('running');
            return ["response" => "OK"];
        }
        return ["response" => ""];
        // if ($this->request->isPost()) {
        //     $backend = new Backend();
        //     $response = $backend->configdRun("remoteaccess restart");
        //     return ["response" => $response];
        // } else {
        //     return ["response" => ""];
        // }
    }

    public function statusAction()
    {
        return ["status" => $this->getFakeStatus()];
    }


    /*
        Regenerate config files from config.xml WITHOUT restarting the service.
        When to use:
            - When the service supports hot reload (reload config without restart)
            - When you only want to update config files but not apply yet
            - When you need to prepare config for the next restart
    */
    public function reloadAction()
    {
        if ($this->request->isPost()) {
            sleep(1);
            return ["response" => "reloaded"];
        }
        return ["response" => ""];

        // if ($this->request->isPost()) {
        //     $backend = new Backend();
        //     $response = $backend->configdRun("template reload OPNsense/RemoteAccess");
        //     return ["response" => $response];
        // } else {
        //     return ["response" => ""];
        // }
    }

    /*  
        Generate config files AND restart/reload service to apply changes.
        When to use:
            - After user clicks Save or Apply
            - When changes need to be applied immediately
            - Standard workflow in OPNsense
    */
    public function reconfigureAction()
    {
        if ($this->request->isPost()) {
            sleep(1);
            $this->setFakeStatus('running');
            return ["status" => "ok"];
        }
        return ["status" => "failed"];
    }
}