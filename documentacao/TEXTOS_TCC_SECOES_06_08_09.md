# Textos sugeridos para o TCC — Fix Now (atualização)

Use os trechos abaixo como base para incorporar ao documento nas seções **6 (Abrangência do sistema)**, **8 (Modelo do software)** e **9 (Conclusão)**. Ajuste redação, numeração e referências cruzadas ao padrão da sua instituição.

---

## 6. ABRANGÊNCIA DO SISTEMA (atualizada)

O sistema **Fix Now** abrange o ciclo de contratação de serviços técnicos rápidos entre **clientes**, **prestadores (técnicos)** e **administradores**, com foco em usabilidade, rastreabilidade e governança dos cadastros.

A abrangência funcional atual inclui:

- **Autenticação unificada**: uma única tela de login identifica automaticamente o tipo de usuário (cliente, prestador ou administrador) e redireciona ao painel correspondente.
- **Perfis completos**: página de perfil acessível a todos os perfis, exibindo dados pessoais, foto opcional, histórico de chamados e histórico de avaliações (enviadas pelo cliente ou recebidas pelo prestador).
- **Validação de CPF**: formulários que coletam CPF contam com validação em tempo real no navegador e validação no servidor, além de fluxo pós-cadastro com confirmação visual e recarga automática da página para consolidar a experiência de sucesso.
- **Visualização de fotos**: exibição da foto do cliente e do prestador no rastreamento/detalhes do chamado e nos painéis, com visualização ampliada em modal (lightbox).
- **Gestão de cadastros de prestadores**: área administrativa para **aprovar** ou **recusar** novos prestadores, controlando `status_cadastro` e liberação de acesso ao painel.
- **Negativa de serviço**: prestador e administrador podem **negar** chamados pendentes ou em andamento (conforme regra de negócio implementada), alterando o status para **Negado** e registrando **notificação** ao cliente.
- **Avaliação pós-serviço**: componente de **5 estrelas** interativo (hover e clique), com estilização dedicada em CSS e sincronização com campo oculto enviado ao servidor.

A abrangência técnica permanece alinhada ao stack adotado no TCC: **PHP 8**, **MySQL**, **Bootstrap 5** e **JavaScript** nativo, sem dependência de framework front-end pesado.

---

## 8. MODELO DO SOFTWARE (atualizada)

O modelo do software reflete uma arquitetura em camadas típica de aplicação **Web monolítica em PHP**, com páginas `.php` para apresentação e regras de negócio, **PDO** para persistência e scripts **JS/CSS** modularizados em `assets/`.

Principais elementos do modelo, acrescentados nesta versão:

### 8.x Dados e persistência

Foram incorporadas entidades e atributos para suportar as novas funcionalidades:

- **Tabelas `cliente` e `tecnico`**: campos `cpf` (11 dígitos, único quando informado), `foto_perfil` (caminho relativo do arquivo) e, para técnicos, `status_cadastro` (`Pendente`, `Aprovado`, `Recusado`) associado ao fluxo de aprovação e ao campo `ativo`.
- **Tabela `chamado`**: inclusão do status **`Negado`**, permitindo representar recusa/cancelamento operacional com consistência nas consultas dos painéis.
- **Tabela `notificacao`**: armazena mensagens para o cliente (ex.: chamado negado), com flag de leitura e vínculo opcional ao `chamado_id`.

### 8.x Fluxos de autenticação e autorização

O **login unificado** consulta, em ordem definida, contas administrativas (`cliente.is_admin = 1`), credenciais de **prestador** (`tecnico`, com validação de senha antes de mensagens de “cadastro pendente”) e, por fim, **cliente comum**. A sessão utiliza chaves distintas (`admin_id`, `tecnico_id`, `cliente_id`) para separar contextos e facilitar o controle de acesso às páginas.

### 8.x Interface e experiência

- **Perfil (`perfil.php`)**: consolida edição de dados e upload de foto em um único fluxo, com **PRG** (redirect após POST) para evitar reenvio acidental.
- **Estrelas de avaliação**: componente visual composto por botões SVG estilizados, atualizando um campo oculto `nota_avaliacao` submetido ao servidor.
- **Lightbox de fotos**: script reutilizável (`foto-lightbox.js`) acoplado ao modal Bootstrap para ampliar fotos de perfil nas telas de chamado e painel do prestador.

---

## 9. CONCLUSÃO (atualizada)

Esta etapa do trabalho consolidou o **Fix Now** como uma plataforma mais completa para o cenário de TCC, aproximando o protótipo de um fluxo real de marketplace de serviços: **um único ponto de entrada (login)**, **gestão de prestadores** com aprovação administrativa, **transparência visual** com fotos de perfil e **comunicação com o cliente** por notificações quando o serviço é negado.

A validação de **CPF** no cliente (JavaScript + PHP) reforça a preocupação com qualidade de dados, enquanto o módulo de **avaliação por estrelas** melhora a percepção de usabilidade e o alinhamento com padrões de interfaces modernas.

Como trabalhos futuros, podem ser explorados: integração com gateway de pagamento real, envio de notificações por e-mail ou push, política de privacidade/LGPD para tratamento de CPF e imagens, e testes automatizados (unitários e de interface) para reduzir regressões em novas entregas.

---

_Fim do arquivo de apoio ao TCC._
