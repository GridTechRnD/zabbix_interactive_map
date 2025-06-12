<?php declare(strict_types=1);

namespace Modules\GridtechGeoMap\Actions;

use CController;
use CControllerResponseData;
use API;

class GridtechGeoMapHosts extends CController
{
	public function init(): void
	{
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool
	{
		$fields = [
			'sysmapid' => ['type' => 'int32', 'optional' => true]
		];
		$this->validateInput($fields);
		return true;
	}

	protected function checkPermissions(): bool
	{
		$permit_user_types = [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN];
		return in_array($this->getUserType(), $permit_user_types);
	}

	protected function doAction(): void
	{
		$sysmapid = $this->getInput('sysmapid', null);

		$maps = API::Map()->get([
			'sysmapids' => [$sysmapid],
			'output' => ['sysmapid', 'name'],
			'selectSelements' => ['elements']
		]);
		$hostids = [];
		foreach ($maps as $map) {
			if (!empty($map['selements'])) {
				foreach ($map['selements'] as $selement) {
					if (!empty($selement['elements'])) {
						foreach ($selement['elements'] as $element) {
							if (isset($element['hostid'])) {
								$hostids[] = $element['hostid'];
							}
						}
					}
				}
			}
		}

		$hosts = [];
		if (!empty($hostids)) {
			$hosts = API::Host()->get([
				'hostids' => $hostids,
				'output' => ['extend', 'host', 'name'],
				'selectInventory' => [
					'type',
					'location',
					'location_lat',
					'location_lon',
					'type_full',
					'serialno_a'
				],
				'selectInterfaces' => ['ip'],
			]);

			$items = API::Item()->get([
				'hostids' => $hostids,
				'search' => ['key_' => 'availability.status'],
				'output' => ['hostid', 'lastvalue']
			]);

			$ping_status = [];
			foreach ($items as $item) {
				if (isset($item['lastvalue'])) {
					$ping_status[$item['hostid']] = strval(round(floatval($item['lastvalue'])));
				}
			}

			foreach ($hosts as &$host) {
				$status = $ping_status[$host['hostid']] ?? null;
				$host['available'] = $status;
				$host['links'] = API::Script()->get([
					'hostids' => [$host['hostid']],
					'filter' => ['type' => 6, 'scope' => 2],
					'output' => 'extend'
				]);
				$host['scripts'] = API::Script()->get([
					'hostids' => [$host['hostid']],
					'filter' => ['type' => 0, 'scope' => 2],
					'output' => 'extend'
				]);
			}
			unset($host);
		}

		$sysmap_name = $maps[0]['name'] ?? '';

		if (!empty($hosts)) {
			$data = [
				'hosts' => $hosts,
				'sysmap_name' => $sysmap_name
			];
		} else {
			$data = [
				'hosts' => 'No data',
				'sysmap_name' => $sysmap_name
			];
		}


		$response = new CControllerResponseData($data);
		$response->setTitle(_('Hosts'));
		$this->setResponse($response);
	}
}
