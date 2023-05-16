<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class ClientesController extends CI_Controller {

    public function __construct() {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "OPTIONS") {
            die();
        }

        parent::__construct();
        // Cargamos el modelo
        $this->load->model('UsuariosModel');
        $this->load->model('ClientesModel');
    }

    public function agregarCliente() {
        // Obtenemos los datos del cliente a agregar
        $nombre = $this->input->post('nombre');
        $apellido = $this->input->post('apellido');
        $email = $this->input->post('email');
        $telefono = $this->input->post('telefono');
        $password = $this->input->post('password');

        $response = $this->ClientesModel->insertarCliente($nombre, $apellido, $email, $telefono, $password);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function autenticarCliente() {
        // Obtenemos los datos del cliente a agregar
        $email = $this->input->post('email');
        $password = $this->input->post('password');

        $response = $this->ClientesModel->autenticarCliente($email, $password);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function actualizarCliente() {
        // Obtenemos los datos del cliente a actualizar
        $id = $this->input->post('id');
        $nombre = $this->input->post('nombre');
        $apellido = $this->input->post('apellido');
        $email = $this->input->post('email');
        $telefono = $this->input->post('telefono');
        $password = $this->input->post('password');
        $token = $this->input->post('token');

        // Actualizar el cliente
        $response = $this->ClientesModel->actualizarCliente($token, $id, $nombre, $apellido, $email, $telefono, $password);

        // Enviar la respuesta en formato JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function activarCliente() {
        $id = $this->input->post('id');
        $token = $this->input->post('token');

        // Actualizar el cliente
        $response = $this->ClientesModel->activarCliente($token, $id);

        // Enviar la respuesta en formato JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function desactivarCliente() {
        $id = $this->input->post('id');
        $token = $this->input->post('token');

        // Actualizar el cliente
        $response = $this->ClientesModel->desactivarCliente($token, $id);

        // Enviar la respuesta en formato JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function obtenerClientes() {
        $token = $this->input->post('token');
        $response = $this->ClientesModel->obtenerClientes($token, $this->input->post('estado_clientes'));

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function getDatosCliente() {
        $token = $this->input->post('token');
        $response = $this->ClientesModel->getDatosUsuario($token, $this->input->post('id'));

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function decodeTokenCliente() {
        $token = $this->input->post('token');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->ClientesModel->decode_token($token));
    }

    public function tiempoRestante() {
        $token = $this->input->post('token');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(time() - $this->ClientesModel->decode_token($token)->exp);
    }

}
