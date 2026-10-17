import os
os.environ["SEMANTIC_SEARCH_TOKEN"] = "test-token"
from fastapi.testclient import TestClient
from app.main import app, engine, index

class FakeEngine:
    def token_count(self, text): return len(text.split())
    def passages(self, texts): return [[1, 0] for _ in texts]
    def query(self, text): return [1, 0]

def setup_function():
    index.entries = {}; index.generation = 0; index.initialized = False

def test_reload_upsert_delete_and_search(monkeypatch):
    monkeypatch.setattr("app.main.engine", FakeEngine())
    client = TestClient(app); headers = {"X-Internal-Token": "test-token"}
    assert client.post("/internal/index/reload", headers=headers, json=[{"id":"a","document_id":1,"chunk_index":0,"vector":[1,0]}]).status_code == 200
    assert client.get("/internal/health", headers=headers).json()["initialized"] is True
    assert client.post("/internal/index/upsert", headers=headers, json=[{"id":"b","document_id":2,"chunk_index":0,"vector":[0,1]}]).status_code == 200
    assert client.post("/internal/search", headers=headers, json={"query":"grace"}).json()["results"][0]["document_id"] == 1
    assert client.post("/internal/index/delete", headers=headers, json=["a"]).json()["size"] == 1

def test_rejects_unauthorized_and_too_long_input(monkeypatch):
    monkeypatch.setattr("app.main.engine", FakeEngine())
    client = TestClient(app)
    assert client.get("/internal/health").status_code == 401
    assert client.post("/internal/embed/passages", headers={"X-Internal-Token":"test-token"}, json=[{"id":"a","text":"x " * 600}]).status_code == 422

def test_reports_an_uninitialized_empty_index(monkeypatch):
    monkeypatch.setattr("app.main.engine", FakeEngine())
    client = TestClient(app); headers = {"X-Internal-Token": "test-token"}
    assert client.get("/internal/health", headers=headers).json()["initialized"] is False
    assert client.post("/internal/index/reload", headers=headers, json=[]).status_code == 200
    health = client.get("/internal/health", headers=headers).json()
    assert health["initialized"] is True and health["size"] == 0
