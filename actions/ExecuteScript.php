<?php declare(strict_types=1);

namespace Modules\GridtechGeoMap\Actions;

use CController;
use CControllerResponseData;
use API;

class ExecuteScript extends CController
{
    public function init(): void
    {
        $this->disableCsrfValidation();
    }

    protected function checkPermissions(): bool
	{
		$permit_user_types = [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN];
		return in_array($this->getUserType(), $permit_user_types);
	}

    protected function checkInput(): bool
    {
        $fields = [
            'scriptid' => ['type' => 'int32', 'optional' => false],
            'hostid' => ['type' => 'int32', 'optional' => false]
        ];
        $this->validateInput($fields);
        return true;
    }

    protected function doAction(): void
    {
        $scriptid = $this->getInput('scriptid');
        $hostid = $this->getInput('hostid');

        $result = API::Script()->execute([
            'scriptid' => $scriptid,
            'hostid' => $hostid
        ]);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }
}