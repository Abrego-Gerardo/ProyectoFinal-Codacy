<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$error_message = '';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Error de seguridad: Token inválido";
    } else {
        // Validate and sanitize input
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if ($email === false || empty($password)) {
            $error_message = "Por favor proporciona un correo y contraseña válidos";
        } else {
            // Get database connection
            $db_host = 'localhost';
            $db_user = 'root';
            $db_pass = '';
            $db_name = 'agencia_db';

            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            
            if ($conn->connect_error) {
                $error_message = "Error de conexión a la base de datos";
            } else {
                // Use prepared statement to prevent SQL injection
                $sql = "SELECT id, username, password, usertype FROM users WHERE email = ?";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    $error_message = "Error en la consulta de base de datos";
                } else {
                    $stmt->bind_param("s", $email);
                    if (!$stmt->execute()) {
                        $error_message = "Error al ejecutar la consulta";
                    } else {
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();
                        
                        if ($user && password_verify($password, $user["password"])) {
                            // Login successful - set session variables
                            $_SESSION["user"] = htmlspecialchars($user["username"], ENT_QUOTES, 'UTF-8');
                            $_SESSION["usertype"] = htmlspecialchars($user["usertype"], ENT_QUOTES, 'UTF-8');
                            $_SESSION["user_id"] = $user["id"];
                            
                            // Redirect based on user type
                            if ($user["usertype"] === "admin") {
                                header("Location: administracion.php");
                            } else {
                                header("Location: ../index.php");
                            }
                            exit();
                        } else {
                            $error_message = "El correo o contraseña fue incorrecto";
                        }
                    }
                    $stmt->close();
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
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message" style="color: #d32f2f; padding: 10px; margin-bottom: 15px; border: 1px solid #d32f2f; border-radius: 4px;">
                <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <form action="login_form.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            
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
        <a href="recover_password_form.php">¿No recuerdas tu contraseña? Recuperar Contraseña</a>
        <a href="../index.php">Volver a Inicio</a>
    </div>
</body>
</html>
