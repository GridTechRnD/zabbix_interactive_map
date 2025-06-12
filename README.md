# Zabbix Geomap
***Designed for Zabbix 7.0.13 version***

## Installation
This module is designed for use with Zabbix Docker installations and wraps the original Zabbix Sysmap List.
Installation is simple: you just need to add two volumes to the `docker-compose` file for the `zabbix-frontend` service.

```yaml
zabbix-frontend:
    ...
    volumes:
        - ./zabbix_interactive_map:/usr/share/zabbix/modules/zabbix_interactive_map
        - ./zabbix_interactive_map/monitoring.sysmap.list.php:/usr/share/zabbix/include/views/monitoring.sysmap.list.php
```

In Zabbix, go to **Administration > General > Modules**, then click the "Scan Directory" button.
The module will appear in the modules list, disabled by default.
Enable the module and look for "Geomaps" in the "Monitoring" tab.

## Usage
This module uses the Zabbix API::Map(). The network maps created in the "Maps" section will also be shown in the new "Geomaps" tab.
Hosts need to have latitude and longitude in their inventory.

The wrapping implementation is designed to redirect the maps from the original "Maps" tab to this new "Geomaps" tab.
The maps you want to redirect to "Geomaps" need to have the "Host label type" property enabled as a "Custom label" with the string "4G".

If you want to enable more redirect types, edit **`monitoring.sysmap.list.php`** on line 105:

The current condition is hardcoded to look for "4G" in the "Host label type" property, but you can change it as you wish.

Change the line `if (array_key_exists('label_string_host', $label) && $label['label_string_host'] === '4G')` and reload Zabbix.