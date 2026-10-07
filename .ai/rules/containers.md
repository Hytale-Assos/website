---
paths:
  - 'container/**'
  - '.dockerignore'
  - '.github/workflows/build.yml'
---

# Containers

## Layout

- `container/Containerfile`: production image, OCI-generic (Podman first).
- `container/systemd/`: Quadlet files, copied at deploy time to `~/.config/containers/systemd/` (rootless) or `/etc/containers/systemd/`.
- `.dockerignore`: build context filter, must live at the context root, not next to the Containerfile.

## Containerfile

- Pin every base image as `tag@sha256:<digest>` using the multi-arch OCI index digest. Bump the tag and the digest together, in the same commit.
- Keep the version catalog as `ARG` lines at the top. `COPY --from=` does not support ARG expansion, but `FROM` does, so declare named source stages for each external image and copy from them by stage name.
- Two stages only: `build` and the runtime. Do not try to split PHP and bun into separate stages: the Wayfinder Vite plugin runs `php artisan wayfinder:generate` during `bun run build`, so one stage must contain PHP, composer, and the bun binary.
- Keep the install layers cacheable in this order: `composer install` (from composer.json/lock), `bun install` (from package.json/bun.lock), then `COPY . .`.
- `RUN rm -rf node_modules` at the end of the build stage. `.dockerignore` does not filter `COPY --from=`, so the runtime copies whatever the build stage still contains.
- The runtime is unprivileged: create a dedicated user, remove capabilities from the frankenphp binary, `COPY --chown` the app tree, then `chmod -R ug+rwX storage bootstrap/cache` (capital X: directories and already-executable files only).
- `CMD` must pass `--config /etc/caddy/Caddyfile` explicitly: `frankenphp run` without it looks for a Caddyfile in the working directory and starts with an empty config.
- Defaults live in `ENV`: `APP_ENV=production`, `APP_DEBUG=false`, `INERTIA_SSR_ENABLED=false`, `LOG_CHANNEL=stderr`, `SERVER_NAME=":8080"` (unprivileged port, published by the host as `80:8080`). Never bake secrets or a `.env` into the image.

## .dockerignore

Must exclude at minimum: `.env*` (secrets), `bootstrap/cache` (a stale `packages.php` referencing dev packages breaks `package:discover` in a `--no-dev` image), `storage`, `node_modules`, `vendor`, `container/`, tests, docs, and dotfolders. `bootstrap/ssr` and `public/build` are build outputs and stay excluded too.

## Verification

Before pushing a Containerfile change: build the image, then smoke-test it: run the container, check `php artisan --version` boots, hit `/up` and `/login` for HTTP 200, and confirm the process user with `podman exec <container> id`. If the build fails on package discovery, check what leaked into the context.

## Quadlet files

- One systemd unit per file; the unit name equals the file name. Prefix everything with `hytale-`.
- Peer networks linking exactly two containers are named `hytale-<container-a>-hytale-<container-b>`. Networks with more members get an intelligible functional name. Never put all services on one shared flat network: each dependency pair gets its own network, the website is the only bridge.
- Volumes are named after their owning container: `hytale-postgres-data.volume`.
- Secrets never live in the repo. Sensitive values are Podman secrets injected with `Secret=<name>,type=env,target=<VAR>` and provisioned on the host with `podman secret create`. They are resolved at container creation: rotation means recreating the secret and restarting the unit. A missing secret makes systemd fail to start the unit.
- Secret names follow the unit prefixes: `hytale-website-*`, `hytale-postgres-*`.
- Non-secret configuration lives in an env file on the host, provisioned from `container/website.env.example`; the template never contains secret values. `DB_HOST` and `REDIS_HOST` are Podman container names (`hytale-postgres`, `hytale-redis`), not the Sail dev names.
- Rootless deployment layout: quadlets in `~/.config/containers/systemd/`, env files in `~/hytale/env/`, secret source files in `~/hytale/secrets/`.
- Wire dependencies in the `[Unit]` section (`Requires=`/`After=` on the generated service names). `Restart=always` goes in `[Service]`. Every container declares a `HealthCmd`.
- Hardening is mandatory: `NoNewPrivileges=true` everywhere; run application images as their non-root user (`User=postgres`, `User=redis`, or the image USER) so `DropCapability=ALL` is safe; `ReadOnly=true` with a `Tmpfs=` per writable path. Do not pass `uid=`/`gid=` to `Tmpfs=`: the kernel's tmpfs supports it but Podman 5.8 rejects it (`unknown mount option "uid=..."`) and the unit fails to start. Give the non-root user write access with `mode=1777` (sticky, world-writable, fine for single-purpose containers on a read-only rootfs), or use a `Mount=type=tmpfs,destination=...,tmpcopyup` to inherit the image path ownership where the path exists. Do not add capabilities back: the named volume copy-up provides the data dir ownership for the official images.
- `AutoUpdate=registry` only on the application container, never on databases.
- Escape environment variables in systemd-inherited values as `$$` (`HealthCmd=pg_isready -q -d $$POSTGRES_DB`).

## CI workflow

`.github/workflows/build.yml` builds the image on every pull request targeting `develop` and on every push to `develop` (verification only, no publish), and builds and pushes to `ghcr.io/hytale-assos/website` on every push to `main` with two tags: `latest` and `sha-<short commit>`. Pin GitHub Actions by commit SHA with the version in a comment. The registry namespace is lowercase.
