# GreenQL

GreenQL is the framework query/script language for GBDB.

## Core commands

```gql
GROW INSTANCE demo;
USE INSTANCE demo;
SHOW INSTANCES;
GROW BASE main;
ROOT main;
SHOW BASES;
GROW TABLE users (uid, name, active);
SHOW TABLES;
SEED users WITH uid="u1", name="Max", active=true;
PICK * FROM users WHERE uid = "u1";
RESHAPE users WITH name="Max M." WHERE uid = "u1";
ERASE FROM users WHERE uid = "u1";
```

## ENV

```gql
OUTPUT ENV("api_auth");
```

ENV reads from `.config/.greenql.env.php`.
