import json
from openai import OpenAI
import os
import random
from database import get_all_products, get_products_by_ids
from search import vector_search
from dotenv import load_dotenv

load_dotenv()
client = OpenAI(
    api_key=os.getenv("OPENROUTER_API_KEY"),
    base_url="https://openrouter.ai/api/v1"
)

def get_recommendations(user_id, cart_items):
    all_products = get_all_products()
    if not all_products:
        return []

    candidates = []
    
    # 1. Semantic Search (Based on Cart Items)
    if cart_items:
        try:
            cart_products = get_products_by_ids(cart_items)
            for cp in cart_products:
                # Search for products similar to each cart item
                similar = vector_search.search(f"{cp['title']} {cp['price']}", top_k=3)
                candidates.extend(similar)
        except Exception as e:
            print(f"Error in semantic search for cart: {e}")

    # 2. Diversity/Popularity (Fill candidates if needed)
    # Pick some random items from different categories to ensure variety
    if len(candidates) < 10:
        random_picks = random.sample(all_products, min(len(all_products), 15))
        candidates.extend(random_picks)

    # De-duplicate candidates by ID
    unique_candidates = {p['id']: p for p in candidates}.values()
    # Remove items already in cart
    if cart_items:
        unique_candidates = [p for p in unique_candidates if p['id'] not in cart_items]
    
    # Take top 10 for AI to rank
    candidate_list = [
        {"id": p["id"], "title": p["title"], "desc": p["description"][:100] + "...", "cat": p["category_id"]}
        for p in list(unique_candidates)[:10]
    ]

    prompt = f"""
You are an expert product recommender for a Halal Health Store.

User Context:
- User ID: {user_id}
- Items already in cart IDs: {cart_items}

Candidate Products (from our store):
{json.dumps(candidate_list)}

Task:
1. Select the BEST 4 products for this user.
2. If the cart has items, prioritize complementary products (e.g., if coffee is in cart, suggest honey or healthy snacks).
3. If the cart is empty, suggest a mix of our best health supplements.
4. For each selected product, provide a short, catchy "reason" (max 10 words).

Output format (JSON Array ONLY):
[
  {{"id": 12, "reason": "Boost your morning energy naturally"}},
  ...
]
"""

    try:
        response = client.chat.completions.create(
            model="meta-llama/llama-3.1-8b-instruct",
            messages=[
                {"role": "system", "content": "You are a specialized recommendation engine. Return ONLY valid JSON array."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.4,
            max_tokens=500
        )

        content = response.choices[0].message.content.strip()
        # Clean up possible markdown code blocks
        if content.startswith("```json"):
            content = content[7:-3].strip()
        elif content.startswith("```"):
            content = content[3:-3].strip()
            
        return json.loads(content)

    except Exception as e:
        print("Recommendation Error:", e)
        # Fallback to simple random selection
        fallback_samples = random.sample(all_products, min(len(all_products), 4))
        return [{"id": p["id"], "reason": "Recommended for you"} for p in fallback_samples]
