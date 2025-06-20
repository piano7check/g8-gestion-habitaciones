<?php
class Habitacion {
    private $conn;
    private $table_name = "habitaciones";

    public $id;
    public $numero_habitacion;
    public $estado;
    public $tiene_banio_privado;
    public $capacidad;
    public $residentes_asignados;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nueva habitación
    public function crear($numero_habitacion, $capacidad, $tiene_banio_privado) {
        $query = "INSERT INTO " . $this->table_name . " 
                (numero_habitacion, estado, tiene_banio_privado, capacidad) 
                VALUES (:numero, 'vacía', :banio, :capacidad)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->numero_habitacion = limpiarDatos($numero_habitacion);
        $this->tiene_banio_privado = (bool)$tiene_banio_privado;
        $this->capacidad = (int)$capacidad;
        
        $stmt->bindParam(':numero', $this->numero_habitacion);
        $stmt->bindParam(':banio', $this->tiene_banio_privado, PDO::PARAM_BOOL);
        $stmt->bindParam(':capacidad', $this->capacidad, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // En tu clase Habitacion.php
public function obtenerTodas() {
    $query = "SELECT *, 
            (SELECT COUNT(*) FROM asignacion_habitaciones 
            WHERE id_habitacion = habitaciones.id AND fecha_fin IS NULL) as residentes_asignados
            FROM " . $this->table_name . " 
            ORDER BY numero_habitacion";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // Obtener habitación por ID
    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar habitación
    public function actualizar($id, $numero_habitacion, $capacidad, $tiene_banio_privado) {
        $query = "UPDATE " . $this->table_name . " 
                SET numero_habitacion = :numero, 
                    capacidad = :capacidad, 
                    tiene_banio_privado = :banio 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->numero_habitacion = limpiarDatos($numero_habitacion);
        $this->tiene_banio_privado = (bool)$tiene_banio_privado;
        $this->capacidad = (int)$capacidad;
        
        $stmt->bindParam(':numero', $this->numero_habitacion);
        $stmt->bindParam(':capacidad', $this->capacidad, PDO::PARAM_INT);
        $stmt->bindParam(':banio', $this->tiene_banio_privado, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function eliminar($id) {
    // Iniciar transacción
    $this->conn->beginTransaction();

    try {
        // 1. Eliminar reportes relacionados con la habitación
        $this->eliminarReportesDeHabitacion($id);
        
        // 2. Eliminar asignaciones de habitación
        $this->eliminarAsignacionesHabitacion($id);
        
        // 3. Finalmente eliminar la habitación
        $query = "DELETE FROM habitaciones WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Confirmar transacción
        $this->conn->commit();
        return true;
    } catch (Exception $e) {
        // Revertir en caso de error
        $this->conn->rollBack();
        throw $e;
    }
}

private function eliminarReportesDeHabitacion($id_habitacion) {
    $query = "DELETE FROM reportes WHERE id_habitacion = :id_habitacion";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id_habitacion', $id_habitacion);
    return $stmt->execute();
}

private function eliminarAsignacionesHabitacion($id_habitacion) {
    $query = "DELETE FROM asignacion_habitaciones WHERE id_habitacion = :id_habitacion";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id_habitacion', $id_habitacion);
    return $stmt->execute();
}

    public function actualizarEstado($id_habitacion) {
    // Obtener número de residentes asignados
    $query = "SELECT COUNT(*) as total 
            FROM asignacion_habitaciones 
            WHERE id_habitacion = :id_habitacion AND fecha_fin IS NULL";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id_habitacion', $id_habitacion);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Determinar nuevo estado
    $habitacion = $this->obtenerPorId($id_habitacion);
    $capacidad = $habitacion['capacidad'];
    $residentes_asignados = $result['total'];
    
    if ($residentes_asignados == 0) {
        $estado = 'vacía';
    } elseif ($residentes_asignados < $capacidad) {
        $estado = 'con espacio';
    } else {
        $estado = 'ocupada';
    }

    // Actualizar estado
    $query = "UPDATE " . $this->table_name . " 
            SET estado = :estado, residentes_asignados = :residentes
            WHERE id = :id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':residentes', $residentes_asignados);
    $stmt->bindParam(':id', $id_habitacion);
    
    return $stmt->execute();
}

    // Obtener habitaciones disponibles
    public function obtenerDisponibles() {
    $query = "SELECT h.*, 
            (SELECT COUNT(*) FROM asignacion_habitaciones 
            WHERE id_habitacion = h.id AND fecha_fin IS NULL) as residentes_asignados
            FROM " . $this->table_name . " h
            WHERE h.estado != 'ocupada' 
            ORDER BY h.numero_habitacion";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodasFiltradas($filtros = []) {
    $query = "SELECT h.*, 
            (SELECT COUNT(*) FROM asignacion_habitaciones 
            WHERE id_habitacion = h.id AND fecha_fin IS NULL) as residentes_asignados
            FROM " . $this->table_name . " h
            WHERE 1=1";
    
    if (!empty($filtros['numero'])) {
        $query .= " AND h.numero_habitacion LIKE :numero";
    }
    
    if (!empty($filtros['estado'])) {
        $query .= " AND h.estado = :estado";
    }
    
    if (!empty($filtros['capacidad'])) {
        $query .= " AND h.capacidad = :capacidad";
    }
    
    $query .= " ORDER BY h.numero_habitacion ASC";
    
    $stmt = $this->conn->prepare($query);
    
    if (!empty($filtros['numero'])) {
        $numero = "%" . $filtros['numero'] . "%";
        $stmt->bindParam(':numero', $numero);
    }
    
    if (!empty($filtros['estado'])) {
        $stmt->bindParam(':estado', $filtros['estado']);
    }
    
    if (!empty($filtros['capacidad'])) {
        $stmt->bindParam(':capacidad', $filtros['capacidad'], PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    $habitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determinar el estado basado en la capacidad y residentes asignados
    foreach ($habitaciones as &$habitacion) {
        if ($habitacion['residentes_asignados'] >= $habitacion['capacidad']) {
            $habitacion['estado'] = 'ocupada';
        } elseif ($habitacion['residentes_asignados'] > 0) {
            $habitacion['estado'] = 'con espacio';
        } else {
            $habitacion['estado'] = 'disponible';
        }
    }
    
    return $habitaciones;
}
}
?>