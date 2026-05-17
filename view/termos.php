<?php
session_start();
$titulo  = ($_GET['tipo'] ?? '') === 'privacidade' ? 'Política de Privacidade e LGPD' : 'Termos de Uso';
$isEmbed = !empty($_GET['embed']);
$tema    = ($isEmbed && ($_GET['theme'] ?? '') === 'dark') ? 'dark' : '';

$_urlInicio = '../index.php';
if (isset($_SESSION['cliente_id']))      $_urlInicio = 'dashboardCliente.php';
elseif (isset($_SESSION['tecnico_id'])) $_urlInicio = 'prestador/dashboardPrestador.php';
elseif (isset($_SESSION['admin_id']))   $_urlInicio = 'admin/painelAdmin.php';
?>
<!DOCTYPE html>
<html lang="pt-BR"<?php echo $tema ? ' data-theme="dark"' : ''; ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $titulo; ?> - Fix Now</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .doc-section { margin-bottom: 2rem; }
    .doc-section h4 { color: var(--bs-primary); border-bottom: 2px solid var(--bs-primary); padding-bottom: .4rem; margin-bottom: 1rem; }
    .highlight-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem 1.25rem; border-radius: 0 .5rem .5rem 0; margin: 1rem 0; color: #3d3000; }
    [data-theme="dark"] .highlight-box { background: #2e2600; border-left-color: #ffc107; color: #ffe083; }
  </style>
</head>
<body>
<?php if (!$isEmbed): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $_urlInicio ?>">Fix Now</a>
  </div>
</nav>
<?php endif; ?>

<main class="<?php echo $isEmbed ? 'container-fluid py-3 px-4' : 'container py-5 mt-5'; ?>" style="max-width:860px;">

  <!-- Seletor de documento -->
  <?php
    $qEmbed = $isEmbed ? '&embed=1' : '';
    $qTheme = $tema    ? '&theme=dark' : '';
  ?>
  <ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
      <a class="nav-link <?php echo ($_GET['tipo'] ?? '') !== 'privacidade' ? 'active' : ''; ?>" href="?tipo=termos<?= $qEmbed . $qTheme ?>">Termos de Uso</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo ($_GET['tipo'] ?? '') === 'privacidade' ? 'active' : ''; ?>" href="?tipo=privacidade<?= $qEmbed . $qTheme ?>">Política de Privacidade e LGPD</a>
    </li>
  </ul>

  <?php if (($_GET['tipo'] ?? '') === 'privacidade'): ?>

  <!-- ═══════════════ POLÍTICA DE PRIVACIDADE / LGPD ═══════════════ -->
  <h1 class="h3 mb-1">Política de Privacidade e Proteção de Dados</h1>
  <p class="text-muted small mb-4">Última atualização: <?php echo date('d/m/Y'); ?> &nbsp;·&nbsp; Em conformidade com a <strong>Lei nº 13.709/2018 (LGPD)</strong></p>

  <div class="highlight-box mb-4">
    <strong>Resumo:</strong> A Fix Now coleta apenas os dados necessários para prestar o serviço, não vende suas informações a terceiros e garante seus direitos como titular de dados conforme a LGPD.
  </div>

  <div class="doc-section">
    <h4>1. Controlador dos Dados</h4>
    <p>A plataforma <strong>Fix Now</strong> é a controladora dos dados pessoais coletados neste site, responsável por definir as finalidades e os meios de tratamento, conforme o art. 5º, VI, da LGPD.</p>
    <p>Contato do encarregado (DPO): <strong>privacidade@fixnow.com.br</strong></p>
  </div>

  <div class="doc-section">
    <h4>2. Dados Coletados e Finalidade</h4>
    <table class="table table-bordered table-sm">
      <thead class="table-light"><tr><th>Dado</th><th>Finalidade</th><th>Base Legal (LGPD)</th></tr></thead>
      <tbody>
        <tr><td>Nome completo</td><td>Identificação na plataforma</td><td>Execução de contrato (art. 7º, V)</td></tr>
        <tr><td>CPF</td><td>Verificação de identidade e prevenção de fraudes</td><td>Legítimo interesse / Execução de contrato</td></tr>
        <tr><td>E-mail</td><td>Login, notificações e comunicações</td><td>Execução de contrato (art. 7º, V)</td></tr>
        <tr><td>Telefone</td><td>Contato para serviços contratados</td><td>Execução de contrato (art. 7º, V)</td></tr>
        <tr><td>Endereço / CEP</td><td>Localização para prestação do serviço</td><td>Execução de contrato (art. 7º, V)</td></tr>
        <tr><td>Foto de perfil</td><td>Identificação visual na plataforma</td><td>Consentimento (art. 7º, I)</td></tr>
        <tr><td>Documento (RG/CNH) — prestadores</td><td>Verificação de identidade e gênero pelo admin</td><td>Legítimo interesse / Obrigação legal</td></tr>
        <tr><td>Gênero</td><td>Filtro de preferência de prestador por gênero</td><td>Consentimento (art. 7º, I)</td></tr>
        <tr><td>Coordenadas GPS do serviço</td><td>Rastreamento em tempo real do prestador durante o atendimento</td><td>Execução de contrato (art. 7º, V)</td></tr>
        <tr><td>Histórico de chamados</td><td>Registro de serviços para fins de suporte e financeiro</td><td>Execução de contrato / Obrigação legal</td></tr>
      </tbody>
    </table>
  </div>

  <div class="doc-section">
    <h4>3. Compartilhamento de Dados</h4>
    <p>Seus dados <strong>não são vendidos</strong> a terceiros. O compartilhamento ocorre somente nas situações:</p>
    <ul>
      <li>Entre <strong>cliente e prestador</strong> vinculados ao mesmo chamado (nome, telefone, endereço do serviço);</li>
      <li>Com o <strong>administrador da plataforma</strong> para fins de moderação e suporte;</li>
      <li>Com <strong>autoridades públicas</strong>, quando exigido por lei ou ordem judicial.</li>
    </ul>
  </div>

  <div class="doc-section">
    <h4>4. Seus Direitos como Titular (art. 18 da LGPD)</h4>
    <p>Você tem direito a, a qualquer momento:</p>
    <ul>
      <li><strong>Acesso</strong> — saber quais dados temos sobre você;</li>
      <li><strong>Correção</strong> — corrigir dados incompletos, inexatos ou desatualizados;</li>
      <li><strong>Anonimização, bloqueio ou eliminação</strong> — de dados desnecessários ou excessivos;</li>
      <li><strong>Portabilidade</strong> — receber seus dados em formato estruturado;</li>
      <li><strong>Revogação do consentimento</strong> — retirar o consentimento a qualquer tempo;</li>
      <li><strong>Exclusão da conta</strong> — solicitar a exclusão de todos os seus dados pessoais.</li>
    </ul>
    <p>Para exercer seus direitos, entre em contato pelo e-mail: <strong>privacidade@fixnow.com.br</strong></p>
  </div>

  <div class="doc-section">
    <h4>5. Retenção dos Dados</h4>
    <p>Os dados são mantidos pelo período necessário para cumprir as finalidades descritas ou obrigações legais. Após a exclusão de conta, os dados são eliminados em até <strong>30 dias</strong>, salvo obrigação legal de retenção (ex: registros fiscais).</p>
  </div>

  <div class="doc-section">
    <h4>6. Segurança</h4>
    <p>Adotamos medidas técnicas e organizacionais para proteger seus dados, incluindo: criptografia de senhas (bcrypt), conexões HTTPS, acesso restrito ao banco de dados e controle de sessões autenticadas.</p>
  </div>

  <div class="doc-section">
    <h4>7. Cookies</h4>
    <p>Utilizamos apenas cookies de sessão, necessários para manter o login ativo. Não utilizamos cookies de rastreamento ou publicidade.</p>
  </div>

  <div class="doc-section">
    <h4>8. Contato e Reclamações</h4>
    <p>Em caso de dúvidas ou para registrar uma reclamação: <strong>privacidade@fixnow.com.br</strong></p>
    <p>Você também pode contatar a <strong>Autoridade Nacional de Proteção de Dados (ANPD)</strong>: <a href="https://www.gov.br/anpd" target="_blank">www.gov.br/anpd</a></p>
  </div>

  <?php else: ?>

  <!-- ═══════════════ TERMOS DE USO ═══════════════ -->
  <h1 class="h3 mb-1">Termos de Uso</h1>
  <p class="text-muted small mb-4">Última atualização: <?php echo date('d/m/Y'); ?></p>

  <div class="highlight-box mb-4">
    <strong>Ao criar uma conta na Fix Now, você concorda com estes termos.</strong> Leia com atenção antes de se cadastrar.
  </div>

  <div class="doc-section">
    <h4>1. Sobre a Plataforma</h4>
    <p>A <strong>Fix Now</strong> é uma plataforma digital de intermediação entre <strong>clientes</strong> que necessitam de serviços técnicos e <strong>prestadores de serviço</strong> (profissionais autônomos). A Fix Now não é empregadora dos prestadores nem parte no contrato de serviço firmado entre cliente e prestador.</p>
  </div>

  <div class="doc-section">
    <h4>2. Cadastro e Conta</h4>
    <ul>
      <li>O usuário deve ter no mínimo <strong>18 anos</strong>;</li>
      <li>As informações fornecidas no cadastro devem ser <strong>verdadeiras e atualizadas</strong>;</li>
      <li>O CPF é de uso pessoal e intransferível — não é permitido criar contas em nome de terceiros;</li>
      <li>O usuário é responsável pela segurança da sua senha;</li>
      <li>Contas com informações falsas serão <strong>suspensas ou excluídas</strong>.</li>
    </ul>
  </div>

  <div class="doc-section">
    <h4>3. Regras para Clientes</h4>
    <ul>
      <li>Descrever o problema com clareza e veracidade no chamado;</li>
      <li>Fornecer o endereço correto do local do serviço;</li>
      <li>Efetuar o pagamento pelo método acordado;</li>
      <li>Não utilizar a plataforma para solicitar serviços ilegais;</li>
      <li>Avaliar o prestador com honestidade após a conclusão do serviço.</li>
    </ul>
  </div>

  <div class="doc-section">
    <h4>4. Regras para Prestadores</h4>
    <ul>
      <li>Apresentar documentação válida (RG/CNH) no cadastro para verificação;</li>
      <li>Executar os serviços com qualidade, pontualidade e profissionalismo;</li>
      <li>Não cobrar valores diferentes dos acordados via plataforma;</li>
      <li>Manter dados de especialidade e disponibilidade atualizados;</li>
      <li>Prestadores aprovados passam por análise manual pelo administrador.</li>
    </ul>
  </div>

  <div class="doc-section">
    <h4>5. Pagamentos</h4>
    <p>Os pagamentos são processados diretamente na plataforma. A Fix Now pode reter ou estornar valores em caso de disputas comprovadas. O prestador recebe o valor após a confirmação de conclusão do serviço.</p>
  </div>

  <div class="doc-section">
    <h4>6. Responsabilidades</h4>
    <p>A Fix Now <strong>não se responsabiliza</strong> por:</p>
    <ul>
      <li>Qualidade ou resultado dos serviços prestados pelos profissionais;</li>
      <li>Danos causados durante a execução do serviço;</li>
      <li>Inadimplência de qualquer parte;</li>
      <li>Indisponibilidade temporária da plataforma por manutenção ou falhas técnicas.</li>
    </ul>
  </div>

  <div class="doc-section">
    <h4>7. Conduta Proibida</h4>
    <p>É vedado ao usuário:</p>
    <ul>
      <li>Criar múltiplas contas para burlar suspensões;</li>
      <li>Assediar, ameaçar ou discriminar outros usuários;</li>
      <li>Publicar avaliações falsas;</li>
      <li>Tentar acessar dados de outros usuários sem autorização;</li>
      <li>Usar a plataforma para fins ilegais.</li>
    </ul>
    <p>Violações resultam em suspensão ou exclusão permanente da conta.</p>
  </div>

  <div class="doc-section">
    <h4>8. Propriedade Intelectual</h4>
    <p>Todo o conteúdo da plataforma (marca, layout, código, textos) é de propriedade da Fix Now. É proibida a reprodução sem autorização expressa.</p>
  </div>

  <div class="doc-section">
    <h4>9. Alterações nos Termos</h4>
    <p>A Fix Now pode atualizar estes termos a qualquer momento. Usuários serão notificados por e-mail em caso de alterações relevantes. O uso continuado da plataforma após a notificação implica aceitação dos novos termos.</p>
  </div>

  <div class="doc-section">
    <h4>10. Legislação Aplicável</h4>
    <p>Estes termos são regidos pelas leis brasileiras, em especial o <strong>Código de Defesa do Consumidor (Lei 8.078/90)</strong>, o <strong>Marco Civil da Internet (Lei 12.965/14)</strong> e a <strong>LGPD (Lei 13.709/18)</strong>. O foro competente é o da comarca de <strong>Brasília/DF</strong>.</p>
  </div>

  <?php endif; ?>

  <?php if (!$isEmbed): ?>
  <div class="text-center mt-4 mb-2">
    <button onclick="window.close()" class="btn btn-outline-secondary me-2">Fechar</button>
    <a href="javascript:history.back()" class="btn btn-primary">Voltar ao cadastro</a>
  </div>
  <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
