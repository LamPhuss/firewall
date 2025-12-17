<?php

namespace OPNsense\RemoteAccess;

use OPNsense\Base\IndexController as BaseIndexController;

class ConnectionsController extends BaseIndexController
{
    public function indexAction()
    {
        // $this->view->generalForm = $this->getForm("general");
        $this->view->title = gettext("Remote Access - Connections");


        $gridConfig = $this->getFormGrid("dialogConnection");
        $gridConfig['command_width'] = '180';
        $this->view->formGridConnection = $gridConfig;

        $this->view->formDialogConnection = $this->getForm('dialogConnection');

        $this->view->pick('OPNsense/RemoteAccess/connections');
    }
}
