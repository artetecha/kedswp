<?php

namespace FluentCampaign\App\Http\Controllers;

use FluentCampaign\App\Core\App;
use FluentCrm\App\Http\Controllers\Controller as BaseController;

abstract class Controller extends BaseController
{
    public function __construct()
    {
        parent::__construct(App::getInstance());
    }
}
