<?php
// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "agencia_db");
if ($conn->connect_error) {
    die("Conexión fallida: " . htmlspecialchars($conn->connect_error));
}

// Obtener detalles del viaje - Use prepared statement to prevent SQL injection
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if ($id === false || $id === 0) {
    $row = null;
    $result = null;
} else {
    $sql = "SELECT * FROM destinos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->num_rows > 0 ? $result->fetch_assoc() : null;
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Viaje - Agencia de Viajes</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="left">Detalles del Viaje</div>
        <div class="right">
        <?php
            session_start();
            if (isset($_SESSION['user'])) {
                echo "Usuario: " . htmlspecialchars($_SESSION['user']);
                echo "<a href='logout.php'>Cerrar sesión</a>";
            } else {
                echo "<a href='login_form.php' style='color: white;'>Iniciar Sesión</a>";
            }
        ?>
        </div>
    </div>
    <div class="nav">
        <a href="../index.php">Inicio</a>
        <a href="catalogo_viajes.php">Catálogo de Viajes</a>
        <a href="detalles_reservas.php">Reservas</a>
        <a href="administracion.php">Administración</a>
        <a href="contacto.php">Soporte y Contacto</a>
    </div>
    <div class="main-content">
        <h1>Detalles del Viaje</h1>
        <?php if ($row): ?>
            <div class='detalle-viaje'>
                <img src='../<?php echo htmlspecialchars($row["foto"], ENT_QUOTES, 'UTF-8'); ?>' alt='<?php echo htmlspecialchars($row["city"], ENT_QUOTES, 'UTF-8'); ?>'>
                <h2><?php echo htmlspecialchars($row["city"], ENT_QUOTES, 'UTF-8') . ", " . htmlspecialchars($row["pais"], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p>Tipo de Destino: <?php echo htmlspecialchars($row["tipo_destino"], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Precio Niño: $<?php echo htmlspecialchars($row["precio_nino"], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Precio Adulto: $<?php echo htmlspecialchars($row["precio_adulto"], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Precio Mayor: $<?php echo htmlspecialchars($row["precio_mayor"], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Detalles: <?php echo isset($row["detalles"]) ? nl2br(htmlspecialchars($row["detalles"], ENT_QUOTES, 'UTF-8')) : "No hay detalles disponibles"; ?></p>
                <form action="procesar_reserva.php" method="post">
                    <input type="hidden" name="id_viaje" value="<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit">Reservar</button>
                </form>
            </div>
        <?php else: ?>
            <p>No se encontraron detalles para este viaje.</p>
        <?php endif; ?>
    </div>
    <div class="footer">
        <p>&copy; 2024 Agencia de Viajes. Todos los derechos reservados.</p>
    </div>
</body>
</html>

