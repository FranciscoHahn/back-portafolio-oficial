<?php

class InventarioModel extends CI_Model {

    private $jwt;
    private $utilidades;
    private $table;

    function __construct() {
        parent::__construct();
        $this->jwt = new JWT();
        $this->utilidades = new Utilidades();
    }

    public function listarRegistrosCompra($token) {
        // Verificar el token
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Obtener los registros de compra desde la base de datos
        $query = $this->db->get('registro_compras');
        $registrosCompra = $query->result_array();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Listado de registros de compra', array('registrosCompra' => $registrosCompra));
    }

    public function crear_registro_compra($token, $proveedor, $id_usuario, $nro_doc_compra, $total_compra, $fecha_compra) {
        // Verificar el token
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Crear el nuevo registro de compra en la base de datos
        $data = array(
            'fecha_registro' => date('Y-m-d H:i:s'),
            'proveedor' => $proveedor,
            'id_usuario' => $id_usuario,
            'nro_doc_compra' => $nro_doc_compra,
            'total_compra' => $total_compra,
            'fecha_compra' => $fecha_compra
        );
        $this->db->insert('registro_compras', $data);
        $registro_compra_id = $this->db->insert_id();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Registro de compra creado exitosamente', array('registro_compra_id' => $registro_compra_id));
    }

    public function modificar_registro_compra($token, $registro_compra_id, $proveedor, $id_usuario, $nro_doc_compra, $total_compra) {
        // Verificar el token
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Verificar si el registro de compra existe
        $registro_compra = $this->db->get_where('registro_compras', array('id' => $registro_compra_id))->row();
        if (!$registro_compra) {
            return $this->utilidades->buildResponse(false, 'failed', 404, 'El registro de compra no existe');
        }

        // Actualizar el registro de compra en la base de datos
        $data = array(
            'proveedor' => $proveedor,
            'id_usuario' => $id_usuario,
            'nro_doc_compra' => $nro_doc_compra,
            'total_compra' => $total_compra
        );
        $this->db->where('id', $registro_compra_id);
        $this->db->update('registro_compras', $data);

        return $this->utilidades->buildResponse(true, 'success', 200, 'Registro de compra modificado exitosamente');
    }

    public function eliminar_registro_compra($token, $registro_compra_id) {
        // Verificar el token
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Actualizar el registro de compra en la base de datos
        $data = array(
            'activo' => 0
        );
        $this->db->where('id', $registro_compra_id);
        $this->db->update('registro_compras', $data);

        return $this->utilidades->buildResponse(true, 'success', 200, 'Registro de compra eliminado exitosamente');
    }

    public function activar_registro_compra($token, $registro_compra_id) {
        // Verificar el token
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Actualizar el registro de compra en la base de datos
        $data = array(
            'activo' => 1
        );
        $this->db->where('id', $registro_compra_id);
        $this->db->update('registro_compras', $data);

        return $this->utilidades->buildResponse(true, 'success', 200, 'Registro de compra activado exitosamente');
    }

    public function agregar_detalles_compra($token, $registro_compra_id, $producto_id, $cantidad, $precio_unitario) {
        // Verificar el token
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $data = array(
            'registro_compra_id' => $registro_compra_id,
            'producto_id' => $producto_id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario
        );
        $this->db->insert('detalles_compra', $data);
        $detalle_id = $this->db->insert_id();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Detalles de compra agregados exitosamente', array('detalle_insertao' => $detalle_id));
    }

    public function eliminar_detalle_compra($token, $detalle_compra_id) {
        // Verificar el token
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Eliminar el detalle de compra de la base de datos
        $this->db->where('id', $detalle_compra_id);
        $this->db->delete('detalles_compra');

        return $this->utilidades->buildResponse(true, 'success', 200, 'Detalle de compra eliminado exitosamente');
    }

    public function get_detalle_compra($token, $compra_id) {
        // Verificar el token
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Eliminar el detalle de compra de la base de datos
        $this->db->where('registro_compra_id', $compra_id);
        $query = $this->db->get('detalles_compra');
        $data = $query->result_array();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Detalle compra id ' . $compra_id, array('data' => $data));
    }

}
