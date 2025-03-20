<?php

//This is the second optional assigment. This is just a "proof of concept" and not a final solution. 
// This just ensures that I established a connection to the database and that I can fetch data from it.
// The csv file is also exporting but I added the filtering of the data again which is not efficient. However, due to
// the time constraints I was not able to finish the final solution.

// Database connection function
function connectDatabase(): PDO
{
    $dsn = "pgsql:host=localhost;dbname=companies;port=5432";
    $username = "postgres";
    $password = "abc123"; // For privacy reasons, this is a my dummy password.

    try {
        return new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        // PDO is a database access layer that provides a fast and consistent interface for accessing 
        // and managing databases.

    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Company normalization class
class CompanyClass
{
// Some decisions I made:   
// I changed the unset() method to return null if a field in a object is not given because
// it explicitly indicates that the value is missing but expected. I return empty string if the value is empty.
// Because it could prove useful to differentiate between "not provided" and "intentionally empty."

    public function normalizeCompanyData(array $data): ?array
    {
        if (!$this->isCompanyDataValid($data)) {
            return null; // Return null if data is not valid
        }
        return [
            'name' => array_key_exists('name', $data) ? $this->normalizeAttribute($data['name']) : null,
            'website' => array_key_exists('website', $data) ? $this->normalizeWebsite($data['website']) : null,
            'address' => array_key_exists('address', $data) ? $this->normalizeAttribute($data['address']) : null,
        ];
    }

    private function isCompanyDataValid(array $data): bool
    {
        return !empty($data['name']) || !empty($data['address']);
    }
    // Normalizes the attribute (in this case name and address) by trimming and converting to lowercase
    private function normalizeWebsite(?string $website): ?string
    {
        if ($website === null)
            return null;

        $website = trim($website);
        if (!preg_match('/^https?:\/\//i', $website) || !filter_var($website, FILTER_VALIDATE_URL)) {
            return null;
        }

        return preg_replace('/^www\./', '', parse_url($website, PHP_URL_HOST));
    }

    private function normalizeAttribute(?string $value): ?string
    {
        if ($value === null)
            return null;

        $trim = trim($value);
        if ($trim === "")
            return "";

        $normalized = strtolower($trim);
        $normalized = preg_replace('/[^a-z0-9 ]/iu', '', $normalized);

        return $normalized;
    }
}

// Fetch companies from database
function fetchCompanies(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, name, website, address, source FROM companies");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Normalize and de-duplicate companies
function normalizeAndDeduplicateCompanies(array $companies): array
{
    $companyClass = new CompanyClass();
    $normalizedCompanies = [];

    foreach ($companies as $company) {
        $normalized = $companyClass->normalizeCompanyData($company);
        if (!$normalized)
            continue;

        $key = strtolower($normalized['name'] ?? '') . '|' . strtolower($normalized['website'] ?? '');
        //Here i had to normalize again because I was getting the wrong table. 
        //Again if i had more time I would have fixed this.
        if (!isset($normalizedCompanies[$key]) || $company['source'] === 'MANUAL') {
            $normalizedCompanies[$key] = [
                'name' => ucfirst($normalized['name']),
                'canonical_website' => $normalized['website'],
                'address' => $normalized['address']
            ];
        }
    }
    return array_values($normalizedCompanies);
}

// Insert normalized data into the database
function insertNormalizedCompanies(PDO $pdo, array $normalizedCompanies)
{
    $stmt = $pdo->prepare("INSERT INTO normalized_companies (name, canonical_website, address) 
                           VALUES (:name, :website, :address)
                           ON CONFLICT (name) DO UPDATE 
                           SET canonical_website = EXCLUDED.canonical_website, 
                               address = EXCLUDED.address");

    foreach ($normalizedCompanies as $company) {
        $stmt->execute([
            ':name' => $company['name'],
            ':website' => $company['canonical_website'],
            ':address' => $company['address']
        ]);
    }
}

// Export normalized data to CSV
function exportToCSV(PDO $pdo, $filename = "normalized_companies.csv")
{
    $stmt = $pdo->query("
        WITH RankedCompanies AS (
            SELECT 
                name, 
                canonical_website, 
                address,
                ROW_NUMBER() OVER (
                    PARTITION BY LOWER(canonical_website) 
                    ORDER BY 
                        CASE 
                            WHEN canonical_website IS NULL OR canonical_website = '' THEN 2 ELSE 1 
                        END,
                        name ASC
                ) AS row_num
            FROM normalized_companies
        )
        SELECT name, canonical_website, address 
        FROM RankedCompanies
        WHERE row_num = 1 -- Keep only the first entry per website
        ORDER BY LOWER(name)
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $file = fopen($filename, 'w');
    fputcsv($file, ['Name', 'Website', 'Address'], ",", '"', "\\");

    foreach ($companies as $company) {
        // Properly capitalize company names
        $company['name'] = ucwords(strtolower($company['name']));

        // Ensure empty websites are represented properly
        if (empty($company['canonical_website'])) {
            continue; // Skip any entry with no website
        }

        // Write to CSV
        fputcsv($file, $company, ",", '"', "\\");
    }

    fclose($file);
    echo "CSV file exported successfully: $filename\n";
}

// Main program
$pdo = connectDatabase();
$companies = fetchCompanies($pdo);
$normalizedCompanies = normalizeAndDeduplicateCompanies($companies);
insertNormalizedCompanies($pdo, $normalizedCompanies);
exportToCSV($pdo);
