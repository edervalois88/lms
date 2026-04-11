<?php
echo "<h1>🚀 ¡EL MOTOR PHP ESTÁ VIVO!</h1>";
echo "<p>Si ves esto, el servidor (Nginx + PHP 8.4) está funcionando correctamente.</p>";
echo "<hr>";
echo "<strong>Información técnica:</strong><br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Protocolo: " . ($_SERVER['HTTPS'] ? 'HTTPS' : 'HTTP') . "<br>";
?>
