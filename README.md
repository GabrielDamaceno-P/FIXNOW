# Fix Now - MVP (PHP 8 + MySQL + Bootstrap 5.3)

Projeto acadêmico de plataforma de serviços técnicos rápidos.

## Requisitos

- PHP 8+
- MySQL 5.7+ ou 8+
- Apache (XAMPP/WAMP/Laragon)
- Navegador moderno

## Estrutura esperada

```txt
FixNow/
├── index.php
├── cadastro.php
├── login.php
├── logout.php
├── solicitar.php
├── dashboard-cliente.php
├── rastreamento.php
├── admin.php
├── config/
│   └── db.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── img/
│       └── uploads/ (criada automaticamente)
├── database.sql
└── README.md
```

## Instalação (localhost / XAMPP)

1. Copie a pasta `FixNow` para `htdocs`.
2. Inicie Apache e MySQL no XAMPP.
3. Abra o phpMyAdmin.
4. Crie um banco e importe o arquivo `database.sql`.
5. Verifique as credenciais em `config/db.php`:
   - host: `127.0.0.1`
   - dbname: `fixnow`
   - user: `root`
   - pass: `` (vazio no XAMPP padrão)
6. Acesse no navegador:
   - `http://localhost/FixNow/index.php`

## Login admin padrão

- E-mail: `admin@fixnow.com`
- Senha: `admin123`

## Funcionalidades MVP

- Cadastro de cliente com validação e senha com hash
- Login com sessão PHP
- Solicitação de serviço com:
  - descrição
  - upload de foto
  - categoria
  - diagnóstico simulado com preço sugerido
  - endereço automático do cadastro
- Dashboard do cliente com status dos chamados
- Rastreamento simulado com mapa (Leaflet)
- Painel admin com lista de chamados e técnicos

## Observações

- Projeto feito em PHP puro (sem frameworks).
- Para produção real, adicionar:
  - proteção CSRF
  - validação mais robusta
  - controle de permissões avançado
  - sanitização e políticas de upload mais rígidas
  - logs e monitoramento
