# Packistry

Packistry is a self-hosted Composer repository designed to handle your PHP package distribution. It supports importing from multiple sources like GitHub, GitLab, and Gitea, with seamless updates using webhooks. Packistry allows you to effortlessly run your own composer repository with just a few commands, giving you full control over your packages, access management, and security.

- Explore our docs at **[https://packistry.github.io/docs/ »](https://packistry.github.io/docs/)**

### Features

- **Private Repository Support**: Keep your sensitive or proprietary packages secure by hosting them in private repositories.

- **Token-Based Authentication**: Ensure secure access to your repositories with token-based authentication. This allows you to manage permissions for both users and automated systems (machines), providing granular control over who can view or modify your repositories.

- **Package Source Integration**: Easily manage and import Composer packages from various platforms:
    - **GitHub**
    - **GitLab**
    - **Gitea**

  Stays up to date automatically, as Packistry uses **webhooks** to pull the latest changes from your source repositories.

- **Comprehensive Repository Management**:
    - **Public/Private Repository Options**: Define repositories as public or private based on your project needs.
    - **Human Access Control**: Create user accounts to assign and manage access to your private repositories, ensuring only authorized individuals can interact with sensitive content.
    - **Machine Access Control**: Generate deploy tokens to allow machines (e.g., build systems or CI/CD pipelines) to access private repositories, ensuring smooth, secure automation.

Packistry combines ease of use, flexibility, and security to give you complete control over your PHP package distribution in a self-hosted environment. Whether you're managing a private project, a team of developers, or an open-source initiative, Packistry streamlines your workflow with minimal configuration and maximum control.
