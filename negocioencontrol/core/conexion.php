<?php
class Conexion {
    private $host = "localhost";
    private $user = "root";
    private $pass = "clave";
    private $db = "";
    public $conn;

    // Conectar a BD central por defecto
    public function central() {
        $this->db = "control_clientes"; // BD central
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
        if ($this->conn->connect_error) die("Error de conexión: ".$this->conn->connect_error);
        return $this->conn;
    }

    // Conectar a BD de un negocio específico
    public function negocio($nombre_bd) {
        $this->db = $nombre_bd;
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
        if ($this->conn->connect_error) die("Error de conexión a negocio: ".$this->conn->connect_error);
        return $this->conn;
    }
}
?>
