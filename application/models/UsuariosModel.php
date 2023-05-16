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
        if (!$resultadoAuth->activo) {
            return $this->utilidades->buildResponse(false, 'error', 403, 'Usuario desactivo por administración', null);
        }

        $perfiles = $this->db->query('select perfiles.* from perfiles join usuarios on usuarios.id_perfil = perfiles.id where usuarios.id = ' . $resultadoAuth->id);
        $token_data = array(
            "email" => $email,
            "userId" => $resultadoAuth->id,
            "userName" => $resultadoAuth->username,
            'exp' => time() + (60 * 60 * 24),
            "perfiles" => $perfiles->result_array(),
            "profile" => $perfiles->result_array()[0]["nombre"]
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
            "profile" => $perfiles->result_array()[0]["nombre"],
            "token" => $token
        );
        $response = $this->utilidades->buildResponse(true, 'success', 200, 'login exitoso', $usuario);

        return $response;
    }

    public function insertarUsuario($token, $nombres, $apellidos, $rut, $telefono, $password, $email, $username, $idPerfil) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Perfil no autorizado');
        }

        $verificarInputVars = array(
            [$nombres, 'nombres', 'str'],
            [$apellidos, 'apellidos', 'str'],
            [$rut, 'rut', 'rut'],
            [$password, 'password', 'str'],
            [$email, 'email', 'str'],
            [$username, 'username', 'str'],
        );

        $validacion = $this->utilidades->validadorInput($verificarInputVars);
        if ($validacion["error"]) {
            return $this->utilidades->buildResponse(false, 'failed', 422, 'inputs con errores', array("errores" => $validacion["resultados"]));
        }
        $usuarioExiste = $this->usuarioExiste($username, $rut, $email);
        if (count($usuarioExiste)) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'usuario existente', array("usuarios_existentes" => $usuarioExiste));
        }
        $data = array(
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'run' => $rut,
            'telefono' => $telefono,
            'password' => $password,
            'email' => $email,
            'username' => $username,
            'activo' => 1,
            'id_perfil' => $idPerfil
        );
        $this->db->insert('usuarios', $data);
        return $this->utilidades->buildResponse(true, 'success', 200, 'usuario agregado', array('usuario_creado_id' => $this->db->insert_id()));
    }

    public function actualizarUsuario($token, $id, $nombres, $apellidos, $rut, $telefono, $password, $email, $username, $idPerfil) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Perfil no autorizado');
        }
        $usuarioExiste = $this->buscarPorId($id);
        if (!$usuarioExiste) {
            return $this->utilidades->buildResponse(false, 'failed', 403, 'usuario no existente', array("usuarios_existentes" => $usuarioExiste));
        }

        $verificarInputVars = array(
            [$nombres, 'nombres', 'str'],
            [$apellidos, 'apellidos', 'str'],
            [$rut, 'rut', 'rut'],
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
            'run' => $rut,
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
            'run' => $rut,
            'telefono' => $telefono,
            'password' => $password,
            'email' => $email,
            'username' => $username,
            'id_perfil' => $idPerfil
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
        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Perfil no autorizado');
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
        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Perfil no autorizado');
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

    public function obtenerTodosUsuarios($token, $estadoUsuario = null) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Perfil no autorizado');
        }
        $this->db->select('u.*, p.nombre as perfil_usuario, (case when u.activo = 1 then "activo" else "inactivo" end) as usuario_activo_texto', false);
        $this->db->from('usuarios u');
        $this->db->join('perfiles p', 'u.id_perfil = p.id', 'left');
        if ($estadoUsuario == "inactivos") {
            $this->db->where('activo', 0);
        }
        if ($estadoUsuario == "activos") {
            $this->db->where('activo', 1);
        }
        $query = $this->db->get();
        return $this->utilidades->buildResponse(true, 'success', 200, 'listado de usuarios', $query->result());
    }

    public function usuarioExiste($nomuser, $rut, $email) {
        $nombreExiste = $this->db
                ->select('id, username, email, run')
                ->where('username', $nomuser)
                ->or_where('email', $email)
                ->or_where('run', $rut)
                ->get('usuarios')
                ->result_array();
        return $nombreExiste;
    }

    public function buscarPorId($id) {
        $nombreExiste = $this->db
                ->select('id, username, email, run')
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
        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Perfil no autorizado');
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

    public function getDatosUsuario($token, $id = null) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        if($this->jwt->getProperty($token, 'userId') <> $id && $this->jwt->getProperty($token, 'profile') <> 'Administrador'){
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo administradores puedes acceder a datos de otros usuarios');
        }
               
        $idUsuario = $this->jwt->getProperty($token, 'userId');

        $this->db->select('u.*, p.nombre as perfil_usuario, (case when u.activo = 1 then "activo" else "inactivo" end) as usuario_activo_texto', false);
        $this->db->from('usuarios u');
        $this->db->join('perfiles p', 'u.id_perfil = p.id', 'left');
        if(!$id){
            $this->db->where('u.id', $idUsuario);
        } else {
            $this->db->where('u.id', $id);
        }
        
        
        $query = $this->db->get();
        return $this->utilidades->buildResponse(true, 'success', 200, 'listado de usuarios', $query->result());
        
    }

}
