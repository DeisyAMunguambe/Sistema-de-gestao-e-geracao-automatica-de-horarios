<?php

class curso
{
    private $idCurso;
    private $nome;
    private $idDepartamento;

    public function __construct($id = null, $nome = null, $dep = null)
    {
        $this->idCurso = $id;
        $this->nome = $nome;
        $this->idDepartamento = $dep;
    }
}
