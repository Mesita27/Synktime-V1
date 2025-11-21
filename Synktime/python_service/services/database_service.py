"""
Database service for SNKTIME Biometric System
Handles database operations for biometric data storage
"""

import json
import logging
from typing import Optional, Dict, Any
from datetime import datetime
import pymysql
from pymysql.cursors import DictCursor

from config.settings import settings

logger = logging.getLogger(__name__)

class DatabaseService:
    """Database service for biometric data operations"""

    def __init__(self):
        self.connection_params = {
            'host': settings.DB_HOST,
            'port': settings.DB_PORT,
            'user': settings.DB_USER,
            'password': settings.DB_PASSWORD,
            'database': settings.DB_NAME,
            'charset': 'utf8mb4',
            'cursorclass': DictCursor
        }

    def _get_connection(self):
        """Get database connection"""
        try:
            return pymysql.connect(**self.connection_params)
        except Exception as e:
            logger.error(f"Database connection failed: {e}")
            raise

    def save_biometric_data(self, employee_id: int, biometric_type: str,
                           embedding_data: list, template_id: str,
                           device_id: str = "python_service") -> bool:
        """
        Save biometric data to database using employee_biometrics table

        Args:
            employee_id: Employee ID
            biometric_type: 'facial' or 'fingerprint'
            embedding_data: List of embedding values
            template_id: Template identifier from Python service
            device_id: Device identifier

        Returns:
            bool: Success status
        """
        connection = None
        try:
            connection = self._get_connection()
            with connection.cursor() as cursor:

                # Convert biometric_type to match database format
                db_biometric_type = 'face' if biometric_type == 'facial' else biometric_type

                # Check if biometric data already exists for this employee and type
                cursor.execute("""
                    SELECT id FROM employee_biometrics
                    WHERE employee_id = %s AND biometric_type = %s
                """, (employee_id, db_biometric_type))

                existing_record = cursor.fetchone()

                # Prepare biometric data as JSON
                biometric_json = json.dumps({
                    'template_id': template_id,
                    'embedding': embedding_data,
                    'device_id': device_id,
                    'created_at': datetime.now().isoformat()
                })

                if existing_record:
                    # Update existing record
                    cursor.execute("""
                        UPDATE employee_biometrics
                        SET biometric_data = %s,
                            additional_info = %s,
                            updated_at = NOW()
                        WHERE id = %s
                    """, (
                        biometric_json,
                        json.dumps({'device_id': device_id, 'template_id': template_id}),
                        existing_record['id']
                    ))
                    logger.info(f"Updated existing biometric data for employee {employee_id}")
                else:
                    # Insert new record
                    cursor.execute("""
                        INSERT INTO employee_biometrics
                        (employee_id, biometric_type, biometric_data, additional_info,
                         created_at, updated_at)
                        VALUES (%s, %s, %s, %s, NOW(), NOW())
                    """, (
                        employee_id,
                        db_biometric_type,
                        biometric_json,
                        json.dumps({'device_id': device_id, 'template_id': template_id})
                    ))
                    logger.info(f"Inserted new biometric data for employee {employee_id}")

                connection.commit()
                return True

        except Exception as e:
            logger.error(f"Failed to save biometric data: {e}")
            if connection:
                connection.rollback()
            return False
        finally:
            if connection:
                connection.close()

    def get_biometric_data(self, employee_id: int, biometric_type: str) -> Optional[Dict[str, Any]]:
        """
        Retrieve biometric data for employee from employee_biometrics table

        Args:
            employee_id: Employee ID
            biometric_type: 'facial' or 'fingerprint'

        Returns:
            Dict with biometric data or None if not found
        """
        connection = None
        try:
            connection = self._get_connection()
            with connection.cursor() as cursor:

                # Convert biometric_type to match database format
                db_biometric_type = 'face' if biometric_type == 'facial' else biometric_type

                logger.info(f"Querying biometric data for employee {employee_id}, type {db_biometric_type}")

                cursor.execute("""
                    SELECT * FROM employee_biometrics
                    WHERE employee_id = %s AND biometric_type = %s
                    ORDER BY updated_at DESC
                    LIMIT 1
                """, (employee_id, db_biometric_type))

                result = cursor.fetchone()
                logger.info(f"Query result: {result}")
                logger.info(f"Result type: {type(result)}")

                if result:
                    logger.info(f"Result keys: {list(result.keys()) if hasattr(result, 'keys') else 'No keys method'}")

                    # Handle biometric_data - it may be base64-encoded image data
                    if result.get('biometric_data'):
                        biometric_data = result['biometric_data']
                        logger.info(f"Biometric data type: {type(biometric_data)}")
                        logger.info(f"Biometric data length: {len(biometric_data) if hasattr(biometric_data, '__len__') else 'N/A'}")

                        # Check if it's base64-encoded data (not JSON)
                        if isinstance(biometric_data, str) and len(biometric_data) > 100:
                            # Likely base64-encoded image data
                            result['image_data'] = biometric_data
                            result['embedding_data'] = []  # Will be extracted when needed
                            result['template_id'] = result.get('additional_info', {}).get('template_id', '') if isinstance(result.get('additional_info'), dict) else ''
                            result['device_id'] = result.get('additional_info', {}).get('device_id', '') if isinstance(result.get('additional_info'), dict) else ''
                        else:
                            # Try to parse as JSON (legacy format)
                            try:
                                biometric_info = json.loads(biometric_data)
                                logger.info(f"Parsed biometric_info: {type(biometric_info)}")
                                result['embedding_data'] = biometric_info.get('embedding', [])
                                result['template_id'] = biometric_info.get('template_id', '')
                                result['device_id'] = biometric_info.get('device_id', '')
                            except (json.JSONDecodeError, TypeError) as e:
                                logger.warning(f"Failed to parse biometric data for employee {employee_id}: {e}")
                                result['image_data'] = biometric_data
                                result['embedding_data'] = []

                    # Parse additional_info JSON if exists
                    if result.get('additional_info'):
                        try:
                            if isinstance(result['additional_info'], str):
                                result['additional_info'] = json.loads(result['additional_info'])
                            elif isinstance(result['additional_info'], dict):
                                # Already parsed
                                pass
                            else:
                                logger.warning(f"Unexpected additional_info type: {type(result['additional_info'])}")
                        except json.JSONDecodeError:
                            logger.warning(f"Failed to parse additional info for employee {employee_id}")

                    return result
                return None

        except Exception as e:
            logger.error(f"Failed to retrieve biometric data: {e}")
            import traceback
            logger.error(f"Traceback: {traceback.format_exc()}")
            return None
        finally:
            if connection:
                connection.close()

    def log_biometric_operation(self, employee_id: int, operation: str,
                               biometric_type: str, success: bool,
                               processing_time_ms: int = None,
                               error_message: str = None,
                               device_id: str = "python_service") -> bool:
        """
        Log biometric operation to biometric_logs table

        Args:
            employee_id: Employee ID
            operation: Operation type (enroll, verify, etc.)
            biometric_type: 'facial' or 'fingerprint'
            success: Operation success status
            processing_time_ms: Processing time in milliseconds
            error_message: Error message if operation failed
            device_id: Device identifier

        Returns:
            bool: Success status
        """
        connection = None
        try:
            connection = self._get_connection()
            with connection.cursor() as cursor:
                cursor.execute("""
                    INSERT INTO biometric_logs
                    (ID_EMPLEADO, OPERATION, BIOMETRIC_TYPE, SUCCESS,
                     PROCESSING_TIME_MS, ERROR_MESSAGE, DEVICE_ID, API_SOURCE,
                     CREATED_AT)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW())
                """, (
                    employee_id,
                    operation,
                    biometric_type,
                    success,
                    processing_time_ms,
                    error_message,
                    device_id,
                    "python_service"
                ))

                connection.commit()
                return True

        except Exception as e:
            logger.error(f"Failed to log biometric operation: {e}")
            return False
        finally:
            if connection:
                connection.close()

    def get_employee_biometric_summary(self, employee_id: int) -> Optional[Dict[str, Any]]:
        """
        Get summary of all biometric data for an employee

        Args:
            employee_id: Employee ID

        Returns:
            Dictionary with biometric summary or None if not found
        """
        connection = None
        try:
            connection = self._get_connection()
            with connection.cursor() as cursor:
                cursor.execute("""
                    SELECT
                        biometric_type,
                        COUNT(*) as count,
                        MAX(created_at) as last_updated,
                        MAX(updated_at) as last_modified
                    FROM employee_biometrics
                    WHERE employee_id = %s
                    GROUP BY biometric_type
                """, (employee_id,))

                results = cursor.fetchall()

                if results:
                    summary = {}
                    for result in results:
                        summary[result['biometric_type']] = {
                            'count': result['count'],
                            'last_updated': result['last_updated'],
                            'last_modified': result['last_modified']
                        }

                    return summary

                return None

        except Exception as e:
            logger.error(f"Failed to get employee biometric summary: {e}")
            return None
        finally:
            if connection:
                connection.close()

    def get_company_employees_biometric_data(self, company_id: int, biometric_type: str = "face") -> list:
        """
        Get all employees with biometric data for a specific company
        
        Args:
            company_id: Company ID to filter employees
            biometric_type: Type of biometric data ('face', 'fingerprint')
            
        Returns:
            List of dictionaries with employee and biometric data
        """
        connection = None
        try:
            connection = self._get_connection()
            with connection.cursor() as cursor:
                # Convert biometric_type to match database format
                db_biometric_type = 'face' if biometric_type == 'facial' else biometric_type
                
                logger.info(f"Querying biometric data for company {company_id}, type {db_biometric_type}")
                
                cursor.execute("""
                    SELECT 
                        e.ID_EMPLEADO as employee_id,
                        e.NOMBRE as first_name,
                        e.APELLIDO as last_name,
                        e.DNI as dni,
                        eb.biometric_data,
                        eb.additional_info,
                        eb.created_at,
                        eb.updated_at,
                        est.NOMBRE as establishment_name,
                        est.ID_ESTABLECIMIENTO as establishment_id
                    FROM empleado e
                    JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    JOIN sede s ON est.ID_SEDE = s.ID_SEDE
                    JOIN employee_biometrics eb ON e.ID_EMPLEADO = eb.employee_id
                    WHERE s.ID_EMPRESA = %s 
                        AND eb.biometric_type = %s
                        AND e.ACTIVO = 'S'
                        AND e.ESTADO = 'A'
                    ORDER BY e.NOMBRE, e.APELLIDO
                """, (company_id, db_biometric_type))
                
                results = cursor.fetchall()
                employees_data = []
                
                for result in results:
                    try:
                        # Parse biometric data
                        biometric_data = result['biometric_data']
                        embedding_data = None
                        template_id = ''
                        device_id = ''
                        
                        # Handle different biometric data formats
                        if isinstance(biometric_data, str) and len(biometric_data) > 100:
                            # Check if it's base64-encoded image data or JSON
                            if biometric_data.startswith('data:image'):
                                # Base64 image data - will need extraction
                                pass  # embedding_data stays None
                            else:
                                # Try to parse as JSON
                                try:
                                    biometric_info = json.loads(biometric_data)
                                    embedding_data = biometric_info.get('embedding', [])
                                    template_id = biometric_info.get('template_id', '')
                                    device_id = biometric_info.get('device_id', '')
                                except (json.JSONDecodeError, TypeError):
                                    logger.warning(f"Failed to parse biometric data for employee {result['employee_id']}")
                                    continue
                        
                        # Parse additional_info if exists
                        additional_info = result.get('additional_info', {})
                        if isinstance(additional_info, str):
                            try:
                                additional_info = json.loads(additional_info)
                            except json.JSONDecodeError:
                                additional_info = {}
                        
                        # Only include employees with valid embedding data
                        if embedding_data:
                            employee_data = {
                                'employee_id': result['employee_id'],
                                'first_name': result['first_name'],
                                'last_name': result['last_name'],
                                'full_name': f"{result['first_name']} {result['last_name']}",
                                'dni': result['dni'],
                                'embedding_data': embedding_data,
                                'template_id': template_id,
                                'device_id': device_id,
                                'establishment_name': result['establishment_name'],
                                'establishment_id': result['establishment_id'],
                                'created_at': result['created_at'],
                                'updated_at': result['updated_at']
                            }
                            employees_data.append(employee_data)
                    
                    except Exception as e:
                        logger.warning(f"Error processing employee {result.get('employee_id', 'unknown')}: {e}")
                        continue
                
                logger.info(f"Found {len(employees_data)} employees with valid biometric data for company {company_id}")
                return employees_data
                
        except Exception as e:
            logger.error(f"Failed to get company employees biometric data: {e}")
            import traceback
            logger.error(f"Traceback: {traceback.format_exc()}")
            return []
        finally:
            if connection:
                connection.close()

# Global database service instance
db_service = DatabaseService()
