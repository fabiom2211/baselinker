# Baselinker
Tech task Baselinker

This is a project that uses **PHP** and **Nginx** in a Docker container to create a local development environment.

## Prerequisites

- **Docker** and **Docker Compose** installed on your system.

## Initial Setup

### Step 1: Configuring the `hosts` File

To access the project in your browser using `baselinker.localhost`, follow the steps below to add an entry to your system's `hosts` file.

1. Open the `hosts` file:
   - **Linux / Mac**: 
     ```shell
     sudo vi /etc/hosts
     ```
   - **Windows**: Open **Notepad** as administrator and edit the file at
     ```shell
     C:\Windows\System32\drivers\etc\hosts
     ```

2. Add the following line at the end of the file:
    ```shell
    127.0.0.1 baselinker.localhost
    ```

### Step 2: Cloning the Repository

Clone this repository and navigate to the project directory:

```shell
git clone https://github.com/fabiom2211/baselinker
cd baselinker
```

### Step 3: Starting the Container
```shell
docker-compose up -d
```

### Step 4: Accessing the Project
```shell
http://baselinker.localhost

```

