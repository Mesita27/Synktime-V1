import asyncio
import sys
sys.path.append('.')
from services.database_service import DatabaseService
import json

async def inspect_biometric_data():
    print('Detailed inspection of biometric data...')

    db_service = DatabaseService()

    # Test with specific employees that we know have facial data
    employee_ids = [1, 100, 101, 102, 103]  # Based on previous inspection

    for employee_id in employee_ids:
        print(f'\n--- Employee {employee_id} ---')

        # Get facial biometric data
        record = db_service.get_biometric_data(employee_id, 'face')

        if record:
            print(f'ID: {record.get("id", "N/A")}')
            print(f'Biometric Type: {record.get("biometric_type", "N/A")}')

            # Check image_data
            image_data = record.get('image_data', '')
            if image_data:
                print(f'Image Data Length: {len(image_data)}')
                print(f'Starts with data:image: {image_data.startswith("data:image")}')
                print(f'First 100 chars: {image_data[:100]}')

                # Try to parse as JSON
                try:
                    parsed = json.loads(image_data)
                    print(f'Parsed as JSON: {type(parsed)}')
                    if isinstance(parsed, dict):
                        print(f'JSON Keys: {list(parsed.keys())}')
                        if 'embedding' in parsed:
                            print(f'Embedding length: {len(parsed["embedding"])}')
                except json.JSONDecodeError:
                    print('Not valid JSON')
            else:
                print('No image_data')

            # Check embedding_data
            embedding_data = record.get('embedding_data', '')
            if embedding_data:
                print(f'Embedding Data Type: {type(embedding_data)}')
                print(f'Embedding Data Length: {len(embedding_data) if embedding_data else 0}')
                if isinstance(embedding_data, str):
                    print(f'First 100 chars: {embedding_data[:100]}')
            else:
                print('No embedding_data')

            # Check additional_info
            additional_info = record.get('additional_info', '')
            if additional_info:
                print(f'Additional Info Type: {type(additional_info)}')
                if isinstance(additional_info, dict):
                    print(f'Additional Info Keys: {list(additional_info.keys())}')
        else:
            print(f'No facial biometric data found for employee {employee_id}')

if __name__ == "__main__":
    asyncio.run(inspect_biometric_data())
