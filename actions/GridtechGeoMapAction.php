<?php declare(strict_types = 1);

namespace Modules\GridtechGeoMap\Actions;

use CController;
use CControllerResponseData;
use API;

class GridtechGeoMapAction extends CController {
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
		$hosts = API::Host()->get([
			'output'=> ['name', 'hostid'],
			'selectInventory' => ['location_lat', 'location_lon'],
            'searchWildcardsEnabled' => true,
            'searchInventory' => ['location_lat' => '*'],
		]);

        $final_hosts = array();
        foreach($hosts as $host){
            if ($host['inventory']['location_lat'] != "" && $host['inventory']['location_lon']!=""){
                array_push($final_hosts, $host);
            }
        }

		$index=0;
		foreach($final_hosts as $host){
			$problems = API::Problem()->get([
				'hostids' => $host['hostid'],
				'output' => ['severity']
			]);

			$final_hosts[$index]['problems'] = $problems;
			$index++;
		}

		$value = array();
		$index = 0;
		foreach(glob('modules/zabbix-module-geomap/resources/*', GLOB_ONLYDIR) as $directory){
			$id = pathinfo($directory)['filename'];
			$value[$index]['name'] = $id;
			$limits = array();
			$index_dir = 0;
			foreach(glob('modules/zabbix-module-geomap/resources/'.$id.'/*', GLOB_ONLYDIR) as $under_directory){ 				
				$val = pathinfo($under_directory)['filename'];
				$files = array();
				foreach(glob('modules/zabbix-module-geomap/resources/'.$id.'/'.$val.'/*.geojson') as $filename){
					array_push($files, pathinfo($filename)['filename']);
				}
				array_unshift($files, "All ".$val);
				$limits[$index_dir]['name'] = $val;
				$limits[$index_dir]['values'] = $files;
				$limits[$index_dir]['default'] = "All ".$val;
				$index_dir++;
				$value[$index]['limits'] = $limits;
			}
			$index++;
		}
		
		$data = [
			'values' => $value,
			'hosts' => $final_hosts,
        ];

		$response = new CControllerResponseData($data);
		$response->setTitle(_('Hosts'));
		$this->setResponse($response);
	}
}
