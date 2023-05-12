<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class UsuariosController extends CI_Controller {

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
    }

    // Método que permite al administrador agregar un usuario
    public function agregarUsuario() {
        // Obtenemos los datos del usuario a agregar
        $nombres = $this->input->post('nombres');
        $apellidos = $this->input->post('apellidos');
        $rut = $this->input->post('rut');
        $dvrut = $this->input->post('dvrut');
        $logintoken = '';
        $telefono = $this->input->post('telefono');
        $password = $this->input->post('password');
        $email = $this->input->post('email');
        $username = $this->input->post('username');
        $token = $this->input->post('token');
        $response = $this->UsuariosModel->insertarUsuario($token, $nombres, $apellidos, $rut, $dvrut, $logintoken, $telefono, $password, $email, $username);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function modificarUsuario() {
        // Obtener los datos del usuario a modificar
        $id = $this->input->post('id');
        $nombres = $this->input->post('nombres');
        $apellidos = $this->input->post('apellidos');
        $rut = $this->input->post('rut');
        $dvrut = $this->input->post('dvrut');
        $telefono = $this->input->post('telefono');
        $password = $this->input->post('password');
        $email = $this->input->post('email');
        $username = $this->input->post('username');
        $token = $this->input->post('token');
        $response = $this->UsuariosModel->actualizarUsuario($token, $id, $nombres, $apellidos, $rut, $dvrut, $telefono, $password, $email, $username);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function eliminarUsuario() {
        // Obtenemos los datos del usuario a eliminar
        $usuarioId = $this->input->post('id');
        $token = $this->input->post('token');
        $response = $this->UsuariosModel->eliminarUsuario($usuarioId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function listarUsuarios() {
        // Obtenemos el token de acceso
        $token = $this->input->post('token');
        $result = $this->UsuariosModel->obtenerTodosUsuarios($token);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    public function asignarPerfilUsuario() {
        $token = $this->input->post('token');
        $idperfil = $this->input->post('id_perfil');
        $idusuario = $this->input->post('id_usuario');
        $result = $this->UsuariosModel->asignarPerfilAUsuario($token, $idusuario, $idperfil);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    public function eliminarPerfilUsuario() {
        $token = $this->input->post('token');
        $idperfil = $this->input->post('id_perfil');
        $idusuario = $this->input->post('id_usuario');
        $result = $this->UsuariosModel->eliminarPerfilDeUsuario($token, $idusuario, $idperfil);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    public function getTodosPerfiles() {
        $token = $this->input->post('token');
        $result = $this->UsuariosModel->getTodosPerfiles($token);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    public function getPerfilesUsuario() {
        $token = $this->input->post('token');
        $idUsuario = $this->input->post('id_usuario');
        $result = $this->UsuariosModel->getTodosPerfilesUsuario($token, $idUsuario);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    public function perfilesActualUsuario(){
        $token = $this->input->post('token');
        $result = $this->UsuariosModel->getPerfilesUsuarioActual($token);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }
    public function list_user() {
        echo json_encode($this->db->query('select * from usuarios')->result_array());
    }

}
