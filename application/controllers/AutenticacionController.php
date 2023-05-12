<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class AutenticacionController extends CI_Controller {

    function __construct() {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "OPTIONS") {
            die();
        }
        parent::__construct();
        $this->load->Model('UsuariosModel');
    }

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     * 	- or -
     * 		http://example.com/index.php/welcome/index
     * 	- or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/userguide3/general/urls.html
     */
    public function autenticar() {
        $retorno = $this->UsuariosModel->autenticar($this->input->post('email'), $this->input->post('password'));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($retorno);
    }

    public function decode_token() {
        // $token =$this->uri->segment(3);
        $token = $this->input->post('token');
        $jwt = new JWT();
        $JwtSecretKey = "MySecretKey_Siglo21.Portafolio";
        $decoded_token = $jwt->decode($token, $JwtSecretKey, array('HS256'));
        echo '<pre>';
        print_r($decoded_token);
        $token1 = $jwt->jsonEncode($decoded_token);
        return $token1;
    }

    public function getToken() {
        $response = array(
            "ok" => true,
            "status" => "success",
            "message" => "Login Exitoso",
            "data" => $this->session->token
        );
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    public function validarSession() {
        $tokenRequest = $this->input->post('token');
        $response = null;
        if ($this->UsuariosModel->verificarSession($tokenRequest)) {
            $response = array(
                "ok" => true,
                "status" => "success",
                "message" => "Sessión Válida"
            );
        } else {
            $response = array(
                "ok" => false,
                "status" => "invalid",
                "message" => "Sessión inválida"
            );
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }
    
    public function test(){
        echo $this->UsuariosModel->verificarSession($this->input->post('token'));
    }

}
