import mysql.connector
import os
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "hpa")
    )

def get_all_products():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    # Join with categories and brands for better context
    query = """
    SELECT p.id, p.title, p.price, p.description, 
           c.name as category_name, b.name as brand_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    """
    cursor.execute(query)
    products = cursor.fetchall()
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
    cursor = conn.cursor(dictionary=True)
    format_strings = ','.join(['%s'] * len(ids))
    query = f"""
    SELECT p.id, p.title, p.price, p.description,
           c.name as category_name, b.name as brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.id IN ({format_strings})
    """
    cursor.execute(query, tuple(ids))
    products = cursor.fetchall()
    conn.close()
    
    # Ensure price is float for JSON serialization
    for p in products:
        if 'price' in p and p['price'] is not None:
            p['price'] = float(p['price'])
    return products
