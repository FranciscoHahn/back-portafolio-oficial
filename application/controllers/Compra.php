<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Compra extends CI_Controller {

    private $jwt;

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        header("Access-Control-Allow-Credentials: true");
        // Cargamos el modelo
        $this->load->model('CompraModel');
        $this->jwt = new JWT();
    }

    public function get_mesas() {
        $token = $this->input->post('token');
        $response = $this->CompraModel->get_mesas($token);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function insertar_mesa() {
        $token = $this->input->post('token');
        $numero = $this->input->post('numero');
        $capacidad = $this->input->post('capacidad');
        $estado = $this->input->post('estado');

        $response = $this->CompraModel->insertar_mesa($token, $numero, $capacidad, $estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function modificar_mesa() {
        $token = $this->input->post('token');
        $id_mesa = $this->input->post('id_mesa');
        $numero = $this->input->post('numero');
        $capacidad = $this->input->post('capacidad');
        $estado = $this->input->post('estado');

        $response = $this->CompraModel->modificar_mesa($token, $id_mesa, $numero, $capacidad, $estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function crear_atencion_mesa_mesero() {
        $token = $this->input->post('token');
        $mesa_id = $this->input->post('mesa_id');
        $mesero_id = $this->input->post('mesero_id');

        $response = $this->CompraModel->crear_atencion_mesa_mesero($token, $mesa_id, $this->jwt->getProperty($token, 'userId'));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function crear_atencion_mesa_cliente() {
        $token = $this->input->post('token');
        $mesa_id = $this->input->post('mesa_id');
        $cliente_id = $this->input->post('cliente_id');

        $response = $this->CompraModel->crear_atencion_mesa_cliente($token, $mesa_id, $cliente_id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function asignar_mesero() {
        $token = $this->input->post('token');
        $atencion_id = $this->input->post('atencion_id');
        $mesero_id = $this->input->post('mesero_id');

        $response = $this->CompraModel->asignar_mesero($token, $atencion_id, $mesero_id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function asignar_cliente() {
        $token = $this->input->post('token');
        $atencion_id = $this->input->post('atencion_id');
        $cliente_id = $this->input->post('cliente_id');

        $response = $this->CompraModel->asignar_cliente($token, $atencion_id, $cliente_id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function actualizar_estado_atencion() {
        $token = $this->input->post('token');
        $atencion_id = $this->input->post('atencion_id');
        $estado = $this->input->post('estado');

        $response = $this->CompraModel->actualizar_estado_atencion($token, $atencion_id, $estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function crear_reserva_cliente() {
        $token = $this->input->post('token');
        $mesa_id = $this->input->post('mesa_id');
        $fecha_reserva = $this->input->post('fecha_reserva');

        $response = $this->CompraModel->crear_reserva_cliente($token, $mesa_id, $fecha_reserva);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function crear_pedido() {
        $token = $this->input->post('token');
        $atencion_id = $this->input->post('atencion_id');
        $preparacion_id = $this->input->post('preparacion_id');
        $descripcion = $this->input->post('descripcion');
        $cantidad = $this->input->post('cantidad');

        $response = $this->CompraModel->crear_pedido($token, $atencion_id, $preparacion_id, $descripcion, $cantidad);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function eliminar_pedido() {
        $token = $this->input->post('token');
        $pedido_id = $this->input->post('pedido_id');

        $response = $this->CompraModel->eliminar_pedido($token, $pedido_id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function modificar_estado_pedido() {
        $token = $this->input->post('token');
        $pedido_id = $this->input->post('pedido_id');
        $estado = $this->input->post('estado');

        $response = $this->CompraModel->modificar_estado_pedido($token, $pedido_id, $estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function listar_pedidos_con_informacion() {
        $token = $this->input->post('token');
        $estado_pedido = $this->input->post('estado_pedido');

        $response = $this->CompraModel->listar_pedidos_con_informacion($token, $estado_pedido);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function ultimo_pedido_mesa() {
        $token = $this->input->post('token');
        $id_mesa = $this->input->post('id_mesa');
        $response = $this->CompraModel->get_ultima_atencion_mesa($token, $id_mesa);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    //todas_atenciones_con_info

    public function todas_atenciones_con_info() {
        $token = $this->input->post('token');
        $response = $this->CompraModel->todas_atenciones_con_info($token);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function get_datos_atencion() {
        $token = $this->input->post('token');
        $id_atencion = $this->input->post('id_atencion');
        $response = $this->CompraModel->get_datos_atencion($token, $id_atencion);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function pagar_atencion() {

        $token = $this->input->post('token');
        $atencion_id = $this->input->post('atencion_id');
        $total_cuenta = $this->input->post('total_cuenta');
        $total_cancelado = $this->input->post('total_cancelado');
        $forma_pago = $this->input->post('forma_pago');


        $response = $this->CompraModel->pagar_atencion($token, $atencion_id, $total_cuenta, $total_cancelado, $forma_pago);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function finalizar_atencion() {

        //finalizar_atencion
        $response = $this->CompraModel->finalizar_atencion($this->input->post('token'), $this->input->post('atencion_id'));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

}
