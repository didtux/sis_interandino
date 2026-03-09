<?php

namespace App\Http\Controllers;

use App\Models\MovimientoAlmacen;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use DB;

class MovimientoAlmacenController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoAlmacen::with(['producto', 'proveedor']);

        if ($request->filled('tipo')) {
            $query->where('mov_tipo', $request->tipo);
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('mov_fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        $movimientos = $query->orderBy('mov_fecha', 'desc')->paginate(50);
        return view('movimientos.index', compact('movimientos'));
    }

    public function create()
    {
        $productos = Producto::visible()->get();
        $proveedores = Proveedor::activo()->get();
        return view('movimientos.create', compact('productos', 'proveedores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'prod_codigo' => 'required|exists:ventas_productos,prod_codigo',
            'mov_tipo' => 'required|in:entrada,salida,ajuste,devolucion',
            'mov_cantidad' => 'required|integer|min:1',
            'mov_motivo' => 'required|max:200'
        ]);

        DB::beginTransaction();
        try {
            $producto = Producto::where('prod_codigo', $request->prod_codigo)->first();

            // Crear movimiento
            MovimientoAlmacen::create([
                'mov_codigo' => 'MOV' . time(),
                'prod_codigo' => $request->prod_codigo,
                'prov_codigo' => $request->prov_codigo,
                'mov_tipo' => $request->mov_tipo,
                'mov_cantidad' => $request->mov_cantidad,
                'mov_precio_unitario' => $request->mov_precio_unitario,
                'mov_precio_total' => $request->mov_precio_unitario * $request->mov_cantidad,
                'mov_motivo' => $request->mov_motivo,
                'mov_observacion' => $request->mov_observacion,
                'mov_usuario' => auth()->user()->us_codigo
            ]);

            // Actualizar stock
            if ($request->mov_tipo == 'entrada') {
                $producto->increment('prod_cantidad', $request->mov_cantidad);
            } elseif ($request->mov_tipo == 'salida') {
                if ($producto->prod_cantidad < $request->mov_cantidad) {
                    throw new \Exception('Stock insuficiente');
                }
                $producto->decrement('prod_cantidad', $request->mov_cantidad);
            } elseif ($request->mov_tipo == 'ajuste') {
                $producto->prod_cantidad = $request->mov_cantidad;
                $producto->save();
            }

            DB::commit();
            return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reporteStock(Request $request)
    {
        $query = Producto::with(['categoria', 'proveedor'])->visible();

        if ($request->filled('estado')) {
            switch ($request->estado) {
                case 'sin_stock':
                    $query->where('prod_cantidad', 0);
                    break;
                case 'bajo':
                    $query->whereBetween('prod_cantidad', [1, 5]);
                    break;
                case 'medio':
                    $query->whereBetween('prod_cantidad', [6, 10]);
                    break;
                case 'normal':
                    $query->where('prod_cantidad', '>', 10);
                    break;
            }
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('prod_nombre', 'like', "%{$buscar}%")
                  ->orWhere('prod_codigo', 'like', "%{$buscar}%");
            });
        }

        $productos = $query->orderBy('prod_cantidad', 'asc')->get();
        return view('movimientos.reporte-stock', compact('productos'));
    }

    public function reporteStockPdf(Request $request)
    {
        $query = Producto::with(['categoria', 'proveedor'])->visible();

        $filtros = [];
        if ($request->filled('estado')) {
            $filtros['estado'] = $request->estado;
            switch ($request->estado) {
                case 'sin_stock':
                    $query->where('prod_cantidad', 0);
                    break;
                case 'bajo':
                    $query->whereBetween('prod_cantidad', [1, 5]);
                    break;
                case 'medio':
                    $query->whereBetween('prod_cantidad', [6, 10]);
                    break;
                case 'normal':
                    $query->where('prod_cantidad', '>', 10);
                    break;
            }
        }

        if ($request->filled('buscar')) {
            $filtros['buscar'] = $request->buscar;
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('prod_nombre', 'like', "%{$buscar}%")
                  ->orWhere('prod_codigo', 'like', "%{$buscar}%");
            });
        }

        $productos = $query->orderBy('prod_cantidad', 'asc')->get();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('movimientos.reporte-stock-pdf', compact('productos', 'filtros'))
            ->setPaper('letter', 'portrait');
        return $pdf->stream('reporte-stock-' . date('Y-m-d') . '.pdf');
    }
}
