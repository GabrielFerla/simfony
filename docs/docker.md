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

No `.env` do projeto você não precisa mudar nada para “rodar no Docker”: a `DATABASE_URL` da aplicação em container é definida pelo Compose.
