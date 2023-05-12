<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class CategoriaProductoModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function insertarCategoria($token, $nombre, $link_imagen) {
        if ($this->validarPerfil($token, array('Administrador', ''))) {
            $existe = $this->db->where('nombre', $nombre)->get('categoria_producto')->result_array();
            if ($existe) {
                return $this->buildResponse(false, 'failed', 'categoría existente', array("categorias" => $existe));
            }
            $data = array(
                'nombre' => $nombre,
                'link_imagen' => $link_imagen
            );

            $this->db->insert('categoria_producto', $data);
            return $this->buildResponse(true, 'success', 'categoría agregada', array("id_nueva_categoria" => $this->db->insert_id()));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array());
        }
    }

    public function modificarCategoria($token, $id, $nombre, $link_imagen) {
        if ($this->validarPerfil($token, array('Administrador', ''))) {
            $existe = $this->db->where('nombre', $nombre)->where('id != ', $id)->get('categoria_producto')->result_array();
            if ($existe) {
                return $this->buildResponse(false, 'failed', 'existe otra categoría con este nombre', array("categorias" => $existe));
            }
            $data = array(
                'nombre' => $nombre,
                'link_imagen' => $link_imagen
            );

            $this->db->where('id', $id);
            $this->db->update('categoria_producto', $data);
            return $this->buildResponse(true, 'success', 'categoría modificada', array("filas afectadas" => $this->db->affected_rows()));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array());
        }
    }

    public function getCategorias($token) {
        if ($this->validarPerfil($token, array('Administrador', 'Cocina'))) {
            $this->db->select('*');
            $this->db->from('categoria_producto');
            $query = $this->db->get();
            return $this->buildResponse(true, 'success', 'listado de categorias de productos', $query->result());
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array());
        }
    }

    public function eliminarCategoria($token, $id_categoria) {
        if ($this->validarPerfil($token, array('Administrador', ''))) {
            $data = array(
                'id' => $id_categoria
            );

            $this->db->where('id', $id);
            $this->db->delete('categoria_producto');
            return $this->buildResponse(true, 'success', 'categoría modificada', array("filas afectadas" => $this->db->affected_rows()));
        } else {
            return $this->buildResponse(false, 'failed', 'perfil no autorizado', array());
        }
    }

    public function obtenerCategoriaPorId($id) {
        $query = $this->db->get_where('CATEGORIA_PRODUCTO', array('id' => $id));
        return $query->row();
    }

    public function editarCategoria($id, $nombre, $link_imagen) {
        $data = array(
            'nombre' => $nombre,
            'link_imagen' => $link_imagen
        );
        $this->db->where('id', $id);
        $this->db->update('CATEGORIA_PRODUCTO', $data);
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
