import configparser
import csv
import os
import mysql.connector
from mysql.connector import Error

CONFIG_FILE = "config.ini"
CSV_FILE = "operaciones.csv"
STATUS_FILE = "bot_status.txt"

def leer_config():
    config = configparser.ConfigParser()
    config.read(CONFIG_FILE)
    return config

def guardar_csv(fecha, activo, tipo, monto, resultado):
    existe = os.path.isfile(CSV_FILE)
    with open(CSV_FILE, mode='a', newline='') as f:
        writer = csv.writer(f)
        if not existe:
            writer.writerow(['fecha','activo','tipo','monto','resultado'])
        writer.writerow([fecha, activo, tipo, monto, resultado])

def guardar_mysql(fecha, activo, tipo, monto, resultado, cfg_mysql):
    try:
        conn = mysql.connector.connect(
            host=cfg_mysql['host'],
            user=cfg_mysql['user'],
            password=cfg_mysql['password'],
            database=cfg_mysql['database']
        )
        cursor = conn.cursor()
        sql = "INSERT INTO operaciones (fecha, activo, tipo, monto, resultado) VALUES (%s,%s,%s,%s,%s)"
        cursor.execute(sql, (fecha, activo, tipo, monto, resultado))
        conn.commit()
        cursor.close()
        conn.close()
    except Error as e:
        print("⚠️ Error MySQL:", e)

def leer_status():
    if not os.path.isfile(STATUS_FILE):
        with open(STATUS_FILE, 'w') as f:
            f.write("ON")
    with open(STATUS_FILE, 'r') as f:
        return f.read().strip().upper()
