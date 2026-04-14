# Running Packistry with Docker

You can run Packistry quickly with Docker.

## 1. Generate an application key

```bash
echo APP_KEY=base64:$(openssl rand -base64 32)
```

## 2. Start the container

Replace `REPLACE_WITH_VALUE_FROM_STEP_ABOVE` with the generated key.

```bash
docker run -p 80:80 -e APP_KEY=REPLACE_WITH_VALUE_FROM_STEP_ABOVE -v ./:/data ghcr.io/packistry/packistry:latest
```

## 3. Create the first user

```bash
docker exec -it $(docker ps | grep ":80->" | awk '{print $1}') packistry add:user
```

## 4. Open Packistry

Open http://localhost in your browser and sign in with the created user.

Note: Webhooks are usually not delivered to localhost.

## Additional Documentation

- https://packistry.github.io/
