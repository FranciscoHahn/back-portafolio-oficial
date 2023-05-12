<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class PerfilesModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function insertarPerfil($nombre) {
        $data = array(
            'nombre' => $nombre
        );
        $this->db->insert('perfiles', $data);
        return $this->db->insert_id();
    }

    public function obtenerPerfiles() {
        $query = $this->db->get('perfiles');
        return $query->result();
    }

    public function obtenerPerfilPorId($id) {
        $query = $this->db->get_where('perfiles', array('id' => $id));
        return $query->row();
    }

    public function editarPerfil($id, $nombre) {
        $data = array(
            'nombre' => $nombre
        );
        $this->db->where('id', $id);
        $this->db->update('perfiles', $data);
    }

    public function eliminarPerfil($id) {
        $this->db->where('id', $id);
        $this->db->delete('perfiles');
    }

}
