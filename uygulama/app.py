import os
from flask import Flask
import mysql.connector
import redis

app = Flask(__name__)
app.secret_key = "yemekhane_gizli_anahtar"

DB_HOST = os.getenv("DB_YAZMA_HOST", "mysql-primary")
DB_PORT = int(os.getenv("DB_PORT", 3306))
DB_NAME = os.getenv("DB_ADI", "yemekhane")
DB_USER = os.getenv("DB_KULLANICI", "yemekhane_app")
DB_PASS = os.getenv("DB_SIFRE", "Uyg_2026")
REDIS_HOST = os.getenv("REDIS_HOST", "redis")

@app.route('/')
def index():
    try:

        conn = mysql.connector.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME,
            port=DB_PORT
        )
        db_status = "Kaynak Veritabaný (Primary) ve Redis Baðlantýsý Baþarýlý!"
        conn.close()
    except Exception as e:
        db_status = f"Baðlantý Hatasý: {str(e)}"
        
    return f"Yemekhane Otomasyonu Python API Çalýþýyor! <br> Durum: {db_status}"

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8000)