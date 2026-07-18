# Sistema de Gestão de Restaurante

Projeto acadêmico desenvolvido para a disciplina de Programação para Internet I. Trata-se de um sistema web completo para gerenciamento de restaurante, focado na aplicação prática de modelagem relacional, integridade de dados e consultas analíticas.

Link para a apresentação: [Presentation](https://drive.google.com/file/d/1VSMx7wEqgoAnR4uYu4bZjD9j1ORjWTot/view?usp=drive_link)

## Funcionalidades Principais

* **Controle de Acesso:** Perfis distintos de acesso (`admin` e `caixa`), restringindo módulos financeiros e gerenciais ao administrador.
* **Operações de Caixa:** Abertura de comandas, alocação de mesas, lançamento de itens e fechamento de pedidos com múltiplas formas de pagamento.
* **Controle Automático de Estoque:** Lógica de negócio transferida para o SGBD através de **Triggers**, garantindo validação, baixa e estorno de itens automaticamente durante o manuseio das comandas.
* **Gestão Cadastral (CRUDs):** Administração de Clientes, Funcionários, Produtos, Categorias, Mesas e Despesas.
* **Módulo Analítico e Relatórios:** Geração de KPIs dinâmicos filtrados por período, curva ABC de produtos, ranking de desempenho de funcionários (Garçons) e ticket médio.

## Tecnologias Utilizadas

* **Backend:** PHP Vanilla (PDO)
* **Banco de Dados:** MariaDB / MySQL
* **Frontend:** HTML5 e CSS3 (Vanilla, sem dependências externas)
* **Servidor Web:** Apache

## Estrutura de Diretórios

O projeto segue o princípio de Separação de Responsabilidades:

meu-projeto/
|-- assets/         # Arquivos estáticos (CSS, imagens)
|-- config/         # Configurações de ambiente e conexão com o banco
|-- database/       # Script estrutural do banco (model.sql)
|-- includes/       # Componentes de interface compartilhados (Header/Footer)
|-- pages/          # Lógica de negócio e visualização (Módulos do sistema)
|-- index.php       # Ponto de entrada e autenticação
|-- dashboard.php   # Painel principal
|-- logout.php      # Encerramento de sessão

## Pré-requisitos e Requisitos de Sistema

Para rodar o projeto localmente, você precisará de um ambiente AMP (Apache, MySQL/MariaDB, PHP) configurado. 

**Módulos PHP Obrigatórios:**
O sistema utiliza a biblioteca PDO para comunicação segura com o banco de dados. Certifique-se de que os seguintes módulos estejam ativos no seu `php.ini`:
* `extension=pdo`
* `extension=pdo_mysql`

*(Nota para usuários Arch Linux: Lembre-se de descomentar estas linhas no arquivo `/etc/php/php.ini` e reiniciar o serviço `httpd`)*.

## Instalação e Configuração

**1. Clone o repositório**
Clone ou extraia os arquivos do projeto no diretório público do seu servidor web (ex: `htdocs` no XAMPP, `www` no WAMP, ou `/srv/http/` no Arch Linux).

**2. Configure as Credenciais do Banco de Dados**
No mesmo arquivo `config/config.php`, configure as variáveis `$host`, `$user` e `$pass` de acordo com as credenciais do seu servidor MySQL/MariaDB. Deixe a variável `$db` como `'restaurante_db'`.

**3. Execução e Instalação Automática (Plug and Play)**
**Não é necessário** criar o banco de dados ou importar o arquivo `model.sql` manualmente. 
Basta acessar o projeto no navegador (ex: `http://localhost/restaurante`). O sistema detectará automaticamente se o banco de dados existe; caso negativo, o próprio sistema criará o banco, as tabelas e os *triggers* necessários de forma silenciosa e segura.

## Credenciais de Acesso Padrão

Para fins de avaliação, utilize as seguintes credenciais para acessar o sistema:

**Perfil Administrador (Acesso Total):**
* Usuário: `admin`
* Senha: `admin123`

**Perfil Operador de Caixa (Acesso Restrito):**
* Usuário: `caixa`
* Senha: `caixa123`