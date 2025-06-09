from fastapi import APIRouter, Depends, Query
from zabbix_api import ZabbixAPI
from app.modules.zabbix_session import zabbix_session
from app.modules.get_sysmapid import get_sysmapid

router = APIRouter()


@router.get("/data")
async def data(id: str = Query(None), zapi: ZabbixAPI = Depends(zabbix_session)):
    sysmapids = get_sysmapid(id)
    hostids = zapi.map.get({
        "sysmapids": [sysmapid for sysmapid in sysmapids],
        "output": ["sysmapid"],
        "selectSelements": ["elements"]
    })
    hostids = [
        element["hostid"]
        for sysmap in hostids
        for selement in sysmap.get("selements", [])
        for element in selement.get("elements", [])
        if "hostid" in element
    ]
    if res := zapi.host.get({
        "hostids": hostids,
        "output": ["extend", "host", "name"],
        "selectInventory": [
            "type",
            "location",
            "location_lat",
            "location_lon",
            "type_full",
            "serialno_a"
        ],
        "selectInterfaces": ["ip"],
        "filter": {"status": "0", "inventory.type": "AP"},
    }):

        items = zapi.item.get({
            "hostids": hostids,
            "search": {"key_": "availability.status"},
            "output": ["hostid", "lastvalue"],
        })

        ping_status = {item["hostid"]: round(float(item["lastvalue"]))
                       for item in items if "lastvalue" in item}

        for host in res:
            status = ping_status.get(host["hostid"])
            host["available"] = str(status)

    return res if res else "No data"
