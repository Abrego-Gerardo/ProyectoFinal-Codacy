<?php
declare(strict_types=1);
session_start();

/* --------------------------------------------------
   Redirect if already logged in
-------------------------------------------------- */
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

/* --------------------------------------------------
   Initialize
-------------------------------------------------- */
$error_message = '';

/* --------------------------------------------------
   CSRF Token
-------------------------------------------------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* --------------------------------------------------
   Handle Login
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---- CSRF validation ---- */
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error_message = "Error de seguridad. Intenta nuevamente.";
    } else {

        /* ---- Input validation ---- */
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || $password === '') {
            $error_message = "Correo o contraseña inválidos.";
        } else {

            /* ---- Database connection ---- */
            $db_host = 'localhost';
            $db_user = 'root';
            $db_pass = '';
            $db_name = 'agencia_db';

            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

            if ($conn->connect_error) {
                $error_message = "Error interno. Intenta más tarde.";
            } else {

                /* ---- Prepared statement ---- */
                $sql = "SELECT id, username, password, usertype 
                        FROM users 
                        WHERE email = ? 
                        LIMIT 1";

                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();

                    if ($user && password_verify($password, $user['password'])) {

                        /* ---- Secure session handling ---- */
                        session_regenerate_id(true);

                        $_SESSION['user_id']  = (int)$user['id'];
                        $_SESSION['user']     = $user['username']; // NO escaping here
                        $_SESSION['usertype'] = $user['usertype'];

                        unset($_SESSION['csrf_token']); // rotate token

                        /* ---- Redirect ---- */
                        if ($user['usertype'] === 'admin') {
                            header("Location: administracion.php");
                        } else {
                            header("Location: ../index.php");
                        }
                        exit;

                    } else {
                        $error_message = "Correo o contraseña incorrectos.";
                    }

                    $stmt->close();
                } else {
                    $error_message = "Error interno. Intenta más tarde.";
                }

                $conn->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - Gestión de Usuarios</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>

<div class="login-container">
    <h1>Gestión de Usuarios</h1>

    <?php if ($error_message): ?>
        <div class="error-message">
            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login_form.php">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <input type="email" name="email" placeholder="Correo electrónico" required>
        </div>

        <div class="form-group">
            <input type="password" name="password" placeholder="Contraseña" required>
        </div>

        <div class="form-group">
            <button type="submit">Iniciar Sesión</button>
        </div>
    </form>

    <a href="register_form.php">Registrarse</a>
    <a href="recover_password_form.php">¿Olvidaste tu contraseña?</a>
    <a href="../index.php">Volver a Inicio</a>
</div>

</body>
</html>
