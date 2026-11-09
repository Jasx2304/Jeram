<?php
// Pinta los mensajes flash pendientes (éxito/error) y los borra
// al mostrarse, gracias a Mensajes::todas().
use JERAM\Utilidades\Mensajes;
$mensajesFlash = Mensajes::todas();
if (!empty($mensajesFlash)):
?>
<div class="flash-messages">
    <?php foreach ($mensajesFlash as $type => $message): ?>
        <div class="flash-message flash-<?= htmlspecialchars($type) ?>">
            <span><?= htmlspecialchars($message) ?></span>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>