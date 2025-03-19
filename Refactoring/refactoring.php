<?php
// Some decisions I made:   
// I changed the unset() method to return null if a field in a object is not given because
// it explicitly indicates that the value is missing but expected. I return empty string if the value is empty.
// Because it could prove useful to differentiate between "not provided" and "intentionally empty."
class CompanyClass
{
    public function normalizeCompanyData(array $data): ?array
    {
        if (!$this->isCompanyDataValid($data)) {
            return null; // Return null if data is not valid
        }
        return [ // Return array of normalized data
            'name' => array_key_exists('name', $data) ? $this->normalizeAttribute($data['name']) : null,
            'website' => array_key_exists('website', $data) ? $this->normalizeWebsite($data['website']) : null,
            'address' => array_key_exists('address', $data) ? $this->normalizeAttribute($data['address']) : null,
        ];
    }

    // Checks if data of the company exists
    private function isCompanyDataValid(array $data): bool
    {
        return !empty($data['name']) || !empty($data['address']);
    }

    private function normalizeWebsite(?string $website): array|string|null
    {
        if ($website === null)
            return null;
        $website = trim($website); // Removes whitespace from the beginning and end of a string.
        return (!preg_match('/^https?:\/\//i', $website) || !filter_var($website, FILTER_VALIDATE_URL))
            ? ['error' => 'Invalid URL format']
            : preg_replace('/^www\./', '', parse_url($website, PHP_URL_HOST));
        // replaces 'www.' with an empty string and returns the host name of the URL
    }

    // Normalizes the attribute (in this case name and address) by trimming and converting to lowercase
    private function normalizeAttribute(?string $value): ?string
    {
        if ($value === null) return null;
        
        $trim = trim($value);
        
        // If the input is an empty string after trimming, return an empty string
        if ($trim === "") return "";
    
        // Converts to lowercase
        $normalized = strtolower($trim);
    
        // Removes special characters but allows letters, numbers, and spaces
        $normalized = preg_replace('/[^a-z0-9 ]/iu', '', $normalized);
    
        return $normalized;
    }
    
}


// Test data that was given.
$results = [
    [
        'name' => '  OpenAI ',
        'website' => 'https://openai.com  ',
        'address' => '   '
    ],
    [
        'name' => 'Innovatiespotter',
        'address' => 'Groningen'
    ],
    [
        'name' => '  Apple ',
        'website' => 'xhttps://apple.com  ',
    ],

];


$company = new CompanyClass();
// Loop through the test data and display the results instead of calling each result with the normalize method.
foreach ($results as $index => $input) {
    echo "Result " . ($index + 1) . ":\n";
    var_dump($company->normalizeCompanyData($input));
} 