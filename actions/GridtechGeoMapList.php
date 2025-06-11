<?php declare(strict_types = 1);

namespace Modules\GridtechGeoMap\Actions;

use CController;
use CControllerResponseData;
use API;

class GridtechGeoMapList extends CController {
    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return TRUE;
    }

    protected function checkPermissions(): bool {
        $permit_user_types = [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN];         
        return in_array($this->getUserType(), $permit_user_types);
    }

    protected function doAction(): void {
        $maps = API::Map()->get([
            'output'=> ['name', 'width', 'height']
        ]);

        $data = [
            'maps' => $maps
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Maps'));
        $this->setResponse($response);
    }
}