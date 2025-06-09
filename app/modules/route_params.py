from pydantic import BaseModel


class ScriptsParams(BaseModel):
    hostid: str
    scriptid: str = None
