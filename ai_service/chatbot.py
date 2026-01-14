import os
import json
from openai import OpenAI
from database import get_products_by_ids
from search import vector_search
from dotenv import load_dotenv

load_dotenv()

client = OpenAI(
    api_key=os.getenv("OPENROUTER_API_KEY"),
    base_url="https://openrouter.ai/api/v1"
)

SYSTEM_PROMPT = """
You are a halal-compliant wellness and food advisor for 'HPA International' E-commerce.
You provide educational information only, not medical advice.
You recommend products ONLY from the provided "Available Store Products" list. 
If no relevant products are found in the list, inform the user you couldn't find a specific match but can offer general advice.
You never diagnose, prescribe, or claim to cure diseases.
You always encourage consulting healthcare professionals when needed.
Keep your answers concise, helpful, and polite. 
If suggesting products, mention their traditional benefits based on the provided descriptions.
"""

def get_chat_response(message, cart_items):
    # 1. Semantic search for relevant products based on the user's message
    relevant_products = vector_search.search(message, top_k=3)
    
    # 2. Get cart context
    cart_info = ""
    if cart_items:
        # If cart_items are IDs
        try:
            cart_products = get_products_by_ids(cart_items)
            cart_info = "User's current cart contains: " + ", ".join([p['title'] for p in cart_products])
        except:
            cart_info = f"User has these items in cart: {cart_items}"

    # 3. Format product context for the AI
    product_context = "Available Store Products:\n"
    if relevant_products:
        for p in relevant_products:
            product_context += f"- ID: {p['id']}, Title: {p['title']}, Description: {p['description']}, Price: RM{p['price']}\n"
    else:
        product_context += "No specific products matching current query found.\n"

    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "system", "content": f"Context Information:\n{cart_info}\n\n{product_context}"},
        {"role": "user", "content": message}
    ]

    try:
        response = client.chat.completions.create(
            model="meta-llama/llama-3.1-8b-instruct",
            messages=messages,
            temperature=0.5,
            max_tokens=400
        )
        return response.choices[0].message.content.strip()

    except Exception as e:
        print("Chatbot Error:", e)
        return "I apologize, but I am having trouble connecting to my database right now. How else can I assist you with our halal wellness products?"
