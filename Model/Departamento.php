<?php

class Departamento
{
    private $idDepartamento;
    private $nome;

    public function __construct($id = null, $nome = null)
    {
        $this->idDepartamento = $id;
        $this->nome = $nome;
    }

    public function getIdDepartamento()
    {
        return $this->idDepartamento;
    }

    public function getNome()
    {
        return $this->nome;
    }
}
