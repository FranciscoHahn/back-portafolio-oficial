<?php

class ProductosModel extends CI_Model {

    private $jwt;
    private $utilidades;
    private $table;

    function __construct() {
        parent::__construct();
        $this->jwt = new JWT();
        $this->utilidades = new Utilidades();
        $this->table = 'productos';
    }

    public function obtenerProductos($token, $estatus) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        $this->db->select('productos.*, categoria_producto.nombre AS nombre_categoria');
        $this->db->from($this->table);
        $this->db->join('categoria_producto', 'categoria_producto.id = productos.id_categoria');
        if ($estatus == 'inactivos') {
            $this->db->where('productos.activo', 0);
        } elseif ($estatus == 'activos') {
            $this->db->where('productos.activo', 1);
        }
        $query = $this->db->get();
        $productos = $query->result_array();
        if (empty($productos)) {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontraron productos');
        } else {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Listado de productos', array('productos' => $productos));
        }
    }

    public function obtenerProductosPorCategoria($token, $id_categoria = null, $estatus = null) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $this->db->select('productos.*, categoria_producto.nombre AS nombre_categoria');
        $this->db->from($this->table);
        $this->db->join('categoria_producto', 'categoria_producto.id = productos.id_categoria');
        if ($id_categoria) {
            $this->db->where('productos.id_categoria', $id_categoria);
        }

        if ($estatus == 'inactivos') {
            $this->db->where('productos.activo', 0);
        } elseif ($estatus == 'activos') {
            $this->db->where('productos.activo', 1);
        }
        $query = $this->db->get();
        $productos = $query->result_array();
        if (empty($productos)) {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontraron productos para la categoría especificada');
        } else {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Listado de productos por categoría', array('productos' => $productos));
        }
    }

    public function insertarProducto($token, $nombre, $marca, $link_imagen, $id_categoria) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo administradores pueden insertar productos');
        }
        $this->db->where('nombre', $nombre);
        $query = $this->db->get($this->table);
        if ($query->num_rows() > 0) {
            return $this->utilidades->buildResponse(false, 'error', 400, 'El nombre del producto ya existe', array());
        } else {
            $data = array(
                'nombre' => $nombre,
                'marca' => $marca,
                'link_imagen' => $link_imagen,
                'activo' => 1,
                'id_categoria' => $id_categoria
            );
            $this->db->insert($this->table, $data);
            $insertId = $this->db->insert_id();
            return $this->utilidades->buildResponse(true, 'success', 200, 'Producto insertado', array('insertId' => $insertId));
        }
    }

    public function actualizarProducto($token, $id, $nombre, $marca, $link_imagen, $id_categoria) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo administradores pueden modificar productos');
        }

        $query = $this->db->where('nombre', $nombre)->where('id !=', $id)->get($this->table);
        
        if ($query->num_rows() > 0) {
            return $this->utilidades->buildResponse(false, 'error', 400, 'Ya existe otro producto con el mismo nombre', array('productos' => $query->result_array()));
        } else {
            $data = array(
                'nombre' => $nombre,
                'marca' => $marca,
                'link_imagen' => $link_imagen,
                'id_categoria' => $id_categoria
            );
            if ($this->db->where('id', $id)->update($this->table, $data)) {
                return $this->utilidades->buildResponse(true, 'success', 200, 'Producto actualizado', array("filas actualizadas" => "1"));
            } else {
                return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró el producto solicitado');
            }
        }
    }

    public function activarProducto($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo administradores pueden activar productos');
        }

        $data = array(
            'activo' => 1
        );
        $this->db->where('id', $id);
        
        if ($this->db->update($this->table, $data)) {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Producto activado', array("filas afectadas" => 1));
        } else {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró el producto solicitado');
        }
    }

    public function desactivarProducto($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        if ($this->jwt->getProperty($token, 'profile') <> 'Administrador') {
            return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo administradores pueden desactivar productos');
        }

        $data = array(
            'activo' => 0
        );
        $this->db->where('id', $id);
        if ($this->db->update($this->table, $data)) {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Producto desactivado', array("filas afectadas" => 1));
        } else {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró el producto solicitado');
        }
    }

    public function obtenerProductoPorId($token, $id_producto) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $this->db->select('productos.*, categoria_producto.nombre AS nombre_categoria');
        $this->db->from($this->table);
        $this->db->join('categoria_producto', 'categoria_producto.id = productos.id_categoria');
        $this->db->where('productos.id', $id_producto);
        $query = $this->db->get();
        $producto = $query->result_array();
        if (empty($producto)) {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró el producto');
        } else {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Producto encontrado', array('producto' => $producto));
        }
    }

    public function getCategorias($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        return $this->utilidades->buildResponse(true, 'success', 200, 'listado de categorías de productos', $this->db->from('categoria_producto')->get()->result_array());
    }

    public function getMarcas($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        return $this->utilidades->buildResponse(true, 'success', 200, 'listado de marcas de productos', $this->db->from('marca_producto')->get()->result_array());
    }
    
    private function updateProductos(){
        
    }

    
}
