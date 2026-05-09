# GBDB Framework / SecondServer Module

The **GBDB Framework** is a lightweight PHP framework built around a file-based database engine, a custom query language called **GreenQL**, a remote backend interface called **SecondServer**, and a modular public API system.

It is designed for projects that need a simple but powerful backend without requiring a traditional SQL database. The framework includes authentication, session and cookie helpers, JSON/file utilities, routing helpers, API modules, update/licensing plugins, remote database access, and a web-based GreenQL UI.

---

## What is GBDB?

**GBDB** stands for **GreenBucket Database**.

It is a file-based database system that stores data in structured files instead of relying on MySQL, MariaDB, PostgreSQL, or SQLite. GBDB is useful when you want:

- a lightweight backend
- simple deployment
- no external database server
- portable project folders
- isolated instances
- API-driven data access
- custom scripting through GreenQL

The framework supports both a classic GBDB engine and an instance-aware GBDBv2 engine.

---

## Main Features

The framework includes:

- File-based database engine
- Instance-based database separation
- GreenQL query/script language
- Authentication system
- JWT-based login sessions
- 2FA and email verification support
- Public API with API key permissions
- Modular public API extensions
- SecondServer remote backend module
- Remote database access through `SrvP`
- Cache, Cookie and Session helpers
- File, JSON, HTTP and validation helpers
- mRoot license and update plugin
- GreenQL UI / Admin interface
- Optional SQL bridge
- Documentation and developer recipes

---

## Typical Use Cases

GBDB is useful for projects such as:

- admin panels
- small to medium web applications
- API backends
- file-based CMS systems
- IoT dashboards
- internal tools
- museum or kiosk systems
- multi-instance applications
- self-hosted applications
- lightweight SaaS prototypes

It is especially useful when you want full control over the project folder and do not want to depend on an external database server.

---

## Project Structure

A typical framework project contains:

```txt
assets/
└── php/
    └── inc/
        ├── .config/
        │   └── _config.inc.php
        ├── gbdb_framework/
        │   ├── ENV.php
        │   ├── gbdb.php
        │   ├── core/
        │   ├── plugins/
        │   ├── public/
        │   ├── dev/
        │   ├── backend.php
        │   ├── public_api.php
        │   └── docs/
        ├── Srv.php
        └── functions.php

assets/
└── DB/
