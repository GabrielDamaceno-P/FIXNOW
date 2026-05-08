<?php

require_once __DIR__ . '/../model/dao/Conexao.php';

class FinanceiroPrestadorControl
{
    private PDO $pdo;

    const TAXA = 0.20;

    public int    $tecnicoId      = 0;
    public int    $naoLidas       = 0;
    public float  $brutoRecebido  = 0.0;
    public float  $brutoPendente  = 0.0;
    public float  $totalEstornado = 0.0;
    public float  $liquidoRecebido= 0.0;
    public float  $liquidoPendente= 0.0;
    public float  $taxaTotal      = 0.0;
    public int    $totalServicos  = 0;
    public array  $mensal         = [];
    public array  $detalhes       = [];
    public string $filtroMes      = '';
    public int    $filtroAno      = 0;

    public function __construct()
    {
        $this->pdo = Conexao::getConexao();
    }

    public function verificarSessao(): void
    {
        if (!isset($_SESSION['tecnico_id'])) {
            header('Location: ../login.php'); exit;
        }
        $this->tecnicoId = (int)$_SESSION['tecnico_id'];
    }

    private function carregarNaoLidas(): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notificacao WHERE tecnico_id = ? AND tipo_destinatario = 'prestador' AND lida = 0"
        );
        $stmt->execute([$this->tecnicoId]);
        $this->naoLidas = (int)$stmt->fetchColumn();
    }

    public function processar(): void
    {
        $this->verificarSessao();
        $this->filtroMes = $_GET['mes'] ?? '';
        $this->filtroAno = (int)($_GET['ano'] ?? date('Y'));

        $this->carregarResumo();
        $this->carregarMensal();
        $this->carregarDetalhes();
        $this->carregarNaoLidas();
    }

    private function carregarResumo(): void
    {
        $stmt = $this->pdo->prepare("
            SELECT
              COUNT(DISTINCT c.id) AS total_servicos,
              COALESCE(SUM(CASE WHEN p.status = 'Pago'      THEN p.valor ELSE 0 END), 0) AS bruto_recebido,
              COALESCE(SUM(CASE WHEN p.status = 'Pendente'  THEN p.valor ELSE 0 END), 0) AS bruto_pendente,
              COALESCE(SUM(CASE WHEN p.status = 'Estornado' THEN p.valor ELSE 0 END), 0) AS total_estornado
            FROM chamado c
            LEFT JOIN pagamento p ON p.chamado_id = c.id
            WHERE c.tecnico_id = ? AND c.status IN ('Concluído','Negado')
        ");
        $stmt->execute([$this->tecnicoId]);
        $resumo = $stmt->fetch();

        $this->totalServicos   = (int)($resumo['total_servicos']  ?? 0);
        $this->brutoRecebido   = (float)($resumo['bruto_recebido']  ?? 0);
        $this->brutoPendente   = (float)($resumo['bruto_pendente']  ?? 0);
        $this->totalEstornado  = (float)($resumo['total_estornado'] ?? 0);
        $this->liquidoRecebido = $this->brutoRecebido  * (1 - self::TAXA);
        $this->liquidoPendente = $this->brutoPendente  * (1 - self::TAXA);
        $this->taxaTotal       = $this->brutoRecebido  * self::TAXA;
    }

    private function carregarMensal(): void
    {
        $stmt = $this->pdo->prepare("
            SELECT
              DATE_FORMAT(p.pago_em, '%Y-%m')  AS mes_ano,
              DATE_FORMAT(p.pago_em, '%m/%Y')  AS mes_label,
              COUNT(*) AS qtd,
              SUM(p.valor) AS bruto
            FROM pagamento p
            INNER JOIN chamado c ON c.id = p.chamado_id
            WHERE c.tecnico_id = ? AND p.status = 'Pago'
            GROUP BY mes_ano, mes_label
            ORDER BY mes_ano DESC
            LIMIT 12
        ");
        $stmt->execute([$this->tecnicoId]);
        $this->mensal = $stmt->fetchAll();
    }

    private function carregarDetalhes(): void
    {
        $where  = "AND YEAR(COALESCE(p.pago_em, c.atualizado_em)) = ?";
        $params = [$this->tecnicoId, $this->filtroAno];

        if ($this->filtroMes !== '') {
            $where  = "AND MONTH(COALESCE(p.pago_em, c.atualizado_em)) = ? AND YEAR(COALESCE(p.pago_em, c.atualizado_em)) = ?";
            $params = [$this->tecnicoId, (int)$this->filtroMes, $this->filtroAno];
        }

        $stmt = $this->pdo->prepare("
            SELECT c.id AS chamado_id, c.descricao, c.categoria, c.preco_sugerido,
                   cl.nome AS cliente_nome,
                   p.id AS pag_id, p.valor AS pag_valor, p.status AS pag_status,
                   p.metodo, p.pago_em,
                   c.atualizado_em AS concluido_em
            FROM chamado c
            INNER JOIN cliente cl ON cl.id = c.cliente_id
            LEFT JOIN pagamento p ON p.chamado_id = c.id
            WHERE c.tecnico_id = ? AND c.status = 'Concluído'
            {$where}
            ORDER BY c.atualizado_em DESC
        ");
        $stmt->execute($params);
        $this->detalhes = $stmt->fetchAll();
    }
}
