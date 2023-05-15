<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class ClientesModel extends CI_Model {

    private $jwt;
    private $utilidades;

    public function __construct() {
        parent::__construct();
        $this->jwt = new JWT();
        $this->load->database(); // Carga la base de datos configurada en CodeIgniter
        $this->utilidades = new Utilidades();
    }

    public function obtenerClientes($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }

        $query = $this->db->get_where('clientes', array('activo' => 1));
        $clientes = $query->result_array();
        $response = $this->utilidades->buildResponse(true, 'success', 200, 'Clientes obtenidos activos correctamente', $clientes);
        return $response;
    }

    public function autenticarCliente($email, $password) {
        // Buscar cliente en la base de datos
        $this->db->where('email', $email);
        $query = $this->db->get('clientes');
        if (!$query->num_rows()) {
            return $this->utilidades->buildResponse(false, 'warning', 404, 'Cliente no registrado', null);
        }
        $cliente = $query->row();
        if (!$cliente->activo) {
            $mensaje = 'Cuenta desactivada por administración';
            $response = $this->utilidades->buildResponse(false, 'failed', 404, $mensaje, null);
            return $response;
        }

        if ($password != $cliente->password) {
            $mensaje = 'Usuario o contraseña incorrecto';
            $response = $this->utilidades->buildResponse(false, 'failed', 404, $mensaje, null);
            return $response;
        }

        $token_data = array(
            'id_cliente' => $cliente->id,
            'nombre' => $cliente->nombre,
            'apellido' => $cliente->apellido,
            'email' => $cliente->email,
            'exp' => time() + (60 * 60 * 24), // Token válido por 24 horas,
            'perfil' => array(
                array("id" => "99999", "nombre" => "Cliente")
            ),
            'perfiles' => array(
                array("id" => "99999", "nombre" => "Cliente")
            )
        );
        $token = $this->jwt->generar($token_data);
        $this->db->set('token', $token);
        $this->db->where('id', $cliente->id);
        $this->db->update('clientes');
        $mensaje = 'Cliente autenticado correctamente';
        $response = $this->utilidades->buildResponse(true, 'success', 200, $mensaje, array("data_cliente" => $token_data, "token" => $token));
        return $response;
    }

    public function insertarCliente($nombre, $apellido, $email, $telefono, $password) {
        // Sanitizar inputs
        $nombre = $this->db->escape_str($nombre);
        $apellido = $this->db->escape_str($apellido);
        $email = $this->db->escape_str($email);
        $telefono = $this->db->escape_str($telefono);
        $password = $this->db->escape_str($password);

        // Validar inputs
        if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
            $mensaje = 'Faltan datos obligatorios para insertar el cliente';
            return $this->utilidades->buildResponse(false, 'failed', 400, $mensaje, null);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = 'El email no tiene un formato válido';
            return $this->utilidades->buildResponse(false, 'failed', 400, $mensaje, null);
        }

        // Verificar si el email ya existe en la base de datos
        $this->db->where('email', $email);
        $this->db->from('clientes');
        $count = $this->db->count_all_results();

        if ($count > 0) {
            // El email ya existe, devolver respuesta de error
            $mensaje = 'El email ya existe en la base de datos';
            return $this->utilidades->buildResponse(false, 'failed', 403, $mensaje, null);
        } else {
            // El email no existe, insertar el cliente en la base de datos
            $data = array(
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'password' => $password,
                'activo' => '1'
            );
            try {
                $this->db->insert('clientes', $data);
                $mensaje = 'Cliente insertado correctamente';
                return $this->utilidades->buildResponse(true, 'success', 200, $mensaje, array("id_nuevo_cliente" => $this->db->insert_id()));
            } catch (Exception $e) {
                $mensaje = 'Error al insertar el cliente: ' . $e->getMessage();
                return $this->utilidades->buildResponse(false, 'failed', 500, $mensaje, null);
            }
        }
    }

    public function actualizarCliente($token, $id, $nombre, $apellido, $email, $telefono, $password) {
        // Validar perfil del usuario
        //return $this->decode_token($token);
        $nombre = $this->db->escape_str($nombre);
        $apellido = $this->db->escape_str($apellido);
        $email = $this->db->escape_str($email);
        $telefono = $this->db->escape_str($telefono);
        $password = $this->db->escape_str($password);
        $id = $this->db->escape_str($id);
        $decoded_token = $this->jwt->decodificar($token);

        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarAdmin = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        $verificarCliente = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Cliente'));
        if (!$verificarAdmin["result"] && !$verificarCliente["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'No tiene los permisos necesarios', $verificarPropiedad);
        }

        if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($id)) {
            $mensaje = 'Faltan datos obligatorios para insertar el cliente';
            return $this->utilidades->buildResponse(false, 'failed', 403, $mensaje, null);
        }


        if ($verificarAdmin["result"]) {
            $query = $this->db->where(array('email' => $email, 'id !=' => $id))->get('clientes');
            if ($query->num_rows()) {
                $mensaje = 'Existe otro cliente utilizando el email';
                $response = $this->utilidades->buildResponse(false, 'failed', 404, $mensaje, null);
                return $response;
            }

            $data = array(
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'password' => $password
            );
            $this->db->where('id', $id);
            $this->db->update('clientes', $data);
            $mensaje = 'Cliente actualizado correctamente';
            $response = $this->utilidades->buildResponse(true, 'success', 200, $mensaje, null);
            return $response;
        } else if ($verificarCliente["result"]) {
            if ($decoded_token->id_cliente != $id) {
                $mensaje = 'Identificador de cliente proporcionado no corresponde con su identificador';
                $response = $this->utilidades->buildResponse(false, 'failed', 404, $mensaje, null);
                return $response;
            }

            $query = $this->db->where(array('email' => $email, 'id !=' => $id))->get('clientes');
            if ($query->num_rows()) {
                $mensaje = 'Existe otro cliente utilizando el email';
                $response = $this->utilidades->buildResponse(false, 'failed', 404, $mensaje, null);
                return $response;
            }

            $data = array(
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'password' => $password
            );
            $this->db->where('id', $id);
            $this->db->update('clientes', $data);
            $mensaje = 'Cliente actualizado correctamente';
            $response = $this->utilidades->buildResponse(true, 'success', 200, $mensaje, null);
            return $response;
        } else {
            return $this->utilidades->buildResponse(false, 'failed', 500, 'error no controlado', null);
        }
    }

    public function desactivarCliente($token, $id) {
        // Validar perfil de administrador
        if (!$this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'))) {
            $response = $this->utilidades->buildResponse(false, 'failed', 401, 'No tienes autorización para realizar esta acción', null);
            return $response;
        }

        $data = array(
            'activo' => 0
        );
        $this->db->where('id', $id);
        $this->db->update('clientes', $data);
        $response = $this->utilidades->buildResponse(true, 'success', 200, 'Cliente desactivado correctamente', null);
        return $response;
    }

    public function activarCliente($token, $id) {
        // Validar perfil de administrador
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        $verificarPerfil = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPerfil["result"]) {
            $response = $this->utilidades->buildResponse(false, 'failed', 401, 'No tienes autorización para realizar esta acción', null);
            return $response;
        }

        $data = array(
            'activo' => 1
        );
        $this->db->where('id', $id);
        $this->db->update('clientes', $data);
        $response = $this->utilidades->buildResponse(true, 'success', 200, 'Cliente activado correctamente', null);
        return $response;
    }

}
