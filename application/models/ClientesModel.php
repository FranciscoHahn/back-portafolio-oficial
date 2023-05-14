<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class ClientesModel extends CI_Model {
    
    private $jwt;

    public function __construct() {
        parent::__construct();
        $this->jwt = new JWT();
        $this->load->database(); // Carga la base de datos configurada en CodeIgniter
    }

    public function obtenerClientes($token) {
        if(!$this->validarPerfil($token, array("Administrador"))){
            $response = $this->buildResponse(false, 403, 'Perfil o sesión inválida', null);
            return $response;
        }
        
        $query = $this->db->get_where('clientes', array('activo' => 1));
        $clientes = $query->result();
        $response = $this->buildResponse(true, 200, 'Clientes obtenidos correctamente', $clientes);
        return $response;
    }

    public function autenticarCliente($email, $password) {
        // Buscar cliente en la base de datos
        $this->db->where('email', $email);
        $query = $this->db->get('clientes');

        if ($query->num_rows() == 1) {
            // Cliente encontrado, verificar contraseña
            $cliente = $query->row();
            if (!$cliente->activo) {
                $mensaje = 'Cuenta desactivada por administración';
                $response = $this->buildResponse(false, 404, $mensaje, null);
                return $response;
            }
            if ($password == $cliente->password) {
                // Contraseña correcta, generar token
                $token_data = array(
                    'id_cliente' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'email' => $cliente->email,
                    'exp' => time() + (60 * 60 * 24), // Token válido por 24 horas,
                    'perfil' => array(
                        array("id" => "99999", "nombre" => "Cliente")
                    )
                );
                $token = $this->token($token_data);

                // Actualizar token en la base de datos
                $this->db->set('token', $token);
                $this->db->where('id', $cliente->id);
                $this->db->update('clientes');

                // Devolver respuesta exitosa con el token
                $mensaje = 'Cliente autenticado correctamente';
                $response = $this->buildResponse(true, 'success', $mensaje, array("token" => $token));
            } else {
                // Contraseña incorrecta, devolver respuesta de error
                $mensaje = 'La contraseña es incorrecta';
                $response = $this->buildResponse(false, 'warning', $mensaje, null);
            }
        } else {
            // Cliente no encontrado, devolver respuesta de error
            $mensaje = 'El email no está registrado en la base de datos';
            $response = $this->buildResponse(false, 'warning', $mensaje, null);
        }

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
            return $this->buildResponse(false, 'failed', $mensaje, null);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = 'El email no tiene un formato válido';
            return $this->buildResponse(false, 'failed', $mensaje, null);
        }

        // Verificar si el email ya existe en la base de datos
        $this->db->where('email', $email);
        $this->db->from('clientes');
        $count = $this->db->count_all_results();

        if ($count > 0) {
            // El email ya existe, devolver respuesta de error
            $mensaje = 'El email ya existe en la base de datos';
            return $this->buildResponse(false, 'failed', $mensaje, null);
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
                return $this->buildResponse(true, 'success', $mensaje, array("id_nuevo_cliente" => $this->db->insert_id()));
            } catch (Exception $e) {
                $mensaje = 'Error al insertar el cliente: ' . $e->getMessage();
                return $this->buildResponse(false, 'failed', $mensaje, null);
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
        $decoded_token = $this->decode_token($token);
        // Validar inputs
        if ($this->validarPerfil($token, array("Cliente"))) {
            if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($id)) {
                $mensaje = 'Faltan datos obligatorios para insertar el cliente';
                return $this->buildResponse(false, 403, $mensaje, null);
            }

            if (!$this->validarPerfil($token, array('Cliente'))) {
                $mensaje = 'Perfil o sesión inválida';
                return $this->buildResponse(false, 403, $mensaje, null);
            }

            if ($decoded_token->id_cliente != $id) {
                $mensaje = 'Identificador de cliente proporcionado no corresponde con su identificador';
                $response = $this->buildResponse(false, 404, $mensaje, null);
                return $response;
            }

            $query = $this->db->where(array('email' => $email, 'id !=' => $id))->get('clientes');
            if ($query->num_rows()) {
                $mensaje = 'Existe otro cliente utilizando el email';
                $response = $this->buildResponse(false, 404, $mensaje, null);
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
            $response = $this->buildResponse(true, 200, $mensaje, null);
            return $response;
        } else if ($this->validarPerfil($token, array("Administrador"))) {
            $query = $this->db->where(array('email' => $email, 'id !=' => $id))->get('clientes');
            if ($query->num_rows()) {
                $mensaje = 'Existe otro cliente utilizando el email';
                $response = $this->buildResponse(false, 404, $mensaje, null);
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
            $response = $this->buildResponse(true, 200, $mensaje, null);
            return $response;
        } else {
            $mensaje = 'Perfil o sesión inválida';
            return $this->buildResponse(false, 403, $mensaje, null);
        }
    }

    public function desactivarCliente($token, $id) {
        // Validar perfil de administrador
        if (!$this->validarPerfil($token, ['Administrador'])) {
            $response = $this->buildResponse(false, 401, 'No tienes autorización para realizar esta acción', null);
            return $response;
        }

        $data = array(
            'activo' => 0
        );
        $this->db->where('id', $id);
        $this->db->update('clientes', $data);
        $response = $this->buildResponse(true, 200, 'Cliente eliminado correctamente', null);
        return $response;
    }

    public function activarCliente($token, $id) {
        // Validar perfil de administrador
        if (!$this->validarPerfil($token, ['Administrador'])) {
            $response = $this->buildResponse(false, 401, 'No tienes autorización para realizar esta acción', null);
            return $response;
        }

        $data = array(
            'activo' => 1
        );
        $this->db->where('id', $id);
        $this->db->update('clientes', $data);
        $response = $this->buildResponse(true, 200, 'Cliente activado correctamente', null);
        return $response;
    }

    public function token($data) {
        $jwt = new JWT();
        $token = $jwt->encode($data, SECRET_KEY, ENCODE);
        return $token;
    }

    public function decode_token($token) {        
        $jwt = new JWT();
        $decoded_token = $jwt->decode($token, SECRET_KEY, ENCODE);
        return $decoded_token;
    }

    public function validarPerfil($token, $perfiles = []) {
        $decoded_token = $this->decode_token($token);
        $exp = $decoded_token->exp;
        $perfil = null;
        if (isset($decoded_token->perfil)) {
            $perfil = $decoded_token->perfil;
        } else {
            $perfil = $decoded_token->perfiles;
        }


        if (time() > $exp) {
            // Token expirado
            return false;
        }

        if (!empty($perfiles)) {
            // Verificar si el perfil del usuario tiene acceso a la operación
            foreach ($perfil as $p) {
                if (in_array($p->nombre, $perfiles)) {
                    return true;
                }
            }
            return false;
        }

        // Si no se especifica un perfil, se asume que cualquier perfil tiene acceso
        return true;
    }

    public function buildResponse($ok, $status, $mensaje, $data) {
        $response = array(
            "ok" => $ok,
            "status" => $status,
            "message" => $mensaje,
            "data" => $data
        );
        return $response;
    }

    public function verificarToken($token) {
        $decoded_token = $this->decode_token($token);

        // Obtener la fecha de expiración del token
        $exp_date = $decoded_token->exp;

        // Convertir la fecha de expiración a formato timestamp
        $exp_timestamp = strtotime($exp_date);

        // Obtener la fecha y hora actual en formato timestamp
        $current_timestamp = time();

        // Verificar si el token ha expirado
        if ($current_timestamp > $exp_timestamp) {
            return false;
        } else {
            return true;
        }
    }

}
