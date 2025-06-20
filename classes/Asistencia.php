<?php
class Asistencia {
    private $conn;
    private $table_name = "asistencias";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar asistencia
    public function registrar($id_residente) {
        // Verificar si ya registró asistencia hoy
        if ($this->yaRegistroHoy($id_residente)) {
            throw new Exception("Ya registraste tu asistencia hoy");
        }

        $query = "INSERT INTO " . $this->table_name . " (id_residente) VALUES (:residente)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':residente', $id_residente, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Verificar si ya registró asistencia hoy
    public function yaRegistroHoy($id_residente) {
        $query = "SELECT id FROM " . $this->table_name . " 
                WHERE id_residente = :residente AND DATE(fecha_asistencia) = CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':residente', $id_residente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ? true : false;
    }

    // Obtener asistencias de un residente
    public function obtenerPorResidente($id_residente, $limite = 30) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE id_residente = :residente 
                ORDER BY fecha_asistencia DESC 
                LIMIT :limite";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':residente', $id_residente, PDO::PARAM_INT);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener todas las asistencias
    public function obtenerTodas($filtros = []) {
    $query = "SELECT a.*, u.nombre, u.email, h.numero_habitacion 
            FROM " . $this->table_name . " a
            JOIN usuarios u ON a.id_residente = u.id
            LEFT JOIN asignacion_habitaciones ah ON u.id = ah.id_usuario AND ah.fecha_fin IS NULL
            LEFT JOIN habitaciones h ON ah.id_habitacion = h.id
            WHERE 1=1";
    
    // Aplicar filtros
    $params = [];
    
    if (!empty($filtros['fecha_inicio'])) {
        $query .= " AND DATE(a.fecha_asistencia) >= :fecha_inicio";
        $params[':fecha_inicio'] = $filtros['fecha_inicio'];
    }
    
    if (!empty($filtros['fecha_fin'])) {
        $query .= " AND DATE(a.fecha_asistencia) <= :fecha_fin";
        $params[':fecha_fin'] = $filtros['fecha_fin'];
    }
    
    if (!empty($filtros['id_residente'])) {
        $query .= " AND a.id_residente = :id_residente";
        $params[':id_residente'] = $filtros['id_residente'];
    }
    
    if (!empty($filtros['nombre'])) {
        $query .= " AND (u.nombre LIKE :nombre OR u.email LIKE :nombre)";
        $params[':nombre'] = "%" . $filtros['nombre'] . "%";
    }
    
    if (!empty($filtros['habitacion'])) {
        $query .= " AND h.numero_habitacion LIKE :habitacion";
        $params[':habitacion'] = "%" . $filtros['habitacion'] . "%";
    }
    
    $query .= " ORDER BY a.fecha_asistencia DESC";
    
    if (!empty($filtros['limite'])) {
        $query .= " LIMIT :limite";
        $params[':limite'] = $filtros['limite'];
    }
    
    $stmt = $this->conn->prepare($query);
    
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // Obtener reporte de asistencias
    public function generarReporte($fecha_inicio, $fecha_fin) {
        $query = "SELECT u.nombre, u.email, COUNT(a.id) as total_asistencias,
                        h.numero_habitacion
                FROM usuarios u
                LEFT JOIN asistencias a ON u.id = a.id_residente 
                    AND DATE(a.fecha_asistencia) BETWEEN :fecha_inicio AND :fecha_fin
                LEFT JOIN asignacion_habitaciones ah ON u.id = ah.id_usuario AND ah.fecha_fin IS NULL
                LEFT JOIN habitaciones h ON ah.id_habitacion = h.id
                WHERE u.rol = 'residente'
                GROUP BY u.id
                ORDER BY u.nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>