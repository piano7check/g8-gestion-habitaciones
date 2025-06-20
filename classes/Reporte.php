<?php
class Reporte {
    private $conn;
    private $table_name = "reportes";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nuevo reporte
    public function crear($id_usuario, $reporte_texto, $id_habitacion = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                (id_usuario, id_habitacion, reporte_texto) 
                VALUES (:usuario, :habitacion, :reporte)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':habitacion', $id_habitacion, PDO::PARAM_INT);
        $stmt->bindParam(':reporte', $reporte_texto);
        
        return $stmt->execute();
    }

    // Obtener reportes
    public function obtenerTodos($filtros = []) {
        $query = "SELECT r.*, u.nombre as nombre_usuario, u.email, 
                        h.numero_habitacion
                FROM " . $this->table_name . " r
                JOIN usuarios u ON r.id_usuario = u.id
                LEFT JOIN habitaciones h ON r.id_habitacion = h.id";
        
        // Aplicar filtros
        $where = [];
        $params = [];
        
        if (!empty($filtros['id_usuario'])) {
            $where[] = "r.id_usuario = :id_usuario";
            $params[':id_usuario'] = $filtros['id_usuario'];
        }
        
        if (!empty($filtros['id_habitacion'])) {
            $where[] = "r.id_habitacion = :id_habitacion";
            $params[':id_habitacion'] = $filtros['id_habitacion'];
        }
        
        if (!empty($filtros['fecha_inicio'])) {
            $where[] = "DATE(r.fecha) >= :fecha_inicio";
            $params[':fecha_inicio'] = $filtros['fecha_inicio'];
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $where[] = "DATE(r.fecha) <= :fecha_fin";
            $params[':fecha_fin'] = $filtros['fecha_fin'];
        }
        
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        
        $query .= " ORDER BY r.fecha DESC";
        
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

    // Obtener reporte por ID
    public function obtenerPorId($id) {
        $query = "SELECT r.*, u.nombre as nombre_usuario, u.email, 
                        h.numero_habitacion
                FROM " . $this->table_name . " r
                JOIN usuarios u ON r.id_usuario = u.id
                LEFT JOIN habitaciones h ON r.id_habitacion = h.id
                WHERE r.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Marcar reporte como resuelto
    public function marcarResuelto($id) {
        $query = "UPDATE " . $this->table_name . " 
                SET resuelto = 1 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerTodosFiltrados($filtros = []) {
    $query = "SELECT r.*, u.nombre as nombre_usuario, u.email, 
                    h.numero_habitacion
            FROM " . $this->table_name . " r
            JOIN usuarios u ON r.id_usuario = u.id
            LEFT JOIN habitaciones h ON r.id_habitacion = h.id
            WHERE 1=1";
    
    if (!empty($filtros['fecha_inicio'])) {
        $query .= " AND DATE(r.fecha) >= :fecha_inicio";
    }
    
    if (!empty($filtros['fecha_fin'])) {
        $query .= " AND DATE(r.fecha) <= :fecha_fin";
    }
    
    if (!empty($filtros['residente'])) {
        $query .= " AND (u.nombre LIKE :residente OR u.email LIKE :residente)";
    }
    
    if (!empty($filtros['habitacion'])) {
        $query .= " AND h.numero_habitacion LIKE :habitacion";
    }
    
    if (isset($filtros['estado'])) {
        $query .= " AND r.resuelto = :estado";
    }
    
    $query .= " ORDER BY r.fecha DESC";
    
    $stmt = $this->conn->prepare($query);
    
    if (!empty($filtros['fecha_inicio'])) {
        $stmt->bindParam(':fecha_inicio', $filtros['fecha_inicio']);
    }
    
    if (!empty($filtros['fecha_fin'])) {
        $stmt->bindParam(':fecha_fin', $filtros['fecha_fin']);
    }
    
    if (!empty($filtros['residente'])) {
        $residente = "%" . $filtros['residente'] . "%";
        $stmt->bindParam(':residente', $residente);
    }
    
    if (!empty($filtros['habitacion'])) {
        $habitacion = "%" . $filtros['habitacion'] . "%";
        $stmt->bindParam(':habitacion', $habitacion);
    }
    
    if (isset($filtros['estado'])) {
        $stmt->bindParam(':estado', $filtros['estado'], PDO::PARAM_BOOL);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>