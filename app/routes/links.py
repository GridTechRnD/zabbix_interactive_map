from fastapi import APIRouter, Depends
from zabbix_api import ZabbixAPI

from app.modules.route_params import ScriptsParams
from app.modules.zabbix_session import zabbix_session
from app.modules.get_macros import get_macros


router = APIRouter()


@router.post("/links")
async def get_links(item: ScriptsParams, zapi: ZabbixAPI = Depends(zabbix_session)):
    scripts = zapi.script.get({
        "hostids": [item.hostid],
        "filter": {"type": 6, "scope": 2},
        "output": "extend"
    })

    if not scripts:
        return []

    host_data = zapi.host.get({
        "hostids": [item.hostid],
        "output": ["hostid", "host", "name"],
        "selectInterfaces": ["ip"],
        "selectMacros": "extend"
    })[0]

    user_macros = host_data.get("macros", [])
    default_macros = get_macros(host_data)

    def change_macros(text: str) -> str:
        for macro in user_macros:
            text = text.replace(macro["macro"], macro["value"])
        for macro, valor in default_macros.items():
            text = text.replace(macro, valor)
        return text

    for s in scripts:
        if "url" in s:
            s["url"] = change_macros(s["url"])

    return scripts
