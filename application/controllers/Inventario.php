<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Inventario extends CI_Controller {

    private $jwt;

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        header("Access-Control-Allow-Credentials: true");
        // Cargamos el modelo
        $this->load->model('InventarioModel');
        $this->jwt = new JWT();
    }

    // Método que permite al administrador agregar un usuario
    public function inv_crearcompra() {
        // Obtener el token y el estado de la preparación
        $token = $this->input->post('token');
        $proveedor = $this->input->post('proveedor');
        $id_usuario = $this->jwt->getProperty($token, 'userId');
        $nro_doc_compra = $this->input->post('nro_doc_compra');
        $total_compra = $this->input->post('total_compra');
        $fecha_compra = $this->input->post('fecha_compra');

        // Llamar a la función obtenerPreparaciones del modelo
        //echo json_encode($this->input->post());

        $response = $this->InventarioModel->crear_registro_compra($token, $proveedor, $id_usuario, $nro_doc_compra, $total_compra, $fecha_compra);

        // Devolver la respuesta en formato JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function inv_addcompradetail() {
        // Obtener el token y el estado de la preparación
        $token = $this->input->post('token');
        $registro_compra_id = $this->input->post('registro_compra_id');
        $producto_id = $this->input->post('producto_id');
        $cantidad = $this->input->post('cantidad');
        $precio_unitario = $this->input->post('precio_unitario');
        
        //echo json_encode($this->input->post());

        // Llamar a la función obtenerPreparaciones del modelo
        //echo json_encode($this->input->post());

        $response = $this->InventarioModel->agregar_detalles_compra($token, $registro_compra_id, $producto_id, $cantidad, $precio_unitario);

        // Devolver la respuesta en formato JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }
    
    //get_detalle_compra($token, $compra_id)
    
    
    public function inv_getdetallecompra() {
        // Obtener el token y el estado de la preparación
        $token = $this->input->post('token');
        $compra_id = $this->input->post('registro_compra_id');

        
        //echo json_encode($this->input->post());

        // Llamar a la función obtenerPreparaciones del modelo
        //echo json_encode($this->input->post());

        $response = $this->InventarioModel->get_detalle_compra($token, $compra_id);

        // Devolver la respuesta en formato JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

}
