# Mise en prod projet symfony

## Étape 1: Générer son certificat ssl et sa clé privée

```sh
mkdir -p ~/certs && cd ~/certs
openssl genrsa -out ca.key 4096
openssl req -x509 -new -nodes -key privkey.key -sha256 -days 3650 -out certificat.crt \
  -subj "/C=FR/ST=Paris/L=Paris/O=MyCA/CN=My Root CA"

```

## Étape 2: Volume du dossier certs dans le container du php (compose.yaml)

Dans compose.yaml

```yaml
volumes:
      - caddy_data:/data
      - caddy_config:/config
      - ./certs:/etc/caddy/certs
```

Dans compose.prod.yaml

Crée un .env.prod au préalable pour mettre toutes tes variables de prod

```yaml
services:
  php:
    build:
      context: .
      target: frankenphp_prod
    environment:
      env_file: '.env.prod'
      XDEBUG_MODE: "${XDEBUG_MODE:-off}"
      APP_SECRET: ${APP_SECRET}
      MERCURE_PUBLISHER_JWT_KEY: ${CADDY_MERCURE_JWT_SECRET}
      MERCURE_SUBSCRIBER_JWT_KEY: ${CADDY_MERCURE_JWT_SECRET}
    volumes:
      - ./frankenphp/Caddyfile.prod:/etc/caddy/Caddyfile:ro
```

## Étape 3: Ajout du certificat dans le Caddyfile.prod

```Caddyfile
{
	{$CADDY_GLOBAL_OPTIONS}

	frankenphp {
		{$FRANKENPHP_CONFIG}
	}
}

{$CADDY_EXTRA_CONFIG}

nom-de-domaine.fr, www.nom-de-domaine.fr {
	log {
		{$CADDY_SERVER_LOG_OPTIONS}
		# Redact the authorization query parameter that can be set by Mercure
		format filter {
			request>uri query {
				replace authorization REDACTED
			}
		}
	}

	root /app/public
	encode zstd br gzip

	mercure {
		# Transport to use (default to Bolt)
		transport_url {$MERCURE_TRANSPORT_URL:bolt:///data/mercure.db}
		# Publisher JWT key
		publisher_jwt {env.MERCURE_PUBLISHER_JWT_KEY} {env.MERCURE_PUBLISHER_JWT_ALG}
		# Subscriber JWT key
		subscriber_jwt {env.MERCURE_SUBSCRIBER_JWT_KEY} {env.MERCURE_SUBSCRIBER_JWT_ALG}
		# Allow anonymous subscribers (double-check that it's what you want)
		anonymous
		# Enable the subscription API (double-check that it's what you want)
		subscriptions
		# Extra directives
		{$MERCURE_EXTRA_DIRECTIVES}
	}

	vulcain

	{$CADDY_SERVER_EXTRA_DIRECTIVES}

	# Disable Topics tracking if not enabled explicitly: https://github.com/jkarlin/topics
	header ?Permissions-Policy "browsing-topics=()"

	@phpRoute {
		not path /.well-known/mercure*
		not file {path}
	}
	rewrite @phpRoute index.php

	@frontController path index.php
	php @frontController

	file_server

	# permet de spécifier les fichier pour le certificat ssl
	tls /etc/caddy/certs/fichier_certificat.pem /etc/caddy/certs/clé_privée.pem
}

```

## Lancement du projet

```docker compose -f compose.yaml -f compose.prod.yaml up -d --wait```