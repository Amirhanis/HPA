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
        self.model = SentenceTransformer('all-MiniLM-L6-v2')
        self.products = []
        self.index = None
        self.reindex()
        self._initialized = True

    def reindex(self):
        print("Indexing products...")
        self.products = get_all_products()
        if not self.products:
            print("No products found to index.")
            return

        # Prepare strings for embedding: "Title: ... Description: ..."
        texts = [
            f"Title: {p['title']}. Description: {p['description']}" 
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
            if idx < len(self.products):
                results.append(self.products[idx])
        return results

# Singleton instance
vector_search = VectorSearch()
