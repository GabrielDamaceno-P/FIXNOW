<?php

require_once __DIR__ . '/../model/dao/TecnicoDAO.php';
require_once __DIR__ . '/../model/dao/ServicoDAO.php';
require_once __DIR__ . '/../model/dao/CategoriaDAO.php';
require_once __DIR__ . '/../model/dao/Conexao.php';

class CatalogoControl
{
    private TecnicoDAO   $tecnicoDAO;
    private CategoriaDAO $categoriaDAO;
    private PDO          $pdo;

    public array  $prestadores = [];
    public array  $categorias  = [];
    public string $filtroCategoria = '';

    public function __construct()
    {
        $this->tecnicoDAO   = new TecnicoDAO();
        $this->categoriaDAO = new CategoriaDAO();
        $this->pdo          = Conexao::getConexao();
    }

    public function processar(): void
    {
        $this->filtroCategoria = trim($_GET['categoria'] ?? '');
        $this->categorias      = $this->categoriaDAO->listarAtivas();

        $this->carregarPrestadores();
    }

    private function carregarPrestadores(): void
    {
        $sql = "
            SELECT t.id, t.nome, t.especialidade, t.foto_perfil, t.avaliacao_media, t.destaque,
                   s.nome AS servico_nome, s.descricao AS servico_desc,
                   cat.nome AS categoria_nome
            FROM tecnico t
            INNER JOIN servico s ON s.tecnico_id = t.id AND s.ativo = 1
            INNER JOIN categoria cat ON cat.id = s.categoria_id
            WHERE t.ativo = 1 AND t.status_cadastro = 'Aprovado'
        ";
        $params = [];
        if ($this->filtroCategoria) {
            $sql .= " AND cat.nome = ?";
            $params[] = $this->filtroCategoria;
        }
        $sql .= " ORDER BY t.destaque DESC, t.avaliacao_media DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $agrupado = [];
        foreach ($rows as $row) {
            $tid = $row['id'];
            if (!isset($agrupado[$tid])) {
                $agrupado[$tid] = [
                    'id'             => $tid,
                    'nome'           => $row['nome'],
                    'especialidade'  => $row['especialidade'],
                    'foto_perfil'    => $row['foto_perfil'],
                    'avaliacao_media'=> $row['avaliacao_media'],
                    'destaque'       => $row['destaque'],
                    'servicos'       => [],
                ];
            }
            $agrupado[$tid]['servicos'][] = [
                'nome'          => $row['servico_nome'],
                'descricao'     => $row['servico_desc'],
                'categoria_nome'=> $row['categoria_nome'],
            ];
        }
        $this->prestadores = array_values($agrupado);
    }
}
