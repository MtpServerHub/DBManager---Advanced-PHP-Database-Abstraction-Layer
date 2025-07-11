# DBManager - Advanced PHP Database Abstraction Layer

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

DBManager is a powerful, secure, and lightweight PHP database abstraction layer.  
It offers a fluent API for query building, full transaction management, SQL safety, and clean code structure.

---

## 📂 Directory Structure

```
DBManager/
├── autoload.php
├── cache/
│   ├── SQLKeywords.gz
│   └── SQLList.php
├── docs/
│   ├── auxiliaryMethods.html
│   ├── bindings.html
│   ├── connect.html
│   ├── escape.html
│   ├── execute.html
│   ├── fetchMethods.html
│   ├── modes.html
│   ├── newOperation.html
│   ├── operators.html
│   ├── query.html
│   ├── QueryBuilder.html
│   ├── QuickStart.html
│   ├── setAttr.html
│   └── transaction.html
├── examples/
│   ├── BankSystem.php
│   └── ECommerceSystem.php
├── src/
│   ├── DBManager.php
│   ├── DBInterface.php
│   ├── OPInterface.php
│   ├── enums/
│   │   └── MODE.php
│   ├── QueryBuilder/
│   │   ├── QueryBuilder.php
│   │   └── traits/
│   │       ├── qb_buildQuery.php
│   │       ├── qb_CRUD.php
│   │       ├── qb_operators.php
│   │       └── qb_whereClause.php
│   └── traits/
│       ├── bindParameter.php
│       ├── bindValue.php
│       ├── connect.php
│       ├── define.php
│       ├── escape.php
│       ├── execute.php
│       ├── fetch.php
│       ├── fetchAll.php
│       ├── fetchObject.php
│       ├── newOperation.php
│       ├── operators.php
│       ├── options.php
│       ├── query.php
│       ├── setAttr.php
│       ├── tidy.php
│       └── transaction.php
```

---

## ✨ Features

- 🔐 SQL Injection protection (safe parameter binding)
- 🔄 Full transaction support
- 🔧 Powerful query builder with traits-based modularity
- 🧪 Easily testable (see `/examples`)
- 📄 Full HTML documentation (see `/docs` folder)
- 🧩 Supports MODE flags for advanced behavior

---

## 🚀 Getting Started

```php
require_once 'path/to/autoload.php';

use DBManager\src\DBManager;

DBManager::prepare();
DBManager::define('localhost', 'mydb', 'dbname', 'user', 'pass');
DBManager::connect('mydb');

// Example query
$data = DBManager::fetch("SELECT * FROM users WHERE id = :id", ['id' => 1], 'mydb');
```

---

## 🧪 Examples

- `examples/BankSystem.php`: Sample banking system test class
- `examples/ECommerceSystem.php`: Sample shop system test class (custom test)

---

## 📚 Documentation

All documentation is located in the `docs/` directory in HTML format:

- 🔗 `docs/QuickStart.html` – Start here
- 🔧 `docs/QueryBuilder.html` – Learn how to use the query builder
- ⚙️ `docs/connect.html`, `docs/execute.html`, `docs/transaction.html`, and more...

Open them directly in a browser for easy reading.

---

## 📝 License

<<<<<<< HEAD
This project is licensed under the [MIT License](LICENSE).
=======
This project is licensed under the [MIT License](LICENSE).
>>>>>>> 5c0034f (Initial commit: Add DBManager core files and documentions)
