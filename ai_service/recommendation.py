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
    # Ensure cart_items is a list
    if cart_items is None:
        cart_items = []
        
    all_products = get_all_products()
    if not all_products:
        return []

    candidates = []
    
    # 1. Semantic Search (Based on Cart Items)
    if cart_items:
        try:
            valid_ids = [cid for cid in cart_items if cid is not None]
            if valid_ids:
                cart_products = get_products_by_ids(valid_ids)
                for cp in cart_products:
                    if cp and isinstance(cp, dict):
                        # Use enriched data for similarity search
                        search_query = f"{cp.get('brand_name', '')} {cp.get('title', '')} {cp.get('category_name', '')}"
                        similar = vector_search.search(search_query, top_k=4)
                        if similar:
                            candidates.extend(similar)
        except Exception as e:
            print(f"Error in semantic search for cart: {e}")

    # 2. Diversity/Popularity (Fill candidates if needed)
    if len(candidates) < 12:
        fill_count = min(len(all_products), 20)
        random_picks = random.sample(all_products, fill_count)
        candidates.extend(random_picks)

    # De-duplicate candidates by ID and remove items already in cart
    unique_candidates_dict = {}
    cart_ids_set = set(cart_items)
    
    for p in candidates:
        if p and isinstance(p, dict) and 'id' in p:
            pid = p['id']
            if pid not in cart_ids_set:
                unique_candidates_dict[pid] = p
    
    unique_candidates = list(unique_candidates_dict.values())
    
    # Take top 12 for AI to rank
    candidate_list = []
    for p in unique_candidates[:12]:
        desc = p.get("description") or ""
        candidate_list.append({
            "id": p.get("id"),
            "title": p.get("title", "Product"),
            "brand": p.get("brand_name", "HPA"),
            "category": p.get("category_name", "General"),
            "desc": desc[:120] + "..." if desc else "",
            "price": p.get("price")
        })

    if not candidate_list:
        random_fallback = random.sample(all_products, min(len(all_products), 4))
        return [{"id": pf.get("id"), "reason": "Recommended for you"} for pf in random_fallback if pf]

    prompt = f"""
You are an expert product recommender for a 'HPA International' Halal Health Store.

User Context:
- User ID: {user_id}
- Items already in cart IDs: {cart_items}

Candidate Products (from our store):
{json.dumps(candidate_list)}

Task:
1. Select the BEST 4 products for this user.
2. If the cart has items, prioritize complementary products (e.g. if they have coffee, suggest honey/sweetener or healthy snacks).
3. If the cart is empty, suggest a balanced mix of our top-selling healthy supplements.
4. For each selected product, provide a short, catchy "reason" (max 10 words).

Output format (JSON Array ONLY):
[
  {{"id": 12, "reason": "Perfect natural sweetener for your morning coffee"}},
  ...
]
"""

    try:
        response = client.chat.completions.create(
            model="meta-llama/llama-3.1-8b-instruct",
            messages=[
                {"role": "system", "content": "You are a specialized recommendation engine. Return ONLY a valid JSON array of objects."},
                {"role": "user", "content": prompt}
            ],
            temperature=0.4,
            max_tokens=600
        )

        content = response.choices[0].message.content.strip()
        if content.startswith("```json"):
            content = content[7:-3].strip()
        elif content.startswith("```"):
            content = content[3:-3].strip()
            
        result = json.loads(content)
        if isinstance(result, list):
            return result[:4]
        return []

    except Exception as e:
        print("Recommendation Error:", e)
        fallback_samples = random.sample(all_products, min(len(all_products), 4))
        return [{"id": p.get("id"), "reason": "Highly rated by users"} for p in fallback_samples if p]
