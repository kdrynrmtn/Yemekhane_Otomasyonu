# -*- coding: utf-8 -*-
import os
from flask import Flask, render_template_string, request, session, redirect, url_for
import mysql.connector
import redis
import bcrypt

app = Flask(__name__)
app.secret_key = "yemekhane_gizli_anahtar"

# Docker Compose ortam degiskenleri
DB_HOST = os.getenv("DB_YAZMA_HOST", "mysql-primary")
DB_PORT = int(os.getenv("DB_PORT", 3306))
DB_NAME = os.getenv("DB_ADI", "yemekhane")
DB_USER = os.getenv("DB_KULLANICI", "yemekhane_app")
DB_PASS = os.getenv("DB_SIFRE", "Uyg_2026")
REDIS_HOST = os.getenv("REDIS_HOST", "redis")

# Redis baglantisi
r = redis.Redis(host=REDIS_HOST, port=6379, decode_responses=True)

LOGIN_TEMPLATE = """
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yemekhane Otomasyonu - Giris</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #fff0f5, #ffe4e1); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .login-box { 
            background: #ffffff; 
            padding: 35px; 
            border-radius: 12px; 
            box-shadow: 0 8px 20px rgba(255, 182, 193, 0.3); 
            width: 320px; 
            border: 1px solid #ffccd5;
        }
        h2 { 
            text-align: center; 
            color: #d81b60; 
            font-size: 20px;
            margin-bottom: 20px;
        }
        input { 
            width: 100%; 
            padding: 12px; 
            margin: 10px 0; 
            border: 1px solid #ffb6c1; 
            border-radius: 6px; 
            box-sizing: border-box; 
            outline: none;
            transition: border-color 0.3s;
        }
        input:focus {
            border-color: #d81b60;
        }
        button { 
            width: 100%; 
            padding: 12px; 
            background: #ff69b4; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold;
            transition: background 0.3s;
            margin-top: 10px;
        }
        button:hover { 
            background: #e0529d; 
        }
        .error { 
            color: #d9534f; 
            font-size: 13px; 
            text-align: center; 
            margin-bottom: 10px;
            background: #fdf2f2;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h2>Yemekhane Otomasyonu</h2>
            {% if error %}
                <div class="error">{{ error }}</div>
            {% endif %}
            <form method="POST">
                <input type="email" name="eposta" placeholder="E-posta Adresi" required>
                <input type="password" name="sifre" placeholder="Sifre" required>
                <button type="submit">Giris Yap</button>
            </form>
        </div>
    </div>
</body>
</html>
"""

@app.route('/', methods=['GET', 'POST'])
@app.route('/api/', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        eposta = request.form['eposta']
        sifre = request.form['sifre'].encode('utf-8')
        
        try:
            conn = mysql.connector.connect(
                host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME, port=DB_PORT
            )
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT id, ad, soyad, eposta, sifre_hash, rol FROM kullanicilar WHERE eposta = %s", (eposta,))
            user = cursor.fetchone()
            cursor.close()
            conn.close()
            
            if user and bcrypt.checkpw(sifre, user['sifre_hash'].encode('utf-8')):
                session['user_id'] = user['id']
                session['user_name'] = f"{user['ad']} {user['soyad']}"
                r.set(f"session:{user['id']}", user['ad'])
                return f"Giris Basarili! Hos geldin {user['ad']} {user['soyad']} (Rol: {user['rol']})"
            else:
                error = "E-posta veya sifre hatali!"
        except Exception as e:
            error = f"Veritabani baglanti hatasi: {str(e)}"
            
    return render_template_string(LOGIN_TEMPLATE, error=error)

@app.route('/saglik.txt')
def saglik():
    return "OK"

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8000)