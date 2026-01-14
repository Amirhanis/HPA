from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional, Any
import os
from recommendation import get_recommendations
from chatbot import get_chat_response

app = FastAPI()

class Interaction(BaseModel):
    user_id: Any = None
    page_view: Any = None
    cart_items: Any = []

class ChatMessage(BaseModel):
    user_id: Any = None
    message: str
    cart_items: Any = []

@app.get("/")
def read_root():
    return {"status": "AI Service Running"}

@app.post("/recommendations")
def recommend(interaction: Interaction):
    try:
        recommendations = get_recommendations(interaction.user_id, interaction.cart_items)
        return {"recommendations": recommendations}
    except Exception as e:
        print(f"API Error (Recommend): {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/chat")
def chat(message: ChatMessage):
    try:
        response = get_chat_response(message.message, message.cart_items)
        return {"response": response}
    except Exception as e:
        print(f"API Error (Chat): {e}")
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)
