<?php

require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/PortfolioDAO.php';
require_once __DIR__ . '/../model/dao/AvaliacaoDAO.php';
require_once __DIR__ . '/../model/dao/ServicoDAO.php';

class PortfolioPublicoControl
{
    private TecnicoDAO   $tecnicoDAO;
    private PortfolioDAO $portfolioDAO;
    private AvaliacaoDAO $avaliacaoDAO;
    private ServicoDAO   $servicoDAO;

    public ?object $tecnico    = null;
    public array   $portfolio  = [];
    public array   $avaliacoes = [];
    public array   $servicos   = [];

    public function __construct()
    {
        $this->tecnicoDAO   = new TecnicoDAO();
        $this->portfolioDAO = new PortfolioDAO();
        $this->avaliacaoDAO = new AvaliacaoDAO();
        $this->servicoDAO   = new ServicoDAO();
    }

    public function processar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: catalogo.php'); exit;
        }

        $this->tecnico = $this->tecnicoDAO->buscarPorId($id);
        if (!$this->tecnico || $this->tecnico->statusCadastro !== 'Aprovado' || !$this->tecnico->ativo) {
            header('Location: catalogo.php'); exit;
        }

        $this->portfolio  = $this->portfolioDAO->listarPorTecnico($id);
        $this->avaliacoes = $this->avaliacaoDAO->listarPorTecnico($id);
        $this->servicos   = $this->servicoDAO->listarPorTecnico($id);
    }
}
