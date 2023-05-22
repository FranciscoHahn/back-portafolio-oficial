<?php

class PreparacionesModel extends CI_Model {

    private $jwt;
    private $utilidades;
    private $table;

    function __construct() {
        parent::__construct();
        $this->jwt = new JWT();
        $this->utilidades = new Utilidades();
        $this->table = 'productos';
    }

    public function obtenerPreparaciones($token, $estatus) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        $this->db->select('*');
        $this->db->from('preparaciones');
        if ($estatus == 'inactivos') {
            $this->db->where('activo', 0);
        } elseif ($estatus == 'activos') {
            $this->db->where('activo', 1);
        }
        $query = $this->db->get();
        $preparaciones = $query->result_array();
        if (empty($preparaciones)) {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontraron preparaciones');
        } else {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Listado de preparaciones', array('preparaciones' => $preparaciones));
        }
    }

    public function insertarPreparacion($token, $nombre, $descripcion, $precio, $categoria) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        /**
          if ($this->jwt->getProperty($token, 'profile') !== 'Administrador') {
          return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo los administradores pueden insertar preparaciones');
          }
         */
        $this->db->where('nombre', $nombre);
        $query = $this->db->get('preparaciones');
        if ($query->num_rows() > 0) {
            return $this->utilidades->buildResponse(false, 'error', 400, 'El nombre de la preparación ya existe', array());
        } else {
            $data = array(
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'activo' => 1,
                'categoria' => $categoria
            );
            $this->db->insert('preparaciones', $data);
            $insertId = $this->db->insert_id();
            return $this->utilidades->buildResponse(true, 'success', 200, 'Preparación insertada', array('insertId' => $insertId));
        }
    }

    public function actualizarPreparacion($token, $id, $nombre, $precio, $descripcion, $categoria) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        /**
          if ($this->jwt->getProperty($token, 'profile') !== 'Administrador') {
          return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo los administradores pueden insertar preparaciones');
          }
         */
        $query = $this->db->where('nombre', $nombre)->where('id !=', $id)->get('preparaciones');

        if ($query->num_rows() > 0) {
            return $this->utilidades->buildResponse(false, 'error', 400, 'Ya existe otra preparación con el mismo nombre', array('preparaciones' => $query->result_array()));
        } else {
            $data = array(
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'categoria' => $categoria
            );
            $this->db->where('id', $id);
            if ($this->db->update('preparaciones', $data)) {
                return $this->utilidades->buildResponse(true, 'success', 200, 'Preparación actualizada', array("filas actualizadas" => "1"));
            } else {
                return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró la preparación solicitada');
            }
        }
    }

    public function activarPreparacion($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        /*         * *
          if ($this->jwt->getProperty($token, 'profile') !== 'Administrador') {
          return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo los administradores pueden activar preparaciones');
          }
         */
        $set = $this->db->query('update preparaciones set activo = 1 where id = ' . $id);

        if ($set) {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Preparación activada', array("filas afectadas" => 1));
        } else {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró la preparación solicitada');
        }
    }

    public function desactivarPreparacion($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        /*         * *
          if ($this->jwt->getProperty($token, 'profile') !== 'Administrador') {
          return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo los administradores pueden activar preparaciones');
          }
         */
        $data = array(
            'activo' => 0
        );
        $this->db->where('id', $id);

        if ($this->db->update('preparaciones', $data)) {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Preparación activada', array("filas afectadas" => 1));
        } else {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró la preparación solicitada');
        }
    }

    public function relacionProductoPreparacion($token, $idProducto, $idPreparacion) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        /**
          if ($this->jwt->getProperty($token, 'profile') !== 'Administrador') {
          return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo los administradores pueden gestionar la relación de productos y preparaciones');
          }
         */
        // Verificar si la relación ya existe en la base de datos
        $query = $this->db->where('id_producto', $idProducto)->where(array('id_preparacion' => $idPreparacion, 'activo' => 1))->get('preparacion_producto');
        if ($query->num_rows() > 0) {

            return $this->utilidades->buildResponse(true, 'success', 200, 'Relación producto-preparación ya existe', null);
        } else {
            // Crear una nueva relación
            $data = array(
                'id_producto' => $idProducto,
                'id_preparacion' => $idPreparacion,
                'activo' => 1
            );
            if ($this->db->insert('preparacion_producto', $data)) {
                return $this->utilidades->buildResponse(true, 'success', 200, 'Relación producto-preparación creada', array("filas afectadas" => 1));
            } else {
                return $this->utilidades->buildResponse(false, 'error', 500, 'Error al crear la relación producto-preparación');
            }
        }
    }

    public function activarRelPreparacion($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        /*         * *
          if ($this->jwt->getProperty($token, 'profile') !== 'Administrador') {
          return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo los administradores pueden activar preparaciones');
          }
         */
        $data = array(
            'activo' => 1
        );
        $this->db->where('id', $id);

        if ($this->db->update('preparacion_producto', $data)) {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Relación producto preparación activada', array("filas afectadas" => 1));
        } else {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró la relación producto preparación indicada');
        }
    }

    public function desactivarRelPreparacion($token, $id) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }
        /*         * *
          if ($this->jwt->getProperty($token, 'profile') !== 'Administrador') {
          return $this->utilidades->buildResponse(false, 'failed', 401, 'Solo los administradores pueden activar preparaciones');
          }
         */
        $data = array(
            'activo' => 0
        );
        $this->db->where('id', $id);

        if ($this->db->update('preparacion_producto', $data)) {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Relación producto preparación desactivada', array("filas afectadas" => 1));
        } else {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontró la relación producto preparación indicada');
        }
    }

    public function obtenerProductosConRelacion($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $this->db->select('p.id AS id_producto, p.nombre AS nombre_producto, pr.id AS id_preparacion, pr.nombre AS nombre_preparacion');
        $this->db->from('productos AS p');
        $this->db->join('preparacion_producto AS pp', 'p.id = pp.id_producto');
        $this->db->join('preparaciones AS pr', 'pr.id = pp.id_preparacion');
        $query = $this->db->get();
        $productos = $query->result_array();

        if (empty($productos)) {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontraron productos con relación de preparaciones');
        } else {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Listado de productos con relación de preparaciones', array('productos' => $productos));
        }
    }

    public function obtenerProductosPorPreparacion($token, $idPreparacion) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }


        $this->db->select('p.id AS id_producto, p.nombre AS nombre_producto, pp.stock_critico, pp.stock_bodega');
        $this->db->from('productos AS p');
        $this->db->join('preparacion_producto AS pp', 'p.id = pp.id_producto');
        $this->db->where('pp.activo = 1');
        $this->db->where('pp.id_preparacion', $idPreparacion);
        $query = $this->db->get();
        $productos = $query->result_array();

        $hayDisponibles = true;
        foreach ($productos as $producto) {
            if ($producto['stock_critico'] > $producto['stock_bodega']) {
                $hayDisponibles = false;
                break;
            }
        }

        if (empty($productos)) {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontraron productos para la preparación con el ID proporcionado');
        } else {
            $data = array(
                'productos' => $productos,
                'hay_disponibles' => $hayDisponibles
            );
            return $this->utilidades->buildResponse(true, 'success', 200, 'Listado de productos para la preparación', $data);
        }
    }

    public function obtenerMenu($token) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }


        $query = $this->db->query("
        SELECT 
            preparaciones.*,
            COUNT(productos.id) AS total_productos,
            SUM(CASE WHEN productos.stock_critico <= productos.stock_bodega THEN 1 ELSE 0 END) AS productos_con_stock,
            CASE WHEN SUM(CASE WHEN productos.stock_critico < productos.stock_bodega THEN 1 ELSE 0 END) = COUNT(productos.id) THEN 'disponible' ELSE 'no disponible' END AS disponibilidad
        FROM preparaciones
        LEFT JOIN preparacion_producto ON preparacion_producto.id_preparacion = preparaciones.id
        LEFT JOIN productos ON productos.id = preparacion_producto.id_producto
        where preparacion_producto.activo = 1
        GROUP BY preparaciones.id", false);
        $data = $query->result_array();
        return $this->utilidades->buildResponse(true, 'success', 200, 'Menu completo', $data);
    }

    public function obtenerProductosDePreparacion($token, $idPreparacion) {
        $verificarExpiracion = $this->jwt->verificarExpiracion($token, 'exp');
        if (!$verificarExpiracion["result"]) {
            return $this->utilidades->buildResponse(false, 'failed', 401, $verificarExpiracion["usrmsg"], $verificarExpiracion);
        }

        $this->db->select('p.*');
        $this->db->from('productos AS p');
        $this->db->join('preparacion_producto AS pp', 'p.id = pp.id_producto');
        $this->db->where('pp.id_preparacion', $idPreparacion);
        $query = $this->db->get();
        $productos = $query->result_array();

        if (empty($productos)) {
            return $this->utilidades->buildResponse(false, 'error', 404, 'No se encontraron productos para la preparación especificada');
        } else {
            return $this->utilidades->buildResponse(true, 'success', 200, 'Listado de productos de la preparación', array('productos' => $productos));
        }
    }

}
