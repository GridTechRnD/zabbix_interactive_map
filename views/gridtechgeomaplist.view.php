<?php declare(strict_types=1);

/**
 * @var CView $this
 */

$widget = (new CHtmlPage())->setTitle(_('Geomaps'))->setNavigation(
    (new CList())->addItem(
        (new CBreadcrumbs([
            (new CSpan())->addItem(
                new CLink(
                    _('All'),
                    (new CUrl('zabbix.php'))->setArgument('action', 'gridtechgeomaplist.view')
                )
            )
        ]))->addClass('wide')
    )
);

if (!empty($data['maps'])) {
    $table = (new CTableInfo())
        ->setHeader([_('Name'), _('Width'), _('Height')]);

    foreach ($data['maps'] as $map) {
        $table->addRow([
            new CLink(
                $map['name'] ?? '',
                (new CUrl('zabbix.php'))
                    ->setArgument('action', 'gridtechgeomap.view')
                    ->setArgument('sysmapid', $map['sysmapid'] ?? '')
            ),
            $map['width'] ?? '',
            $map['height'] ?? ''
        ]);
    }

    $widget->addItem($table);
} else {
    $widget->addItem((new CTableInfo())->setNoDataMessage(_('No maps found.')));
}

$widget->show();

$this->includeJsFile('gridtechgeomap.js.php');
