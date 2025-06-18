#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    DROP TABLE IF NOT EXISTS url_checks;
    DROP TABLE IF NOT EXISTS urls;

    CREATE TABLE urls (
        id BIGSERIAL PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE url_checks (
        id BIGSERIAL PRIMARY KEY,
        url_id BIGINT REFERENCES urls(id),
        status_code INT,
        h1 VARCHAR(255),
        title VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
EOSQL