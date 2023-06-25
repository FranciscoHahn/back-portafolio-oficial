<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Integracion extends CI_Controller {

    function __construct() {
        header('Access-Control-Allow-Origin: *'); // Permitir acceso desde cualquier origen
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE'); // Permitir los métodos HTTP permitidos
        header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding'); // Permitir los encabezados especificados
        header('Access-Control-Allow-Credentials: true'); // Permitir los encabezados especificados
        parent::__construct();
        $this->load->Model('ClientesModel');
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
    public function google_auth() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Leer el contenido de la solicitud en formato JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json);
            $response = $this->ClientesModel->google_auth($data->sub, $data->given_name, $data->family_name, $data->email);
            $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($response));
        } else {
            // Devolver un error si no se cumple la condición
            $response = array('error' => 'Invalid request');
            $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($response));
        }
    }

    public function facebook_auth() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Leer el contenido de la solicitud en formato JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json);
            $response = $this->ClientesModel->facebook_auth($data->sub, $data->given_name, $data->family_name);
            $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($response));
        } else {
            // Devolver un error si no se cumple la condición
            $response = array('error' => 'Invalid request');
            $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($response));
        }
    }

}
