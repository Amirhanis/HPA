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
    query = "SELECT id, title, price, description, category_id, brand_id FROM products"
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
    cursor.execute("SELECT id, title, price FROM products WHERE id IN (%s)" % format_strings, tuple(ids))
    products = cursor.fetchall()
    conn.close()
    
    # Ensure price is float for JSON serialization
    for p in products:
        if 'price' in p and p['price'] is not None:
            p['price'] = float(p['price'])
    return products
