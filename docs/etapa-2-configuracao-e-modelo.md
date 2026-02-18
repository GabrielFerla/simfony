# Etapa 2 — Configuração e modelo de dados

Esta etapa descreve **pacotes Composer**, **variáveis de ambiente**, **arquivos de configuração** (Doctrine, Security, JWT, CORS), **entidades e repositórios** em detalhe e **migrations**.

---

## 1. Pacotes Composer a instalar

| Pacote | Finalidade |
|--------|------------|
| `doctrine/orm` + `doctrine/doctrine-bundle` | ORM e mapeamento (PostgreSQL) |
| `symfony/security-bundle` | Autenticação e autorização |
| `lexik/jwt-authentication-bundle` | Geração e validação de JWT |
| `symfony/uid` | UUID nas entidades |
| `symfony/validator` | Validação dos requests |
| `symfony/serializer` | Serialização para JSON |
| `nelmio/cors-bundle` | CORS para o frontend Angular |

Comandos (apenas após validação):

```bash
composer require symfony/orm-pack
composer require symfony/security-bundle
composer require lexik/jwt-authentication-bundle
composer require symfony/uid
composer require symfony/validator
composer require symfony/serializer
composer require nelmio/cors-bundle
```

Configuração do JWT (geração de chaves):

```bash
php bin/console lexik:jwt:generate-keypair
```

---

## 2. Arquivo `.env` — variáveis a adicionar/alterar

- **DATABASE_URL** — conexão PostgreSQL, por exemplo:  
  `DATABASE_URL="postgresql://user:password@127.0.0.1:5432/soumacoisa?serverVersion=16&charset=utf8"`
- **JWT_SECRET_KEY** e **JWT_PUBLIC_KEY** — caminhos para as chaves geradas pelo Lexik (ou variáveis que o bundle usar).
- Manter **APP_SECRET** preenchido.

---

## 3. Configuração (arquivos em `config/`)

### 3.1 bundles.php

Registrar:

- `Doctrine\Bundle\DoctrineBundle\DoctrineBundle`
- `Symfony\Bundle\SecurityBundle\SecurityBundle`
- `Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle`
- `Nelmio\CorsBundle\NelmioCorsBundle`

(Outros podem ser adicionados automaticamente pelo Flex.)

### 3.2 packages/doctrine.yaml

- Driver: `pdo_pgsql`
- Entidades em `src/Entity`
- Diretório de migrations (padrão ou customizado)

### 3.3 packages/security.yaml

- Firewall para `/api` com stateless JWT.
- User provider carregando usuário por **email** a partir da entidade `User`.
- Rotas públicas: `/api/auth/register`, `/api/auth/login`, `/api/auth/refresh`.
- Demais rotas `/api/*` protegidas (exigem token válido).

### 3.4 packages/lexik_jwt_authentication.yaml

- Secret key e public key (paths ou variáveis de ambiente).
- Tempo de expiração do token.

(Arquivo costuma ser gerado/ajustado pelo Flex ao instalar o bundle.)

### 3.5 packages/nelmio_cors.yaml

- Origens permitidas (ex.: `http://localhost:4200` para o Angular).
- Métodos e headers necessários para a API (Authorization, Content-Type, etc.).

---

## 4. Entidades e repositórios (detalhes)

### 4.1 User (src/Entity/User.php)

- **id:** UUID (symfony/uid), gerado automaticamente.
- **email:** string, único na tabela, usado como login.
- **passwordHash:** string, senha criptografada com bcrypt.
- **displayName:** string, nome exibido na saudação.
- **timezone:** string (ex.: `America/Sao_Paulo`).
- **createdAt:** datetime, data de criação da conta.

Implementar **UserInterface** para o Security:

- `getUserIdentifier()` → email
- `getRoles()` → ex.: `['ROLE_USER']`
- `getPassword()` → passwordHash
- `eraseCredentials()` → vazio (não armazenar senha em memória)

### 4.2 DailyEntry (src/Entity/DailyEntry.php)

- **id:** UUID (symfony/uid).
- **user:** ManyToOne → User (FK).
- **date:** date, data da entrada. **UNIQUE por (user, date).**
- **intention:** text, a “única coisa” do dia.
- **completed:** boolean nullable — `null` = sem resposta, `true` = feito, `false` = não feito.
- **skipped:** boolean — true se o usuário escolheu pular o dia.
- **createdAt:** datetime — check-in da manhã.
- **updatedAt:** datetime — check-in da noite (quando respondeu).

Índice/constraint **UNIQUE (user_id, date)** na entidade e na migration.

### 4.3 UserRepository (src/Repository/UserRepository.php)

- Método **findOneByEmail(string $email)** (ou equivalente) para o user provider do Security.

### 4.4 DailyEntryRepository (src/Repository/DailyEntryRepository.php)

- **findByUserAndDate(User $user, \DateTimeInterface $date): ?DailyEntry**
- **findByUserAndMonth(User $user, string $yearMonth): array** — formato `YYYY-MM`, ordenado por `date` ASC.
- **findRecentByUser(User $user, int $limit = 7): array** — últimas N entradas (para `/api/history/recent`).

---

## 5. Migrations

- Após as entidades e a config do Doctrine estarem prontas:  
  `php bin/console make:migration`
- Revisar o SQL gerado (presença da UNIQUE em `user_id` + `date` na tabela `daily_entry`).
- Aplicar:  
  `php bin/console doctrine:migrations:migrate`

---

**Anterior:** [Etapa 1 — Visão e estrutura](etapa-1-visao-e-estrutura.md)  
**Próximo:** [Etapa 3 — API e implementação](etapa-3-api-e-implementacao.md)
