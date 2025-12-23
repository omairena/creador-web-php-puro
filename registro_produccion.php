

<?php
// Cargar PHPMailer y dependencias desde el inicio
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer y Dotenv si están instalados
	if (class_exists('Dotenv\\Dotenv')) {
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
		$dotenv->load();
	}
}
// --- AJAX para cargar productos y registrar producción ---
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/products_model.php';
require_login();

// --- helpers para AJAX ---
function get_all_products_with_presentation() {
	global $mysqli;
	$sql = "SELECT p.id, p.code, p.name, pr.name as presentacion, p.vida_util FROM products p LEFT JOIN presentations pr ON p.presentation_id = pr.id ORDER BY p.name";
	$res = $mysqli->query($sql);
	$out = [];
	if ($res) {
		while ($row = $res->fetch_assoc()) {
			$out[] = $row;
		}
	}
	return $out;
}

if (isset($_GET['debug']) && $_GET['debug'] == '1') {
	echo '<pre>DEBUG productos:<br>';
	$productos = get_all_products_with_presentation();
	var_dump($productos);
	echo '</pre>';
	exit;
}
if (isset($_GET['ajax'])) {
	header('Content-Type: application/json');
	if ($_GET['ajax'] === 'productos') {
		$productos = get_all_products_with_presentation();
		error_log('AJAX productos: '.json_encode($productos));
		echo json_encode($productos);
		exit;
	}
	if ($_GET['ajax'] === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
		$data = json_decode(file_get_contents('php://input'), true);
		$resp = guardar_registro_produccion($data, $_SESSION['user_id']);
		echo json_encode($resp);
		exit;
	}
	if ($_GET['ajax'] === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
		$data = json_decode(file_get_contents('php://input'), true);
		if (!empty($data['codigo'])) {
			// Confirmación con código
			$resp = confirmar_eliminacion_registro($data['id'], $data['codigo']);
		} else if (!empty($data['justificacion'])) {
			// Solicitud inicial: enviar código
			$resp = solicitar_eliminacion_registro($data['id'], $data['justificacion']);
		} else {
			$resp = ['success'=>false,'message'=>'Falta justificación o código'];
		}
		echo json_encode($resp);
		exit;
	}
	if ($_GET['ajax'] === 'listar') {
		$registros = listar_registros_produccion();
		echo json_encode($registros);
		exit;
	}
	exit;
}

// Guardar registro de producción
function guardar_registro_produccion($data, $user_id) {
	global $mysqli;
	$producto_id = intval($data['producto']);
	$fecha = $data['fecha'];
	$cantidad = intval($data['cantidad']);
	$observaciones = trim($data['observaciones'] ?? '');
	// Obtener vida útil y presentación
	$stmt = $mysqli->prepare("SELECT vida_util, code, name, presentation_id FROM products WHERE id = ?");
	$stmt->bind_param('i', $producto_id);
	$stmt->execute();
	$stmt->bind_result($vida_util, $codigo, $nombre, $presentation_id);
	if (!$stmt->fetch()) {
		$stmt->close();
		return ['success'=>false,'message'=>'Producto no encontrado'];
	}
	$stmt->close();
	// Calcular vencimiento
	$vida_util = intval($vida_util);
	$vencimiento = date('Y-m-d', strtotime("$fecha +$vida_util months"));
	// Generar lote: DDMMAA-XX
	$fecha_lote = date('dmy', strtotime($fecha));
	$sql = "SELECT COUNT(*) FROM registros_produccion WHERE fecha = ?";
	$stmt = $mysqli->prepare($sql);
	$stmt->bind_param('s', $fecha);
	$stmt->execute();
	$stmt->bind_result($cuantos);
	$stmt->fetch();
	$stmt->close();
	$consecutivo = str_pad($cuantos+1, 2, '0', STR_PAD_LEFT);
	$lote = $fecha_lote.'-'.$consecutivo;
	// Insertar registro
	$sql = "INSERT INTO registros_produccion (producto_id, fecha, cantidad, lote, vencimiento, responsable_id, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)";
	$stmt = $mysqli->prepare($sql);
	if (!$stmt) return ['success'=>false,'message'=>'Error al preparar registro: '.$mysqli->error];
	$stmt->bind_param('isissis', $producto_id, $fecha, $cantidad, $lote, $vencimiento, $user_id, $observaciones);
	$ok = $stmt->execute();
	$stmt->close();
	if ($ok) return ['success'=>true,'message'=>'Registro guardado','lote'=>$lote,'vencimiento'=>$vencimiento];
	else return ['success'=>false,'message'=>'Error al guardar: '.$mysqli->error];
}

// Listar registros de producción
function listar_registros_produccion() {
	global $mysqli;
	$sql = "SELECT r.id, p.code, p.name, r.fecha, pr.name as presentacion, r.cantidad, r.lote, r.vencimiento, u.name as responsable, r.observaciones FROM registros_produccion r INNER JOIN products p ON r.producto_id = p.id LEFT JOIN presentations pr ON p.presentation_id = pr.id INNER JOIN users u ON r.responsable_id = u.id WHERE r.deleted = 0 ORDER BY r.fecha DESC, r.id DESC";
	$res = $mysqli->query($sql);
	$out = [];
	if ($res) {
		while ($row = $res->fetch_assoc()) {
			$out[] = $row;
		}
	}
	return $out;
}

// Flujo de eliminación con confirmación por correo
function solicitar_eliminacion_registro($id, $justificacion) {
	global $mysqli;
	$id = intval($id);
	$justificacion = trim($justificacion);
	// Obtener código del registro
	$stmt = $mysqli->prepare("SELECT code FROM registros_produccion r INNER JOIN products p ON r.producto_id = p.id WHERE r.id = ?");
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$stmt->bind_result($codigo);
	if (!$stmt->fetch()) {
		$stmt->close();
		return ['success'=>false,'message'=>'Registro no encontrado'];
	}
	$stmt->close();
	// Generar token/código de confirmación
	$token = bin2hex(random_bytes(4));
	$token_hash = hash('sha256', $token);
	$expires_at = date('Y-m-d H:i:s', time() + 1800); // 30 min
	// Guardar token
	$stmt = $mysqli->prepare("INSERT INTO delete_produccion_tokens (registro_id, code, justification, token_hash, expires_at) VALUES (?, ?, ?, ?, ?)");
	$stmt->bind_param('issss', $id, $codigo, $justificacion, $token_hash, $expires_at);
	if (!$stmt->execute()) {
		$err = $stmt->error;
		$stmt->close();
		return ['success'=>false,'message'=>'Error al guardar token: '.$err];
	}
	$stmt->close();
	// Enviar correo
	$enviado = enviar_codigo_eliminacion($codigo, $justificacion, $token);
	if ($enviado) {
		return ['success'=>true,'message'=>'Se envió un código de confirmación al correo. Debe ingresarlo para completar la eliminación.'];
	} else {
		return ['success'=>false,'message'=>'No se pudo enviar el correo de confirmación'];
	}
}

function confirmar_eliminacion_registro($id, $codigo) {
	global $mysqli;
	$id = intval($id);
	$codigo = trim($codigo);
	$token_hash = hash('sha256', $codigo);
	$now = date('Y-m-d H:i:s');
	$stmt = $mysqli->prepare("SELECT id FROM delete_produccion_tokens WHERE registro_id = ? AND token_hash = ? AND expires_at >= ?");
	$stmt->bind_param('iss', $id, $token_hash, $now);
	$stmt->execute();
	$stmt->bind_result($token_id);
	if (!$stmt->fetch()) {
		$stmt->close();
		return ['success'=>false,'message'=>'Código inválido o expirado'];
	}
	$stmt->close();
	// Eliminar registro (borrado lógico)
	$stmt = $mysqli->prepare("UPDATE registros_produccion SET deleted = 1 WHERE id = ?");
	if (!$stmt) return ['success'=>false,'message'=>'Error al preparar: '.$mysqli->error];
	$stmt->bind_param('i', $id);
	$ok = $stmt->execute();
	$stmt->close();
	if ($ok) {
		// Borrar token usado
		$stmt = $mysqli->prepare("DELETE FROM delete_produccion_tokens WHERE id = ?");
		$stmt->bind_param('i', $token_id);
		$stmt->execute();
		$stmt->close();
		return ['success'=>true,'message'=>'Registro eliminado correctamente'];
	} else {
		return ['success'=>false,'message'=>'Error al eliminar: '.$mysqli->error];
	}
}

function enviar_codigo_eliminacion($codigo, $justificacion, $token) {
	// DEPURACIÓN: Verificar si PHPMailer está disponible
	if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
		file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - PHPMailer NO disponible\n", FILE_APPEND);
	} else {
		file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - PHPMailer SÍ disponible\n", FILE_APPEND);
	}
	$to = 'osmv789@gmail.com';
	$subject = 'Solicitud de eliminación de registro de producción';
	$body = "Se ha solicitado la eliminación del registro de producción con código: $codigo\n\nJustificación: $justificacion\n\nCódigo de confirmación: $token\n\nEste código expira en 30 minutos.";

	// Leer configuración SMTP desde variables de entorno
	// Configuración SMTP pegada directamente
	$smtp_host = 'mail.fesanesteban.com';
	$smtp_port = 465;
	$smtp_user = 'terminos@fesanesteban.com';
	$smtp_pass = '9p.w-?B+D=If';
	$smtp_secure = 'ssl';
	$from = 'terminos@fesanesteban.com';
	$from_name = 'Mi App';

	// Log de variables SMTP
	file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - SMTP_HOST: $smtp_host | SMTP_PORT: $smtp_port | SMTP_USER: $smtp_user | SMTP_PASS: $smtp_pass | SMTP_SECURE: $smtp_secure | MAIL_FROM: $from | MAIL_FROM_NAME: $from_name\n", FILE_APPEND);
	if ($smtp_host && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
		// Usar PHPMailer
		file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - Entrando a bloque PHPMailer\n", FILE_APPEND);
		$mail = new PHPMailer\PHPMailer\PHPMailer(true);
		try {
			$mail->SMTPDebug = 2;
			$mail->Debugoutput = function($str, $level) {
				file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - SMTP: ".$str."\n", FILE_APPEND);
			};
			$mail->isSMTP();
			$mail->Host = $smtp_host;
			$mail->SMTPAuth = true;
			$mail->Username = $smtp_user;
			$mail->Password = $smtp_pass;
			$mail->SMTPSecure = $smtp_secure;
			$mail->Port = $smtp_port;

			$mail->setFrom($from, $from_name);
			$mail->addAddress($to);

			$mail->isHTML(false);
			$mail->Subject = $subject;
			$mail->Body = $body;

			file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - Antes de mail->send()\n", FILE_APPEND);
			$ok = $mail->send();
			file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - Después de mail->send() resultado: ".($ok?'OK':'FALLO')."\n", FILE_APPEND);
			if (!$ok) {
				file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - PHPMailer error: ".$mail->ErrorInfo."\n", FILE_APPEND);
			}
			return $ok;
		} catch (Exception $e) {
			file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - PHPMailer EXCEPTION: ".$e->getMessage()."\n", FILE_APPEND);
			return false;
		}
	}
	// Fallback a mail()
	file_put_contents(__DIR__.'/phpmailer_debug.log', date('c')." - Entrando a fallback mail()\n", FILE_APPEND);

	// Fallback a mail()
	$headers = 'From: ' . $from . "\r\n" . 'Reply-To: ' . $from . "\r\n" . 'X-Mailer: PHP/' . phpversion();
	return mail($to, $subject, $body, $headers);
}

$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Registro de Producción</title>
	<link rel="stylesheet" href="assets/css/styles.css">
	<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
</head>
<body class="layout">
	<?php include 'sidebar.php'; ?>

	<div class="main">
		<header class="header">
			<button id="toggleBtn" class="toggle-btn">☰</button>
			<button id="collapseBtn" class="collapse-btn" aria-label="Ocultar menú">◀</button>
			<h1>Registro de Producción</h1>
		</header>

		<section class="content">
			<div class="card">
				<button class="btn" id="btnNuevoRegistro">Nuevo registro</button>
				<h2>Registros creados</h2>
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:10px;flex-wrap:wrap;">
					<input type="text" id="buscadorRegistros" placeholder="Buscar..." style="max-width:220px;padding:6px 10px;border-radius:5px;border:1px solid #ccc;">
					<div id="paginadorRegistros" style="font-size:0.98rem;"></div>
				</div>
				<table class="table">
					<thead>
						<tr>
							<th>Código</th>
							<th>Nombre</th>
							<th>Fecha registro</th>
							<th>Presentación</th>
							<th>Cantidad</th>
							<th># Lote</th>
							<th>Vencimiento</th>
							<th>Responsable</th>
							<th>Observaciones</th>
							<th style="min-width:90px;max-width:120px;">Acción</th>
						</tr>
					</thead>
					<tbody id="tablaRegistros">
						<!-- Aquí se cargarán los registros -->
					</tbody>
				</table>
			</div>
		</section>
	</div>

	<!-- Modal para nuevo registro -->
	<div id="modalRegistro" class="modal" style="display:none;">
		<div class="modal-content card" style="max-width:500px;">
			<h2>Nuevo registro de producción</h2>
			<form id="formRegistro">
				<label>Producto
  <input list="productos" id="producto" name="producto" autocomplete="off" required>
  <datalist id="productos"></datalist>
</label>
				<label>Fecha
					<input type="date" id="fecha" name="fecha" required>
				</label>
				<label>Cantidad
					<input type="number" id="cantidad" name="cantidad" min="1" required>
				</label>
				<div id="presentacionInfo" style="margin-bottom:8px;color:#555;"></div>
				<label>Observaciones
					<textarea id="observaciones" name="observaciones" rows="2" style="width:100%"></textarea>
				</label>
				<div style="margin-top:12px;display:flex;gap:10px;">
					<button type="submit" class="btn">Registrar</button>
					<button type="button" class="btn" id="btnCerrarModal">Cancelar</button>
				</div>
			</form>
		</div>
	</div>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
	<script src="assets/js/app.js"></script>
	<script>
		// --- Lógica JS para cargar productos y manejar modal ---
		let productos = [];

		$(document).ready(function(){
			// Cargar productos vía AJAX y llenar datalist
			function cargarProductos() {
				$.get('registro_produccion.php?ajax=productos', function(data){
					console.log('Productos recibidos:', data);
					productos = data;
					let options = '';
					data.forEach(function(p){
						let texto = `[${p.code}] ${p.name}`;
						options += '<option value="' + texto + '"></option>';
					});
					$('#productos').html(options);
					console.log('Opciones datalist generadas:', options);
					if (data.length === 0) {
						alert('No se recibieron productos desde el servidor.');
					}
				}).fail(function(xhr, status, error){
					alert('Error al cargar productos: ' + error);
					console.error('AJAX error:', status, error);
				});
			}
			cargarProductos();

			// Mostrar info de presentación al seleccionar producto
			$('#producto').on('input', function(){
				let texto = $(this).val();
				let prod = productos.find(p => `[${p.code}] ${p.name}` === texto);
				if (prod) {
					$('#presentacionInfo').text('Presentación: '+prod.presentacion+' | Vida útil: '+prod.vida_util+' meses');
				} else {
					$('#presentacionInfo').text('');
				}
			});

			// Enviar registro
			$('#formRegistro').on('submit', function(e){
				e.preventDefault();
				let texto = $('#producto').val();
				let prod = productos.find(p => `[${p.code}] ${p.name}` === texto);
				console.log('Submit: texto producto:', texto, 'prod:', prod);
				if (!prod) {
					alert('Selecciona un producto válido de la lista');
					return;
				}
				let data = {
					producto: prod.id,
					fecha: $('#fecha').val(),
					cantidad: $('#cantidad').val(),
					observaciones: $('#observaciones').val()
				};
				console.log('Enviando datos:', data);
				$.ajax({
					url: 'registro_produccion.php?ajax=guardar',
					method: 'POST',
					contentType: 'application/json',
					data: JSON.stringify(data),
					success: function(resp){
						console.log('Respuesta AJAX guardar:', resp);
						if(resp.success){
							alert('Registro guardado. Lote: '+resp.lote+'\nVencimiento: '+resp.vencimiento);
							$('#modalRegistro').hide();
							$('#formRegistro')[0].reset();
							$('#producto').val('');
							$('#presentacionInfo').text('');
							cargarRegistros();
						}else{
							alert(resp.message||'Error al guardar');
						}
					},
					error: function(xhr, status, error){
						alert('Error AJAX al guardar: '+error);
						console.error('AJAX guardar error:', status, error, xhr.responseText);
					}
				});
			});


			// --- Paginación y búsqueda en tiempo real ---
			let registrosData = [];
			let paginaActual = 1;
			const registrosPorPagina = 15;
			let filtroBusqueda = '';

			function renderTablaRegistros() {
				let filtrados = registrosData.filter(function(r) {
					if (!filtroBusqueda) return true;
					let texto = (
						(r.code||'') + ' ' +
						(r.name||'') + ' ' +
						(r.lote||'') + ' ' +
						(r.responsable||'') + ' ' +
						(r.observaciones||'')
					).toLowerCase();
					return texto.includes(filtroBusqueda.toLowerCase());
				});
				let totalPaginas = Math.ceil(filtrados.length / registrosPorPagina) || 1;
				if (paginaActual > totalPaginas) paginaActual = totalPaginas;
				let inicio = (paginaActual - 1) * registrosPorPagina;
				let fin = inicio + registrosPorPagina;
				let pagina = filtrados.slice(inicio, fin);
				let html = '';
				pagina.forEach(function(r){
					html += '<tr>' +
						'<td>' + r.code + '</td>' +
						'<td>' + r.name + '</td>' +
						'<td>' + r.fecha + '</td>' +
						'<td>' + r.presentacion + '</td>' +
						'<td>' + r.cantidad + '</td>' +
						'<td>' + r.lote + '</td>' +
						'<td>' + r.vencimiento + '</td>' +
						'<td>' + r.responsable + '</td>' +
						'<td>' + (r.observaciones || '') + '</td>' +
						'<td><button class="btn btn-danger btnEliminar" data-id="' + r.id + '">Eliminar</button></td>' +
					'</tr>';
				});
				$('#tablaRegistros').html(html);
				// Paginador
				let pagHtml = '';
				if (totalPaginas > 1) {
					pagHtml += '<button class="btn" id="pagAnt" ' + (paginaActual==1?'disabled':'') + '>Anterior</button>';
					pagHtml += ' Página ' + paginaActual + ' de ' + totalPaginas + ' ';
					pagHtml += '<button class="btn" id="pagSig" ' + (paginaActual==totalPaginas?'disabled':'') + '>Siguiente</button>';
				} else {
					pagHtml = 'Mostrando '+filtrados.length+' registro(s)';
				}
				$('#paginadorRegistros').html(pagHtml);
			}

			function cargarRegistros(){
				$.get('registro_produccion.php?ajax=listar', function(data){
					registrosData = data;
					paginaActual = 1;
					renderTablaRegistros();
				});
			}
			cargarRegistros();

			// Búsqueda en tiempo real
			$(document).on('input', '#buscadorRegistros', function(){
				filtroBusqueda = $(this).val();
				paginaActual = 1;
				renderTablaRegistros();
			});
			// Paginador
			$(document).on('click', '#pagAnt', function(){
				if (paginaActual > 1) { paginaActual--; renderTablaRegistros(); }
			});
			$(document).on('click', '#pagSig', function(){
				let filtrados = registrosData.filter(function(r) {
					if (!filtroBusqueda) return true;
					let texto = (
						(r.code||'') + ' ' +
						(r.name||'') + ' ' +
						(r.lote||'') + ' ' +
						(r.responsable||'') + ' ' +
						(r.observaciones||'')
					).toLowerCase();
					return texto.includes(filtroBusqueda.toLowerCase());
				});
				let totalPaginas = Math.ceil(filtrados.length / registrosPorPagina) || 1;
				if (paginaActual < totalPaginas) { paginaActual++; renderTablaRegistros(); }
			});

			// Modal abrir/cerrar
			$('#btnNuevoRegistro').on('click', function(){
				$('#modalRegistro').show();
				$('#fecha').val(new Date().toISOString().slice(0,10));
			});
			$('#btnCerrarModal').on('click', function(){
				$('#modalRegistro').hide();
				$('#formRegistro')[0].reset();
				$('#producto').val('');
				$('#presentacionInfo').text('');
			});
		});
	</script>
</body>
</html>
