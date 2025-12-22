<?php
// products_model.php - funciones para manejar productos y presentaciones
require_once __DIR__ . '/db.php';

/* Presentaciones */
function get_presentations() {
    global $mysqli;
    $res = [];
    $stmt = $mysqli->prepare("SELECT id, name, created_at FROM presentations ORDER BY name");
    if ($stmt === false) {
        error_log('DB prepare error (get_presentations): ' . $mysqli->error);
        return $res;
    }
    $stmt->execute();
    $stmt->bind_result($id, $name, $created_at);
    while ($stmt->fetch()) {
        $res[] = ['id' => $id, 'name' => $name, 'created_at' => $created_at];
    }
    $stmt->close();
    return $res;
}

function get_presentation_by_id($id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT id, name, created_at FROM presentations WHERE id = ?");
    if ($stmt === false) {
        error_log('DB prepare error (get_presentation_by_id): ' . $mysqli->error);
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($pid, $name, $created_at);
    if ($stmt->fetch()) {
        $stmt->close();
        return ['id' => $pid, 'name' => $name, 'created_at' => $created_at];
    }
    $stmt->close();
    return null;
}

function create_presentation($name) {
    global $mysqli;
    $name = trim($name);
    if ($name === '') return ['success' => false, 'message' => 'El nombre no puede estar vacío'];
    $stmt = $mysqli->prepare("INSERT INTO presentations (name) VALUES (?)");
    if ($stmt === false) {
        error_log('DB prepare error (create_presentation): ' . $mysqli->error);
        return ['success' => false, 'message' => 'Error interno al crear presentación. Asegúrate de que la base de datos y las tablas existen.'];
    }
    $stmt->bind_param('s', $name);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'id' => $id];
    } else {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al crear presentación: ' . $err];
    }
}

function update_presentation($id, $name) {
    global $mysqli;
    $name = trim($name);
    if ($name === '') return ['success' => false, 'message' => 'El nombre no puede estar vacío'];
    $stmt = $mysqli->prepare("UPDATE presentations SET name = ? WHERE id = ?");
    $stmt->bind_param('si', $name, $id);
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true];
    } else {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al actualizar: ' . $err];
    }
}

/* Productos */
function get_products() {
    global $mysqli;
    $res = [];
    $stmt = $mysqli->prepare("SELECT p.id, p.code, p.name, p.presentation_id, pr.name AS presentation_name, p.vida_util, p.created_at FROM products p LEFT JOIN presentations pr ON p.presentation_id = pr.id ORDER BY p.id DESC");
    if ($stmt === false) {
        error_log('DB prepare error (get_products): ' . $mysqli->error);
        return $res;
    }
    $stmt->execute();
    $stmt->bind_result($id, $code, $name, $presentation_id, $presentation_name, $vida_util, $created_at);
    while ($stmt->fetch()) {
        $res[] = [
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'presentation_id' => $presentation_id,
            'presentation_name' => $presentation_name,
            'vida_util' => $vida_util,
            'created_at' => $created_at
        ];
    }
    $stmt->close();
    return $res;
}

function get_product_by_id($id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT id, code, name, presentation_id, vida_util, created_at FROM products WHERE id = ?");
    if ($stmt === false) {
        error_log('DB prepare error (get_product_by_id): ' . $mysqli->error);
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($pid, $code, $name, $presentation_id, $vida_util, $created_at);
    if ($stmt->fetch()) {
        $stmt->close();
        return [
            'id' => $pid,
            'code' => $code,
            'name' => $name,
            'presentation_id' => $presentation_id,
            'vida_util' => $vida_util,
            'created_at' => $created_at
        ];
    }
    $stmt->close();
    return null;
}

function create_product($name, $presentation_id = null, $vida_util = null) {
    global $mysqli;
    $name = trim($name);
    if ($name === '') return ['success' => false, 'message' => 'El nombre no puede estar vacío'];

    if ($presentation_id !== null) {
        // comprobar que la presentación exista
        $p = get_presentation_by_id($presentation_id);
        if (!$p) return ['success' => false, 'message' => 'Presentación inválida'];
    } else {
        $presentation_id = null;
    }

    $vida_util = ($vida_util !== '' && $vida_util !== null) ? intval($vida_util) : null;
    if ($vida_util !== null && $vida_util < 0) {
        return ['success' => false, 'message' => 'Vida útil inválida'];
    }

    $stmt = $mysqli->prepare("INSERT INTO products (code, name, presentation_id, vida_util) VALUES (NULL, ?, ?, ?)");
    if ($stmt === false) {
        error_log('DB prepare error (create_product): ' . $mysqli->error);
        return ['success' => false, 'message' => 'Error interno al crear producto'];
    }
    $stmt->bind_param('sii', $name, $presentation_id, $vida_util);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al crear producto: ' . $err];
    }
    $insert_id = $stmt->insert_id;
    $stmt->close();

    // Generar código automático: PR + id padded
    $code = 'PR' . str_pad($insert_id, 6, '0', STR_PAD_LEFT);
    $stmt = $mysqli->prepare("UPDATE products SET code = ? WHERE id = ?");
    $stmt->bind_param('si', $code, $insert_id);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al asignar código: ' . $err];
    }
    $stmt->close();

    return ['success' => true, 'id' => $insert_id, 'code' => $code];
}

function update_product($id, $name, $presentation_id = null, $vida_util = null) {
    global $mysqli;
    $name = trim($name);
    if ($name === '') return ['success' => false, 'message' => 'El nombre no puede estar vacío'];

    if ($presentation_id !== null) {
        $p = get_presentation_by_id($presentation_id);
        if (!$p) return ['success' => false, 'message' => 'Presentación inválida'];
    } else {
        $presentation_id = null;
    }

    $vida_util = ($vida_util !== '' && $vida_util !== null) ? intval($vida_util) : null;
    if ($vida_util !== null && $vida_util < 0) {
        return ['success' => false, 'message' => 'Vida útil inválida'];
    }

    $stmt = $mysqli->prepare("UPDATE products SET name = ?, presentation_id = ?, vida_util = ? WHERE id = ?");
    $stmt->bind_param('siii', $name, $presentation_id, $vida_util, $id);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al actualizar producto: ' . $err];
    }
    $stmt->close();
    return ['success' => true];
}

?>