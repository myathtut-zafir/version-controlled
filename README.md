## 🕰️ Version-Controlled Key-Value Store

A RESTful API that implements a time-traveling key-value store. It allows storing values, retrieving the latest version, and querying the state of a key at any specific point in time.

![Tests](https://github.com/myathtut-zafir/version-controlled/actions/workflows/phpunit.yml/badge.svg)
![Coverage](https://img.shields.io/badge/coverage-60%25+-brightgreen)
![PHP Version](https://img.shields.io/badge/PHP-8.2-blue)
![Laravel](https://img.shields.io/badge/Laravel-12.0-red)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-blue)

## 🛠 Tech Stack
- Framework: Laravel 12
- php: 8.2
- Database: PostgreSQL 17 (Leveraging JSONB and Composite Indexes)
- Environment: Docker
- Testing: PHPUnit (Feature & Unit Tests)

## 🚀 Setup & Installation

This project is containerized using Docker. The purpose is for local machine.

#### Prerequisites
- Docker Compose
- Git
### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/myathtut/version-controlled.git
   cd version-controlled
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Start Docker containers**
   ```bash
   docker-compose -f docker-compose.local.yml up -d --build
   ```

4. **Install dependencies**
   ```bash
   docker compose -f docker-compose.local.yml exec app composer install
   ```

5. **Generate application key**
   ```bash
   docker compose -f docker-compose.local.yml exec app php artisan key:generate
   ```

6. **Run migrations**
   ```bash
   docker compose -f docker-compose.local.yml exec app php artisan migrate
   ```
7. **Run api doc**
   ```bash
   docker compose -f docker-compose.local.yml exec app php artisan scribe:generate
   ```

8. **Access the application**
    - API Base URL: `http://localhost:8000/api/v1`
    - Database: `localhost:5436` (user: `laravel`, password: `secret`, database: `laravel`)

## 📚 API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### API documentation URL
```
http://localhost:8000/docs
```

### Endpoints

---

#### 1. **Accept a key(string) and value(some JSON blob/string) and store them.**
```http
POST /objects
Content-Type: application/json
```

**Request Body**:
```json
{
  "key": "my_key",
  "value": {"foo": "bar"}
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Resource created successfully",
  "data": {
    "id": 1,
    "key": "my_key",
    "value": {"foo": "bar"},
    "created_at_timestamp": 1700000000
  }
}
```

**Validation Rules**:
- `key`: required, string, max 255 characters
- `value`: required, valid JSON object

---
#### 2. **Accept a key and return the corresponding latest value**
```http
GET /objects/{key}
```

**Example**:
```http
GET /objects/my_key
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": {
    "id": 1,
    "key": "my_key",
    "value": {"foo": "bar"},
    "created_at_timestamp": 1700000000
  }
}
```
---
#### 3. **When given a key AND a timestamp, return whatever the value of the key at the time was.**
```http
GET /objects/keys/{key}?timestamp={unix_timestamp}
```

**Example**:
```http
GET /objects/keys/my_key?timestamp=1699999999
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": {
    "id": 1,
    "key": "my_key",
    "value": {"foo": "bar"},
    "created_at_timestamp": 1699999000
  }
}
```

**Notes**:
- Returns the most recent version at or before the given timestamp
- If no version exists before the timestamp, returns 404

---

#### 4. **Displays all values currently stored in the database**
```http
GET /objects
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": [
    {
      "id": 1,
      "key": "my_key",
      "value": {"foo": "bar"},
      "created_at_timestamp": 1700000000
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/objects?cursor=...",
    "last": null,
    "prev": null,
    "next": "http://localhost:8000/api/v1/objects?cursor=..."
  },
  "meta": {
    "path": "http://localhost:8000/api/v1/objects",
    "per_page": 15,
    "next_cursor": "...",
    "prev_cursor": null
  }
}
```
### Error Responses
All errors follow this format:
```json
{
    "success": false,
    "message": "Resource not found",
    "error": {
        "type": "ModelNotFoundException",
        "details": "The requested resource could not be found"
    }
}
```
**Error Response** (422 Unprocessable Content):
```json
{
    "success": false,
    "message": "Validation failed",
    "error": {
        "type": "ValidationException",
        "details": {
            "key": [
                "The key field is required."
            ]
        }
    }
}
```
**Status Codes**:
- `200 OK`: Successful GET request
- `201 Created`: Successful POST request
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation error
---

## 🏗 Architecture & Design Decisions
### Database Schema
```sql
CREATE TABLE object_stores (
    id BIGSERIAL PRIMARY KEY,
    key VARCHAR(255) NOT NULL,
    value JSONB NOT NULL,
    created_at_timestamp BIGINT NOT NULL COMMENT 'UNIX timestamp (UTC)',
    INDEX idx_key_created_at_timestamp (key, created_at_timestamp)
);
```
**Key Design Decisions**:
- **created_at_timestamp**: I used BIGINT data type because comparing a raw integer like 1701083000 is significantly faster and computationally cheaper than parsing and comparing a formatted date string.And then also it is good performance of **"indexing"** and **"sorting"**
- **JSONB Type**: Native PostgreSQL JSONB for efficient JSON storage and querying.
- **Composite Index**: `(key, created_at_timestamp)` for fast historical lookups.
- **Remove default timestamp**: I remove default timestamp for avoid confusion.
### Architecture Patterns

#### 1. **Service Layer Pattern**
```
Controller → Service → Model
```
- Controllers handle HTTP requests/responses
- Services contain business logic
- Models represent data layer
#### 2. **Interface**
```php
interface IObjectService {}
```
```php
$this->app->bind(IObjectService::class, ObjectService::class);
```
- Dependency injection for testability
- Easy to mock in unit tests
- used laravel IOC
  If we need to change new business logic we can easily add new "Service" class. we don't need to change controller or model. And then if we want to add logic we can easily add at "interface" and "service".

## 🧪 Testing

### Run Tests

```bash
# All tests
docker compose -f docker-compose.local.yml exec app php artisan test

# With coverage report
docker compose -f docker-compose.local.yml exec app php artisan test --coverage
```
### Test Structure

```
tests/
├── Feature/        
│   ├── Api/
│   └── ExceptionHandlerTest.php
└── Unit/              # unit tests
    ├── Controllers/
    ├── Models/
    ├── Request/
    └── Services/
```
---
### Infrastructure Choices:
**PostgreSQL 17**

Chosen for its robust JSON handling capabilities which fit the "Key-Value store" requirement perfectly.
### Infrastructure Choices:
#### **Why PostgreSQL 17?**

Chosen for its robust JSON handling capabilities which fit the "Key-Value store" requirement perfectly.
-  **JSONB Support**: First-class JSON support with indexing.
-  **Performance**: Superior indexing for composite queries.
-  **Modern Features**: Advanced query optimization.

#### **Hosting**
Railway - Chosen for its seamless integration with GitHub and its usage of Nixpacks (Open Source) for reproducible builds.
#### **CI/CD**
GitHub Actions - Used to ensure all tests pass before a deployment is triggered.

## 🔮 Future Improvements
- Cache: For this assignment, I hit the database directly for simplicity. In a high-traffic production environment, I would implement `Redis` Caching specifically for the 'Get Latest Value' endpoint.
- Authentication: Adding API Token or Sanctum authentication for security.
---
## 👤 Author

**Myat Htut**

- GitHub: [@myathtut](https://github.com/myathtut-zafir)

---


