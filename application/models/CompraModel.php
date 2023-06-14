<?php

class CompraModel extends CI_Model {

    private $jwt;
    private $utilidades;

    function __construct() {
        parent::__construct();
        $this->jwt = new JWT();
        $this->utilidades = new Utilidades();
    }

    public function get_mesas($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Obtener los registros de compra desde la base de datos
        $query = $this->db->get('mesas');
        $mesas = $query->result_array();
        return $this->utilidades->buildResponse(true, 'success', 200, 'listado de mesas', array('mesas' => $mesas));
    }

    public function insertar_mesa($token, $numero, $capacidad, $estado) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Verificar si ya existe una mesa con el mismo número
        $this->db->where('numero', $numero);
        $query = $this->db->get('mesas');
        if ($query->num_rows() > 0) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'Ya existe una mesa con el mismo número');
        }

        // Insertar la mesa si no existe duplicado
        $data = array(
            'numero' => $numero,
            'capacidad' => $capacidad,
            'estado' => $estado
        );

        $this->db->insert('mesas', $data);
        $id_mesa = $this->db->insert_id();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Mesa insertada correctamente', array('id_mesa' => $id_mesa));
    }

    public function modificar_mesa($token, $id_mesa, $numero, $capacidad, $estado) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Verificar si ya existe otra mesa con el nuevo número
        $this->db->where('numero', $numero);
        $this->db->where('id !=', $id_mesa); // Excluir la mesa actual de la verificación
        $query = $this->db->get('mesas');
        if ($query->num_rows() > 0) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'Ya existe otra mesa con el mismo número');
        }

        // Actualizar la mesa si no existe duplicado
        $data = array(
            'numero' => $numero,
            'capacidad' => $capacidad,
            'estado' => $estado
        );

        $this->db->where('id', $id_mesa);
        $this->db->update('mesas', $data);

        return $this->utilidades->buildResponse(true, 'success', 200, 'Mesa modificada correctamente');
    }

    public function crear_atencion_mesa_mesero($token, $mesa_id, $mesero_id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Obtener la fecha y hora actual
        $fechaActual = date('Y-m-d H:i:s');

        // Crear el array de datos para la inserción
        $data = array(
            'mesa_id' => $mesa_id,
            'mesero_id' => $mesero_id,
            'fecha_atencion' => $fechaActual
        );

        // Cambiar el estado de la mesa a "ocupada" al crear la atención
        $this->db->where('id', $mesa_id);
        $this->db->set('estado', 'ocupada');
        $this->db->update('mesas');

        // Insertar los datos en la tabla "atencion_mesa"
        $this->db->insert('atencion_mesa', $data);
        $id_atencion = $this->db->insert_id();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Atención a mesa creada correctamente', array('id_atencion' => $id_atencion));
    }

    public function crear_atencion_mesa_cliente($token, $mesa_id, $cliente_id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Obtener la fecha y hora actual
        $fechaActual = date('Y-m-d H:i:s');

        // Crear el array de datos para la inserción
        $data = array(
            'mesa_id' => $mesa_id,
            'cliente_id' => $cliente_id,
            'fecha_atencion' => $fechaActual
        );

        // Cambiar el estado de la mesa a "ocupada" al crear la atención
        $this->db->where('id', $mesa_id);
        $this->db->set('estado', 'ocupada');
        $this->db->update('mesas');

        // Insertar los datos en la tabla "atencion_mesa"
        $this->db->insert('atencion_mesa', $data);
        $id_atencion = $this->db->insert_id();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Atención a mesa creada correctamente', array('id_atencion' => $id_atencion));
    }

    public function asignar_mesero($token, $atencion_id, $mesero_id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Verificar si la atención existe
        $this->db->where('id', $atencion_id);
        $query = $this->db->get('atencion_mesa');
        $atencion = $query->row();

        if (!$atencion) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'La atención a mesa no existe');
        }

        // Verificar si el mesero existe
        $this->db->where('id', $mesero_id);
        $query = $this->db->get('usuarios');
        $mesero = $query->row();

        if (!$mesero) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'El mesero no existe');
        }

        // Asignar el mesero a la atención
        $this->db->set('mesero_id', $mesero_id);
        $this->db->where('id', $atencion_id);
        $this->db->update('atencion_mesa');

        return $this->utilidades->buildResponse(true, 'success', 200, 'Mesero asignado correctamente');
    }

    public function asignar_cliente($token, $atencion_id, $cliente_id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        // Verificar si la atención existe
        $this->db->where('id', $atencion_id);
        $query = $this->db->get('atencion_mesa');
        $atencion = $query->row();

        if (!$atencion) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'La atención a mesa no existe');
        }

        // Verificar si el cliente existe
        $this->db->where('id', $cliente_id);
        $query = $this->db->get('usuarios');
        $cliente = $query->row();

        if (!$cliente) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'El cliente no existe');
        }

        // Asignar el cliente a la atención
        $this->db->set('cliente_id', $cliente_id);
        $this->db->where('id', $atencion_id);
        $this->db->update('atencion_mesa');

        return $this->utilidades->buildResponse(true, 'success', 200, 'Cliente asignado correctamente');
    }

    public function actualizar_estado_atencion($token, $atencion_id, $estado) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $estadosPermitidos = array('pendiente', 'en proceso', 'finalizada', 'cancelada', 'pago solicitado');
        if (!in_array($estado, $estadosPermitidos)) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'Estado de atención no válido');
        }

        // Verificar si la atención existe
        $this->db->where('id', $atencion_id);
        $query = $this->db->get('atencion_mesa');
        $atencion = $query->row();

        if (!$atencion) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'La atención a mesa no existe');
        }

        // Actualizar el estado de la atención
        $this->db->set('estado', $estado);
        $this->db->where('id', $atencion_id);
        $this->db->update('atencion_mesa');

        return $this->utilidades->buildResponse(true, 'success', 200, 'Estado de atención actualizado correctamente');
    }

    public function crear_reserva_cliente($token, $mesa_id, $fecha_reserva) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $cliente_id = $this->jwt->getProperty($token, 'id_cliente');

        // Verificar si la mesa existe
        $this->db->where('id', $mesa_id);
        $query = $this->db->get('mesas');
        $mesa = $query->row();

        if (!$mesa) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'La mesa no existe');
        }

        // Verificar si hay reservas previas en la misma mesa dentro de la tolerancia de horas
        $fecha_limite = date('Y-m-d H:i:s', strtotime('-' . TOLERANCIA_HRS_MESAS . ' hours'));
        $this->db->where('mesa_id', $mesa_id);
        $this->db->where('fecha_reserva >=', $fecha_limite);
        $query = $this->db->get('atencion_mesa');
        $reservas_previas = $query->result_array();

        if (!empty($reservas_previas)) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'Ya existe una reserva en la misma mesa dentro de la tolerancia de horas');
        }

        // Crear la reserva
        $data = array(
            'mesa_id' => $mesa_id,
            'cliente_id' => $cliente_id,
            'mesero_id' => null,
            'estado' => 'reservada',
            'fecha_reserva' => $fecha_reserva
        );

        $this->db->insert('atencion_mesa', $data);
        $reserva_id = $this->db->insert_id();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Reserva creada correctamente', array('reserva_id' => $reserva_id));
    }

    public function crear_pedido($token, $atencion_id, $preparacion_id, $descripcion) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $data = array(
            'atencion_id' => $atencion_id,
            'preparacion_id' => $preparacion_id,
            'descripcion' => $descripcion,
            'estado' => 'en preparación'
        );

        $this->db->insert('pedidos', $data);
        $pedido_id = $this->db->insert_id();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Pedido creado correctamente', array('pedido_id' => $pedido_id));
    }

    public function eliminar_pedido($token, $pedido_id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $this->db->where('id', $pedido_id);
        $this->db->delete('pedidos');

        return $this->utilidades->buildResponse(true, 'success', 200, 'Pedido eliminado correctamente');
    }

    public function modificar_estado_pedido($token, $pedido_id, $estado) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $allowedStates = array('pendiente', 'en preparación', 'entregado', 'cancelado', 'disponible para entrega');
        if (!in_array($estado, $allowedStates)) {
            return $this->utilidades->buildResponse(false, 'failed', 400, 'Estado de pedido no válido');
        }

        $data = array(
            'estado' => $estado
        );

        $this->db->where('id', $pedido_id);
        $this->db->update('pedidos', $data);

        return $this->utilidades->buildResponse(true, 'success', 200, 'Estado del pedido modificado correctamente');
    }

    public function listar_pedidos_con_informacion($token, $estado_pedido) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $this->db->select('pedidos.*, mesas.numero AS numero_mesa, atencion_mesa.estado AS estado_atencion');
        $this->db->from('pedidos');
        $this->db->join('atencion_mesa', 'pedidos.atencion_id = atencion_mesa.id');
        $this->db->join('mesas', 'atencion_mesa.mesa_id = mesas.id');
        $this->db->where('pedidos.estado', $estado_pedido);
        $query = $this->db->get();
        $pedidos = $query->result_array();

        return $this->utilidades->buildResponse(true, 'success', 200, 'Listado de pedidos con información', array('pedidos' => $pedidos));
    }

}
