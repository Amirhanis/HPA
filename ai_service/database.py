import psycopg2
from psycopg2.extras import RealDictCursor
import os
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    # Detect if we should use mysql or postgres based on DB_CONNECTION
    # But for Render, we definitely need Postgres
    return psycopg2.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        dbname=os.getenv("DB_DATABASE", "hpa"),
        port=os.getenv("DB_PORT", "5432")
    )

def get_all_products():
    conn = get_db_connection()
    # Use RealDictCursor to get dictionary-like results similar to mysql's dictionary=True
    cur = conn.cursor(cursor_factory=RealDictCursor)
    query = """
    SELECT p.id, p.title, p.price, p.description, 
           c.name as category_name, b.name as brand_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    """
    cur.execute(query)
    products = cur.fetchall()
    cur.close()
    conn.close()
    
    # Ensure price is float for JSON serialization
    for p in products:
        if 'price' in p and p['price'] is not None:
            p['price'] = float(p['price'])
    return products

def get_products_by_ids(ids):
    if not ids:
        return []
    conn = get_db_connection()
    cur = conn.cursor(cursor_factory=RealDictCursor)
    
    # Postgres uses %s for placeholders too
    placeholders = ', '.join(['%s'] * len(ids))
    query = f"""
    SELECT p.id, p.title, p.price, p.description,
           c.name as category_name, b.name as brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.id IN ({placeholders})
    """
    cur.execute(query, tuple(ids))
    products = cur.fetchall()
    cur.close()
    conn.close()
    
    # Ensure price is float for JSON serialization
    for p in products:
        if 'price' in p and p['price'] is not None:
            p['price'] = float(p['price'])
    return products
