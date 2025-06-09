def get_macros(host: dict) -> dict:
    interface = host.get("interfaces", [{}])[0]

    macros = {
        "{HOST.HOST}": host.get("host", ""),
        "{HOST.NAME}": host.get("name", ""),
        "{HOST.ID}": host.get("hostid", ""),
        "{HOST.IP}": interface.get("ip", ""),
        "{HOST.DNS}": interface.get("dns", ""),
    }

    conn_type = interface.get("useip", 1)
    macros["{HOST.CONN}"] = macros["{HOST.IP}"] if str(
        conn_type) == "1" else macros["{HOST.DNS}"]

    return macros
