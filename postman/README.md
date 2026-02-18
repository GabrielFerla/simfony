# Postman – Symfony API (Docker)

Coleção e ambiente para testar a API do projeto com o app rodando no Docker.

## Pré-requisito

API no ar:

```bash
docker compose up -d
```

A API fica em **http://localhost:8000**.

## Importar no Postman

1. Abra o Postman.
2. **Import** → arraste ou selecione:
   - `Symfony-API.postman_collection.json`
   - (opcional) `Symfony-Docker.postman_environment.json`
3. Se importou o environment, selecione **Symfony Docker** no canto superior direito.

## Variáveis

| Variável   | Valor padrão           | Uso |
|-----------|------------------------|-----|
| `base_url` | `http://localhost:8000` | URL base da API (Docker). Altere se usar outra porta (ex.: 9000). |
| `token`    | *(vazio)*              | Preenchido automaticamente após **Login** ou **Register**. |

## Fluxo sugerido

1. **Auth → Register** ou **Auth → Login**  
   - O script da request salva o JWT em `token`.
2. Chamar qualquer outra request (Me, Today, History).  
   - Todas usam `Authorization: Bearer {{token}}`.

## Endpoints na collection

- **Auth:** Register, Login, Refresh Token  
- **Me:** Get Profile, Update Profile  
- **Today:** Get Today, Create/Update Today, Complete Today, Skip Today  
- **History:** Recent (7 days), Month (`?month=YYYY-MM`)

## Porta diferente

Se você subir com outra porta (ex.: `APP_PORT=9000 docker compose up -d`), altere `base_url` para `http://localhost:9000` nas variáveis da collection ou do environment.
