<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')->visible()->paginate(50);
        $categorias = Categoria::visible()->get();
        return view('productos.index', compact('productos', 'categorias'));
    }

    public function create()
    {
        $categorias = Categoria::visible()->get();
        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'prod_nombre' => 'required',
            'categ_codigo' => 'required|exists:ventas_categorias,categ_codigo',
            'prod_cantidad' => 'required|integer',
            'prod_preciounitario' => 'required|numeric'
        ]);

        Producto::create([
            'prod_codigo' => 'PROD' . time(),
            'prod_item' => $request->prod_item,
            'categ_codigo' => $request->categ_codigo,
            'prod_nombre' => $request->prod_nombre,
            'prod_detalles' => $request->prod_detalles,
            'prod_cantidad' => $request->prod_cantidad,
            'prod_precioreal' => $request->prod_precioreal ?? $request->prod_preciounitario,
            'prod_preciounitario' => $request->prod_preciounitario,
            'prod_preciodescuento' => $request->prod_preciodescuento ?? 0
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto creado');
    }

    public function edit($id)
    {
        $producto = Producto::findOrFail($id);
        $categorias = Categoria::visible()->get();
        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);
        $producto->update($request->all());
        return redirect()->route('productos.index')->with('success', 'Producto actualizado');
    }

    public function destroy($id)
    {
        Producto::findOrFail($id)->update(['prod_visible' => 0]);
        return redirect()->route('productos.index')->with('success', 'Producto eliminado');
    }

    public function etiqueta($id)
    {
        $producto = Producto::with('categoria')->findOrFail($id);
        
        $qrData = "{$producto->prod_codigo}|{$producto->prod_nombre}|{$producto->categoria->categ_nombre}|Bs.{$producto->prod_preciounitario}";
        
        $options = new QROptions([
            'version'    => 10,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => QRCode::ECC_L,
            'scale'      => 8,
            'imageBase64' => true,
        ]);
        
        $qrcode = new QRCode($options);
        $qrCode = $qrcode->render($qrData);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('productos.etiqueta-pdf', compact('producto', 'qrCode'))
            ->setPaper([0, 0, 226.77, 340]);
        
        return $pdf->stream('etiqueta-' . $producto->prod_codigo . '.pdf');
    }

    public function buscarPorBarcode($barcode)
    {
        $producto = Producto::where('prod_item', $barcode)->where('prod_visible', 1)->first();
        
        if ($producto) {
            return response()->json([
                'found' => true,
                'prod_codigo' => $producto->prod_codigo
            ]);
        }
        
        return response()->json(['found' => false]);
    }
}
