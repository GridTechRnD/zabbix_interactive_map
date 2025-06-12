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
        return true;
    }

    protected function checkPermissions(): bool {
        return true;
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