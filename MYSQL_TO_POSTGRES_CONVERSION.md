# MySQL to PostgreSQL Conversion Guide

## Quick Manual Conversion Steps

### 1. Common Data Type Conversions

| MySQL | PostgreSQL |
|-------|------------|
| `int(11)` | `INTEGER` |
| `bigint(20)` | `BIGINT` |
| `varchar(255)` | `VARCHAR(255)` |
| `text` | `TEXT` |
| `longtext` | `TEXT` |
| `datetime` | `TIMESTAMP` |
| `timestamp` | `TIMESTAMP` |
| `date` | `DATE` |
| `time` | `TIME` |
| `decimal(10,2)` | `DECIMAL(10,2)` |
| `float` | `REAL` |
| `double` | `DOUBLE PRECISION` |
| `tinyint(1)` | `BOOLEAN` |
| `bool` | `BOOLEAN` |

### 2. Auto Increment Conversion

**MySQL:**
```sql
id INT(11) AUTO_INCREMENT PRIMARY KEY
```

**PostgreSQL:**
```sql
id SERIAL PRIMARY KEY
```

### 3. Remove MySQL-Specific Commands

Remove these lines from your SQL file:
- `SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";`
- `SET AUTOCOMMIT = 0;`
- `START TRANSACTION;`
- `SET time_zone = "+00:00";`
- `/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;`
- `LOCK TABLES` and `UNLOCK TABLES`
- `ENGINE=InnoDB`
- `CHARSET=utf8mb4`
- `COLLATE=utf8mb4_unicode_ci`

### 4. Convert Backticks to Quotes

**MySQL:**
```sql
CREATE TABLE `users` (
  `id` int(11) AUTO_INCREMENT,
  `name` varchar(255)
);
```

**PostgreSQL:**
```sql
CREATE TABLE "users" (
  "id" SERIAL,
  "name" VARCHAR(255)
);
```

### 5. Fix Default Values

**MySQL:**
```sql
created_at TIMESTAMP DEFAULT '0000-00-00 00:00:00'
```

**PostgreSQL:**
```sql
created_at TIMESTAMP DEFAULT NULL
```

### 6. Convert Boolean Values

**MySQL:**
```sql
is_active TINYINT(1) DEFAULT 1
```

**PostgreSQL:**
```sql
is_active BOOLEAN DEFAULT TRUE
```

## Automated Conversion

### Option 1: Use the PHP Script
```bash
php convert_mysql_to_postgres.php your_mysql_file.sql converted_postgres.sql
```

### Option 2: Online Converters
- **MySQL to PostgreSQL Online Converter**
- **DBConvert**
- **Full Convert**

### Option 3: Manual Steps
1. **Open your MySQL SQL file**
2. **Apply the conversions above**
3. **Save as new file**
4. **Test the converted SQL**

## Testing the Converted SQL

1. **Create Postgres database on Render**
2. **Import the converted SQL**
3. **Test database connection**
4. **Verify tables and data**

## Common Issues and Fixes

### Issue: Syntax Errors
**Fix:** Check for MySQL-specific functions and convert them

### Issue: Data Type Errors
**Fix:** Use the conversion table above

### Issue: Constraint Errors
**Fix:** PostgreSQL has stricter constraints than MySQL

## Next Steps

1. **Convert your SQL file**
2. **Create Postgres database on Render**
3. **Import converted SQL**
4. **Update environment variables**
5. **Test your application**
