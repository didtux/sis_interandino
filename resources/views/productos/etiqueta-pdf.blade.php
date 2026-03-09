<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etiqueta - {{ $producto->prod_codigo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            width: 80mm;
            padding: 5mm;
        }
        .etiqueta {
            text-align: center;
            border: 2px dashed #333;
            padding: 8px;
        }
        .titulo {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .info-row {
            font-size: 10px;
            margin: 6px 0;
            text-align: center;
        }
        .label {
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }
        .value {
            display: block;
        }
        .precio {
            font-size: 24px;
            font-weight: bold;
            margin: 12px 0;
            padding: 8px;
            background-color: #000;
            color: #fff;
            border-radius: 4px;
        }
        .qr-container {
            margin-top: 10px;
            text-align: center;
            padding: 5px;
            background-color: #fff;
        }
        .qr-container img {
            display: block;
            margin: 0 auto;
            width: 180px;
            height: 180px;
        }
        .footer {
            font-size: 8px;
            margin-top: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="etiqueta">
      
        <div class="info-row">
            <span class="label">CODIGO:</span>
            <span class="value">{{ $producto->prod_codigo }}</span>
        </div>
        
        <div class="qr-container">
            <img src="{{ $qrCode }}" alt="QR">
        </div>
        
        <div class="footer">
            Escanea el codigo QR para ver detalles
        </div>
    </div>
</body>
</html>
