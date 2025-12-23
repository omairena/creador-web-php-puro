<?php
// reporte_produccion.php - Reporte filtrable e imprimible en PDF
require_once __DIR__ . '/auth.php';
require_login();

// Cargar datos de empresa
$empresa = [
    'nombre' => 'Mi Empresa',
    'telefono' => '',
    'correo' => '',
    'direccion' => '',
    'logo' => ''
];
if (file_exists(__DIR__.'/empresa.json')) {
    $empresa = json_decode(file_get_contents(__DIR__.'/empresa.json'), true);
}

// Cargar productos para filtro
require_once __DIR__ . '/db.php';
$productos = [];
$res = $mysqli->query("SELECT id, code, name FROM products ORDER BY name");
while ($row = $res->fetch_assoc()) {
    $productos[] = $row;
}

// Filtros
$producto_id = isset($_GET['producto']) ? intval($_GET['producto']) : '';
$fecha_ini = isset($_GET['fecha_ini']) ? $_GET['fecha_ini'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Consulta de registros filtrados
$where = "WHERE r.deleted = 0";
$params = [];
if ($producto_id) {
    $where .= " AND r.producto_id = ?";
    $params[] = $producto_id;
}
if ($fecha_ini) {
    $where .= " AND r.fecha >= ?";
    $params[] = $fecha_ini;
}
if ($fecha_fin) {
    $where .= " AND r.fecha <= ?";
    $params[] = $fecha_fin;
}
$sql = "SELECT r.id, p.code, p.name, r.fecha, pr.name as presentacion, r.cantidad, r.lote, r.vencimiento, u.name as responsable, r.observaciones FROM registros_produccion r INNER JOIN products p ON r.producto_id = p.id LEFT JOIN presentations pr ON p.presentation_id = pr.id INNER JOIN users u ON r.responsable_id = u.id $where ORDER BY r.fecha DESC, r.id DESC";
$stmt = $mysqli->prepare($sql . (count($params) ? '' : ''));
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$registros = [];
while ($row = $res->fetch_assoc()) {
    $registros[] = $row;
}
$stmt->close();

// Si se solicita PDF
if (isset($_GET['pdf'])) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (!class_exists('TCPDF')) {
        echo "No está instalado TCPDF. Instale con composer require tecnickcom/tcpdf";
        exit;
    }
      class MyFooter extends TCPDF {
        public function Footer() {
          $this->SetY(-18);
          $this->SetFont('helvetica', 'I', 8);
          $this->Cell(0, 8, 'Generado por: ' . $_SESSION['user_name'] . ' el ' . date('d/m/Y H:i'), 0, 0, 'R');
        }
      }
      $pdf = new MyFooter('L', 'mm', 'A4', true, 'UTF-8', false);
      $pdf->SetCreator('Mi App');
      $pdf->SetAuthor($_SESSION['user_name']);
      $pdf->SetTitle('Reporte de Producción');
      $pdf->SetPrintHeader(false);
      $pdf->SetMargins(12, 20, 12);
      $pdf->AddPage();
      // Encabezado empresa (sin línea sobre el logo)
      $logo = $empresa['logo'] && file_exists($empresa['logo']) ? $empresa['logo'] : '';
      if ($logo) {
        $pdf->Image($logo, 12, 8, 28, 0, '', '', '', false, 300);
      }
      $pdf->SetFont('helvetica', 'B', 14);
      $pdf->SetXY(42, 10);
      $pdf->Cell(0, 8, $empresa['nombre'], 0, 1, 'L');
      $pdf->SetFont('helvetica', '', 10);
      $pdf->SetXY(42, 18);
      $pdf->Cell(0, 6, $empresa['direccion'], 0, 1, 'L');
      $pdf->SetXY(42, 24);
      $pdf->Cell(0, 6, 'Tel: '.$empresa['telefono'].'  Correo: '.$empresa['correo'], 0, 1, 'L');
      $pdf->SetY(34);
      $pdf->SetFont('helvetica', 'B', 12);
      $pdf->Cell(0, 8, 'Reporte de Producción', 0, 1, 'C');
      $pdf->SetFont('helvetica', '', 9);
      $pdf->Ln(2);
      // Anchos fijos en mm para cada columna (A4 horizontal: 297mm - márgenes)
      $w = [25, 45, 25, 25, 15, 25, 25, 30, 45]; // Suma aprox. 260mm
      $pdf->SetFont('helvetica', 'B', 9);
      // Encabezado
      $pdf->SetFillColor(230,230,230);
      $pdf->SetTextColor(0);
      $pdf->SetDrawColor(180,180,180);
      $pdf->SetLineWidth(0.3);
      $header = ['Código','Nombre','Fecha','Presentación','Cantidad','Lote','Vencimiento','Responsable','Observaciones'];
      $pdf->SetX(12);
      for($i=0;$i<count($header);$i++) {
        $pdf->Cell($w[$i],8,$header[$i],1,0,'C',true);
      }
      $pdf->Ln();
      $pdf->SetFont('helvetica','',8.5);
      // Contenido
      foreach($registros as $r) {
        $pdf->SetX(12);
        $pdf->MultiCell($w[0],7,$r['code'],1,'C',false,0,'','',true,0,true,true,7,'M');
        $pdf->MultiCell($w[1],7,$r['name'],1,'L',false,0,'','',true,0,true,true,7,'M');
        $pdf->MultiCell($w[2],7,$r['fecha'],1,'C',false,0,'','',true,0,true,true,7,'M');
        $pdf->MultiCell($w[3],7,$r['presentacion'],1,'L',false,0,'','',true,0,true,true,7,'M');
        $pdf->MultiCell($w[4],7,$r['cantidad'],1,'C',false,0,'','',true,0,true,true,7,'M');
        $pdf->MultiCell($w[5],7,$r['lote'],1,'C',false,0,'','',true,0,true,true,7,'M');
        $pdf->MultiCell($w[6],7,$r['vencimiento'],1,'C',false,0,'','',true,0,true,true,7,'M');
        $pdf->MultiCell($w[7],7,$r['responsable'],1,'L',false,0,'','',true,0,true,true,7,'M');
        $pdf->MultiCell($w[8],7,$r['observaciones'],1,'L',false,1,'','',true,0,true,true,7,'M');
      }
      $pdf->Output('reporte_produccion.pdf', 'I');
      exit;
}
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reporte de Producción</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .filtros-form { display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:18px; }
    .filtros-form label { font-weight:500; }
    .filtros-form select, .filtros-form input { padding:6px 10px; border-radius:5px; border:1px solid #ccc; }
    .btn { margin-top:0; }
    .table th, .table td { font-size:0.97rem; }
  </style>
</head>
<body class="layout">
  <?php include 'sidebar.php'; ?>
  <div class="main" style="display:flex;flex-direction:column;min-height:100vh;">
    <header class="header">
      <button id="toggleBtn" class="toggle-btn">☰</button>
      <button id="collapseBtn" class="collapse-btn" aria-label="Ocultar menú">◀</button>
      <h1>Reporte de Producción</h1>
    </header>
    <section class="content" style="flex:1 1 auto;">
      <form class="filtros-form" method="get">
        <label>Producto
          <select name="producto">
            <option value="">Todos</option>
            <?php foreach($productos as $p): ?>
              <option value="<?=$p['id']?>" <?=($producto_id==$p['id'])?'selected':''?>>[<?=$p['code']?>] <?=$p['name']?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Desde
          <input type="date" name="fecha_ini" value="<?=htmlspecialchars($fecha_ini)?>">
        </label>
        <label>Hasta
          <input type="date" name="fecha_fin" value="<?=htmlspecialchars($fecha_fin)?>">
        </label>
        <button class="btn" type="submit">Filtrar</button>
        <button class="btn" type="submit" name="pdf" value="1">Imprimir PDF</button>
      </form>
      <table class="table">
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Fecha</th>
            <th>Presentación</th>
            <th>Cantidad</th>
            <th>Lote</th>
            <th>Vencimiento</th>
            <th>Responsable</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($registros as $r): ?>
            <tr>
              <td><?=htmlspecialchars($r['code'])?></td>
              <td><?=htmlspecialchars($r['name'])?></td>
              <td><?=htmlspecialchars($r['fecha'])?></td>
              <td><?=htmlspecialchars($r['presentacion'])?></td>
              <td><?=htmlspecialchars($r['cantidad'])?></td>
              <td><?=htmlspecialchars($r['lote'])?></td>
              <td><?=htmlspecialchars($r['vencimiento'])?></td>
              <td><?=htmlspecialchars($r['responsable'])?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
    <?php include 'footer.php'; ?>
    <script src="assets/js/app.js"></script>
  </div>
</body>
</html>
