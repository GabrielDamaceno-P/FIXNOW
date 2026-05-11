<?php
session_start();
require_once __DIR__ . '/../model/dao/Conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$pdo       = Conexao::getConexao();
$chamadoId = (int)($_REQUEST['chamado'] ?? 0);

if ($chamadoId <= 0) {
    echo json_encode(['ok' => false, 'erro' => 'chamado inválido']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['tecnico_id'])) {
        echo json_encode(['ok' => false, 'erro' => 'não autenticado']);
        exit;
    }
    $tecnicoId = (int)$_SESSION['tecnico_id'];

    
    $chk = $pdo->prepare("SELECT id FROM chamado WHERE id=? AND tecnico_id=? AND status='Em Andamento'");
    $chk->execute([$chamadoId, $tecnicoId]);
    if (!$chk->fetch()) {
        echo json_encode(['ok' => false, 'erro' => 'acesso negado']);
        exit;
    }

    $acao = $_POST['acao'] ?? 'posicao';

    if ($acao === 'parar') {
        $pdo->prepare("UPDATE chamado SET em_deslocamento=0 WHERE id=?")
            ->execute([$chamadoId]);
    } else {
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        if ($lat === 0.0 && $lng === 0.0) {
            echo json_encode(['ok' => false, 'erro' => 'coordenadas inválidas']);
            exit;
        }
        $pdo->prepare("
            UPDATE chamado
            SET em_deslocamento=1,
                deslocamento_inicio = COALESCE(deslocamento_inicio, NOW()),
                tecnico_lat=?,
                tecnico_lng=?
            WHERE id=?
        ")->execute([$lat, $lng, $chamadoId]);
    }

    echo json_encode(['ok' => true]);
    exit;
}


$stmt = $pdo->prepare("SELECT em_deslocamento, tecnico_lat, tecnico_lng, status, cliente_id FROM chamado WHERE id=?");
$stmt->execute([$chamadoId]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['ok' => false, 'erro' => 'chamado não encontrado']);
    exit;
}


$clienteOk = isset($_SESSION['cliente_id']) && (int)$_SESSION['cliente_id'] === (int)$row['cliente_id'];
$tecnicoOk = isset($_SESSION['tecnico_id']);
if (!$clienteOk && !$tecnicoOk) {
    echo json_encode(['ok' => false, 'erro' => 'não autenticado']);
    exit;
}

echo json_encode([
    'ok'        => true,
    'a_caminho' => (bool)$row['em_deslocamento'],
    'lat'       => $row['tecnico_lat'] !== null ? (float)$row['tecnico_lat'] : null,
    'lng'       => $row['tecnico_lng'] !== null ? (float)$row['tecnico_lng'] : null,
    'status'    => $row['status'],
]);
