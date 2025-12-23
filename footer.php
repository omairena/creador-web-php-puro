<?php
// footer.php
// Incluye el footer editable en cualquier página
$footer = file_exists('footer.html') ? file_get_contents('footer.html') : '© Mi Empresa';
echo '<footer style="text-align:center;padding:10px 0;background:#f2f2f2;">'. $footer .'</footer>';
