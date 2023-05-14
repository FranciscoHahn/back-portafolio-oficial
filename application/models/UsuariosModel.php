<?php

class UsuariosModel extends CI_Model {

    private $jwt;
    private $utilidades;

    function __construct() {
        parent::__construct();
        $this->jwt = new JWT();
        $this->utilidades = new Utilidades();
    }

    public function autenticar($email, $password) {
        $arraySearch = array('email' => $email, 'password' => $password);
        $resultadoAuth = $this->db->get_where('usuarios', $arraySearch)->row(0);
        $retorno = array();

        if ($resultadoAuth == null) {
            return $this->utilidades->buildResponse(false, 'error', 403, 'Usuario o contraseña incorrectos', null);
        }
        $perfiles = $this->db->query('select perfiles.* from perfiles join usuarios_perfiles on usuarios_perfiles.perfiles_id = perfiles.id where usuarios_id = ' . $resultadoAuth->id);
        $token_data = array(
            "email" => $email,
            "userId" => $resultadoAuth->id,
            "userName" => $resultadoAuth->username,
            'exp' => time() + (60 * 60 * 24),
            "perfiles" => $perfiles->result_array()
        );
        $token = $this->jwt->generar($token_data);
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
        $response = $this->utilidades->buildResponse(true, 'success', 200, 'login exitoso', $usuario);

        return $response;
    }

    public function insertarUsuario($token, $nombres, $apellidos, $rut, $dvrut, $telefono, $password, $email, $username) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }

        $verificarInputVars = array(
            [$nombres, 'nombres', 'str'],
            [$apellidos, 'apellidos', 'str'],
            [$rut . '-' . $dvrut, 'rut-dvrut', 'rut'],
            [$password, 'password', 'str'],
            [$email, 'email', 'str'],
            [$username, 'username', 'str'],
        );

        $validacion = $this->utilidades->validadorInput($verificarInputVars);
        if ($validacion["error"]) {
            return $this->utilidades->buildResponse(false, 'failed', 422, 'inputs con errores', array("errores" => $validacion["resultados"]));
        }
        $usuarioExiste = $this->usuarioExiste($username, $rut, $dvrut, $email);
        if (count($usuarioExiste)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'usuario existente', array("usuarios_existentes" => $usuarioExiste));
        }
        $data = array(
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'rut' => $rut,
            'dvrut' => $dvrut,
            'telefono' => $telefono,
            'password' => $password,
            'email' => $email,
            'username' => $username,
            'activo' => 1
        );
        $this->db->insert('usuarios', $data);
        return $this->buildResponse(true, 'success', 200, 'usuario agregado', array('usuario_creado_id' => $this->db->insert_id()));
    }

    public function actualizarUsuario($token, $id, $nombres, $apellidos, $rut, $dvrut, $telefono, $password, $email, $username) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }
        $usuarioExiste = $this->buscarPorId($id);
        if (!$usuarioExiste) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'usuario no existente', array("usuarios_existentes" => $usuarioExiste));
        }

        $verificarInputVars = array(
            [$nombres, 'nombres', 'str'],
            [$apellidos, 'apellidos', 'str'],
            [$rut . '-' . $dvrut, 'rut-dvrut', 'rut'],
            [$password, 'password', 'str'],
            [$email, 'email', 'email'],
            [$username, 'username', 'str'],
        );
        $validacion = $this->utilidades->validadorInput($verificarInputVars);
        if ($validacion["error"]) {
            return $this->utilidades->buildResponse(false, 'failed', 422, 'inputs con errores', array("errores" => $validacion["resultados"]));
        }

        $id_excluir = $id;
        $datos_busqueda = array(
            'CONCAT(rut,"-",dvrut)' => $rut . '-' . $dvrut,
            'email' => $email,
            'username' => $username,
        );
        $buscarExcluido = $this->buscarExistenteExcluir($id_excluir, $datos_busqueda);
        if (count($buscarExcluido)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'existe otro usuario con el mismo email, rut o username', array("usuarios_existentes" => $buscarExcluido));
        }


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
        return $this->utilidades->buildResponse(true, 'success', 200, 'usuario modificado', array('filas_afectadas' => $this->db->affected_rows()));
    }

    public function eliminarUsuario($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }

        if ($this->jwt->getProperty($token, 'userId') == $id) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'no se puede eliminar a si mismo', null);
        }

        if (!$this->buscarPorId($id)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'usuario no existe', null);
        }

        $this->db->where('id', $id);
        $this->db->update('usuarios', array('activo' => 0));
        return $this->utilidades->buildResponse(true, 'success', 200, 'usuarios eliminado', array('filas_afectadas' => $this->db->affected_rows()));
    }

    public function restaurarUsuario($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }

        if ($this->jwt->getProperty($token, 'userId') == $id) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'no se puede activar a si mismo', null);
        }

        if (!$this->buscarPorId($id)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'usuario no existe', null);
        }

        $this->db->where('id', $id);
        $this->db->update('usuarios', array('activo' => 1));
        return $this->utilidades->buildResponse(true, 'success', 200, 'usuario activado', array('filas_afectadas' => $this->db->affected_rows()));
    }

    public function obtenerTodosUsuarios($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }
        $this->db->select('*');
        $this->db->from('usuarios');
        $query = $this->db->get();
        return $this->utilidades->buildResponse(true, 'success', 200, 'listado de usuarios', $query->result());
    }

    public function usuarioExiste($nomuser, $rut, $dvrut, $email) {
        $nombreExiste = $this->db
                ->select('id, username, email, rut, dvrut')
                ->where('username', $nomuser)
                ->or_where('email', $email)
                ->or_where('concat(rut,"-",dvrut)', $rut . '-' . $dvrut)
                ->get('usuarios')
                ->result_array();
        return $nombreExiste;
    }

    public function buscarPorId($id) {
        $nombreExiste = $this->db
                ->select('id, username, email, rut, dvrut')
                ->where('id', $id)
                ->get('usuarios')
                ->result_array();
        return $nombreExiste;
    }

    public function buscarPorCampos($datos_busqueda) {
        $this->db->select('*');
        $this->db->from('usuarios');
        foreach ($datos_busqueda as $campo => $valor) {
            $this->db->where($campo, $valor);
        }
        $query = $this->db->get();
        return $query->result_array();
    }

    public function buscarExistenteExcluir($id_excluir, $datos_busqueda) {
        $this->db->select('*');
        $this->db->from('usuarios');

        $where_clauses = array();
        foreach ($datos_busqueda as $campo => $valor) {
            $where_clauses[] = "$campo = '$valor'";
        }

        $where_clause_string = implode(' OR ', $where_clauses);
        $this->db->where("(" . $where_clause_string . ")");
        $this->db->where_not_in('id', $id_excluir);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function buscarPerfilPorId($id) {
        $perfil = $this->db
                ->where('id', $id)
                ->get('perfiles')
                ->result_array();
        return $perfil;
    }

    public function asignarPerfilAUsuario($token, $usuario_id, $perfil_id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }

        $existeUsuario = $this->buscarPorId($usuario_id);
        if (!count($existeUsuario)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'usuario no existente', null);
        }

        $existePerfil = $this->buscarPerfilPorId($perfil_id);
        if (!count($existePerfil)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'perfil no existe', null);
        }

        $arraySearch = array('up.perfiles_id' => $perfil_id, 'up.usuarios_id' => $usuario_id);
        $existe = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch)->get()->result_array();
        if (count($existe)) {
            $perfilesAsignados2 = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where(array('up.usuarios_id' => $usuario_id))->get()->result_array();
            return $this->utilidades->buildResponse(false, 'failed', 403, 'asignación de perfil existente', array("perfiles_asignados" => $perfilesAsignados2));
        }
        $data = array(
            'usuarios_id' => $usuario_id,
            'perfiles_id' => $perfil_id
        );
        $this->db->insert('usuarios_perfiles', $data);
        $insertId = $this->db->insert_id();
        $perfilesAsignados2 = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where(array('up.usuarios_id' => $usuario_id))->get()->result_array();

        return $this->utilidades->buildResponse(true, 'success', 200, 'perfil asignado', array('id_perfil_usuario' => $insertId, "perfiles_asignados_usuario" => $perfilesAsignados2));
    }

    public function eliminarPerfilDeUsuario($token, $usuario_id, $perfil_id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }

        $existeUsuario = $this->buscarPorId($usuario_id);
        if (!count($existeUsuario)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'usuario no existente', null);
        }

        $existePerfil = $this->buscarPerfilPorId($perfil_id);
        if (!count($existePerfil)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'perfil no existe', null);
        }


        $arraySearch = array('up.perfiles_id' => $perfil_id, 'up.usuarios_id' => $usuario_id);
        $existe = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch)->get()->result_array();
        if (!count($existe)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'No existe esta asignación de perfil', array("perfil_asignado_a_usuario" => $existe));
        }
        $this->db->where('usuarios_id', $usuario_id);
        $this->db->where('perfiles_id', $perfil_id);
        $this->db->delete('usuarios_perfiles');
        $affectedRows = $this->db->affected_rows();
        $arraySearch2 = array('up.usuarios_id' => $usuario_id);
        $perfilesAsignados2 = $this->db->select('p.nombre')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch2)->get()->result_array();
        return $this->utilidades->buildResponse(true, 'success', 200, 'perfil asignado', array('filas_afectadas' => $affectedRows, "perfiles_asignados_usuario" => $perfilesAsignados2));
    }

    public function getTodosPerfiles($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }

        $query = $this->db->select('*')->from('perfiles')->get()->result_array();
        return $this->utilidades->buildResponse(true, 'success', 200, 'listado de perfiles', array("perfiles" => $query));
    }

    public function getTodosPerfilesUsuario($token, $idusuario) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $verificarPropiedad = $this->jwt->verificarPropiedad($token, 'perfiles', 'nombre', array('Administrador'));
        if (!$verificarPropiedad["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarPropiedad["usrmsg"], $verificarPropiedad);
        }

        $arraySearch2 = array('up.usuarios_id' => $idusuario);
        $perfilesAsignados2 = $this->db->select('p.id,up.usuarios_id, up.perfiles_id, p.nombre as nombre_perfil')->from('perfiles p')->join('usuarios_perfiles up', 'p.id = up.perfiles_id')->where($arraySearch2)->get()->result_array();
        return $this->buildResponse(true, 'success', 200, 'listado de perfiles de usuario', array("perfiles_usuario" => $perfilesAsignados2));
    }

    public function getPerfilesUsuarioActual($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        
        return $this->utilidades->buildResponse(true, 'success', 200, "se listan los perfiles de la sesión actual", $this->jwt->getProperty($token, 'perfiles'));
    }

}
