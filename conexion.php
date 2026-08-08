<?php
$server   = "localhost\\SQLEXPRESS";
$database = "ControlActivosColegio";

try {
    // Usamos el driver nativo ODBC de Windows con la extensión pdo_odbc que acabamos de habilitar
$conn = new PDO("odbc:Driver={ODBC Driver 17 for SQL Server};Server=$server;Database=$database;Trusted_Connection=Yes;");    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div style='color: red; font-family: sans-serif; padding: 15px; background: #fee2e2; border-radius: 8px;'>";
    echo "<h3 style='margin-top:0;'>⚠️ Error al conectar con SQL Server:</h3>";
    echo $e->getMessage();
    echo "</div>";
    exit;
}
//git add .
//git commit -m "Aquí pones una breve descripción de lo que cambiaste o agregaste"
//git push
?>
