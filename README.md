# Conductor

Conductor is a self-hosted Composer repository server designed to handle your Composer package distribution. It supports importing from multiple sources like GitHub, GitLab, and Gitea, with seamless updates using webhooks. Conductor allows you to effortlessly run your own composer repository with just a few commands, giving you full control over your packages, access management, and security.

### Features

- **Private Repositories**: Protect your sensitive packages with private repository support.
- **Access Tokens**: Set up secure token-based authentication for granular control over who can access or modify your repositories.
- **Package Source Providers**: Easily add Composer packages from multiple sources including:
  - **GitHub**
  - **GitLab**
  - **Gitea**
  - Conductor stays updated automatically with the latest changes via **webhooks**.

- **Multiple Repository Management**
  - **Public/Private Repositories**: Set your repositories as either public or private depending on your needs.
  - **Access Tokens**: Use secure access tokens to manage who can view or contribute to your repositories.

- **Deploy With Docker**
  - Conductor is **dockerized**, making it extremely easy to deploy. Get your server running in seconds:

### Getting Started

1. **Run the Server:**
   Start Conductor locally or on your cloud platform with Docker:
   ```bash
   docker run -p 80:80 ghcr.io/maantje/conductor
   ```

2. **[Add Package Sources](#adding-a-new-package-source)**:
   Use sources to connect your repositories from GitHub, GitLab, or Gitea, and let Conductor handle the rest.

3. **[Import Package](#importing-packages-from-a-source)**:
   Import a repository from your package source as package, once set-up conductor will create the necessary webhooks to keep your package up to date with the latest tags and branches

4. **Add repository to composer.json**  
    Replace `[url]` with e.g. `http://localhost`, `https://domain.com`, `https://sub.domain.com:1234`
   ```bash
   composer config repositories.conductor composer [url]
   ```
5. **Optionally, Authenticate with repository**  
    Replace `[url]` with e.g. `localhost`, `domain.com`, `sub.domain.com:1234`
    ```bash
    composer config bearer.[url] "1|Q38MkoeJYOqTRgNlbn0M78Ktjxu77YRiG7MvlITO25d5ff86"  
    ```
6. **Optionally, allow insecure http connection**
    ```bash
    composer config secure-http false  
    ```
7. **Install package from repository**
   ```bash
   composer require vendor/name
   ```

#### Adding a New Package Source

Conductor provides a simple and interactive way to add new sources via an Artisan command. This command allows you to add sources such as GitHub, GitLab, or Gitea to your Conductor server, ensuring that you can manage your Composer packages from multiple repositories seamlessly.

To add a new package source, use the following command:

```bash
php artisan app:add-package-source
```

This command will guide you through a series of prompts to configure your package source:

1. **Package Source Name**:  
   You'll be prompted to enter the name of your package source.

2. **Provider Selection**:  
   Choose the package source provider from a list of available options like GitHub, GitLab, or Gitea.

3. **Base URL**:  
   Enter the base URL of your package source (e.g., `https://github.com`).

4. **Access Token**:  
   For private provider, you will be prompted to provide an access token to authenticate with the package source provider. The token is encrypted before being stored in the database.

Once you've provided all the required information, the package source will be saved to your system.

#### Importing Packages from a Source

Conductor allows you to import packages directly from a package source (e.g., Gitea, GitHub, Gitlab).

To import packages, you can run the following command:

```bash
php artisan app:import-package
```

1. **Selecting a Repository**:  
   You'll be prompted to choose whether to import the package into the main repository or a sub-repository.

2. **Choosing a Package Source**:  
   After selecting the repository, you will be prompted to select the package source from which to import packages.

3. **Import Process**:  
   Once the import is successful, Conductor will display the name of the imported repository along with a list of:
    - **Branches**: All branches detected from the package source.
    - **Tags**: All tags associated with the package in the selected source.
