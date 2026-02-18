# Etapa 3 — API e implementação

Esta etapa descreve os **controllers** da API, as **regras de negócio** aplicadas neles, a **ordem de implementação** sugerida e o que **não** será feito (fora do escopo).

---

## 1. Controllers (resumo do que cada um faz)

### 1.1 AuthController (src/Controller/Api/AuthController.php)

| Ação | Rota | Descrição |
|------|------|-----------|
| **register** | POST /api/auth/register | Valida body (email, password, displayName). Cria User com senha em bcrypt, persiste, gera token JWT e retorna `{ token, user }`. |
| **login** | POST /api/auth/login | Pode ser delegado ao Lexik (rota que recebe credentials e retorna JWT). |
| **refresh** | POST /api/auth/refresh | Renovar token (conforme config do Lexik). |

### 1.2 MeController (src/Controller/Api/MeController.php)

| Ação | Rota | Descrição |
|------|------|-----------|
| **get** | GET /api/me | Retorna o usuário autenticado (serializado: id, email, displayName, timezone, etc.). |
| **patch** | PATCH /api/me | Atualiza displayName e/ou timezone. Valida input, persiste e retorna usuário atualizado. |

### 1.3 TodayController (src/Controller/Api/TodayController.php)

| Ação | Rota | Descrição |
|------|------|-----------|
| **get** | GET /api/today | Busca entrada de “hoje” (data calculada pelo **timezone do usuário**). Retorna objeto ou `null`. |
| **post** | POST /api/today | Valida `intention`. Se já existir entrada para hoje (e não skipped) → **409 Conflict**. Cria DailyEntry com data “hoje” no timezone do usuário. Retorna 201 + entrada. |
| **complete** | PATCH /api/today/complete | Busca entrada de hoje. Se não existir → **404**. Atualiza `completed` e `updatedAt`. Retorna 200 + entrada. |
| **skip** | PATCH /api/today/skip | Busca entrada de hoje. Marca `skipped`. (Comportamento exato pode ser: criar entrada só com skipped ou atualizar existente — conforme regra de negócio definida.) |

### 1.4 HistoryController (src/Controller/Api/HistoryController.php)

| Ação | Rota | Descrição |
|------|------|-----------|
| **month** | GET /api/history?month=YYYY-MM | Valida formato `month` (regex YYYY-MM). Busca todas as DailyEntry do usuário no mês. Monta **summary** (total_days, completed, not_completed, skipped). Retorna `{ month, entries, summary }`. Entradas ordenadas por `date` ASC. |
| **recent** | GET /api/history/recent | Retorna as últimas 7 entradas do usuário (fallback para tela de histórico). |

Rotas com prefixo `/api`; métodos HTTP e atributos `#[Route]` conforme tabela da Etapa 1.

---

## 2. Regras de negócio (reforço)

- **Data “hoje”:** sempre com base no **timezone do usuário** (campo `User::timezone`), não em UTC.
- **POST /api/today:** não permitir duas entradas para o mesmo usuário na mesma data (exceto se uma for “pulada” conforme regra); retornar **409** se já existir entrada não skipped.
- **PATCH /api/today/complete:** retornar **404** se não houver entrada de hoje.
- **GET /api/history:** validar parâmetro `month` (YYYY-MM); summary com total de dias no mês, completed, not_completed, skipped.

---

## 3. Ordem sugerida de implementação

1. Instalar pacotes Composer e configurar JWT (chaves).
2. Configurar `.env` (DATABASE_URL, JWT).
3. Registrar bundles e criar arquivos em `config/packages/` (doctrine, security, lexik_jwt, nelmio_cors).
4. Criar entidades `User` e `DailyEntry` e repositórios.
5. Gerar e executar migration.
6. Implementar AuthController (register; login/refresh conforme Lexik).
7. Implementar MeController.
8. Implementar TodayController (GET, POST, PATCH complete, PATCH skip) com regras de timezone e 409/404.
9. Implementar HistoryController (month + summary, recent).
10. Ajustar CORS e testar com Postman/Insomnia (e depois com o Angular).

---

## 4. O que **não** será feito neste backend (conforme escopo)

- Notificações push  
- Insights e análise de padrões  
- Streak tracker  
- Monetização e planos pagos  
- PWA offline / Service Worker  

O frontend Angular (login, registro, /home, /history, /settings) fica em **outro projeto**; esta documentação refere-se apenas ao **backend Symfony**.

---

## 5. Como validar o plano

- **Estrutura e API:** conferir [Etapa 1](etapa-1-visao-e-estrutura.md) (árvore, endpoints, contratos JSON).
- **Config e modelo:** conferir [Etapa 2](etapa-2-configuracao-e-modelo.md) (pacotes, .env, entidades, migrations).
- **Implementação:** conferir esta Etapa 3 (controllers, regras, ordem de execução).

Quando estiver de acordo, você pode pedir para **executar o plano** ou **implementar a partir do passo X**. Nada será executado até sua confirmação.

— *Só Uma Coisa: foco total. todo dia.*
