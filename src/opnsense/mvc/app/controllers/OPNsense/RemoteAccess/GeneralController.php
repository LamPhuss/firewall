<?php

namespace OPNsense\RemoteAccess;

use OPNsense\Base\IndexController as BaseIndexController;

class GeneralController extends BaseIndexController
{
    public function indexAction()
    {
        $this->view->title = gettext("Remote Access - General");
        $this->view->generalForm = $this->getForm("general");
        $this->view->pick('OPNsense/RemoteAccess/general');
    }
}
