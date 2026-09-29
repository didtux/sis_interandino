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
                'venta_usuario' => $items->first()->venta_usuario,
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
        $primeraVenta = null;   // para poder abrir el ticket al cerrar la venta

        foreach ($request->productos as $item) {
            $producto = Producto::where('prod_codigo', $item['prod_codigo'])->first();
            
            if ($producto->prod_cantidad < $item['cantidad']) {
                return response()->json(['success' => false, 'message' => 'Stock insuficiente para ' . $producto->prod_nombre]);
            }

            $filaVenta = Venta::create([
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

            $primeraVenta = $primeraVenta ?: $filaVenta;
            $producto->decrement('prod_cantidad', $item['cantidad']);
            $totalGeneral += $item['subtotal'];
        }

        // Se devuelve el id para que la pantalla abra el ticket sola: antes el
        // backend no devolvía nada y había que ir a buscar la venta en el
        // listado para poder imprimir el comprobante.
        return response()->json([
            'success'    => true,
            'total'      => $totalGeneral,
            'ven_codigo' => $venCodigo,
            'recibo_url' => $primeraVenta ? route('ventas.recibo', $primeraVenta->ven_id) : null,
        ]);
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
        $productos  = Producto::visible()->get();
        $categorias = \App\Models\Categoria::orderBy('categ_nombre')->get();
        return view('ventas.reportes', compact('productos', 'categorias'));
    }

    public function reporteProductoPdf(Request $request)
    {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 600);
            
            // Se puede pedir por producto o por categoría entera. Antes sólo
            // existía por producto, así que para saber cuánto se vendió de
            // "Refrigerios" había que sacar un reporte por cada ítem y sumar.
            if (!$request->filled('prod_codigo') && !$request->filled('categ_codigo')) {
                return back()->with('error', 'Elegí un producto o una categoría para el reporte.');
            }

            $porProducto = null;
            $query = Venta::where('venta_estado', 'completado');

            if ($request->filled('prod_codigo')) {
                $producto = Producto::where('prod_codigo', $request->prod_codigo)->firstOrFail();
                $titulo   = 'VENTA DE ' . strtoupper($producto->prod_nombre);
                $query->where('prod_codigo', $request->prod_codigo);
            } else {
                $categoria = \App\Models\Categoria::where('categ_codigo', $request->categ_codigo)->firstOrFail();
                $codigos   = Producto::where('categ_codigo', $request->categ_codigo)->pluck('prod_codigo');
                $titulo    = 'VENTAS DE LA CATEGORÍA ' . strtoupper($categoria->categ_nombre);
                // El formato térmico imprime un solo nombre; le pasamos el de la categoría.
                $producto  = new Producto(['prod_nombre' => $categoria->categ_nombre]);
                $query->whereIn('prod_codigo', $codigos);
            }
            
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

            // En el reporte por categoría interesa además el desglose por ítem.
            if ($request->filled('categ_codigo') && !$request->filled('prod_codigo')) {
                $nombres = Producto::whereIn('prod_codigo', $ventasData->pluck('prod_codigo')->unique())
                    ->pluck('prod_nombre', 'prod_codigo');
                $porProducto = $ventasData->groupBy('prod_codigo')->map(function($items, $cod) use ($nombres) {
                    return [
                        'nombre'   => $nombres[$cod] ?? $cod,
                        'cantidad' => $items->sum('venta_cantidad'),
                        'total'    => $items->sum('venta_preciototal'),
                    ];
                })->sortByDesc('total')->values();
            }
            
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
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($vista, compact('producto', 'ventas', 'fechaInicio', 'fechaFin', 'titulo', 'porProducto'))
                ->setPaper($papel, 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);
            return $pdf->stream('ventas-' . \Illuminate\Support\Str::slug($producto->prod_nombre) . '.pdf');
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
