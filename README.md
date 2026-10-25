# Packistry

Packistry is a self-hosted Composer repository designed to handle your PHP package distribution. It supports importing from multiple sources like GitHub, GitLab, Gitea and Bitbucket, with seamless updates using webhooks. Packistry allows you to effortlessly run your own composer repository with just a few commands, giving you full control over your packages, access management, and security.

- Explore the docs at **[https://packistry.github.io/ »](https://packistry.github.io/)**

### Features

- **Private Repository Support**: Keep your proprietary packages secure by hosting them in private repositories.

- **Token-Based Authentication**: Ensure secure access to your repositories with token-based authentication. This allows you to manage permissions for both users and automated systems (machines), providing granular control over who can view or modify your repositories.

- **Package Source Integration**: Easily manage and import Composer packages from various platforms:
    - **GitHub**
    - **GitLab**
    - **Gitea**
    - **Bitbucket**

  Stays up to date automatically, as Packistry uses **webhooks** to pull the latest changes from your source.

- **Comprehensive Repository Management**:
    - **Public/Private Repository Options**: Define repositories as public or private based on your project needs.
    - **Human Access Control**: Create user accounts to assign and manage access to your private repositories, ensuring only authorized individuals can interact with sensitive content.
    - **Machine Access Control**: Generate deploy tokens to allow machines (e.g., build systems or CI/CD pipelines) to access private repositories, ensuring smooth, secure automation.

- **Upstream Mirroring**: Serve packages from Packagist, GitLab groups, Private Packagist or any other Composer 2 repository through Packistry. Packistry caches their metadata and dists, so installs keep working while an upstream is down, and clients only need one repository and one Packistry token.

- **Authentication Sources**: Packistry supports **Single Sign-On (SSO)** through OAuth 2.0 and OpenID Connect.
    - **OpenID Connect**: Authenticate using an OIDC provider, such as Okta or Microsoft Azure AD (Entra ID).
    - **GitHub**: Authenticate using your GitHub account.
    - **GitLab**: Authenticate using your GitLab credentials.
    - **Bitbucket**: Authenticate via Bitbucket's OAuth.
    - **Google**: Use Google authentication for login.

Packistry combines ease of use, flexibility, and security to give you complete control over your PHP package distribution in a self-hosted environment. Whether you're managing a private project, a team of developers, or an open-source initiative, Packistry streamlines your workflow with minimal configuration and maximum control.

### Running Packistry

You can easily run Packistry using Docker.

First, generate an app key:
```
echo APP_KEY=base64:$(openssl rand -base64 32)
```

Set the APP_KEY and start the container:

```
docker run -p 80:80 -e APP_KEY=REPLACE_WITH_VALUE_FROM_STEP_ABOVE -v ./:/data ghcr.io/packistry/packistry:latest
```

Run the following command to create a user:

```
docker exec -it $(docker ps | grep ":80->" | awk '{print $1}') packistry add:user
```

Now, open http://localhost in your browser and log in with the newly created user. Note: Webhooks will not be delivered to localhost.

- Explore the docs at **[https://packistry.github.io/ »](https://packistry.github.io/)** for more options to run Packistry.

### Upstream mirroring

Upstreams are Composer repositories that Packistry mirrors, for example Packagist or the package registry of a GitLab group. Add them under **Upstreams** and attach them to a repository (**Repositories → Upstreams**) in priority order. When a package is requested that the repository does not have, Packistry asks the upstreams from top to bottom: the first upstream that has the package serves it, versions are never merged and your own packages always win.

Packistry caches the metadata (revalidated every 5 minutes) and downloads every dist once. When an upstream is down, cached metadata and dists keep being served.

- **Types**: an upstream is either **Packagist** (no URL needed, with an optional GitHub token, recommended because GitHub limits anonymous dist downloads to 60 per hour) or a **Composer repository** (any Composer 2 repository, such as a GitLab group registry, Private Packagist, Satis or another Packistry).
- **Credentials** of Composer repositories are configured per host and only sent to that host. A GitLab group typically needs an HTTP Basic credential for its host (a deploy token with `read_package_registry`).
- **Filters** (`only` / `exclude`, e.g. `acme/*`) limit which packages an upstream is asked for. Use them for private upstreams, so lookups stay fast and the names of private packages are never sent to other upstreams like Packagist.
- **Access**: tokens with access to a repository can download all of its mirrored packages. Deploy tokens, users and authentication sources can also be given access to specific upstreams. Upstreams that authenticate against their own host cannot be used by public repositories.

Clients only configure Packistry. Keeping packagist.org enabled is optional, it is then only used for packages Packistry does not know:

```json
{
    "repositories": [
        { "type": "composer", "url": "https://packistry.example/r/company" },
        { "packagist.org": false }
    ]
}
```

```bash
composer config --global bearer.packistry.example YOUR_PACKISTRY_TOKEN
```

Existing lock files still point to the original dist urls, run `composer update --lock` once to download everything through Packistry.

Maintenance commands:

```bash
# cache everything a project needs, e.g. before relying on it during an outage
php artisan mirror:warm composer.lock --repository=company

# fetch new versions before the metadata cache expires
php artisan mirror:refresh acme/package

# remove dists and metadata that have not been used for 90 days (schedule this)
php artisan mirror:prune --unused-for=90

# remove download records older than a year, totals are kept
php artisan downloads:prune --older-than=365
```

Packistry never connects to private or reserved addresses (loopback, private networks, cloud metadata endpoints) on behalf of an upstream, including its metadata urls, dist urls and redirects, and only mirrors zip dists. Allow internal registries, such as a self-hosted GitLab, with `MIRROR_ALLOWED_HOSTS=gitlab.internal,satis.internal`.

Settings (environment variables, in seconds): `MIRROR_METADATA_TTL` (300), `MIRROR_NOT_FOUND_TTL` (600), `MIRROR_DOWN_TTL` (60), `MIRROR_ROOT_TTL` (86400), `MIRROR_CONNECT_TIMEOUT` (5), `MIRROR_TIMEOUT` (15), `MIRROR_DOWNLOAD_TIMEOUT` (300), `MIRROR_DOWNLOAD_STATS` (`full`, `counts` or `off`) and `MIRROR_ALLOWED_HOSTS` (comma separated, empty by default).

## Dependencies

Packistry is built on a solid foundation of well-maintained dependencies from both the PHP and JavaScript ecosystems, ensuring a high level of performance, security, and developer productivity. Here are some of the key dependencies that power Packistry:

### PHP Dependencies

- **[Laravel](https://laravel.com/)**: A powerful and elegant PHP framework that provides the core foundation of Packistry's architecture.
- **[Spiral RoadRunner](https://roadrunner.dev/)**: A high-performance PHP application server that improves request handling and enhances the scalability of Packistry.
- **[Pest](https://pestphp.com/)**: An intuitive and minimal testing framework that ensures the stability and reliability of our codebase.
- **[PHPStan](https://phpstan.org/)**: A static analysis tool that helps detect bugs and ensures code quality by performing rigorous type checks.
- **[Spatie Laravel Data](https://spatie.be/docs/laravel-data)**: A robust data-handling package that simplifies validation and transformation of data across Packistry.
- **[Spatie Query Builder](https://spatie.be/docs/laravel-query-builder)**: A powerful package that allows us to easily build clean, flexible, and reusable queries for filtering and sorting in our API.
- **[Barryvdh Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper)**: A package that enhances the development experience by generating helper files, providing accurate code completion, and improving IDE integration.

### JavaScript Dependencies

- **[TanStack Query](https://tanstack.com/query/latest)**: A powerful and flexible data-fetching library that simplifies state management for server-side data in React applications.
- **[TanStack Router](https://tanstack.com/router)**: A modern router library that provides advanced navigation capabilities, improving client-side routing in our React-based frontend.
- **[React](https://reactjs.org/)**: A declarative JavaScript library for building user interfaces, enabling the creation of dynamic and interactive frontend components.
- **[Tailwind CSS](https://tailwindcss.com/)**: A utility-first CSS framework that streamlines the styling process by providing a highly customizable and responsive design system.
- **[Vite](https://vitejs.dev/)**: A fast and efficient build tool that enhances the development experience by providing lightning-fast hot module replacement and optimized builds.

## Security Vulnerabilities

Please review [our security policy](./SECURITY.md) on how to report security vulnerabilities.

## License

Packistry is open-sourced software licensed under the [GPL-3.0](./LICENSE).

