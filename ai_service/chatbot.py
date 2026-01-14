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
You are the official 'HPA International' Halal Wellness Advisor. 
Your primary goal is to help customers find relevant products from our store based on their needs.

GUIDELINES:
1. Only recommend products listed in the "Available Store Products" section below.
2. If a user asks for something we don't have, politely explain that we don't have that specific item but suggest the closest alternative from our list.
3. Base your product benefits on the provided descriptions and their traditional uses in Halal wellness.
4. Always maintain a polite, helpful, and professional tone.
5. Do NOT make up products or prices.
6. If the user's request is health-related, provide general wellness advice but remind them to consult a professional.
7. Use the "User's current cart" context to avoid recommending things they already have, or to suggest items that pair well with them.

ALWAYS prioritize products from the list. If the list is empty, tell the user you are currently fetching the catalog.
"""

def get_chat_response(message, cart_items):
    # 1. Semantic search with higher top_k for better accuracy
    relevant_products = []
    try:
        # Search for more products to allow the LLM to pick the best matches
        relevant_products = vector_search.search(message, top_k=7)
    except Exception as e:
        print(f"Search Error: {e}")

    # 2. Get cart context
    cart_info = "Cart is currently empty."
    if cart_items:
        try:
            valid_ids = [cid for cid in cart_items if cid is not None]
            if valid_ids:
                cart_products = get_products_by_ids(valid_ids)
                titles = [p.get('title', 'Product') for p in cart_products if p and isinstance(p, dict)]
                if titles:
                    cart_info = "User's current cart contains: " + ", ".join(titles)
        except Exception as e:
            print(f"Cart Context Error: {e}")

    # 3. Format product context for the AI with better detail
    product_context = "Available Store Products:\n"
    if relevant_products:
        for p in relevant_products:
            if p and isinstance(p, dict):
                p_id = p.get('id', 'N/A')
                p_title = p.get('title', 'Unknown Product')
                p_brand = p.get('brand_name', 'HPA')
                p_cat = p.get('category_name', 'Wellness')
                p_desc = p.get('description', 'Details available on request.')
                p_price = p.get('price', '0.00')
                product_context += f"- [{p_brand}] {p_title} (Category: {p_cat}) | Price: RM{p_price} | Bio: {p_desc}\n"
    else:
        product_context += "No specific matches found in catalog for this query.\n"

    messages = [
        {"role": "system", "content": SYSTEM_PROMPT},
        {"role": "system", "content": f"CONTEXT:\n{cart_info}\n\n{product_context}"},
        {"role": "user", "content": message}
    ]

    try:
        response = client.chat.completions.create(
            model="meta-llama/llama-3.1-8b-instruct",
            messages=messages,
            temperature=0.3, # Lower temperature for higher accuracy/less hallucination
            max_tokens=500
        )
        return response.choices[0].message.content.strip()

    except Exception as e:
        print("Chatbot Error:", e)
        return "I'm sorry, I encountered an error while processing your request. Please try again or contact support."
