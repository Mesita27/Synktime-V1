import sys
sys.path.append('.')
from services.database_service import DatabaseService
import json

# Inspeccionar datos biométricos
db = DatabaseService()
connection = db._get_connection()
with connection.cursor() as cursor:
    cursor.execute('SELECT * FROM employee_biometrics WHERE biometric_type = %s LIMIT 5', ('face',))
    results = cursor.fetchall()

    print('Datos biométricos faciales encontrados:')
    for i, result in enumerate(results):
        print(f'\n--- Registro {i+1} ---')
        print(f'ID: {result.get("id")}')
        print(f'Employee ID: {result.get("employee_id")}')
        print(f'Biometric Type: {result.get("biometric_type")}')
        biometric_data = result.get('biometric_data', '')
        print(f'Biometric Data Length: {len(biometric_data)}')
        print(f'Biometric Data Preview: {biometric_data[:200]}...')

        additional_info = result.get('additional_info')
        if additional_info:
            print(f'Additional Info Type: {type(additional_info)}')
            print(f'Additional Info Preview: {str(additional_info)[:200]}...')

connection.close()
