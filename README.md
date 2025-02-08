# Packistry

Packistry is a self-hosted Composer repository designed to handle your PHP package distribution. It supports importing from multiple sources like GitHub, GitLab, Gitea and Bitbucket, with seamless updates using webhooks. Packistry allows you to effortlessly run your own composer repository with just a few commands, giving you full control over your packages, access management, and security.

- Explore our docs at **[https://packistry.github.io/ »](https://packistry.github.io/)**

### Features

- **Private Repository Support**: Keep your sensitive or proprietary packages secure by hosting them in private repositories.

- **Token-Based Authentication**: Ensure secure access to your repositories with token-based authentication. This allows you to manage permissions for both users and automated systems (machines), providing granular control over who can view or modify your repositories.

- **Package Source Integration**: Easily manage and import Composer packages from various platforms:
    - **GitHub**
    - **GitLab**
    - **Gitea**
    - **Bitbucket**

  Stays up to date automatically, as Packistry uses **webhooks** to pull the latest changes from your source repositories.

- **Comprehensive Repository Management**:
    - **Public/Private Repository Options**: Define repositories as public or private based on your project needs.
    - **Human Access Control**: Create user accounts to assign and manage access to your private repositories, ensuring only authorized individuals can interact with sensitive content.
    - **Machine Access Control**: Generate deploy tokens to allow machines (e.g., build systems or CI/CD pipelines) to access private repositories, ensuring smooth, secure automation.

Packistry combines ease of use, flexibility, and security to give you complete control over your PHP package distribution in a self-hosted environment. Whether you're managing a private project, a team of developers, or an open-source initiative, Packistry streamlines your workflow with minimal configuration and maximum control.

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

