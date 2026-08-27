# Royal Brazilian Extensions — Painel Administrativo

Sistema interno da Royal Brazilian Extensions: cadastro e login de usuários, dashboard, agenda de eventos com calendário e controle de produtos/estoque.

PHP puro (sem framework), com autoload PSR-4 via Composer, MySQL/PDO e um pouco de TypeScript nas páginas do dashboard que precisam de interação dinâmica (Agenda e Controle de Produtos).

## Funcionalidades

- **Usuários**: cadastro, login (com hash de senha e proteção CSRF), listagem com busca/filtro/paginação, edição e exclusão.
- **Dashboard**: painel principal após o login, com o nome do usuário logado.
- **Agenda**: calendário mensal (FullCalendar) ligado à tabela `eventos` — criar, editar, buscar (por título/status) e excluir eventos, tudo via AJAX.
- **Controle de Produtos**: cadastro com código único, nome, categoria, preço e estoque; busca por nome/código/categoria/status; lista paginada (10 por página); editar e excluir.

## Tecnologias

- **PHP** (sem framework), autoload PSR-4 via [Composer](https://getcomposer.org/)
- **MySQL** via PDO, com prepared statements em todas as queries que usam dado de entrada
- **TypeScript**, compilado com `tsc` puro (sem bundler) para `dashboard/public/assets/js/`
- **[FullCalendar](https://fullcalendar.io/)** para o calendário da Agenda
- **Bootstrap** e **Font Awesome** para o visual do dashboard

## Estrutura do projeto

```
rbextensions/
├── app/                    # Classes PHP (autoload PSR-4: App\)
│   ├── Db/                 # Database (PDO) e Pagination
│   ├── Entity/              # Vaga (usuário), Evento, Produto
│   └── Session/              # Login (sessão/autenticação) e Csrf
├── dashboard/               # Painel logado
│   ├── index.php             # Home do dashboard
│   ├── agenda.php + eventos*.php       # Agenda e seus endpoints JSON
│   ├── controle-produtos.php + produtos*.php   # Produtos e seus endpoints JSON
│   ├── src/                 # Código-fonte TypeScript (.ts)
│   └── public/assets/       # CSS, JS compilado, imagens, FullCalendar vendorizado
├── usuarios/                 # Páginas públicas/de autenticação
│   ├── index.php, login.php, logout.php, cadastrar.php
│   ├── listar.php, editar.php, excluir.php   # Gestão de usuários (exige login)
│   └── includes/              # Partials HTML compartilhados
├── index.php                 # Redireciona a raiz do site para usuarios/index.php
├── composer.json / vendor/    # Dependências PHP
├── package.json / tsconfig.json / node_modules/   # Dependências e build do TypeScript
└── .env                       # Credenciais do banco (não versionado)
```

## Requisitos

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8+)
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) + npm (só para compilar o TypeScript)

## Instalação

1. Coloque o projeto em `C:\xampp\htdocs\rbextensions`.
2. Instale as dependências PHP:
   ```
   composer install
   ```
3. Instale as dependências de build e compile o TypeScript:
   ```
   npm install
   npm run build
   ```
4. Copie `.env.example` para `.env` e ajuste se necessário (os valores padrão já funcionam num XAMPP local comum):
   ```
   DB_HOST=localhost
   DB_NAME=rbextensions
   DB_USER=root
   DB_PASS=
   ```


## Desenvolvimento

- Sempre que editar um arquivo em `dashboard/src/*.ts`, rode `npm run build` antes de testar — o navegador só carrega o `.js` já compilado, nunca o `.ts` diretamente.
- Cada script de página TypeScript roda dentro de uma IIFE, pra não colidir nomes de função com outras páginas quando os `.ts` são compilados juntos (não há bundler nem sistema de módulos).
- Padrões de segurança seguidos no projeto: senhas com `password_hash`/`password_verify`, todas as queries com dado de usuário usando parâmetros (`?`) via PDO, token CSRF em todo formulário/endpoint que altera dados, e a sessão de login confere a cada requisição se o usuário ainda existe de fato no banco.
