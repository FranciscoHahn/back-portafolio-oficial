<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class MarcaProductoModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function insertarMarcaProducto($nombre) {
        $data = array(
            'nombre' => $nombre
        );
        $this->db->insert('marca_producto', $data);
        return $this->db->insert_id();
    }

    public function obtenerMarcasProducto() {
        $query = $this->db->get('marca_producto');
        return $query->result();
    }

    public function obtenerMarcaProductoPorId($id) {
        $query = $this->db->get_where('marca_producto', array('id' => $id));
        return $query->row();
    }

    public function editarMarcaProducto($id, $nombre) {
        $data = array(
            'nombre' => $nombre
        );
        $this->db->where('id', $id);
        $this->db->update('marca_producto', $data);
    }

    public function eliminarMarcaProducto($id) {
        $this->db->where('id', $id);
        $this->db->delete('marca_producto');
    }

}
