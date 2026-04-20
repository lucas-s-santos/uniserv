# 📋 Documentação do Sistema Uniserv

## 🎯 Visão Geral

**Uniserv** é uma plataforma de serviços descentralizada que conecta clientes com profissionais qualificados de forma rápida, segura e transparente. O sistema foi criado para a comunidade de Alfenas e região, permitindo que qualquer pessoa possa oferecer ou solicitar serviços através de uma interface intuitiva.

### Slogan
*"Serviços incríveis a um clique de distância"*

---

## 👥 Tipos de Usuários

O sistema suporta **3 tipos de usuários** com funcionalidades específicas:

### 1️⃣ **Cliente (Função 3)**
- Solicita serviços profissionais
- Busca profissionais qualificados
- Acompanha serviços em tempo real
- Avalia e comenta sobre os profissionais
- Gerencia histórico de serviços
- Realiza pagamentos via PIX

**Páginas principais:**
- `chamar.php` - Solicitar novo serviço
- `servicos.php` - Ver serviços ativos/em andamento
- `historico.php` - Ver histórico completo
- `perfil.php` - Gerenciar perfil pessoal

### 2️⃣ **Colaborador/Profissional (Função 2)**
- Oferece serviços e ganha dinheiro
- Controla seus próprios horários
- Gerencia chamados recebidos
- Configura dados bancários para receber pagamentos
- Acompanha ganhos e histórico de serviços
- Recebe notificações de novos chamados

**Páginas principais:**
- `colabo/colaborador.php` - Painel do profissional
- `colabo/cadastro_colaborador.php` - Inscrição de novos profissionais
- `pagamento_config.php` - Configurar conta bancária para receber
- `historico.php` - Ver histórico de serviços prestados

### 3️⃣ **Administrador (Função 1)**
- Gerencia toda a plataforma
- Controla funcionalidades dos usuários
- Visualiza auditoria completa de ações
- Gerencia serviços e profissionais
- Acessa relatórios detalhados
- Controla pagamentos e transações

**Páginas principais:**
- `administrador.php` - Painel de controle administrativo
- Sistema de auditoria (`audit.php`)

---

## 🔄 Fluxo de um Serviço

Um serviço passa por diferentes **status** e **etapas**:

### Status de Serviço

| Status | Código | Descrição |
|--------|--------|-----------|
| Finalizado | 0 | Serviço concluído |
| Em Andamento | 1 | Serviço sendo executado |
| Em Pedido | 2 | Aguardando resposta do profissional |
| Aguardando Pagamento | 3 | Serviço completo, aguardando pagamento |
| Recusado | -1 | Profissional recusou o serviço |
| Cancelado | -2 | Cliente ou sistema cancelou |

### Etapas do Serviço

1. **Pendente** - Cliente criou o chamado
2. **Orçamento Enviado** - Profissional enviou orçamento
3. **Aguardando Início** - Orçamento aceito, aguardando execução
4. **Em Execução** - Profissional está executando o serviço
5. **Finalizado** - Serviço concluído

### Fluxo Visual

```
Cliente cria chamado
        ↓
    [PENDENTE]
        ↓
Profissional recebe notificação
        ↓
    [ORÇAMENTO ENVIADO]
        ↓
Cliente aceita/recusa orçamento
        ↓
    [AGUARDANDO INÍCIO]
        ↓
Profissional começa a trabalhar
        ↓
    [EM EXECUÇÃO]
        ↓
Profissional marca como finalizado
Cliente confirma recebimento
        ↓
    [FINALIZADO]
        ↓
    Pagamento
        ↓
    [CONCLUÍDO]
```

---

## 🏗️ Arquitetura Técnica

### Stack Tecnológico

| Camada | Tecnologia |
|--------|-----------|
| **Servidor** | PHP 8.2 (Apache/XAMPP) |
| **Banco de Dados** | MySQL |
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla + jQuery) |
| **CSS Framework** | Bootstrap 3.3.7 |
| **UI Components** | Customizado com Material Design |
| **Autenticação** | Sessions PHP + CSRF Token |
| **Criptografia** | password_hash() com algoritmo PASSWORD_DEFAULT |

### Arquitetura do Projeto

```
uniserv/
├── 📄 Página Principale Públicas
│   ├── index.php           # Página inicial
│   ├── sobre.php           # Quem somos
│   ├── login.php           # Autenticação
│   ├── cadastro.php        # Novo usuário
│   └── processa_*.php      # Processadores de formulários
│
├── 👤 Páginas de Cliente
│   ├── chamar.php          # Solicitar serviço
│   ├── servicos.php        # Serviços ativos
│   ├── historico.php       # Histórico completo
│   └── pagamento.php       # Página de pagamento
│
├── 💼 Páginas de Profissional
│   └── colabo/
│       ├── colaborador.php          # Painel principal
│       ├── cadastro_colaborador.php # Inscrição
│       └── *.php                    # Processadores
│
├── ⚙️ Páginas de Administrador
│   ├── administrador.php   # Painel completo
│   └── audit.php           # Auditoria
│
├── 📁 includes/            # Bibliotecas reutilizáveis
│   ├── bootstrap.php       # Inicialização do sistema
│   ├── auth.php            # Autenticação
│   ├── admin_funcoes.php   # Funções do Admin
│   ├── pix_gateway.php     # Integração PIX
│   └── status.php          # Constantes de status
│
├── 📁 css/                 # Estilos
│   └── estrutura_geral.css # Tema principal
│
├── 📁 js/                  # JavaScript
│   ├── jquery.min.js       # jQuery
│   ├── bootstrap.min.js    # Bootstrap JS
│   ├── all.js              # Scripts customizados
│   └── toast.js            # Notificações
│
├── 📁 image/               # Imagens e uploads
│   ├── perfil/             # Fotos de perfil
│   ├── comprovantes/       # Comprovantes de pagamento
│   ├── certificados/       # Certificados profissionais
│   └── servicos/           # Imagens de serviços
│
├── 📁 adm/                 # Processadores admin
│   ├── processa_criar.php
│   ├── processa_editar.php
│   └── processa_deletar.php
│
└── 📄 Configurações
    ├── conexao.php         # Conexão com banco
    ├── all.php             # Setup global
    ├── menu.php            # Menu compartilhado
    └── banco.sql           # Schema do banco
```

---

## 🔐 Segurança

### Proteções Implementadas

✅ **Sessões PHP** - Controle de acesso por sessão  
✅ **CSRF Token** - Proteção contra ataques CSRF  
✅ **Password Hashing** - Senhas criptografadas com PASSWORD_DEFAULT  
✅ **Prepared Statements** - Prevenção de SQL Injection  
✅ **Input Validation** - Validação de entrada em formulários  
✅ **Auditoria** - Registro de todas as ações importantes  
✅ **Rate Limiting** - Limite de tentativas de login (5 tentativas em 5 minutos)  

### Fluxo de Autenticação

1. Usuário preenche CPF e senha em `login.php`
2. `processa_login.php` valida CSRF token
3. Busca usuário no banco por CPF formatado
4. Verifica senha com `password_verify()`
5. Se inválida, incrementa tentativas (bloqueio após 5)
6. Se válida, regenera session ID (segurança)
7. Armazena dados em `$_SESSION`

---

## 💳 Integração de Pagamento

### Sistema PIX

A plataforma suporta pagamentos via PIX através da classe `PIXGateway`:

- **Geração de QR Code** - Dinâmic, específico por serviço
- **Validação** - Webhook para confirmar pagamento
- **Recebimentos** - Sistema de contas para colaboradores
- **Status** - Rastreamento em tempo real

**Arquivo:** `includes/pix_gateway.php`  
**Webhook:** `webhook_pix.php`  
**Status:** `pix_status.php`

---

## 📱 Funcionalidades Principais

### Para Clientes

| Funcionalidade | Descrição |
|---|---|
| **Buscar Profissionais** | Filtrar por tipo de serviço, avaliação, valor |
| **Solicitar Serviço** | Criar chamado com descrição e localização |
| **Acompanhar** | Ver status real do serviço em tempo real |
| **Avaliar** | Deixar comentário e nota de 1 a 5 |
| **Pagar** | Via PIX com geração de QR Code |
| **Histórico** | Visualizar todos os serviços contratados |

### Para Profissionais

| Funcionalidade | Descrição |
|---|---|
| **Receber Notificações** | Quando cliente cria chamado na sua área |
| **Aceitar/Recusar** | Responder a chamados recebidos |
| **Enviar Orçamento** | Detalhar valor, prazo, escopo |
| **Atualizar Status** | Marcar em andamento, finalizado |
| **Configurar Pagamento** | Adicionar conta para receber |
| **Gerenciar Agenda** | Controlar disponibilidade |

### Para Admins

| Funcionalidade | Descrição |
|---|---|
| **Dashboard** | Visão geral de usuários, serviços, pagamentos |
| **Criar/Editar Funções** | Tipos de serviços disponíveis |
| **Gerenciar Usuários** | Ativar, desativar, editar dados |
| **Auditoria** | Log de todas as ações |
| **Relatórios** | Estatísticas de uso, receita, etc |

---

## 🗄️ Banco de Dados

### Tabelas Principais

#### **registro** (Usuários)
- `id_registro` - ID único
- `cpf` - CPF formatado
- `apelido` - Nome de usuário
- `senha` - Hash de senha
- `funcao` - Tipo: 1=Admin, 2=Colaborador, 3=Cliente
- `telefone` - Contato
- `email` - Email
- `endereco`, `numero`, `bairro` - Localização

#### **servico** (Solicitações de Serviço)
- `id_servico` - ID único
- `registro_id_registro` - Cliente
- `id_trabalhador` - Profissional/Colaborador
- `descricao` - O que precisa ser feito
- `ativo` - Status do serviço
- `preco` - Valor final
- `data_inicio` - Quando começa
- `data_termino` - Previsão de término
- `localidade` - Onde será realizado

#### **notificacoes** (Alertas)
- `id_notificacao` - ID único
- `registro_id_registro` - Para qual usuário
- `titulo` - Título
- `descricao` - Conteúdo
- `lida` - Se foi visualizada

#### **pagamentos** (Histórico de Transações)
- `id_pagamento` - ID único
- `servico_id_servico` - Referências
- `status_pagamento` - Status da transação
- `valor` - Montante
- `data_pagamento` - Quando foi pago
- `comprovante` - URL da imagem/PDF

---

## 🚀 Como Acessar

### URLs Principais

| Página | URL |
|--------|-----|
| Início | `http://localhost/uniserv/` |
| Login | `http://localhost/uniserv/login.php` |
| Cadastro | `http://localhost/uniserv/cadastro.php` |
| Painel Cliente | `http://localhost/uniserv/chamar.php` |
| Painel Colaborador | `http://localhost/uniserv/colabo/colaborador.php` |
| Painel Admin | `http://localhost/uniserv/administrador.php` |
| Histórico | `http://localhost/uniserv/historico.php` |
| Perfil | `http://localhost/uniserv/perfil.php` |

### Contas Teste

💡 *Para testar, você precisa criar um usuário através do cadastro ou importer dados de teste no banco.*

---

## 🛠️ Requesitos para Rodar

- **Windows 7+** com XAMPP instalado
- **Apache** ativo
- **MySQL** ativo
- **PHP 7.4+**
- **Navegador moderno** (Chrome, Firefox, Edge)

### Passos para Configurar

1. Copie a pasta `uniserv` para `C:\xampp\htdocs\`
2. Abra XAMPP Control Panel
3. Inicie **Apache** e **MySQL**
4. Acesse `http://localhost/phpmyadmin`
5. Crie banco: `CREATE DATABASE relampagoservice;`
6. Importe `banco.sql` via phpMyAdmin
7. Abra `http://localhost/uniserv/`

---

## 📝 Fluxo de Desenvolvimento

### Convenções de Código

- **PHP**: Prepared statements, não raw SQL
- **JS**: Vanilla JS + jQuery para compatibilidade
- **CSS**: Customizado com variáveis CSS
- **HTML**: HTML5 semântico

### Como Adicionar Nova Página

1. Crie `minha_pagina.php` na raiz ou em subpasta
2. Sempre iniciar com: `<?php session_start(); include_once "includes/bootstrap.php"; ?>`
3. Incluir menu: `<?php include 'menu.php'; ?>`
4. Usar classes CSS existentes para consistência
5. Validar entrada em `processa_minha_pagina.php`

---

## 🔗 Dependências Externas

- **jQuery 1.9.1** - Manipulação DOM
- **jQuery UI** - Widgets adicionais
- **Bootstrap 3.3.7** - Framework CSS/Components
- **Font Awesome** (via CDN) - Ícones
- **Google Maps** (opcional) - Geolocalização
- **OpenStreetMap Nominatim** - Geocodificação

---

## 📞 Suporte

**Desenvolvedor:** Lucas Silva dos Santos  
**Email:** lucassilvadossantos2005@gmail.com  
**LinkedIn:** https://www.linkedin.com/in/lucas-silva-dos-santos-a82b4b201/  
**GitHub:** https://github.com/lucas-s-santos  
**Portfólio:** https://portfolio-lucas-s-s.netlify.app/

---

## 📄 Licença

Este projeto foi desenvolvido como trabalho profissional. Todos os direitos reservados.

---

## 🎓 Status do Projeto

✅ **Versão 1.0** - Sistema completo e funcional  
🔄 **Em manutenção** - Correções e melhorias contínuas  
🚀 **Próximas features** - Aplicativo mobile, notificações push, chat em time real

---

*Última atualização: Abril de 2026*
