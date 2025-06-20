<?php
class AsignacionHabitacion {
    private $conn;
    private $table_name = "asignacion_habitaciones";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Asignar habitación a usuario
    public function asignar($id_usuario, $id_habitacion, $fecha_inicio) {
        // Verificar si el usuario ya tiene una asignación activa
        if ($this->tieneAsignacionActiva($id_usuario)) {
            throw new Exception("El usuario ya tiene una asignación activa");
        }

        // Verificar si la habitación tiene capacidad
        $habitacion = new Habitacion($this->conn);
        $info_habitacion = $habitacion->obtenerPorId($id_habitacion);
        
        if (!$info_habitacion || $info_habitacion['estado'] === 'ocupada') {
            throw new Exception("La habitación no está disponible");
        }

        // Iniciar transacción
        $this->conn->beginTransaction();

        try {
            // Crear asignación
            $query = "INSERT INTO " . $this->table_name . " 
                    (id_usuario, id_habitacion, fecha_inicio) 
                    VALUES (:usuario, :habitacion, :fecha)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->bindParam(':habitacion', $id_habitacion, PDO::PARAM_INT);
            $stmt->bindParam(':fecha', $fecha_inicio);
            $stmt->execute();

            // Actualizar contador de residentes en la habitación
            $query_count = "SELECT COUNT(*) as total 
                        FROM " . $this->table_name . " 
                        WHERE id_habitacion = :habitacion AND fecha_fin IS NULL";
            
            $stmt_count = $this->conn->prepare($query_count);
            $stmt_count->bindParam(':habitacion', $id_habitacion, PDO::PARAM_INT);
            $stmt_count->execute();
            $result = $stmt_count->fetch(PDO::FETCH_ASSOC);
            
            $habitacion->actualizarEstado($id_habitacion);

            // Confirmar transacción
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Liberar habitación
    public function liberar($id_usuario) {
        // Obtener asignación activa
        $asignacion = $this->obtenerAsignacionActiva($id_usuario);
        if (!$asignacion) {
            throw new Exception("El usuario no tiene asignación activa");
        }

        // Iniciar transacción
        $this->conn->beginTransaction();

        try {
            // Actualizar asignación
            $query = "UPDATE " . $this->table_name . " 
                    SET fecha_fin = CURDATE() 
                    WHERE id_usuario = :usuario AND fecha_fin IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->execute();

            // Actualizar contador de residentes en la habitación
            $habitacion = new Habitacion($this->conn);
            
            $query_count = "SELECT COUNT(*) as total 
                        FROM " . $this->table_name . " 
                        WHERE id_habitacion = :habitacion AND fecha_fin IS NULL";
            
            $stmt_count = $this->conn->prepare($query_count);
            $stmt_count->bindParam(':habitacion', $asignacion['id_habitacion'], PDO::PARAM_INT);
            $stmt_count->execute();
            $result = $stmt_count->fetch(PDO::FETCH_ASSOC);
            
            $habitacion->actualizarEstado($asignacion['id_habitacion']);

            // Confirmar transacción
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Obtener asignación activa de un usuario
    public function obtenerAsignacionActiva($usuario_id) {
    $query = "SELECT 
                a.id_habitacion,
                h.numero_habitacion,
                h.estado,
                h.capacidad,
                h.tiene_banio_privado,
                a.fecha_inicio,
                (SELECT COUNT(*) FROM asignacion_habitaciones WHERE id_habitacion = h.id AND fecha_fin IS NULL) as residentes_asignados
            FROM asignacion_habitaciones a
            JOIN habitaciones h ON a.id_habitacion = h.id
            WHERE a.id_usuario = ? AND a.fecha_fin IS NULL";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $usuario_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    // Verificar si usuario tiene asignación activa
    public function tieneAsignacionActiva($id_usuario) {
        return $this->obtenerAsignacionActiva($id_usuario) !== false;
    }

    // Obtener todas las asignaciones activas
    public function obtenerTodasAsignacionesActivas() {
        $query = "SELECT a.*, u.nombre as nombre_usuario, u.email, 
                        h.numero_habitacion, h.capacidad, h.estado 
                FROM " . $this->table_name . " a
                JOIN usuarios u ON a.id_usuario = u.id
                JOIN habitaciones h ON a.id_habitacion = h.id
                WHERE a.fecha_fin IS NULL
                ORDER BY h.numero_habitacion";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener historial de asignaciones
    public function obtenerHistorialAsignaciones() {
        $query = "SELECT a.*, u.nombre as nombre_usuario, u.email, 
                        h.numero_habitacion 
                FROM " . $this->table_name . " a
                JOIN usuarios u ON a.id_usuario = u.id
                JOIN habitaciones h ON a.id_habitacion = h.id
                ORDER BY a.fecha_inicio DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodasAsignacionesFiltradas($filtros = []) {
    $query = "SELECT a.*, u.nombre as nombre_usuario, u.email, h.numero_habitacion, h.estado 
            FROM asignacion_habitaciones a
            JOIN usuarios u ON a.id_usuario = u.id
            JOIN habitaciones h ON a.id_habitacion = h.id
            WHERE a.fecha_fin IS NULL";
    
    if (!empty($filtros['residente'])) {
        $query .= " AND (u.nombre LIKE :residente OR u.email LIKE :residente)";
    }
    
    if (!empty($filtros['habitacion'])) {
        $query .= " AND h.numero_habitacion LIKE :habitacion";
    }
    
    if (!empty($filtros['estado'])) {
        $query .= " AND h.estado = :estado";
    }
    
    $query .= " ORDER BY h.numero_habitacion";
    
    $stmt = $this->conn->prepare($query);
    
    if (!empty($filtros['residente'])) {
        $residente = "%" . $filtros['residente'] . "%";
        $stmt->bindParam(':residente', $residente);
    }
    
    if (!empty($filtros['habitacion'])) {
        $habitacion = "%" . $filtros['habitacion'] . "%";
        $stmt->bindParam(':habitacion', $habitacion);
    }
    
    if (!empty($filtros['estado'])) {
        $stmt->bindParam(':estado', $filtros['estado']);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>