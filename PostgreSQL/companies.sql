-- companies.sql: Create tables for companies and normalized companies and insert test data into the tables.
DROP TABLE IF EXISTS companies CASCADE;
DROP TABLE IF EXISTS normalized_companies CASCADE;

-- Below query creates a table with attributes of companies.
CREATE TABLE companies ( 
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    website VARCHAR(255),
    address TEXT,
    source VARCHAR(50), -- 'API_1', 'SCRAPER_2', 'MANUAL'
    inserted_at TIMESTAMP DEFAULT NOW()
);

-- Below query creates a table with attributes of normalized companies.
CREATE TABLE normalized_companies (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE,
    canonical_website VARCHAR(255),
    address TEXT
);

-- Below query inserts test data. The data is random to test the code. 
INSERT INTO companies (name, website, address, source) VALUES
('Acme Inc', 'www.acme.com', 'Herenstraat', 'API_1'),
('Acme Incorporated', 'www.acme.com', 'Herenstraat', 'SCRAPER_2'),
('Acme Inc.', 'www.acme.com', 'Herenstraat', 'MANUAL'),
('Beta Corp', 'www.betacorp.com', 'Hoogeweg', 'API_1'),
('Beta Corp', 'www.betacorp.com', 'Hoogeweg', 'SCRAPER_2'),
('Gamma LLC', 'www.gamma.io', 'Bloemstraat', 'API_1');