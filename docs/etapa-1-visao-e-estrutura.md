# Etapa 1 — Visão e estrutura

Esta etapa descreve **o que é o projeto**, a **estrutura atual** do código, a **estrutura desejada** e o **contrato da API** (endpoints e JSON).

> **Status:** ✅ Implementada. Entidades, repositórios e migration criados. Ao subir o PostgreSQL, execute:  
> `php bin/console doctrine:migrations:migrate`

---

## 1. Visão geral

- **Nome:** Só Uma Coisa — MVP de teste  
- **Backend:** Symfony (projeto atual Symfony 8.x)  
- **Banco:** PostgreSQL  
- **API:** REST em JSON + autenticação JWT  
- **Fora do escopo:** notificações push, insights, monetização, streak, PWA offline  

**Fluxo principal:** o usuário define “a única coisa do dia” de manhã (check-in), vê um card de foco e à noite responde se completou ou não; pode ver histórico em calendário mensal.

---

## 2. Estrutura atual do projeto (antes das alterações)

```
c:\simfony\
├── bin/console
├── config/
│   ├── bundles.php          # só FrameworkBundle
│   ├── packages/            # framework, cache, routing
│   ├── routes.yaml
│   ├── services.yaml
│   └── ...
├── public/index.php
├── src/
│   └── Kernel.php           # só o Kernel, sem entidades/controllers
├── var/
├── vendor/
├── .env                     # sem DATABASE_URL nem JWT
└── composer.json            # PHP 8.4, Symfony 8.0 (flex), sem Doctrine/Security/JWT
```

Ou seja: projeto Symfony mínimo, **sem** banco, **sem** segurança e **sem** API.

---

## 3. Estrutura desejada (como o projeto deve ficar)

### 3.1 Árvore de pastas/arquivos novos e alterados

```
c:\simfony\
├── config/
│   ├── bundles.php                    # ALTERADO — novos bundles
│   ├── packages/
│   │   ├── doctrine.yaml              # NOVO — conexão PostgreSQL + mapeamento
│   │   ├── security.yaml              # NOVO — firewall, JWT, user provider
│   │   ├── lexik_jwt_authentication.yaml  # NOVO — config JWT (gerado pelo Flex)
│   │   ├── nelmio_cors.yaml           # NOVO — CORS para o Angular
│   │   └── framework.yaml             # pode ganhar serializer/validation se necessário
│   └── routes.yaml                    # sem mudança estrutural; rotas vêm dos controllers
├── migrations/                        # NOVO — migrations Doctrine (geradas após entidades)
│   └── VersionXXXXXXXXXXXXXX.php
├── src/
│   ├── Entity/
│   │   ├── User.php                   # NOVO
│   │   └── DailyEntry.php             # NOVO
│   ├── Repository/
│   │   ├── UserRepository.php         # NOVO
│   │   └── DailyEntryRepository.php   # NOVO
│   ├── Controller/
│   │   └── Api/
│   │       ├── AuthController.php     # NOVO — register, login, refresh
│   │       ├── MeController.php       # NOVO — GET e PATCH /api/me
│   │       ├── TodayController.php    # NOVO — GET, POST, PATCH /api/today
│   │       └── HistoryController.php  # NOVO — GET /api/history
│   └── Kernel.php                     # sem alteração
├── .env                               # ALTERADO — DATABASE_URL, JWT_SECRET_KEY, etc.
└── composer.json                      # ALTERADO — novas dependências
```

### 3.2 Banco de dados — entidades

Apenas **2 entidades**:

| Entidade     | Campos principais |
|-------------|-------------------|
| **User**    | `id` (UUID), `email` (único), `passwordHash`, `displayName`, `timezone`, `createdAt` |
| **DailyEntry** | `id` (UUID), `user` (FK → User), `date` (date), `intention` (text), `completed` (bool ou null), `skipped` (bool), `createdAt`, `updatedAt` |

- **Constraint:** `UNIQUE (user_id, date)` — um usuário só pode ter uma entrada por dia.
- UUIDs: uso de `symfony/uid` (Uuid v7 ou v4) nas entidades.

---

## 4. Endpoints da API (contrato)

Todos os que aparecem como “🔒” exigem header: `Authorization: Bearer {token}`.

| Método | Rota | Descrição | Protegido |
|--------|------|-----------|-----------|
| POST   | `/api/auth/register` | Criar conta | Não |
| POST   | `/api/auth/login`    | Login → retorna JWT | Não |
| POST   | `/api/auth/refresh`  | Renovar token | Não |
| GET    | `/api/me`            | Dados do usuário logado | Sim 🔒 |
| PATCH  | `/api/me`            | Atualizar displayName e/ou timezone | Sim 🔒 |
| GET    | `/api/today`         | Entrada de hoje (ou null) | Sim 🔒 |
| POST   | `/api/today`         | Check-in manhã (criar intenção) | Sim 🔒 |
| PATCH  | `/api/today/complete`| Check-in noite (completed: true/false) | Sim 🔒 |
| PATCH  | `/api/today/skip`    | Marcar dia como pulado | Sim 🔒 |
| GET    | `/api/history?month=YYYY-MM` | Entradas do mês + summary | Sim 🔒 |
| GET    | `/api/history/recent`| Últimas 7 entradas | Sim 🔒 |

---

## 5. Regras de negócio (resumo)

- **POST /api/today:**  
  - Data do “hoje” conforme **timezone do usuário**, não UTC.  
  - Se já existir entrada para esse usuário nessa data e não estiver `skipped`, retornar **409 Conflict**.  
- **PATCH /api/today/complete:**  
  - Se não existir entrada de hoje para o usuário → **404**.  
  - Atualizar `completed` e `updatedAt`.  
- **GET /api/history?month=YYYY-MM:**  
  - Validar formato `month` (regex `YYYY-MM`).  
  - Retornar entradas do mês + `summary` (total_days, completed, not_completed, skipped).  
  - Ordenar por `date` ASC.

---

## 6. Exemplos de contratos JSON (conforme escopo)

- **POST /api/auth/register**  
  - Request: `{ "email", "password", "displayName" }`  
  - Response 201: `{ "token", "user": { "id", "email", "displayName" } }`

- **POST /api/today**  
  - Request: `{ "intention": "Texto da única coisa" }`  
  - Response 201: `{ "id", "date", "intention", "completed", "skipped", "createdAt" }`

- **PATCH /api/today/complete**  
  - Request: `{ "completed": true }` ou `{ "completed": false }`  
  - Response 200: objeto da entrada atualizado (incluindo `updatedAt`).

- **GET /api/history?month=2025-02**  
  - Response 200: `{ "month", "entries": [ ... ], "summary": { "total_days", "completed", "not_completed", "skipped" } }`

---

**Próximo:** [Etapa 2 — Configuração e modelo de dados](etapa-2-configuracao-e-modelo.md)
