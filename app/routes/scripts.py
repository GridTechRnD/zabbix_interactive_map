from fastapi import APIRouter, Depends
from zabbix_api import ZabbixAPI

from app.modules.route_params import ScriptsParams
from app.modules.zabbix_session import zabbix_session


router = APIRouter()


@router.post("/scripts")
async def get_scripts(item: ScriptsParams, zapi: ZabbixAPI = Depends(zabbix_session)):
    if res := zapi.script.get({
        "hostids": [item.hostid],
            "filter": {"type": 0, "scope": 2}}):

        return res
    return []
