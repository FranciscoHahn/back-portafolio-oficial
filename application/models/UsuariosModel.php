<?php

class UsuariosModel extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    public function autenticar($email, $password) {
        session_destroy();
        $arraySearch = array('email' => $email, 'password' => $password);
        $resultadoAuth = $this->db->get_where('usuarios', $arraySearch)->row(0);
        $retorno = array();
        if ($resultadoAuth == null) {
            return array('ok' => false, 'status' => 'error', 'message' => 'Usuario o contraseña incorrectos');
        } else {
            $perfiles = $this->db->query('select perfiles.* from perfiles join usuarios_perfiles on usuarios_perfiles.perfiles_id = perfiles.id where usuarios_id = ' . $resultadoAuth->id);
            $token_data = array(
                "email" => $email,
                "userId" => $resultadoAuth->id,
                "userName" => $resultadoAuth->username,
                'exp' => time() + (60 * 60 * 24),
                "perfiles" => $perfiles->result_array()
            );
            $token = $this->token($token_data);
            $this->session->token = $token;
            $this->session->idusuario = $resultadoAuth->id;
            $this->session->perfil = $perfiles->result_array();
            $this->db->where('id', $resultadoAuth->id)->update('usuarios', array('logintoken' => $token));
            $usuario = array(
                "id_usuario" => $resultadoAuth->id,
                "nombres" => $resultadoAuth->nombres,
                "apellidos" => $resultadoAuth->apellidos,
                "fullName" => $resultadoAuth->nombres . " " . $resultadoAuth->apellidos,
                "userName" => $resultadoAuth->username,
                "telefono" => $resultadoAuth->telefono,
                "email" => $email,
                "perfil" => $perfiles->result_array(),
                "token" => $token
            );
            $response = $this->buildResponse(true, 'success', 'login exitoso', $usuario);
        }
        return $response;
    }

    public function insertarUsuario($token, $nombres, $apellidos, $rut, $dvrut, $telefono, $password, $email, $username) {
        if ($this->validarPerfil($token, 'Administrador')) {
            $data = array(
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'rut' => $rut,
                'dvrut' => $dvrut,
                'telefono' => $telefono,
                'password' => $password,
                'email' => $email,
                'username' => $username,
            );

            $this->db->insert('usuarios', $data);
            return $this->buildResponse(true, 'success', 'usuario modificado', array('usuario_creado_id' => $this->db->insert_id()));
        } else {
            return $this->buildResponse(false, 'failed', 'Perfil inválido', array(0));
        }
    }

    public function eliminarUsuario($token, $id) {
        if ($this->validarPerfil($token, 'Administrador')) {
            $this->db->where('id', $id);
            $this->db->delete('usuarios');
            return $this->buildResponse(true, 'success', 'usuarios eliminado', array('filas_afectadas' => $this->db->affected_rows()));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil inválido', array(0));
        }
    }

    public function actualizarUsuario($token, $id, $nombres, $apellidos, $rut, $dvrut, $telefono, $password, $email, $username) {
        if ($this->validarPerfil($token, 'Administrador')) {
            $data = array(
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'rut' => $rut,
                'dvrut' => $dvrut,
                'telefono' => $telefono,
                'password' => $password,
                'email' => $email,
                'username' => $username
            );
            $this->db->where('id', $id);
            $this->db->update('usuarios', $data);
            return $this->buildResponse(true, 'success', 'usuario modificado', array('filas_afectadas' => $this->db->affected_rows()));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil inválido', array(0));
        }
    }

    public function obtenerTodosUsuarios($token) {
        if ($this->validarPerfil($token, 'Administrador')) {
            $this->db->select('*');
            $this->db->from('usuarios');
            $query = $this->db->get();
            return $this->buildResponse(true, 'success', 'listado de usuarios', $query->result());
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array());
        }
    }

    public function asignarPerfilAUsuario($token, $usuario_id, $perfil_id) {
        if ($this->validarPerfil($token, 'Administrador')) {
            $arraySearch = array('up.perfiles_id' => $perfil_id, 'up.usuarios_id' => $usuario_id);
            $existe = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch)->get()->result_array();
            if (count($existe)) {
                $perfilesAsignados2 = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where(array('up.usuarios_id' => $usuario_id))->get()->result_array();
                return $this->buildResponse(false, 'failed', 'asignación de perfil existente', array("perfiles_asignados" => $perfilesAsignados2));
            }
            $data = array(
                'usuarios_id' => $usuario_id,
                'perfiles_id' => $perfil_id
            );
            $this->db->insert('usuarios_perfiles', $data);
            $insertId = $this->db->insert_id();
            $perfilesAsignados2 = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where(array('up.usuarios_id' => $usuario_id))->get()->result_array();

            return $this->buildResponse(true, 'success', 'perfil asignado', array('id_perfil_usuario' => $insertId, "perfiles_asignados_usuario" => $perfilesAsignados2));
        } else {

            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array(0));
        }
    }

    public function eliminarPerfilDeUsuario($token, $usuario_id, $perfil_id) {
        if ($this->validarPerfil($token, 'Administrador')) {
            $arraySearch = array('up.perfiles_id' => $perfil_id, 'up.usuarios_id' => $usuario_id);
            $existe = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch)->get()->result_array();
            if (!count($existe)) {
                return $this->buildResponse(false, 'failed', 'No existe esta asignación de perfil', array("perfiles_asignados" => $existe));
            }
            $this->db->where('usuarios_id', $usuario_id);
            $this->db->where('perfiles_id', $perfil_id);
            $this->db->delete('usuarios_perfiles');
            $affectedRows = $this->db->affected_rows();
            $arraySearch2 = array('up.usuarios_id' => $usuario_id);
            $perfilesAsignados2 = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch2)->get()->result_array();

            return $this->buildResponse(true, 'success', 'perfil asignado', array('filas_afectadas' => $affectedRows, "perfiles_asignados_usuario" => $perfilesAsignados2));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array(0));
        }
    }

    public function getTodosPerfiles($token) {
        if ($this->validarPerfil($token, 'Administrador')) {
            $query = $this->db->select('*')->from('perfiles')->get()->result_array();
            return $this->buildResponse(true, 'success', 'listado de perfiles', array("perfiles" => $query));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array());
        }
    }

    
    public function getTodosPerfilesUsuario($token, $idusuario) {
        if ($this->validarPerfil($token, 'Administrador')) {
            $arraySearch2 = array('up.usuarios_id' => $idusuario);
            $perfilesAsignados2 = $this->db->select('p.id,up.usuarios_id, up.perfiles_id, p.nombre as nombre_perfil')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch2)->get()->result_array();
            return $this->buildResponse(true, 'success', 'listado de perfiles de usuario', array("perfiles_usuario" => $perfilesAsignados2));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array());
        }
    }
    
    public function getPerfilesUsuarioActual($token) {
        if ($this->validarPerfil($token, array('Administrador', 'Cocina', 'Mesa', 'Bodega', 'Mesero', 'Cliente', 'Cajero', 'Administrador Sistema'))) {
            $arraySearch2 = array('up.usuarios_id' => $this->decode_token($token)->userId);
            $perfilesAsignados2 = $this->db->select('p.id,up.usuarios_id, up.perfiles_id, p.nombre as nombre_perfil')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch2)->get()->result_array();
            return $this->buildResponse(true, 'success', 'listado de perfiles de usuario', array("perfiles_usuario" => $perfilesAsignados2));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array());
        }
    }

    public function token($data) {
        $jwt = new JWT();
        $JwtSecretKey = "MiLlaveSecreta.portafolio.2023";
        $token = $jwt->encode($data, $JwtSecretKey, 'HS256');
        return $token;
    }

    public function decode_token($token) {
        $jwt = new JWT();
        $JwtSecretKey = "MiLlaveSecreta.portafolio.2023";
        $decoded_token = $jwt->decode($token, $JwtSecretKey, array('HS256'));
        return $decoded_token;
    }

    public function validarToken($token) {
        $result = false;
        if ($token == $this->session->token) {
            $result = true;
        }
        return $result;
    }

    public function validarPerfil($token, $search = '') {
        $result = false;
        foreach ($this->decode_token($token)->perfiles as $perfil) {
            if (is_array($search)) {
                if (in_array($perfil->nombre, $search)) {
                    $result = true;
                    break;
                }
            } else {
                if ($perfil->nombre == $search) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
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

}
