<?php
// Manejo centralizado de logout para todas las páginas
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: index.php');
    exit;
}
// auth.php - funciones de autenticación (usa $mysqli de db.php)
require_once __DIR__ . '/db.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer si está instalado
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function register_user($name, $email, $password) {
    global $mysqli;
    $name = trim($name);
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email inválido'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
    }

    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'El email ya está registrado'];
    }
    $stmt->close();

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $name, $email, $password_hash);
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true];
    } else {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al registrar: ' . $err];
    }
}

function login_user($email, $password) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($id, $name, $password_hash);
    if ($stmt->fetch()) {
        $stmt->close();
        if (password_verify($password, $password_hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Contraseña incorrecta'];
        }
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'No existe una cuenta con ese email'];
    }
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/* ---------------------------
   Forgot password helpers
   --------------------------- */

function record_reset_request($email, $ip) {
    global $mysqli;
    $stmt = $mysqli->prepare("INSERT INTO password_reset_requests (email, ip) VALUES (?, ?)");
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $stmt->close();
}

function is_allowed_reset_request($email, $ip, $max_per_hour = 5) {
    global $mysqli;
    $one_hour_ago = date('Y-m-d H:i:s', time() - 3600);
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM password_reset_requests WHERE (ip = ? OR email = ?) AND created_at >= ?");
    $stmt->bind_param('sss', $ip, $email, $one_hour_ago);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return ($count < $max_per_hour);
}

function create_password_reset($email) {
    global $mysqli;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!is_allowed_reset_request($email, $ip)) {
        record_reset_request($email, $ip);
        return ['success' => true, 'message' => 'Si el email existe, recibirás instrucciones para resetear la contraseña.'];
    }

    record_reset_request($email, $ip);

    $stmt = $mysqli->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $user_name);
    if (!$stmt->fetch()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Si el email existe, recibirás instrucciones para resetear la contraseña.'];
    }
    $stmt->close();

    $token = bin2hex(random_bytes(24));
    $token_hash = hash('sha256', $token);
    $expires_at = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $user_id, $token_hash, $expires_at);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al crear token: ' . $err];
    }
    $stmt->close();

    send_reset_email($email, $user_name, $token);

    return ['success' => true, 'message' => 'Si el email existe, recibirás instrucciones para resetear la contraseña.'];
}

function verify_password_reset_token($token) {
    global $mysqli;
    $token_hash = hash('sha256', $token);
    $now = date('Y-m-d H:i:s');

    $stmt = $mysqli->prepare("SELECT user_id, expires_at FROM password_resets WHERE token_hash = ?");
    $stmt->bind_param('s', $token_hash);
    $stmt->execute();
    $stmt->bind_result($user_id, $expires_at);
    if ($stmt->fetch()) {
        $stmt->close();
        if ($expires_at >= $now) {
            return ['user_id' => $user_id];
        }
    } else {
        $stmt->close();
    }
    return false;
}

function reset_password_with_token($token, $new_password) {
    global $mysqli;
    if (strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
    }

    $verify = verify_password_reset_token($token);
    if (!$verify) {
        return ['success' => false, 'message' => 'Token inválido o expirado'];
    }
    $user_id = $verify['user_id'];

    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $password_hash, $user_id);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al actualizar contraseña: ' . $err];
    }
    $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    return ['success' => true];
}

/* ---------------------------
   Funciones para perfil/usuarios
   --------------------------- */

/**
 * Obtener datos públicos del usuario por ID
 */
function get_user_by_id($id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT id, name, email, created_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($uid, $name, $email, $created_at);
    if ($stmt->fetch()) {
        $stmt->close();
        return [
            'id' => $uid,
            'name' => $name,
            'email' => $email,
            'created_at' => $created_at
        ];
    }
    $stmt->close();
    return null;
}

/**
 * Cambiar la contraseña del usuario autenticado (requiere la contraseña actual)
 */
function change_user_password($user_id, $current_password, $new_password) {
    global $mysqli;
    if (strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'];
    }

    // Obtener hash actual
    $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($password_hash);
    if (!$stmt->fetch()) {
        $stmt->close();
        return ['success' => false, 'message' => 'Usuario no encontrado'];
    }
    $stmt->close();

    if (!password_verify($current_password, $password_hash)) {
        return ['success' => false, 'message' => 'La contraseña actual es incorrecta'];
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $new_hash, $user_id);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al actualizar contraseña: ' . $err];
    }
    $stmt->close();

    return ['success' => true];
}

/**
 * Guardar cambios simples del perfil (por ejemplo nombre) - opcional
 */
function update_user_name($user_id, $new_name) {
    global $mysqli;
    $new_name = trim($new_name);
    if ($new_name === '') return ['success' => false, 'message' => 'El nombre no puede estar vacío'];
    $stmt = $mysqli->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->bind_param('si', $new_name, $user_id);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error al actualizar nombre: ' . $err];
    }
    $stmt->close();
    // actualizar sesión
    $_SESSION['user_name'] = $new_name;
    return ['success' => true];
}

function send_reset_email($email, $name, $token) {
    // Preferir SMTP vía PHPMailer si se configura
    $BASE_URL = rtrim(getenv('APP_BASE_URL') ?: 'http://localhost', '/');
    $link = $BASE_URL . '/password_reset.php?token=' . urlencode($token);

    $subject = 'Restablecer contraseña';
    $body = "Hola " . $name . ",\n\n";
    $body .= "Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace:\n\n";
    $body .= $link . "\n\nEl enlace expira en 1 hora. Si no solicitaste este cambio, puedes ignorar este mensaje.\n\nSaludos,\nMi App";

    // Leer configuración SMTP desde variables de entorno
    $smtp_host = getenv('SMTP_HOST');
    $smtp_port = getenv('SMTP_PORT') ?: 587;
    $smtp_user = getenv('SMTP_USER');
    $smtp_pass = getenv('SMTP_PASS');
    $from = getenv('MAIL_FROM') ?: 'no-reply@example.com';
    $from_name = getenv('MAIL_FROM_NAME') ?: 'Mi App';

    if ($smtp_host && class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        // Usar PHPMailer
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->SMTPSecure = getenv('SMTP_SECURE') ?: 'tls';
            $mail->Port = $smtp_port;

            $mail->setFrom($from, $from_name);
            $mail->addAddress($email, $name);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            return $mail->send();
        } catch (Exception $e) {
            // en fallo, continua al fallback
        }
    }

    // Fallback a mail()
    $headers = 'From: ' . $from . "\r\n" . 'Reply-To: ' . $from . "\r\n" . 'X-Mailer: PHP/' . phpversion();
    return mail($email, $subject, $body, $headers);
}
?>