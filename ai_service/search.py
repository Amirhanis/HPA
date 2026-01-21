import numpy as np
import faiss
from sentence_transformers import SentenceTransformer
from database import get_all_products

class VectorSearch:
    _instance = None

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(VectorSearch, cls).__new__(cls)
            cls._instance._initialized = False
        return cls._instance

    def __init__(self):
        if self._initialized:
            return
        
        print("Initializing Semantic Search Engine...")
        self.model = SentenceTransformer('all-MiniLM-L6-v2', device='cpu')
        self.products = []
        self.index = None
        try:
            self.reindex()
        except Exception as e:
            print(f"FAILED to index products: {e}")
        self._initialized = True

    def reindex(self):
        print("Indexing products...")
        self.products = get_all_products()
        if not self.products:
            print("No products found to index.")
            return

        # Enriched string for embedding: includes Category and Brand for better matching
        texts = [
            f"Product: {p.get('title', '')}. "
            f"Category: {p.get('category_name', 'General')}. "
            f"Brand: {p.get('brand_name', 'HPA')}. "
            f"Description: {p.get('description', '')}" 
            for p in self.products
        ]
        
        embeddings = self.model.encode(texts)
        dimension = embeddings.shape[1]
        
        self.index = faiss.IndexFlatL2(dimension)
        self.index.add(np.array(embeddings).astype('float32'))
        print(f"Indexed {len(self.products)} products.")

    def search(self, query, top_k=5):
        if not self.index or not self.products:
            return []
            
        query_vector = self.model.encode([query])
        distances, indices = self.index.search(np.array(query_vector).astype('float32'), top_k)
        
        results = []
        for idx in indices[0]:
            if idx >= 0 and idx < len(self.products):
                results.append(self.products[idx])
        return results

# Singleton instance
vector_search = VectorSearch()
