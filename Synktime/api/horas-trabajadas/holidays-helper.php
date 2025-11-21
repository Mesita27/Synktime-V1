<?php
/**
 * Helper class for managing holidays and civic days
 * Integrates with Colombian holidays API and manages local civic days
 */
class HolidaysHelper {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Get Colombian holidays for a specific year
     * Uses external API: https://date.nager.at/Api
     */
    public function getColombianHolidays($year) {
        $cacheKey = "holidays_colombia_{$year}";
        
        // Check cache first (you could implement database caching here)
        $cached = $this->getCachedHolidays($year);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Using free API for Colombian holidays
            $url = "https://date.nager.at/api/v3/PublicHolidays/{$year}/CO";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'SynkTime/1.0'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log("Failed to fetch holidays from API for year {$year}");
                return $this->getFallbackHolidays($year);
            }
            
            $holidays = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error for holidays: " . json_last_error_msg());
                return $this->getFallbackHolidays($year);
            }
            
            // Extract dates
            $holidayDates = [];
            foreach ($holidays as $holiday) {
                $holidayDates[] = $holiday['date'];
            }
            
            // Cache the result
            $this->cacheHolidays($year, $holidayDates);
            
            return $holidayDates;
            
        } catch (Exception $e) {
            error_log("Error fetching holidays: " . $e->getMessage());
            return $this->getFallbackHolidays($year);
        }
    }
    
    /**
     * Get holidays for a date range
     */
    public function getFestivosRango($fechaDesde, $fechaHasta) {
        $festivos = [];
        
        $yearStart = date('Y', strtotime($fechaDesde));
        $yearEnd = date('Y', strtotime($fechaHasta));
        
        // Get holidays for all years in the range
        for ($year = $yearStart; $year <= $yearEnd; $year++) {
            $yearHolidays = $this->getColombianHolidays($year);
            $festivos = array_merge($festivos, $yearHolidays);
        }
        
        // Filter holidays within the date range
        $filteredFestivos = [];
        foreach ($festivos as $festivo) {
            if ($festivo >= $fechaDesde && $festivo <= $fechaHasta) {
                $filteredFestivos[] = $festivo;
            }
        }
        
        // Add civic days
        $civicDays = $this->getCivicDays($fechaDesde, $fechaHasta);
        $filteredFestivos = array_merge($filteredFestivos, $civicDays);
        
        return array_unique($filteredFestivos);
    }
    
    /**
     * Check if a specific date is a civic day
     */
    public function esDiaCivico($fecha) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) 
                FROM DIAS_CIVICOS 
                WHERE FECHA = :fecha 
                AND ESTADO = 'A'
            ");
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking civic day: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get civic days within a date range
     */
    private function getCivicDays($fechaDesde, $fechaHasta) {
        try {
            $stmt = $this->conn->prepare("
                SELECT FECHA 
                FROM DIAS_CIVICOS 
                WHERE FECHA BETWEEN :fecha_desde AND :fecha_hasta 
                AND ESTADO = 'A'
                ORDER BY FECHA
            ");
            $stmt->bindParam(':fecha_desde', $fechaDesde);
            $stmt->bindParam(':fecha_hasta', $fechaHasta);
            $stmt->execute();
            
            $civicDays = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $civicDays[] = $row['FECHA'];
            }
            
            return $civicDays;
        } catch (PDOException $e) {
            error_log("Error getting civic days: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Register a new civic day
     */
    public function registerCivicDay($fecha, $nombre, $descripcion = null, $empresaId = null) {
        try {
            // Check if already exists
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) 
                FROM DIAS_CIVICOS 
                WHERE FECHA = :fecha 
                AND (ID_EMPRESA = :empresa_id OR ID_EMPRESA IS NULL)
            ");
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':empresa_id', $empresaId);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Ya existe un día cívico registrado para esta fecha'];
            }
            
            // Insert new civic day
            $stmt = $this->conn->prepare("
                INSERT INTO DIAS_CIVICOS (FECHA, NOMBRE, DESCRIPCION, ID_EMPRESA, ESTADO, FECHA_CREACION) 
                VALUES (:fecha, :nombre, :descripcion, :empresa_id, 'A', NOW())
            ");
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':empresa_id', $empresaId);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Día cívico registrado correctamente'];
            
        } catch (PDOException $e) {
            error_log("Error registering civic day: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar el día cívico: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cache holidays in database
     */
    private function cacheHolidays($year, $holidays) {
        try {
            // First, delete existing cache for the year
            $stmt = $this->conn->prepare("DELETE FROM HOLIDAYS_CACHE WHERE YEAR = :year");
            $stmt->bindParam(':year', $year);
            $stmt->execute();
            
            // Insert new cache
            foreach ($holidays as $holiday) {
                $stmt = $this->conn->prepare("
                    INSERT INTO HOLIDAYS_CACHE (YEAR, FECHA, FECHA_CACHE) 
                    VALUES (:year, :fecha, NOW())
                ");
                $stmt->bindParam(':year', $year);
                $stmt->bindParam(':fecha', $holiday);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            // Cache errors are not critical, just log them
            error_log("Error caching holidays: " . $e->getMessage());
        }
    }
    
    /**
     * Get cached holidays
     */
    private function getCachedHolidays($year) {
        try {
            // Check if we have recent cache (less than 1 week old)
            $stmt = $this->conn->prepare("
                SELECT FECHA 
                FROM HOLIDAYS_CACHE 
                WHERE YEAR = :year 
                AND FECHA_CACHE > DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY FECHA
            ");
            $stmt->bindParam(':year', $year);
            $stmt->execute();
            
            $holidays = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $holidays[] = $row['FECHA'];
            }
            
            // Return null if no cache found, otherwise return the holidays
            return count($holidays) > 0 ? $holidays : null;
            
        } catch (PDOException $e) {
            error_log("Error getting cached holidays: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fallback holidays for Colombia (major fixed holidays)
     */
    private function getFallbackHolidays($year) {
        $fallbackHolidays = [
            "{$year}-01-01", // New Year's Day
            "{$year}-05-01", // Labour Day
            "{$year}-07-20", // Independence Day
            "{$year}-08-07", // Battle of Boyacá
            "{$year}-12-08", // Immaculate Conception
            "{$year}-12-25"  // Christmas Day
        ];
        
        return $fallbackHolidays;
    }
}

// Create tables if they don't exist (you might want to run this once)
function createHolidayTables() {
    global $conn;
    
    try {
        // Create civic days table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS DIAS_CIVICOS (
                ID_DIA_CIVICO INT AUTO_INCREMENT PRIMARY KEY,
                FECHA DATE NOT NULL,
                NOMBRE VARCHAR(100) NOT NULL,
                DESCRIPCION TEXT,
                ID_EMPRESA INT,
                ESTADO CHAR(1) DEFAULT 'A',
                FECHA_CREACION DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_fecha (FECHA),
                INDEX idx_empresa (ID_EMPRESA)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create holidays cache table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS HOLIDAYS_CACHE (
                ID_CACHE INT AUTO_INCREMENT PRIMARY KEY,
                YEAR INT NOT NULL,
                FECHA DATE NOT NULL,
                FECHA_CACHE DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_year (YEAR),
                INDEX idx_fecha_cache (FECHA_CACHE)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
    } catch (PDOException $e) {
        error_log("Error creating holiday tables: " . $e->getMessage());
    }
}

// Initialize tables on first run
createHolidayTables();
?>