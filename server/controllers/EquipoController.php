<?php

namespace Controllers;

use Model\Equipo;
use Model\Mantenimiento;
use Model\Calibracion;

class EquipoController
{
    public static function index()
    {
        $equipos = Equipo::all();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($equipos);
        exit;
    }

    // Equipo Mantenimiento Calibracion
    public static function equiposAll()
    {
        $equipos = Equipo::ejecutarSQL('SELECT 
                e.id, 
                u.nombre as uci,
                e.ubicacion,
                e.equipo, 
                e.marca, 
                e.modelo, 
                e.serie, 
                e.placa, 
                e.registro_invima as registroInvima, 
                e.clasificacion_invima as clasificacionInvima,
                m.frecuencia as frecuenciaMtto,
                m.fecha_realizado as mttoRealizado,
                m.fecha_proxima as mttoProximo,
                c.fecha_realizada as calibracion,
                c.fecha_proxima as proximaCalibracion,
                c.certificado_calibracion as codigoCalibracion
                FROM equipos as e
            INNER JOIN ucis as u ON u.id = e.uci_id
            INNER JOIN mantenimientos as m ON m.equipo_id = e.id
              INNER JOIN calibraciones as c ON c.equipo_id = e.id');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($equipos);
        exit;
    }

    public static function show($id)
    {
        return Equipo::find($id);
    }

    public static function store($router)
    {
        $datos = json_decode(file_get_contents('php://input'), true);
        $equipo = new Equipo($datos);

        $resultado = $equipo->guardar();

        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }

    // POST /equipos/actualizar -> actualiza equipo + su mantenimiento + su calibración
    public static function actualizar($router)
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = (int) ($datos['id'] ?? 0);

        $responder = function (int $codigo, array $cuerpo) {
            http_response_code($codigo);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($cuerpo);
            exit;
        };

        $equipo = $id ? Equipo::find($id) : null;
        if (!$equipo) {
            $responder(404, ['ok' => false, 'error' => 'Equipo no encontrado']);
        }
        if (trim($datos['equipo'] ?? '') === '') {
            $responder(422, ['ok' => false, 'error' => 'El nombre del equipo es obligatorio']);
        }

        $db = Equipo::getDB();
        $db->begin_transaction();
        try {
            $equipo->sincronizar([
                'uci_id' => $datos['uci_id'] ?? null,
                'ubicacion' => $datos['ubicacion'] ?? '',
                'equipo' => $datos['equipo'],
                'marca' => $datos['marca'] ?? '',
                'modelo' => $datos['modelo'] ?? '',
                'serie' => $datos['serie'] ?? '',
                'placa' => $datos['placa'] ?? '',
                'registro_invima' => $datos['registro_invima'] ?? '',
                'clasificacion_invima' => $datos['clasificacion_invima'] ?? '',
            ]);
            $equipo->guardar();

            $m = $datos['mantenimiento'] ?? [];
            $mant = Mantenimiento::where('equipo_id', $id) ?? new Mantenimiento(['equipo_id' => $id]);
            $mant->sincronizar([
                'frecuencia' => $m['frecuencia'] ?? '',
                'fecha_realizado' => $m['fecha_realizado'] ?? '',
                'fecha_proxima' => $m['fecha_proxima'] ?? '',
            ]);
            // sincronizar() ignora nulos: las fechas vacías se fuerzan a '' (-> NULL en BD)
            $mant->fecha_realizado = $m['fecha_realizado'] ?? '';
            $mant->fecha_proxima = $m['fecha_proxima'] ?? '';
            $mant->guardar();

            $c = $datos['calibracion'] ?? [];
            $cal = Calibracion::where('equipo_id', $id) ?? new Calibracion(['equipo_id' => $id]);
            $cal->fecha_realizada = $c['fecha_realizada'] ?? '';
            $cal->fecha_proxima = $c['fecha_proxima'] ?? '';
            $cal->certificado_calibracion = $c['certificado_calibracion'] ?? '';
            $cal->guardar();

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            error_log('HOMI actualizar equipo: ' . $e->getMessage());
            $responder(500, ['ok' => false, 'error' => 'No se pudo guardar en la base de datos']);
        }

        $responder(200, ['ok' => true, 'id' => $id]);
    }

    public static function update($id, $datos)
    {
        $equipo = Equipo::find($id);

        if (!$equipo) {
            return [
                'error' => true,
                'mensaje' => 'Equipo no encontrado'
            ];
        }

        $equipo->sincronizar($datos);

        return $equipo->guardar();
    }

    public static function delete($id)
    {
        $equipo = Equipo::find($id);

        if (!$equipo) {
            return [
                'error' => true,
                'mensaje' => 'Equipo no encontrado'
            ];
        }

        return $equipo->eliminar();
    }
}
