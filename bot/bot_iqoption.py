import time
import datetime
import csv
import os
from iqoptionapi.stable_api import IQ_Option

# Configuración del bot
email = "brionariomen@gmail.com"           # Cambia por tu email de IQ Option
password = "clave"   # Cambia por tu contraseña
activo = "EURUSD"            # Activo a operar
monto = 1.0                  # Monto por operación
expiracion = 1               # Expiración en minutos

# Archivos para registrar operaciones
CSV_FILE = "operaciones.csv"

def guardar_csv(fecha, activo, tipo, monto, resultado):
    existe = os.path.isfile(CSV_FILE)
    with open(CSV_FILE, mode='a', newline='') as f:
        writer = csv.writer(f)
        if not existe:
            writer.writerow(['fecha','activo','tipo','monto','resultado'])
        writer.writerow([fecha, activo, tipo, monto, resultado])

# Conexión a IQ Option en DEMO
api = IQ_Option(email, password)
api.connect()
api.change_balance("PRACTICE")  # Modo DEMO

if api.check_connect():
    print("✅ Bot conectado en DEMO")
else:
    print("❌ Error al conectar a IQ Option")
    exit()

# Loop principal: operaciones cada 5 segundos
while True:
    try:
        # Tomamos últimas 2 velas de 1 minuto
        candles = api.get_candles(activo, 60, 2, time.time())
        ultima_cierre = candles[-1]['close']
        penultima_cierre = candles[-2]['close']

        # Estrategia simple: CALL si última vela cerró alcista
        if ultima_cierre > penultima_cierre:
            señal = "call"
        else:
            señal = "call"  # para ver ejecuciones constantes en DEMO

        # Ejecutar operación
        status, trade_id = api.buy(monto, activo, señal, expiracion)
        fecha = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        resultado = "Ejecutada" if status else "Error"
        guardar_csv(fecha, activo, señal, monto, resultado)
        print(f"📊 {fecha} | {activo} | {señal.upper()} | {monto}$ | {resultado}")

    except Exception as e:
        print("❌ Error:", e)
        while not api.check_connect():
            print("Reconectando...")
            api.connect()
            time.sleep(5)

    time.sleep(5)
