# Ensure that the environment variables for Zabbix API are set
from os import getenv
if not all(getenv(var) for var in ["ZBX_HOST", "ZBX_USER", "ZBX_PASSWORD"]):
    raise EnvironmentError(
        "Please set ZBX_HOST, ZBX_USER, and ZBX_PASSWORD environment variables.")


import asyncio
import contextlib
# FastAPI imports
from fastapi import FastAPI
from fastapi.responses import HTMLResponse
from fastapi.staticfiles import StaticFiles
from fastapi.middleware.cors import CORSMiddleware

from app.modules.zabbix_session import zabbix_session
from app.routes import data, execute, links, scripts
from app.modules.cache import save_maps_cache


async def update_maps(interval=43200):
    while True:
        zapi = zabbix_session()
        save_maps_cache(zapi.map.get({
            "output": ["sysmapid"],
            "selectSelements": ["elementtype", "elements"]
        }))
        await asyncio.sleep(interval)


@contextlib.asynccontextmanager
async def lifespan(app: FastAPI):
    zapi = zabbix_session()
    save_maps_cache(zapi.map.get({
        "output": ["sysmapid"],
        "selectSelements": ["elementtype", "elements"]
    }))
    task = asyncio.create_task(update_maps())
    yield
    task.cancel()
    with contextlib.suppress(asyncio.CancelledError):
        await task


# FastAPI config
app = FastAPI(title="Zabbix Map", version="0.0.1", lifespan=lifespan)
# Add CORS middleware to allow cross-origin requests
# and serve static files from the "static" directory
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)
# Mount the static files directory to serve CSS, JS and other assets
app.mount("/static", StaticFiles(directory="static"), name="static")


# Define the root endpoint to serve the main HTML page
@app.get("/favicon.ico", include_in_schema=False)
async def favicon():
    return HTMLResponse(content="", status_code=204)


# Define the home endpoint to serve the Leaflet map
# This endpoint reads the index.html file from the static directory
# and returns it as an HTML response
@app.get("/")
async def home():
    with open("static/index.html") as file:
        leaflet_map = file.read()
    return HTMLResponse(leaflet_map)


app.include_router(data.router)
app.include_router(execute.router)
app.include_router(links.router)
app.include_router(scripts.router)
