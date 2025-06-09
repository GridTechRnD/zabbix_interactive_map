import io
import json


CACHE_FILE = "/app/data/maps_cache.json"


def save_maps_cache(maps):
    with io.open(CACHE_FILE, "w", encoding="utf-8") as file:
        json.dump(maps, file)


def load_maps_cache():
    try:
        with io.open(CACHE_FILE, "r", encoding="utf-8") as file:
            return json.load(file)
    except Exception:
        return None
