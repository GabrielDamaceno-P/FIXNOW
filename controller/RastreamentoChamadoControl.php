<?php

require_once __DIR__ . '/../model/dao/ChamadoDAO.php';

class RastreamentoChamadoControl
{
    private ChamadoDAO $chamadoDAO;

    public int    $clienteId   = 0;
    public ?array $chamado     = null;
    public string $tecnicoNome = 'Prestador';
    public string $clienteNome = 'Cliente';

    public function __construct()
    {
        $this->chamadoDAO = new ChamadoDAO();
    }

    public function processar(): void
    {
        if (!isset($_SESSION['cliente_id'])) {
            header('Location: login.php'); exit;
        }
        $this->clienteId = (int)$_SESSION['cliente_id'];
        $chamadoId = (int)($_GET['chamado'] ?? 0);

        if ($chamadoId > 0) {
            $this->chamado = $this->chamadoDAO->buscarParaRastreamento($chamadoId, $this->clienteId);
        } else {
            $this->chamado = $this->chamadoDAO->buscarUltimoPorClienteParaRastreamento($this->clienteId);
        }

        $this->tecnicoNome = $this->chamado['tecnico_nome'] ?? 'Prestador';
        $this->clienteNome = $this->chamado['cliente_nome'] ?? 'Cliente';
    }
}
