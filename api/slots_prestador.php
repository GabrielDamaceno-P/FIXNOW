<?php
/**
 * Retorna os horários disponíveis de um prestador nos próximos N dias.
 * Replica a lógica de SolicitarChamadoControl::carregarTecnicoESlots():
 *   - gera todos os slots de 08:00–17:00 nos próximos 28 dias
 *   - remove os horários bloqueados pelo prestador (tabela disponibilidade = blocklist)
 *   - remove os horários já reservados por chamados ativos
 *
 * GET ?tecnico_id=X&chamado_id=Y&dias=28
 * Resposta: { "slots_por_data": { "2026-05-20": ["09:00","10:00"], ... } }
 */
session_start();
if (!isset($_SESSION['cliente_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

require_once __DIR__ . '/../model/dao/Conexao.php';

header('Content-Type: application/json');

$tecnicoId = (int)($_GET['tecnico_id'] ?? 0);
$chamadoId = (int)($_GET['chamado_id'] ?? 0);
$dias      = max(1, min(28, (int)($_GET['dias'] ?? 28)));

if ($tecnicoId <= 0) {
    echo json_encode(['slots_por_data' => []]);
    exit;
}

$pdo = Conexao::getConexao();

// Horários bloqueados manualmente pelo prestador (disponibilidade = blocklist)
$stmtBloq = $pdo->prepare("
    SELECT DATE_FORMAT(data, '%Y-%m-%d') AS data,
           TIME_FORMAT(hora, '%H:%i') AS hora
    FROM disponibilidade
    WHERE tecnico_id = ? AND data >= CURDATE() AND data <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
");
$stmtBloq->execute([$tecnicoId, $dias]);
$bloqueados = [];
foreach ($stmtBloq->fetchAll() as $b) {
    $bloqueados[$b['data']][$b['hora']] = true;
}

// Horários já reservados por chamados ativos (ignora o chamado atual)
$stmtChamados = $pdo->prepare("
    SELECT DATE_FORMAT(data_agendamento, '%Y-%m-%d') AS data,
           TIME_FORMAT(data_agendamento, '%H:%i') AS hora
    FROM chamado
    WHERE tecnico_id = ?
      AND id != ?
      AND status IN ('Pendente','Aguardando Orçamento','Em Andamento')
      AND data_agendamento >= NOW()
      AND data_agendamento <= DATE_ADD(NOW(), INTERVAL ? DAY)
");
$stmtChamados->execute([$tecnicoId, $chamadoId, $dias]);
foreach ($stmtChamados->fetchAll() as $c) {
    $bloqueados[$c['data']][$c['hora']] = true;
}

// Gera todos os slots possíveis e remove os bloqueados
$horasPoss = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'];
$minDt     = new DateTime('+1 hour');
$curr      = new DateTime('today');
$limite    = (new DateTime('today'))->modify('+' . $dias . ' days');
$resultado = [];

while ($curr <= $limite) {
    $dataStr = $curr->format('Y-m-d');
    foreach ($horasPoss as $hora) {
        $slotDt = new DateTime($dataStr . ' ' . $hora . ':00');
        if ($slotDt < $minDt) continue;
        if (!isset($bloqueados[$dataStr][$hora])) {
            $resultado[$dataStr][] = $hora;
        }
    }
    $curr->modify('+1 day');
}

if (empty($resultado)) {
    echo json_encode(['slots_por_data' => [], 'aviso' => 'Nenhum horário disponível nos próximos ' . $dias . ' dias.']);
    exit;
}

echo json_encode(['slots_por_data' => $resultado]);
