from os import getenv
from zabbix_api import ZabbixAPI


def zabbix_session():
    zapi = ZabbixAPI(server=getenv("ZBX_HOST"))
    zapi.login(user=getenv("ZBX_USER"), password=getenv("ZBX_PASSWORD"))
    return zapi
