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

    public function getCompraPorId($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Obtener los registros de compra desde la base de datos
        $this->db->where('id', $id);
        $query = $this->db->get('registro_compras');
        $registrosCompra = $query->result_array();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Registro de compra id' . $id, array('registrosCompra' => $registrosCompra));
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
        $fecha_compra_timestamp = strtotime($fecha_compra);
        $data = array(
            'fecha_registro' => date('Y-m-d H:i:s'),
            'proveedor' => $proveedor,
            'id_usuario' => $id_usuario,
            'nro_doc_compra' => $nro_doc_compra,
            'total_compra' => $total_compra,
            'fecha_compra' => date('Y-m-d H:i:s', $fecha_compra_timestamp)
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
        //$this->db->where('registro_compra_id', $compra_id);
        $this->db->select('dc.id, dc.registro_compra_id, dc.precio_unitario ,dc.cantidad, p.id as id_producto, p.nombre as nombre_producto');
        $this->db->from('productos p');
        $this->db->join('detalles_compra dc', 'dc.producto_id = p.id');
        $this->db->where('dc.registro_compra_id', $compra_id);

        // Ejecutar la consulta y obtener los resultados
        $query = $this->db->get();
        return $this->utilidades->buildResponse(true, 'success', 200, 'Detalle compra id ' . $compra_id, array('data' => $query->result_array()));
    }

    public function crear_salida_inventario($token, $producto_id, $cantidad, $id_usuario) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        // Verificar si el producto existe
        $producto = $this->db->get_where('productos', ['id' => $producto_id])->row();
        if (!$producto) {
            return $this->utilidades->buildResponse(false, 'failed', 404, 'El producto no existe');
        }

        // Verificar si hay suficiente stock para la salida
        if ($cantidad > $producto->stock_bodega) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'No hay suficiente stock para realizar la salida');
        }

        // Crear el registro de salida de inventario
        $data = [
            'producto_id' => $producto_id,
            'cantidad' => $cantidad,
            'id_usuario' => $id_usuario,
            'fecha_salida' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('salidas_inventario', $data);
        $insert_id = $this->db->insert_id();

        // Verificar si el stock disponible es menor al stock crítico
        if ($producto->stock_bodega <= $producto->stock_critico) {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Salida de inventario creada exitosamente. Advertencia: Stock crítico alcanzado', array('insert_id' => $insert_id));
        }

        return $this->utilidades->buildResponse(true, 'success', 200, 'Salida de inventario creada exitosamente', array('insert_id' => $insert_id));
    }

    public function eliminar_salida_inventario($token, $salida_id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        // Verificar si la salida de inventario existe
        $salida = $this->db->get_where('salidas_inventario', ['id' => $salida_id])->row();
        if (!$salida) {
            return $this->utilidades->buildResponse(false, 'failed', 404, 'La salida de inventario no existe');
        }

        // Eliminar la salida de inventario
        $this->db->delete('salidas_inventario', ['id' => $salida_id]);

        // Retornar la respuesta
        return $this->utilidades->buildResponse(true, 'success', 200, 'Salida de inventario eliminada exitosamente');
    }

    public function get_salidas_inventario($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        
        $data = $this->db
                ->select('p.nombre, u.nombres, u.apellidos, si.*')
                ->from('productos p')
                ->join('salidas_inventario si', 'on si.producto_id = p.id')
                ->join('usuarios u', 'on si.id_usuario = u.id')
                ->get()->result_array();
        return $this->utilidades->buildResponse(true, 'success', 200, 'Salida de inventario eliminada exitosamente', array("salidas" => $data));
    }

}
