<?php

namespace FluentCampaign\App\Http\Requests;

use FluentCrm\Framework\Foundation\RequestGuard;

class UserRequest extends RequestGuard
{
    /**
     * @return Array
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return Array
     */
    public function messages()
    {
        return [];
    }

    /**
     * @return Array
     */
    public function beforeValidation()
    {
        $data = $this->all();
        
        // Modify the $data

        return $data;
    }

    /**
     * @return Array
     */
    public function afterValidation()
    {
        $data = $this->all();
        
        // Modify the $data

        return $data;
    }
}
