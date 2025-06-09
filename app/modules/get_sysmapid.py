from app.modules.cache import load_maps_cache


def get_sysmapid(hostid):
    maps = load_maps_cache()
    filtered_maps = []
    for map in maps:
        for selement in map["selements"]:
            if selement["elementtype"] == "0" and any(element["hostid"] == hostid for element in selement["elements"]):
                filtered_maps.append(map["sysmapid"])
                break

    return filtered_maps
