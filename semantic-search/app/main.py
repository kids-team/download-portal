import os
from dataclasses import dataclass
from threading import RLock

import numpy as np
from fastapi import FastAPI, Header, HTTPException
from fastembed import TextEmbedding
from pydantic import BaseModel, Field

MODEL = "intfloat/multilingual-e5-small"
MAX_TOKENS = 512
TOKEN = os.environ.get("SEMANTIC_SEARCH_TOKEN", "")

class Passage(BaseModel):
    id: str
    text: str = Field(min_length=1)

class VectorItem(BaseModel):
    id: str
    document_id: int
    chunk_index: int
    vector: list[float]

class SearchRequest(BaseModel):
    query: str = Field(min_length=1)
    limit: int = Field(default=100, ge=1, le=1000)

@dataclass(frozen=True)
class Entry:
    document_id: int
    chunk_index: int
    vector: np.ndarray

class Engine:
    def __init__(self) -> None:
        self.model: TextEmbedding | None = None
    def loaded_model(self) -> TextEmbedding:
        if self.model is None:
            self.model = TextEmbedding(model_name=MODEL)
        return self.model
    def token_count(self, text: str) -> int:
        return self.loaded_model().token_count(text)
    def passages(self, texts: list[str]) -> list[np.ndarray]:
        return [self.normalize(v) for v in self.loaded_model().passage_embed(texts)]
    def query(self, text: str) -> np.ndarray:
        return self.normalize(next(self.loaded_model().query_embed(text)))
    @staticmethod
    def normalize(vector: np.ndarray) -> np.ndarray:
        vector = np.asarray(vector, dtype=np.float32)
        length = np.linalg.norm(vector)
        if length == 0:
            raise ValueError("Embedding must not be zero.")
        return vector / length

class Index:
    def __init__(self) -> None:
        self.lock = RLock()
        self.entries: dict[str, Entry] = {}
        self.generation = 0
        self.initialized = False
    def reload(self, items: list[VectorItem]) -> None:
        entries = {item.id: Entry(item.document_id, item.chunk_index, Engine.normalize(np.array(item.vector))) for item in items}
        with self.lock:
            self.entries = entries
            self.generation += 1
            self.initialized = True
    def upsert(self, items: list[VectorItem]) -> None:
        with self.lock:
            self.entries = {**self.entries, **{item.id: Entry(item.document_id, item.chunk_index, Engine.normalize(np.array(item.vector))) for item in items}}
            self.generation += 1
    def delete(self, ids: list[str]) -> None:
        with self.lock:
            self.entries = {key: value for key, value in self.entries.items() if key not in ids}
            self.generation += 1
    def search(self, query: np.ndarray, limit: int) -> list[dict]:
        with self.lock:
            ranked = sorted(((float(np.dot(query, entry.vector)), key, entry) for key, entry in self.entries.items()), reverse=True)[:limit]
        return [{"id": key, "document_id": entry.document_id, "chunk_index": entry.chunk_index, "score": score} for score, key, entry in ranked]

engine = Engine()
index = Index()
app = FastAPI(docs_url=None, redoc_url=None, openapi_url=None)

def authorize(token: str | None) -> None:
    if not TOKEN or token != TOKEN:
        raise HTTPException(status_code=401, detail="Unauthorized")

def ensure_limit(text: str) -> None:
    if engine.token_count(text) > MAX_TOKENS:
        raise HTTPException(status_code=422, detail="Input exceeds the model token limit.")

@app.get("/internal/health")
def health(x_internal_token: str | None = Header(default=None)):
    authorize(x_internal_token)
    return {"model": MODEL, "dimension": 384, "size": len(index.entries), "generation": index.generation, "initialized": index.initialized}

@app.post("/internal/embed/passages")
def embed_passages(passages: list[Passage], x_internal_token: str | None = Header(default=None)):
    authorize(x_internal_token)
    for passage in passages: ensure_limit("passage: " + passage.text)
    return [{"id": passage.id, "vector": vector.tolist()} for passage, vector in zip(passages, engine.passages([p.text for p in passages]), strict=True)]

@app.post("/internal/search")
def search(request: SearchRequest, x_internal_token: str | None = Header(default=None)):
    authorize(x_internal_token); ensure_limit("query: " + request.query)
    return {"results": index.search(engine.query(request.query), request.limit), "generation": index.generation}

@app.post("/internal/index/reload")
def reload(items: list[VectorItem], x_internal_token: str | None = Header(default=None)):
    authorize(x_internal_token); index.reload(items); return {"generation": index.generation, "size": len(index.entries)}

@app.post("/internal/index/upsert")
def upsert(items: list[VectorItem], x_internal_token: str | None = Header(default=None)):
    authorize(x_internal_token); index.upsert(items); return {"generation": index.generation, "size": len(index.entries)}

@app.post("/internal/index/delete")
def delete(ids: list[str], x_internal_token: str | None = Header(default=None)):
    authorize(x_internal_token); index.delete(ids); return {"generation": index.generation, "size": len(index.entries)}
