<?php

require_once __DIR__ . '/../model/dao/ServicoDAO.php';
require_once __DIR__ . '/../model/dao/CategoriaDAO.php';

class CatalogoControl
{
    private ServicoDAO   $servicoDAO;
    private CategoriaDAO $categoriaDAO;

    public array  $prestadores      = [];
    public array  $categorias       = [];
    public string $filtroCategoria  = '';

    public function __construct()
    {
        $this->servicoDAO   = new ServicoDAO();
        $this->categoriaDAO = new CategoriaDAO();
    }

    public function processar(): void
    {
        $this->filtroCategoria = trim($_GET['categoria'] ?? '');
        $this->categorias      = $this->categoriaDAO->listarAtivas();
        $this->prestadores     = $this->servicoDAO->listarPrestadoresCatalogo(
            $this->filtroCategoria ?: null
        );
    }
}
