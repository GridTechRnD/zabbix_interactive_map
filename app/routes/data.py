from fastapi import APIRouter, Depends
from zabbix_api import ZabbixAPI
from app.modules.zabbix_session import zabbix_session
from app.modules.cities import CITIES

router = APIRouter()


@router.get("/data")
async def data(zapi: ZabbixAPI = Depends(zabbix_session)):
    groupids = [groupid for groups in CITIES.values() for groupid in groups]
    if res := zapi.host.get({
        "groupids": groupids,
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

        hostids = [host["hostid"] for host in res]
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
