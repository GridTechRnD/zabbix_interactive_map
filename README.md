# zabbix-module-geomap

## Introduction
This module is compatible Zabbix 6 and Zabbix 5.</br>
The goal of this module is to provide an interactive geographical map with hosts which have coordinates. There is four possibles to interactive with this map:
* Search one host with search bar
* Search all hosts in one or many severity with filter button
* Highlight one department in map and get all hosts only in this department
* Highlight one region in map and get all hosts only in this region


## Installation
How to install :
* Go to your zabbix frontend installation (default: /usr/share/zabbix/modules)
* Clone the project : git clone [https://github.com/YPSI-SAS/zabbix-module-geomap.git](https://github.com/YPSI-SAS/zabbix-module-geomap.git)
* Change the owner of directory to your web user if necessary
* Go to your web Zabbix interface in : Administration > General > Modules
* Use the Scan directory button on the top right
* Enable the module
* Go to Monitoring > Geomap

## Add new country and filter
You can add a new country and a new filter for this country simply. For my example, I choose to explain how create a new filter for country **Italie** and filter **department** </br>
To create a new country:
* Create a new directory named **Italie** in zabbix-module-geomap/resources

To create a new filters like department or region or other for exemple:
* Create a new directory named **department** in zabbix-module-geomap/resources/Italie

And you must add all GeoJson files in this directory. The name of GeoJson file will be the name which appears in possible option in the filter.
