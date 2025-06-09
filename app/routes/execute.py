from fastapi import APIRouter, Depends
from zabbix_api import ZabbixAPI

from app.modules.route_params import ScriptsParams
from app.modules.zabbix_session import zabbix_session


router = APIRouter()


@router.post("/execute")
async def execute_script(item: ScriptsParams, zapi: ZabbixAPI = Depends(zabbix_session)):
    if res := zapi.script.execute({"scriptid": item.scriptid, "hostid": item.hostid}):
        return res
