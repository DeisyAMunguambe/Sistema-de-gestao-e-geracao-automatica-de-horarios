<?php

/**
 * Devolve a ligação MySQL (singleton por pedido).
 */
function db(): mysqli
{
    static $con;

    if ($con instanceof mysqli) {
        return $con;
    }

    $con = new mysqli('localhost', 'root', '', 'gestao_horarios', "3306");

    if ($con->connect_errno) {
        die('Erro de conexão com a base de dados: ' . htmlspecialchars($con->connect_error));
    }

    $con->set_charset('utf8mb4');

    return $con;
}

$con = db();
