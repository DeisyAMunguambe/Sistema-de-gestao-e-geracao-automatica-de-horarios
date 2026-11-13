<?php
require_once '../../bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
$utilizador = user();
$idUtilizador = (int)($utilizador['id'] ?? $utilizador['id_utilizador'] ?? 0);
$naoLidas = 0;
if (is_formador()) {
    $formador = one('SELECT id_formador FROM formador WHERE email=? LIMIT 1', 's', (string)($utilizador['email'] ?? ''));
    if ($formador) {
        $total = one('SELECT COUNT(*) AS total FROM chat_mensagem m JOIN chat_conversa c ON c.id_conversa=m.id_conversa WHERE c.id_formador=? AND m.id_remetente<>? AND m.lida_em IS NULL', 'ii', (int)$formador['id_formador'], $idUtilizador);
        $naoLidas = (int)($total['total'] ?? 0);
    }
} elseif (can_manage()) {
    $total = one('SELECT COUNT(*) AS total FROM chat_mensagem m JOIN chat_conversa c ON c.id_conversa=m.id_conversa WHERE c.id_administrador=? AND m.id_remetente<>? AND m.lida_em IS NULL', 'ii', $idUtilizador, $idUtilizador);
    $naoLidas = (int)($total['total'] ?? 0);
}
echo json_encode(['nao_lidas' => $naoLidas], JSON_UNESCAPED_UNICODE);
