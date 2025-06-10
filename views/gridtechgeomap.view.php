<?php declare(strict_types=1);

/**
 * @var CView $this
 */

$widget = (new CHtmlPage())->setTitle(_('Geomap'));

$widget->setControls(
    new CList([
        (new CDiv())
                    ->addStyle('display: flex; align-items: center; gap: 12px;')
                    ->addItem(
                        (new CInput('text', 'search-input'))
                            ->setId('search-input')
                            ->setAttribute('placeholder', _('Search location...'))
                    ),
        (new CDiv())
            ->addClass('menu-option')
            ->addStyle('display: flex; align-items: center; gap: 12px;')
            ->addItem((new CSpan(_('Status')))->addClass('menu-text'))
            ->addItem(
                (new CSelect('status-filter'))
                    ->setId('status-filter')
                    ->addOptions(
                        CSelect::createOptionsFromArray([
                            '' => _('-'),
                            'Down' => _('Down'),
                            'Up' => _('Up')
                        ])
                    )
            ),
        (new CDiv())
            ->addStyle('display: flex; align-items: center; gap: 12px;')
            ->addItem((new CSpan(_('Map Style')))->addClass('menu-text'))
            ->addItem(
                (new CSelect('map-theme-filter'))
                    ->setId('map-theme-filter')
                    ->setAttribute('aria-label', _('Map Theme'))
                    ->addOptions(
                        CSelect::createOptionsFromArray([
                            '' => _('-'),
                            'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png' => _('Carto Light'),
                            'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' => _('Carto Dark'),
                            'https://tiles.stadiamaps.com/tiles/stamen_toner/{z}/{x}/{y}{r}.png' => _('Toner'),
                            'https://tiles.stadiamaps.com/tiles/stamen_terrain/{z}/{x}/{y}{r}.png' => _('Terrain')
                        ])
                    )
            ),
        (new CButton('edit', _('Edit')))
            ->setAttribute('title', _('Edit view'))
            ->setId('edit-view-btn')
            ->setAttribute('onclick', "location.href='edit_geomap.php';")
    ])
);

$widget->addItem((new CDiv())->setId('map')->addStyle('width: 1500px;')->addStyle('height: 650px;')->addStyle('border: 1px solid #AAA;'));
$widget->addItem((new CDiv())->setId('custom-pop'));
$widget->show();

$this->includeJsFile('gridtechgeomap.js.php');
