<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar nuevo usuario
    public function registro($nombre, $email, $contraseña) {
        // Validar email
        if (!$this->validarEmail($email)) {
            throw new Exception("El formato del email no es válido");
        }

        // Verificar si el email ya existe
        if ($this->emailExiste($email)) {
            throw new Exception("El email ya está registrado");
        }

        $query = "INSERT INTO " . $this->table_name . " 
                (nombre, email, contrasena, rol) 
                VALUES (:nombre, :email, :contrasena, 'residente')";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $nombre = htmlspecialchars(strip_tags($nombre));
        $email = htmlspecialchars(strip_tags($email));
        $contraseña_hash = password_hash($contraseña, PASSWORD_BCRYPT);

        // Bind parameters
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contrasena', $contraseña_hash);

        return $stmt->execute();
    }

    // Iniciar sesión
    public function login($email, $contraseña) {
        $query = "SELECT id, nombre, email, contrasena, rol 
                FROM " . $this->table_name . " 
                WHERE email = :email";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($contraseña, $row['contrasena'])) {
                // Actualizar sesión
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['nombre'] = $row['nombre'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['rol'] = $row['rol'];
                return true;
            }
        }
        return false;
    }

    // Obtener todos los usuarios
    public function obtenerTodos() {
        $query = "SELECT id, nombre, email, rol, fecha_registro 
                FROM " . $this->table_name . " 
                ORDER BY fecha_registro DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener usuario por ID
    public function obtenerPorId($id) {
        $query = "SELECT id, nombre, email, rol, fecha_registro 
                FROM " . $this->table_name . " 
                WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar usuario
    public function actualizar($id, $nombre, $email, $rol = null) {
        $query = "UPDATE " . $this->table_name . " 
                SET nombre = :nombre, email = :email";
        
        if ($rol !== null) {
            $query .= ", rol = :rol";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        
        $nombre = htmlspecialchars(strip_tags($nombre));
        $email = htmlspecialchars(strip_tags($email));
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id);
        
        if ($rol !== null) {
            $rol = htmlspecialchars(strip_tags($rol));
            $stmt->bindParam(':rol', $rol);
        }

        return $stmt->execute();
    }

    // Eliminar usuario y todos sus datos relacionados
    public function eliminar($id) {
        // Verificar que no sea el último administrador
        $query_admin = "SELECT COUNT(*) FROM " . $this->table_name . " 
                    WHERE rol = 'admin' AND id != :id";
        $stmt_admin = $this->conn->prepare($query_admin);
        $stmt_admin->bindParam(':id', $id);
        $stmt_admin->execute();
        
        if ($stmt_admin->fetchColumn() < 1) {
            throw new Exception("No se puede eliminar. Debe haber al menos un administrador");
        }

        // Iniciar transacción
        $this->conn->beginTransaction();

        try {
            // 1. Eliminar asignaciones de habitación
            $this->eliminarAsignacionesHabitacion($id);
            
            // 2. Eliminar asistencias
            $this->eliminarAsistencias($id);
            
            // 3. Eliminar reportes
            $this->eliminarReportes($id);
            
            // 4. Finalmente eliminar el usuario
            $query_usuario = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt_usuario = $this->conn->prepare($query_usuario);
            $stmt_usuario->bindParam(':id', $id);
            $stmt_usuario->execute();
            
            // Confirmar transacción
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Revertir en caso de error
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Cambiar rol de usuario
    public function cambiarRol($id, $nuevo_rol) {
        // Validar que el rol sea válido
        if (!in_array($nuevo_rol, ['admin', 'residente'])) {
            throw new Exception("Rol no válido");
        }

        // No permitir cambiar el rol del último admin
        if ($nuevo_rol === 'residente') {
            $query_count = "SELECT COUNT(*) FROM " . $this->table_name . " 
                        WHERE rol = 'admin' AND id != :id";
            $stmt_count = $this->conn->prepare($query_count);
            $stmt_count->bindParam(':id', $id);
            $stmt_count->execute();
            
            if ($stmt_count->fetchColumn() < 1) {
                throw new Exception("No se puede cambiar el rol. Debe haber al menos un administrador");
            }
        }

        $query = "UPDATE " . $this->table_name . " SET rol = :rol WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rol', $nuevo_rol);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    // Métodos para recuperación de contraseña
    public function emailExiste($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch() ? true : false;
    }

    public function generarTokenRecuperacion($email) {
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query = "UPDATE " . $this->table_name . " 
                SET token_reset = :token, token_expira = :expiracion 
                WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expiracion', $expiracion);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $token;
    }

    public function validarTokenRecuperacion($token) {
        $query = "SELECT id, email FROM " . $this->table_name . " 
                WHERE token_reset = :token AND token_expira > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cambiarContraseña($id, $nueva_contraseña) {
        $query = "UPDATE " . $this->table_name . " 
                SET contrasena = :contrasena, 
                    token_reset = NULL, 
                    token_expira = NULL 
                WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $contraseña_hash = password_hash($nueva_contraseña, PASSWORD_BCRYPT);
        $stmt->bindParam(':contrasena', $contraseña_hash);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Métodos auxiliares privados
    private function eliminarAsignacionesHabitacion($id_usuario) {
        // Primero necesitamos obtener las habitaciones asignadas para actualizar su estado
        $query_get = "SELECT id_habitacion FROM asignacion_habitaciones WHERE id_usuario = :id";
        $stmt_get = $this->conn->prepare($query_get);
        $stmt_get->bindParam(':id', $id_usuario);
        $stmt_get->execute();
        
        $habitaciones = $stmt_get->fetchAll(PDO::FETCH_COLUMN);
        
        // Eliminar las asignaciones
        $query_delete = "DELETE FROM asignacion_habitaciones WHERE id_usuario = :id";
        $stmt_delete = $this->conn->prepare($query_delete);
        $stmt_delete->bindParam(':id', $id_usuario);
        $stmt_delete->execute();
        
        // Actualizar el estado de las habitaciones afectadas
        if (!empty($habitaciones)) {
            $habitacion = new Habitacion($this->conn);
            foreach ($habitaciones as $id_habitacion) {
                $habitacion->actualizarEstado($id_habitacion);
            }
        }
    }

    private function eliminarAsistencias($id_usuario) {
        $query = "DELETE FROM asistencias WHERE id_residente = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_usuario);
        $stmt->execute();
    }

    private function eliminarReportes($id_usuario) {
        $query = "DELETE FROM reportes WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_usuario);
        $stmt->execute();
    }

    private function validarEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function obtenerTodosFiltrados($filtros = []) {
    $query = "SELECT id, nombre, email, rol, fecha_registro 
            FROM " . $this->table_name . " 
            WHERE 1=1";
    
    if (!empty($filtros['nombre'])) {
        $query .= " AND nombre LIKE :nombre";
    }
    
    if (!empty($filtros['email'])) {
        $query .= " AND email LIKE :email";
    }
    
    if (!empty($filtros['rol'])) {
        $query .= " AND rol = :rol";
    }
    
    $query .= " ORDER BY fecha_registro DESC";
    
    $stmt = $this->conn->prepare($query);
    
    if (!empty($filtros['nombre'])) {
        $nombre = "%" . $filtros['nombre'] . "%";
        $stmt->bindParam(':nombre', $nombre);
    }
    
    if (!empty($filtros['email'])) {
        $email = "%" . $filtros['email'] . "%";
        $stmt->bindParam(':email', $email);
    }
    
    if (!empty($filtros['rol'])) {
        $stmt->bindParam(':rol', $filtros['rol']);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>