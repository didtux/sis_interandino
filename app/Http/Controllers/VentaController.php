<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::with('producto')->whereIn('venta_estado', ['completado', 'anulado']);

        if ($request->filled('prod_codigo')) {
            $query->where('prod_codigo', $request->prod_codigo);
        }
        if ($request->filled('cliente')) {
            $query->where('ven_cliente', 'like', '%' . $request->cliente . '%');
        }
        if ($request->filled('tipo')) {
            $query->where('venta_tipo', $request->tipo);
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('venta_fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('venta_fecha', '<=', $request->fecha_fin);
        }
        if ($request->filled('estado')) {
            $query->where('venta_estado', $request->estado);
        }

        $ventas = $query->orderBy('venta_fecha', 'desc')->get();
        
        // Agrupar ventas por código
        $ventasAgrupadas = $ventas->groupBy('ven_codigo')->map(function($items) {
            return [
                'ven_codigo' => $items->first()->ven_codigo,
                'ven_cliente' => $items->first()->ven_cliente,
                'ven_celular' => $items->first()->ven_celular,
                'venta_fecha' => $items->first()->venta_fecha,
                'venta_estado' => $items->first()->venta_estado,
                'venta_tipo' => $items->first()->venta_tipo,
                'productos' => $items,
                'total' => $items->sum('venta_preciototal'),
                'cantidad_productos' => $items->count()
            ];
        });
        
        $productos = Producto::visible()->get();
        return view('ventas.index', compact('ventasAgrupadas', 'productos'));
    }

    public function create()
    {
        $productos = Producto::visible()->with('categoria')->get();
        $estudiantes = Estudiante::visible()->get();
        return view('ventas.create', compact('productos', 'estudiantes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ven_cliente' => 'required',
            'productos' => 'required|array|min:1'
        ]);

        $venCodigo = 'VEN' . time();
        $totalGeneral = 0;

        foreach ($request->productos as $item) {
            $producto = Producto::where('prod_codigo', $item['prod_codigo'])->first();
            
            if ($producto->prod_cantidad < $item['cantidad']) {
                return response()->json(['success' => false, 'message' => 'Stock insuficiente para ' . $producto->prod_nombre]);
            }

            Venta::create([
                'ven_codigo' => $venCodigo,
                'prod_codigo' => $item['prod_codigo'],
                'ven_cliente' => $request->ven_cliente,
                'ven_celular' => $request->ven_celular,
                'ven_direccion' => $request->ven_direccion,
                'venta_cantidad' => $item['cantidad'],
                'venta_precio' => $item['precio'],
                'venta_preciototal' => $item['subtotal'],
                'venta_estado' => 'completado',
                'venta_tipo' => $item['tipo'],
                'venta_usuario' => auth()->user()->us_codigo
            ]);

            $producto->decrement('prod_cantidad', $item['cantidad']);
            $totalGeneral += $item['subtotal'];
        }

        return response()->json(['success' => true, 'total' => $totalGeneral]);
    }

    public function reportePdf(Request $request)
    {
        $query = Venta::with('producto')->where('venta_estado', 'completado');

        if ($request->filled('prod_codigo')) {
            $query->where('prod_codigo', $request->prod_codigo);
        }
        if ($request->filled('cliente')) {
            $query->where('ven_cliente', 'like', '%' . $request->cliente . '%');
        }
        if ($request->filled('tipo')) {
            $query->where('venta_tipo', $request->tipo);
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('venta_fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('venta_fecha', '<=', $request->fecha_fin);
        }

        $ventas = $query->orderBy('venta_fecha', 'desc')->limit(500)->get();
        $total = $ventas->sum('venta_preciototal');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ventas.reporte-pdf', compact('ventas', 'total', 'request'))
            ->setPaper('letter', 'portrait');
        return $pdf->stream('reporte-ventas-' . date('Y-m-d') . '.pdf');
    }

    public function reporteExcel(Request $request)
    {
        $query = Venta::with('producto');

        if ($request->filled('prod_codigo')) {
            $query->where('prod_codigo', $request->prod_codigo);
        }
        if ($request->filled('cliente')) {
            $query->where('ven_cliente', 'like', '%' . $request->cliente . '%');
        }
        if ($request->filled('tipo')) {
            $query->where('venta_tipo', $request->tipo);
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('venta_fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('venta_fecha', '<=', $request->fecha_fin);
        }

        $ventas = $query->orderBy('venta_fecha', 'desc')->get();
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\VentasExport($ventas), 
            'reporte-ventas-' . date('Y-m-d') . '.xlsx'
        );
    }

    public function anular($id)
    {
        $venta = Venta::findOrFail($id);
        
        if ($venta->venta_estado == 'anulado') {
            return redirect()->back()->with('error', 'Esta venta ya está anulada');
        }

        // Obtener todas las ventas con el mismo código
        $ventas = Venta::where('ven_codigo', $venta->ven_codigo)->get();
        
        // Restablecer stock de todos los productos
        foreach ($ventas as $v) {
            $producto = Producto::where('prod_codigo', $v->prod_codigo)->first();
            if ($producto) {
                $producto->increment('prod_cantidad', $v->venta_cantidad);
            }
        }

        // Anular todas las ventas con el mismo código
        Venta::where('ven_codigo', $venta->ven_codigo)->update(['venta_estado' => 'anulado']);

        return redirect()->back()->with('success', 'Venta anulada y stock restablecido');
    }

    public function recibo($id)
    {
        $venta = Venta::findOrFail($id);
        $ventas = Venta::with('producto')->where('ven_codigo', $venta->ven_codigo)->get();
        $total = $ventas->sum('venta_preciototal');
        
        // Calcular altura dinámica basada en la cantidad de productos
        $alturaBase = 400; // Altura base del recibo
        $alturaPorProducto = 80; // Altura aproximada por cada producto
        $cantidadProductos = $ventas->count();
        $alturaTotal = $alturaBase + ($cantidadProductos * $alturaPorProducto);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ventas.recibo-pdf', compact('ventas', 'total'))
            ->setPaper([0, 0, 226.77, $alturaTotal], 'portrait');
        return $pdf->stream('recibo-' . $venta->ven_codigo . '.pdf');
    }

    public function getPorCodigo($codigo)
    {
        $venta = Venta::where('ven_codigo', $codigo)->first();
        return response()->json(['id' => $venta ? $venta->ven_id : null]);
    }

    public function reportes()
    {
        $productos = Producto::visible()->get();
        return view('ventas.reportes', compact('productos'));
    }

    public function reporteProductoPdf(Request $request)
    {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 600);
            
            $request->validate([
                'prod_codigo' => 'required'
            ]);

            $producto = Producto::where('prod_codigo', $request->prod_codigo)->firstOrFail();
            
            $query = Venta::where('prod_codigo', $request->prod_codigo)
                ->where('venta_estado', 'completado');
            
            $fechaInicio = $request->fecha_inicio ?? now()->subMonth()->format('Y-m-d');
            $fechaFin = $request->fecha_fin ?? now()->format('Y-m-d');
            
            $query->whereBetween('venta_fecha', [$fechaInicio, $fechaFin]);
            
            $ventasData = $query->limit(1000)->get();
            
            // Agrupar por fecha
            $ventas = $ventasData->groupBy(function($item) {
                return $item->venta_fecha->format('Y-m-d');
            })->map(function($items) {
                return $items->sum('venta_preciototal');
            });
            
            $formato = $request->formato ?? 'pdf';
            $vista = $formato == 'termica' ? 'ventas.reporte-producto-termica' : 'ventas.reporte-producto-pdf';
            
            if ($formato == 'termica') {
                $alturaBase = 400;
                $alturaPorItem = 60;
                $cantidadItems = min($ventas->count(), 50);
                $alturaTotal = max(500, $alturaBase + ($cantidadItems * $alturaPorItem));
                $papel = [0, 0, 226.77, $alturaTotal];
            } else {
                $papel = 'letter';
            }
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($vista, compact('producto', 'ventas', 'fechaInicio', 'fechaFin'))
                ->setPaper($papel, 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);
            return $pdf->stream('venta-producto-' . $producto->prod_codigo . '.pdf');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar reporte: ' . $e->getMessage());
        }
    }

    public function reporteArqueoPdf(Request $request)
    {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 600);
            
            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date'
            ]);
            
            $ventas = Venta::with('producto')
                ->where('venta_estado', 'completado')
                ->whereBetween('venta_fecha', [$request->fecha_inicio, $request->fecha_fin])
                ->orderBy('venta_fecha', 'asc')
                ->limit(1000)
                ->get();
            
            $formato = $request->formato ?? 'pdf';
            $vista = $formato == 'termica' ? 'ventas.reporte-arqueo-termica' : 'ventas.reporte-arqueo-pdf';
            
            if ($formato == 'termica') {
                $alturaBase = 400;
                $alturaPorVenta = 100;
                $cantidadVentas = min($ventas->count(), 50);
                $alturaTotal = max(500, $alturaBase + ($cantidadVentas * $alturaPorVenta));
                $papel = [0, 0, 226.77, $alturaTotal];
            } else {
                $papel = 'letter';
            }
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($vista, compact('ventas'))
                ->setPaper($papel, 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);
            return $pdf->stream('arqueo-semanal-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar reporte: ' . $e->getMessage());
        }
    }
}
