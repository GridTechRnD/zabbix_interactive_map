<?php

namespace Modules\GridtechGeoMap;

use Zabbix\Core\CModule,
APP,
CMenuItem,
CController as CAction;

class Module extends CModule
{
	public function init(): void
	{
		APP::Component()->get('menu.main')
			->findOrAdd(_('Monitoring'))
			->getSubmenu()
			->insertAfter(
				_('Dashboard'),
				(new \CMenuItem(
					_('Geomaps')
				))->setAction('gridtechgeomaplist.view')
			);
	}
	public function onBeforeAction(CAction $action): void
	{
	}
	public function onTerminate(CAction $action): void
	{
	}
}
?>