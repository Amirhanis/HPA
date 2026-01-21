import os
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    db_type = os.getenv("DB_CONNECTION", "mysql")
    
    if db_type == "pgsql" or db_type == "postgresql":
        import psycopg2
        import psycopg2.extras
        return psycopg2.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            port=os.getenv("DB_PORT", "5432"),
            user=os.getenv("DB_USER", os.getenv("DB_USERNAME", "root")),
            password=os.getenv("DB_PASSWORD", ""),
            database=os.getenv("DB_NAME", os.getenv("DB_DATABASE", "hpa"))
        )
    else:
        import mysql.connector
        return mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USER", os.getenv("DB_USERNAME", "root")),
            password=os.getenv("DB_PASSWORD", ""),
            database=os.getenv("DB_NAME", os.getenv("DB_DATABASE", "hpa"))
        )

def get_cursor(conn):
    """Get appropriate cursor based on DB type"""
    db_type = os.getenv("DB_CONNECTION", "mysql")
    if db_type == "pgsql" or db_type == "postgresql":
        import psycopg2.extras
        return conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
    else:
        return conn.cursor(dictionary=True)

def get_all_products():
    conn = get_db_connection()
    cursor = get_cursor(conn)
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
    cursor = get_cursor(conn)
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
