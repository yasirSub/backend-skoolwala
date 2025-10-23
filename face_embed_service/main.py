from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse
import uvicorn
import hashlib
from typing import List

app = FastAPI(title="Skoolwala Face Embed Service")

def mock_embedding(data: bytes, dim: int = 512) -> List[float]:
    # Deterministic pseudo-embedding from image bytes
    buf = data
    out = [0.0] * dim
    for i, b in enumerate(buf):
        out[i % dim] += (b & 0xFF) / 255.0
    # L2 normalize
    norm = sum(v * v for v in out) ** 0.5
    if norm > 0:
        out = [v / norm for v in out]
    return out

@app.post("/embed")
async def embed(image: UploadFile = File(...)):
    try:
        content = await image.read()
        if not content:
            raise HTTPException(status_code=400, detail="Empty image")
        embedding = mock_embedding(content)
        fp = hashlib.sha256(content).hexdigest()
        return JSONResponse({
            "status": "success",
            "fingerprint": fp,
            "dimension": len(embedding),
            "embedding": embedding,
        })
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8001)


