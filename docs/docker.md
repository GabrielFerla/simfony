# Rodar o Symfony no Docker

## O que existe

- **Serviço `app`**: container PHP 8.4 que sobe o servidor embutido na porta 8000.
- **Serviço `database`**: PostgreSQL 16 (já existia).

A aplicação no container usa o hostname **`database`** para conectar no banco (rede interna do Docker).

## Comandos

### Subir tudo (app + banco)

```bash
docker compose up -d
```

Na primeira vez a imagem da `app` é construída e o `composer install` roda dentro do container. A API fica em **http://localhost:8000**.

### Só o banco (Symfony rodando no seu PC)

```bash
docker compose up -d database
```

Aí você roda o Symfony na máquina (`symfony serve` ou `php -S 0.0.0.0:8000 -t public`) e o `.env` continua usando `127.0.0.1:5432`.

### Logs da aplicação

```bash
docker compose logs -f app
```

### Rodar comandos Symfony dentro do container

```bash
docker compose exec app php bin/console doctrine:migrations:migrate
docker compose exec app php bin/console cache:clear
```

### Parar

```bash
docker compose down
```

Com `docker compose down -v` os volumes (banco e `vendor` da app) são removidos.

## Variáveis

No `compose.yaml` a `app` usa:

- **DATABASE_URL** com host `database` (nome do serviço).
- **APP_PORT**: porta no host (padrão 8000). Ex.: `APP_PORT=9000 docker compose up -d`.
- **JWT_SECRET_KEY**, **JWT_PUBLIC_KEY**, **JWT_PASSPHRASE**: lidos do seu `.env`; no container os caminhos são `/app/config/jwt/private.pem` e `/app/config/jwt/public.pem`.

No `.env` do projeto você não precisa mudar nada para “rodar no Docker”: a `DATABASE_URL` da aplicação em container é definida pelo Compose.

## JWT (token) — erro “encode the JWT token / verify your configuration”

O login e o registro retornam um token JWT. As chaves ficam em `config/jwt/` (arquivos `.pem`), que **não vão para o Git**. Se essa pasta estiver vazia ou sem os `.pem`, a API no Docker falha ao gerar o token.

### Solução: gerar as chaves JWT dentro do container

1. Suba os serviços:
   ```bash
   docker compose up -d
   ```
2. Gere o par de chaves **dentro do container** (assim os arquivos são criados na pasta montada do projeto):
   ```bash
   docker compose exec app php bin/console lexik:jwt:generate-keypair
   ```
3. Se o comando pedir **passphrase**, use uma e guarde. Depois coloque a mesma no `.env`:
   ```env
   JWT_PASSPHRASE=sua_passphrase_aqui
   ```
4. Reinicie a app para carregar as variáveis (ou só faça uma nova requisição de login):
   ```bash
   docker compose restart app
   ```

Assim as chaves ficam em `config/jwt/private.pem` e `config/jwt/public.pem` no seu projeto (montado no container) e o `JWT_PASSPHRASE` do `.env` deve ser o mesmo usado na geração. O token passa a ser gerado e validado corretamente no Docker.
